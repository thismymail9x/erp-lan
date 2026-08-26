<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CasePartnerModel;
use App\Models\CustomerInteractionModel;
use App\Models\CustomerModel;
use App\Models\PartnerCommissionEntryModel;
use App\Models\PartnerModel;
use App\Models\UserModel;
use Config\AppConstants;

/**
 * PartnerCommissionService
 *
 * Dịch vụ trung tâm cho module đối tác: quản lý hồ sơ đối tác, cấu hình hợp tác
 * theo vụ việc và tự phát sinh hoa hồng khi khách thanh toán. Logic đặt tại đây
 * để controller không chứa query/tính toán phức tạp.
 */
class PartnerCommissionService extends BaseService
{
    public const ROLE_LABELS = [
        'referrer' => 'Giới thiệu khách',
        'consultant' => 'Tư vấn',
        'closer' => 'Chốt khách',
        'operator' => 'Người làm vụ việc',
        'expert' => 'Cộng tác chuyên môn',
        'other' => 'Khác',
    ];

    public const BASE_LABELS = [
        'contract' => 'Theo tổng hợp đồng',
        'paid' => 'Theo tiền thực thu',
    ];

    public const ENTRY_STATUS_LABELS = [
        'accrued' => 'Đã phát sinh',
        'requested' => 'Đối tác yêu cầu',
        'approved' => 'Đã duyệt chi',
        'paid' => 'Đã thanh toán',
        'held' => 'Tạm giữ',
    ];

    private PartnerModel $partnerModel;
    private CasePartnerModel $casePartnerModel;
    private PartnerCommissionEntryModel $entryModel;
    private CaseModel $caseModel;
    private CustomerModel $customerModel;
    private CustomerInteractionModel $interactionModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->partnerModel = new PartnerModel();
        $this->casePartnerModel = new CasePartnerModel();
        $this->entryModel = new PartnerCommissionEntryModel();
        $this->caseModel = new CaseModel();
        $this->customerModel = new CustomerModel();
        $this->interactionModel = new CustomerInteractionModel();
        $this->userModel = new UserModel();
    }

    public function canManage(): bool
    {
        return has_permission('sys.admin') || has_permission('partner.manage');
    }

    public function canPayout(): bool
    {
        return has_permission('sys.admin') || has_permission('partner.payout');
    }

    public function getPartnerByCurrentUser(): ?array
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return $this->partnerModel
            ->where('user_id', $userId)
            ->where('status !=', 'ended')
            ->first();
    }

    public function getPartnerByUserId(int $userId): ?array
    {
        if ($userId <= 0 || !$this->partnerModel->db->tableExists('partners')) {
            return null;
        }

        return $this->partnerModel->where('user_id', $userId)->first();
    }

    public function getAdminData(array $filters, int $perPage = 20): array
    {
        $search = trim((string)($filters['search'] ?? ''));
        $status = trim((string)($filters['status'] ?? ''));

        $partnerQuery = $this->partnerModel->select('partners.*, users.email as login_email')
            ->join('users', 'users.id = partners.user_id AND users.deleted_at IS NULL', 'left');

        if ($search !== '') {
            $partnerQuery->groupStart()
                ->like('partners.name', $search)
                ->orLike('partners.phone', $search)
                ->orLike('partners.email', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        if ($status !== '') {
            $partnerQuery->where('partners.status', $status);
        }

        $partners = $partnerQuery->orderBy('partners.id', 'DESC')->paginate($perPage, 'partners');

        return [
            'partners' => $partners,
            'allPartners' => $this->partnerModel->where('status', 'active')->orderBy('name', 'ASC')->findAll(300),
            'partnerPager' => $this->partnerModel->pager,
            'casePartners' => $this->getCasePartnerRows(),
            'entries' => $this->getEntryRows($filters, 20),
            'entryPager' => $this->entryModel->pager,
            'stats' => $this->getAdminStats(),
        ];
    }

    public function getPortalData(int $partnerId, array $filters, int $perPage = 20): array
    {
        $status = trim((string)($filters['status'] ?? ''));
        $query = $this->buildEntryQuery()
            ->where('partner_commission_entries.partner_id', $partnerId);

        if ($status !== '') {
            $query->where('partner_commission_entries.status', $status);
        }

        $entries = $query->orderBy('partner_commission_entries.payment_date', 'DESC')
            ->orderBy('partner_commission_entries.id', 'DESC')
            ->paginate($perPage);

        $paymentPlans = $this->getPartnerPaymentPlans($partnerId);
        $stats = $this->getPartnerStats($partnerId);
        $stats['future_estimated'] = array_sum(array_map(static fn($row) => (int)$row['future_commission'], $paymentPlans));
        $stats['expected_total'] = ($stats['total'] ?? 0) + $stats['future_estimated'];

        return [
            'entries' => $entries,
            'pager' => $this->entryModel->pager,
            'stats' => $stats,
            'agreements' => $this->getPartnerAgreements($partnerId),
            'paymentPlans' => $paymentPlans,
            'referredCustomers' => $this->getReferredCustomers($partnerId),
        ];
    }

    public function createPartner(array $data): array
    {
        $payload = [
            'name' => trim((string)($data['name'] ?? '')),
            'partner_type' => $data['partner_type'] ?? 'individual',
            'phone' => trim((string)($data['phone'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'tax_code' => trim((string)($data['tax_code'] ?? '')),
            'bank_name' => trim((string)($data['bank_name'] ?? '')),
            'bank_account' => trim((string)($data['bank_account'] ?? '')),
            'bank_owner' => trim((string)($data['bank_owner'] ?? '')),
            'status' => $data['status'] ?? 'active',
            'notes' => trim((string)($data['notes'] ?? '')),
        ];

        if ($payload['name'] === '') {
            return $this->fail('Tên đối tác là bắt buộc.');
        }

        $userId = (int)($data['user_id'] ?? 0);
        $loginEmail = trim((string)($data['login_email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        $this->partnerModel->db->transStart();

        if ($userId > 0) {
            $user = $this->userModel->find($userId);
            if (!$user) {
                $this->partnerModel->db->transRollback();
                return $this->fail('Tài khoản liên kết không tồn tại.');
            }
            $payload['user_id'] = $userId;
            $this->grantPortalPermission($userId);
        } elseif ($loginEmail !== '') {
            if (strlen($password) < 6) {
                $this->partnerModel->db->transRollback();
                return $this->fail('Mật khẩu tài khoản đối tác phải có ít nhất 6 ký tự.');
            }

            $defaultRoleId = $this->getDefaultUserRoleId();
            $inserted = $this->userModel->insert([
                'role_id' => $defaultRoleId,
                'email' => $loginEmail,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'active_status' => 1,
            ]);

            if (!$inserted) {
                $this->partnerModel->db->transRollback();
                return $this->fail('Không tạo được tài khoản đăng nhập cho đối tác.');
            }

            $userId = (int)$this->userModel->getInsertID();
            $payload['user_id'] = $userId;
            if ($payload['email'] === '') {
                $payload['email'] = $loginEmail;
            }
            $this->grantPortalPermission($userId);
        }

        $saved = $this->partnerModel->insert($payload);
        $this->partnerModel->db->transComplete();

        if (!$saved || !$this->partnerModel->db->transStatus()) {
            return $this->fail('Không lưu được hồ sơ đối tác.');
        }

        return $this->success(null, 'Đã tạo hồ sơ đối tác.');
    }

    public function createCasePartner(array $data): array
    {
        $caseId = (int)($data['case_id'] ?? 0);
        $partnerId = (int)($data['partner_id'] ?? 0);
        $case = $this->caseModel->find($caseId);
        $partner = $this->partnerModel->find($partnerId);

        if (!$case || !$partner) {
            return $this->fail('Vụ việc hoặc đối tác không hợp lệ.');
        }

        $payload = [
            'case_id' => $caseId,
            'partner_id' => $partnerId,
            'role_label' => $data['role_label'] ?? 'referrer',
            'calculation_base' => in_array(($data['calculation_base'] ?? 'paid'), ['contract', 'paid'], true) ? $data['calculation_base'] : 'paid',
            'percentage' => $this->normalizeDecimal($data['percentage'] ?? 0),
            'fixed_amount' => $this->normalizeMoney($data['fixed_amount'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'notes' => trim((string)($data['notes'] ?? '')),
        ];

        if ($payload['percentage'] <= 0 && $payload['fixed_amount'] <= 0) {
            return $this->fail('Cần nhập % hoa hồng hoặc số tiền cố định.');
        }

        $this->casePartnerModel->insert($payload);
        $this->syncCaseCommissions($caseId);

        return $this->success(null, 'Đã gắn đối tác vào vụ việc và đồng bộ hoa hồng.');
    }

    public function syncCaseCommissions(int $caseId): void
    {
        $case = $this->caseModel->find($caseId);
        if (!$case) {
            return;
        }

        $payments = $this->parsePayments($case['payment_progress'] ?? '');
        $activeLinks = $this->casePartnerModel
            ->where('case_id', $caseId)
            ->where('status', 'active')
            ->findAll();

        if (empty($activeLinks) || empty($payments)) {
            return;
        }

        $contractValue = (int)($case['contract_value'] ?? 0);

        foreach ($activeLinks as $link) {
            foreach ($payments as $index => $payment) {
                if (empty($payment['is_paid']) || (int)$payment['is_paid'] !== 1) {
                    continue;
                }

                $paymentAmount = $this->normalizeMoney($payment['amount'] ?? 0);
                if ($paymentAmount <= 0) {
                    continue;
                }

                $entryAmount = $this->calculateEntryAmount($link, $paymentAmount, $contractValue, $index);
                if ($entryAmount <= 0) {
                    continue;
                }

                $existing = $this->entryModel
                    ->where('case_partner_id', $link['id'])
                    ->where('payment_index', $index)
                    ->first();

                $payload = [
                    'case_partner_id' => $link['id'],
                    'partner_id' => $link['partner_id'],
                    'case_id' => $caseId,
                    'payment_index' => $index,
                    'payment_title' => $payment['title'] ?? ('Đợt ' . ($index + 1)),
                    'payment_date' => !empty($payment['paid_at']) ? $payment['paid_at'] : ($payment['deadline'] ?? date('Y-m-d')),
                    'calculation_base' => $link['calculation_base'],
                    'base_amount' => $link['calculation_base'] === 'contract' && $contractValue > 0 ? $contractValue : $paymentAmount,
                    'percentage' => $link['percentage'],
                    'fixed_amount' => $this->allocatedFixedAmount($link, $paymentAmount, $contractValue, $index),
                    'commission_amount' => $entryAmount,
                ];

                if ($existing) {
                    if (!in_array($existing['status'], ['approved', 'paid'], true)) {
                        $this->entryModel->update($existing['id'], $payload);
                    }
                } else {
                    $payload['status'] = 'accrued';
                    $this->entryModel->insert($payload);
                }
            }
        }
    }

    public function requestPayment(int $entryId, int $partnerId, string $note): array
    {
        $entry = $this->entryModel->where('partner_id', $partnerId)->find($entryId);
        if (!$entry) {
            return $this->fail('Khoản hoa hồng không thuộc đối tác hiện tại.');
        }

        if ($entry['status'] !== 'accrued') {
            return $this->fail('Chỉ khoản đã phát sinh mới được gửi yêu cầu thanh toán.');
        }

        $this->entryModel->update($entryId, [
            'status' => 'requested',
            'request_note' => trim($note),
            'requested_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success(null, 'Đã gửi yêu cầu thanh toán.');
    }

    public function updateEntryStatus(int $entryId, string $status, string $note = ''): array
    {
        $allowed = ['accrued', 'requested', 'approved', 'paid', 'held'];
        if (!in_array($status, $allowed, true)) {
            return $this->fail('Trạng thái chi trả không hợp lệ.');
        }

        $entry = $this->entryModel->find($entryId);
        if (!$entry) {
            return $this->fail('Không tìm thấy khoản hoa hồng.');
        }

        $payload = [
            'status' => $status,
            'admin_note' => trim($note),
        ];

        if ($status === 'approved') {
            $payload['approved_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'paid') {
            $payload['paid_at'] = date('Y-m-d H:i:s');
            if (empty($entry['approved_at'])) {
                $payload['approved_at'] = date('Y-m-d H:i:s');
            }
        }

        $this->entryModel->update($entryId, $payload);
        return $this->success(null, 'Đã cập nhật trạng thái chi trả.');
    }

    public function getSelectableCases(): array
    {
        return $this->caseModel->select('cases.id, cases.code, cases.title, customers.name as customer_name')
            ->join('customers', 'customers.id = cases.customer_id AND customers.deleted_at IS NULL', 'left')
            ->where('cases.deleted_at', null)
            ->orderBy('cases.id', 'DESC')
            ->findAll(200);
    }

    public function getSelectableUsers(): array
    {
        return $this->userModel->select('users.id, users.email, employees.full_name')
            ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
            ->where('users.active_status', 1)
            ->where('users.deleted_at', null)
            ->orderBy('users.email', 'ASC')
            ->findAll(200);
    }

    private function getCasePartnerRows(): array
    {
        return $this->casePartnerModel
            ->select('case_partners.*, partners.name as partner_name, cases.code as case_code, cases.title as case_title, customers.name as customer_name')
            ->join('partners', 'partners.id = case_partners.partner_id AND partners.deleted_at IS NULL')
            ->join('cases', 'cases.id = case_partners.case_id AND cases.deleted_at IS NULL')
            ->join('customers', 'customers.id = cases.customer_id AND customers.deleted_at IS NULL', 'left')
            ->where('case_partners.deleted_at', null)
            ->orderBy('case_partners.id', 'DESC')
            ->findAll(80);
    }

    private function getEntryRows(array $filters, int $perPage): array
    {
        $query = $this->buildEntryQuery();
        $status = trim((string)($filters['entry_status'] ?? ''));
        if ($status !== '') {
            $query->where('partner_commission_entries.status', $status);
        }

        return $query->orderBy('partner_commission_entries.id', 'DESC')->paginate($perPage, 'entries');
    }

    private function buildEntryQuery()
    {
        return $this->entryModel
            ->select('partner_commission_entries.*, partners.name as partner_name, cases.code as case_code, cases.title as case_title, customers.name as customer_name, case_partners.role_label')
            ->join('partners', 'partners.id = partner_commission_entries.partner_id AND partners.deleted_at IS NULL')
            ->join('cases', 'cases.id = partner_commission_entries.case_id AND cases.deleted_at IS NULL')
            ->join('customers', 'customers.id = cases.customer_id AND customers.deleted_at IS NULL', 'left')
            ->join('case_partners', 'case_partners.id = partner_commission_entries.case_partner_id AND case_partners.deleted_at IS NULL', 'left')
            ->where('partner_commission_entries.deleted_at', null);
    }

    private function getAdminStats(): array
    {
        $rows = $this->entryModel
            ->select('status, SUM(commission_amount) as total')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->findAll();

        return $this->normalizeStats($rows);
    }

    private function getPartnerStats(int $partnerId): array
    {
        $rows = $this->entryModel
            ->select('status, SUM(commission_amount) as total')
            ->where('partner_id', $partnerId)
            ->where('deleted_at', null)
            ->groupBy('status')
            ->findAll();

        return $this->normalizeStats($rows);
    }

    private function getPartnerAgreements(int $partnerId): array
    {
        return $this->casePartnerModel
            ->select('case_partners.*, cases.code as case_code, cases.title as case_title, customers.name as customer_name, cases.contract_value, cases.status as case_status, cases.deadline as case_deadline')
            ->join('cases', 'cases.id = case_partners.case_id AND cases.deleted_at IS NULL')
            ->join('customers', 'customers.id = cases.customer_id AND customers.deleted_at IS NULL', 'left')
            ->where('case_partners.partner_id', $partnerId)
            ->where('case_partners.deleted_at', null)
            ->orderBy('case_partners.id', 'DESC')
            ->findAll(100);
    }

    private function getPartnerPaymentPlans(int $partnerId): array
    {
        $agreements = $this->casePartnerModel
            ->select('case_partners.*, cases.code as case_code, cases.title as case_title, cases.status as case_status, cases.deadline as case_deadline, cases.contract_value, cases.payment_progress, customers.name as customer_name')
            ->join('cases', 'cases.id = case_partners.case_id AND cases.deleted_at IS NULL')
            ->join('customers', 'customers.id = cases.customer_id AND customers.deleted_at IS NULL', 'left')
            ->where('case_partners.partner_id', $partnerId)
            ->where('case_partners.deleted_at', null)
            ->orderBy('cases.id', 'DESC')
            ->findAll(100);

        $plans = [];
        foreach ($agreements as $agreement) {
            $payments = $this->parsePayments((string)($agreement['payment_progress'] ?? ''));
            $contractValue = (int)($agreement['contract_value'] ?? 0);
            $paidAmount = 0;
            $pendingAmount = 0;
            $receivedCommission = 0;
            $futureCommission = 0;
            $paymentRows = [];

            foreach ($payments as $index => $payment) {
                $amount = $this->normalizeMoney($payment['amount'] ?? 0);
                $isPaid = !empty($payment['is_paid']) && (int)$payment['is_paid'] === 1;
                $deadline = $payment['deadline'] ?? null;
                $status = $isPaid ? 'paid' : 'pending';
                if (!$isPaid && !empty($deadline) && strtotime($deadline) < strtotime(date('Y-m-d'))) {
                    $status = 'overdue';
                }

                $commission = $this->calculateEntryAmount($agreement, $amount, $contractValue, $index);
                if ($isPaid) {
                    $paidAmount += $amount;
                    $receivedCommission += $this->getRecordedCommissionAmount((int)$agreement['id'], $index, $commission);
                } else {
                    $pendingAmount += $amount;
                    $futureCommission += $commission;
                }

                $paymentRows[] = [
                    'title' => $payment['title'] ?? ('Dot ' . ($index + 1)),
                    'amount' => $amount,
                    'deadline' => $deadline,
                    'paid_at' => $payment['paid_at'] ?? null,
                    'note' => $payment['note'] ?? '',
                    'status' => $status,
                    'is_vat' => !empty($payment['is_vat']) && (int)$payment['is_vat'] === 1,
                    'commission_amount' => $commission,
                ];
            }

            $plans[] = [
                'case_id' => (int)$agreement['case_id'],
                'case_code' => $agreement['case_code'],
                'case_title' => $agreement['case_title'],
                'customer_name' => $agreement['customer_name'],
                'case_status' => $agreement['case_status'],
                'case_status_label' => AppConstants::CASE_STATUS_LABELS[$agreement['case_status']] ?? $agreement['case_status'],
                'case_deadline' => $agreement['case_deadline'],
                'contract_value' => $contractValue,
                'role_label' => $agreement['role_label'],
                'calculation_base' => $agreement['calculation_base'],
                'percentage' => (float)$agreement['percentage'],
                'fixed_amount' => (int)$agreement['fixed_amount'],
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'received_commission' => $receivedCommission,
                'future_commission' => $futureCommission,
                'payments' => $paymentRows,
            ];
        }

        return $plans;
    }

    private function getRecordedCommissionAmount(int $casePartnerId, int $paymentIndex, int $fallback): int
    {
        $entry = $this->entryModel
            ->where('case_partner_id', $casePartnerId)
            ->where('payment_index', $paymentIndex)
            ->first();

        return $entry ? (int)$entry['commission_amount'] : $fallback;
    }

    public function getReferredCustomers(int $partnerId): array
    {
        if (!$this->customerModel->db->fieldExists('referred_partner_id', 'customers')) {
            return [];
        }

        $customers = $this->customerModel
            ->select('customers.id, customers.code, customers.name, customers.phone, customers.email, customers.source, customers.care_status, customers.customer_segment, customers.notes_internal, customers.created_at, employees.full_name as care_staff_name')
            ->join('employees', 'employees.id = customers.assigned_care_staff_id AND employees.deleted_at IS NULL', 'left')
            ->where('customers.referred_partner_id', $partnerId)
            ->where('customers.deleted_at', null)
            ->orderBy('customers.id', 'DESC')
            ->findAll(100);

        foreach ($customers as &$customer) {
            $customer['cases'] = $this->caseModel
                ->select('cases.id, cases.code, cases.title, cases.status, cases.deadline, cases.contract_value, cases.created_at')
                ->where('customer_id', (int)$customer['id'])
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->findAll(10);

            foreach ($customer['cases'] as &$case) {
                $case['status_label'] = AppConstants::CASE_STATUS_LABELS[$case['status']] ?? $case['status'];
            }
            unset($case);

            $customer['interactions'] = $this->interactionModel
                ->select('customer_interactions.interaction_date, customer_interactions.channel, customer_interactions.summary, customer_interactions.interaction_result, customer_interactions.detailed_content, customer_interactions.next_follow_up, users.email as staff_email')
                ->join('users', 'users.id = customer_interactions.user_id', 'left')
                ->where('customer_id', (int)$customer['id'])
                ->where('customer_interactions.deleted_at', null)
                ->orderBy('interaction_date', 'DESC')
                ->findAll(5);
        }
        unset($customer);

        return $customers;
    }

    private function normalizeStats(array $rows): array
    {
        $stats = [
            'accrued' => 0,
            'requested' => 0,
            'approved' => 0,
            'paid' => 0,
            'held' => 0,
            'total' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'] ?? 'accrued';
            $amount = (int)($row['total'] ?? 0);
            if (!array_key_exists($status, $stats)) {
                $stats[$status] = 0;
            }
            $stats[$status] += $amount;
            $stats['total'] += $amount;
        }

        return $stats;
    }

    private function grantPortalPermission(int $userId): void
    {
        $permission = $this->entryModel->db->table('permissions')->where('name', 'partner.portal')->get()->getRowArray();
        if (!$permission) {
            return;
        }

        $exists = $this->entryModel->db->table('user_permissions')
            ->where('user_id', $userId)
            ->where('permission_id', $permission['id'])
            ->countAllResults();

        if ($exists == 0) {
            $this->entryModel->db->table('user_permissions')->insert([
                'user_id' => $userId,
                'permission_id' => $permission['id'],
                'is_granted' => 1,
            ]);
        }
    }

    private function getDefaultUserRoleId(): int
    {
        $role = $this->entryModel->db->table('roles')
            ->where('name', \Config\AppConstants::ROLE_THUC_TAP_SINH)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return (int)($role['id'] ?? 1);
    }

    private function parsePayments(string $paymentProgress): array
    {
        if ($paymentProgress === '') {
            return [];
        }

        $decoded = json_decode($paymentProgress, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function calculateEntryAmount(array $link, int $paymentAmount, int $contractValue, int $paymentIndex): int
    {
        $percentAmount = (int)round($paymentAmount * ((float)$link['percentage'] / 100));
        $fixedAmount = $this->allocatedFixedAmount($link, $paymentAmount, $contractValue, $paymentIndex);

        return max(0, $percentAmount + $fixedAmount);
    }

    private function allocatedFixedAmount(array $link, int $paymentAmount, int $contractValue, int $paymentIndex): int
    {
        $fixed = (int)($link['fixed_amount'] ?? 0);
        if ($fixed <= 0) {
            return 0;
        }

        if ($contractValue > 0) {
            return (int)round($fixed * min($paymentAmount / $contractValue, 1));
        }

        return $paymentIndex === 0 ? $fixed : 0;
    }

    private function normalizeMoney($value): int
    {
        return (int)preg_replace('/[^\d]/', '', (string)$value);
    }

    private function normalizeDecimal($value): float
    {
        $normalized = str_replace(',', '.', (string)$value);
        return (float)preg_replace('/[^\d.]/', '', $normalized);
    }
}
