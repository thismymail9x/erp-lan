<?php

namespace App\Services;

use App\Models\OfficeExpenseModel;
use Config\AppConstants;

/**
 * Service xử lý chi phí vận hành công ty.
 *
 * Chi phí vận hành được tách khỏi chi phí vụ việc để kế toán nhập các khoản không
 * gắn khách hàng cụ thể, nhưng dashboard công ty vẫn có thể cộng hai nguồn khi cần.
 */
class OfficeExpenseService extends BaseService
{
    public const CATEGORY_LABELS = [
        'electricity' => 'Điện',
        'water'       => 'Nước',
        'internet'    => 'Internet / điện thoại',
        'rent'        => 'Mặt bằng / văn phòng',
        'stationery'  => 'Văn phòng phẩm',
        'maintenance' => 'Sửa chữa / bảo trì',
        'software'    => 'Phần mềm / dịch vụ',
        'tax_fee'     => 'Thuế / lệ phí',
        'salary_misc' => 'Phụ cấp / nhân sự',
        'other'       => 'Khác',
    ];

    public const PAYMENT_METHOD_LABELS = [
        'cash'     => 'Tiền mặt',
        'transfer' => 'Chuyển khoản',
        'card'     => 'Thẻ',
        'other'    => 'Khác',
    ];

    protected $model;
    protected $db;
    private array $allowedReceiptExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    private int $maxReceiptBytes = 10485760;

    public function __construct(?OfficeExpenseModel $model = null)
    {
        parent::__construct();
        $this->model = $model ?? new OfficeExpenseModel();
        $this->db = \Config\Database::connect();
    }

    public function canView(): bool
    {
        return has_permission('sys.admin')
            || has_permission('office_expense.view')
            || has_permission('office_expense.manage')
            || session()->get('role_name') === AppConstants::ROLE_ADMIN
            || (int)session()->get('department_id') === AppConstants::DEPT_HANH_CHINH;
    }

    public function canManage(): bool
    {
        return has_permission('sys.admin')
            || has_permission('office_expense.manage')
            || session()->get('role_name') === AppConstants::ROLE_ADMIN
            || (int)session()->get('department_id') === AppConstants::DEPT_HANH_CHINH;
    }

    public function getDashboardData(array $filters = [], int $perPage = 20): array
    {
        $year = (int)($filters['year'] ?? date('Y'));
        $month = (int)($filters['month'] ?? 0);
        $category = $filters['category'] ?? '';
        $search = trim($filters['search'] ?? '');

        $builder = $this->model
            ->select('office_expenses.*, employees.full_name as creator_name')
            ->join('employees', 'employees.id = office_expenses.created_by', 'left')
            ->where('office_expenses.deleted_at', null)
            ->where('office_expenses.is_fund', 0);

        $this->applyFilters($builder, compact('year', 'month', 'category', 'search'));

        $rows = $builder->orderBy('office_expenses.expense_date', 'DESC')
            ->orderBy('office_expenses.id', 'DESC')
            ->paginate($perPage);

        return [
            'rows' => $rows,
            'pager' => $this->model->pager,
            'summary' => $this->getSummary($year, $month, $category, $search),
            'monthly' => $this->getMonthlySeries($year, $category),
            'previous_monthly' => $this->getMonthlySeries($year - 1, $category),
            'categories' => $this->getCategoryBreakdown($year, $month),
            'top_expenses' => $this->getTopExpenses($year, $month),
            'fund_summary' => $this->getFundSummary($year, $month),
            'fund_entries' => $this->getFundEntries($year, $month),
        ];
    }

    /**
     * Ghi nhận tiền vận hành nhận vào bằng chính bảng chi phí.
     *
     * Dòng tiền nhận dùng `is_fund = 1` để dashboard cộng vào nguồn tiền,
     * còn các khoản chi thường vẫn dùng `is_fund = 0` để trừ ra khi thống kê.
     */
    public function addFund(array $data): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền nhập tiền vận hành.');
        }

        $fundDate = $this->parseFundDate($data['fund_date'] ?? ($data['fund_month'] ?? ''));
        $amount = $this->parseMoney($data['fund_amount'] ?? 0);

        if ($amount <= 0) {
            return $this->fail('Số tiền nhận phải lớn hơn 0.');
        }

        if (!$this->model->insert([
            'expense_date' => $fundDate,
            'category' => 'other',
            'vendor' => 'Tiền vận hành nhận vào',
            'amount' => $amount,
            'is_fund' => 1,
            'payment_method' => 'cash',
            'note' => $data['fund_note'] ?? null,
            'created_by' => (int)session()->get('employee_id'),
        ])) {
            return $this->fail('Không thể lưu khoản tiền vận hành.');
        }

        return $this->success(['id' => (int)$this->model->getInsertID()], 'Đã ghi nhận khoản tiền vận hành.');
    }

    /**
     * Xóa mềm một dòng tiền nhận, chỉ khi đúng là bản ghi `is_fund = 1`.
     *
     * Điều kiện này tránh việc route xóa tiền nhận bị dùng nhầm để xóa khoản chi.
     */
    public function deleteFund(int $id): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền xóa khoản tiền vận hành.');
        }

        $fund = $this->model->find($id);
        if (!$fund || (int)($fund['is_fund'] ?? 0) !== 1) {
            return $this->fail('Không tìm thấy khoản tiền vận hành.');
        }

        $this->model->delete($id);
        return $this->success(null, 'Đã xóa khoản tiền vận hành.');
    }

    public function create(array $data, array $files = []): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền nhập chi phí vận hành.');
        }

        $amount = $this->parseMoney($data['amount'] ?? 0);
        if ($amount <= 0) {
            return $this->fail('Số tiền chi phí phải lớn hơn 0.');
        }

        $category = $data['category'] ?? '';
        if (!array_key_exists($category, self::CATEGORY_LABELS)) {
            return $this->fail('Loại chi phí không hợp lệ.');
        }

        $payload = [
            'expense_date' => $data['expense_date'] ?? date('Y-m-d'),
            'category' => $category,
            'vendor' => $data['vendor'] ?? null,
            'amount' => $amount,
            'is_fund' => 0,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'note' => $data['note'] ?? null,
            'created_by' => (int)session()->get('employee_id'),
        ];

        $receiptFile = $files['receipt'] ?? null;
        $receiptValidation = $this->validateReceipt($receiptFile);
        if ($receiptValidation['status'] !== 'success') {
            return $receiptValidation;
        }

        $receiptResult = $this->storeReceipt($receiptFile);
        if ($receiptResult['status'] !== 'success') {
            return $receiptResult;
        }

        $receipt = $receiptResult['data'];
        if ($receipt) {
            $payload = array_merge($payload, $receipt);
        }

        if (!$this->model->insert($payload)) {
            return $this->fail('Không thể lưu chi phí vận hành.');
        }

        return $this->success(['id' => (int)$this->model->getInsertID()], 'Đã ghi nhận chi phí vận hành.');
    }

    public function update(int $id, array $data, array $files = []): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền sửa chi phí vận hành.');
        }

        $expense = $this->model->find($id);
        if (!$expense || (int)($expense['is_fund'] ?? 0) === 1) {
            return $this->fail('Không tìm thấy chi phí vận hành.');
        }

        $amount = $this->parseMoney($data['amount'] ?? 0);
        if ($amount <= 0) {
            return $this->fail('Số tiền chi phí phải lớn hơn 0.');
        }

        $category = $data['category'] ?? '';
        if (!array_key_exists($category, self::CATEGORY_LABELS)) {
            return $this->fail('Loại chi phí không hợp lệ.');
        }

        $paymentMethod = $data['payment_method'] ?? 'cash';
        if (!array_key_exists($paymentMethod, self::PAYMENT_METHOD_LABELS)) {
            $paymentMethod = 'other';
        }

        $payload = [
            'expense_date' => $data['expense_date'] ?? date('Y-m-d'),
            'category' => $category,
            'vendor' => $data['vendor'] ?? null,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'note' => $data['note'] ?? null,
        ];

        $receiptFile = $files['receipt'] ?? null;
        $receiptValidation = $this->validateReceipt($receiptFile);
        if ($receiptValidation['status'] !== 'success') {
            return $receiptValidation;
        }

        $receiptResult = $this->storeReceipt($receiptFile);
        if ($receiptResult['status'] !== 'success') {
            return $receiptResult;
        }

        $newReceipt = $receiptResult['data'];
        if ($newReceipt) {
            $payload = array_merge($payload, $newReceipt);
        }

        if (!$this->model->update($id, $payload)) {
            if ($newReceipt && !empty($newReceipt['receipt_file_path'])) {
                $this->deleteStoredReceipt($newReceipt['receipt_file_path']);
            }

            return $this->fail('Không thể cập nhật chi phí vận hành.');
        }

        if ($newReceipt && !empty($expense['receipt_file_path'])) {
            $this->deleteStoredReceipt($expense['receipt_file_path']);
        }

        return $this->success(['id' => $id], 'Đã cập nhật chi phí vận hành.');
    }

    public function delete(int $id): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền xóa chi phí vận hành.');
        }

        $expense = $this->model->find($id);
        if (!$expense || (int)($expense['is_fund'] ?? 0) === 1) {
            return $this->fail('Không tìm thấy chi phí vận hành.');
        }

        $this->model->delete($id);
        return $this->success(null, 'Đã xóa chi phí vận hành.');
    }

    private function getSummary(int $year, int $month, string $category, string $search): array
    {
        $current = $this->sumFor($year, $month, $category, $search);
        $previousYear = $this->sumFor($year - 1, $month, $category, $search);
        $currentMonth = $month > 0 ? $current : $this->sumFor($year, (int)date('n'), $category, $search);
        $previousMonth = $month > 1 ? $this->sumFor($year, $month - 1, $category, $search) : $this->sumFor($year - 1, 12, $category, $search);

        return [
            'total' => $current['total'],
            'count' => $current['count'],
            'average' => $current['count'] > 0 ? (int)round($current['total'] / $current['count']) : 0,
            'previous_year_total' => $previousYear['total'],
            'year_change_percent' => $this->percentChange($current['total'], $previousYear['total']),
            'current_month_total' => $currentMonth['total'],
            'previous_month_total' => $previousMonth['total'],
            'month_change_percent' => $this->percentChange($currentMonth['total'], $previousMonth['total']),
        ];
    }

    private function sumFor(int $year, int $month, string $category, string $search): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->select('COUNT(*) as count')
            ->where('deleted_at', null)
            ->where('is_fund', 0);

        $this->applyFilters($builder, compact('year', 'month', 'category', 'search'));
        $row = $builder->get()->getRowArray();

        return [
            'total' => (int)($row['total'] ?? 0),
            'count' => (int)($row['count'] ?? 0),
        ];
    }

    private function getMonthlySeries(int $year, string $category = ''): array
    {
        $rows = array_fill(1, 12, 0);
        $builder = $this->db->table('office_expenses')
            ->select('MONTH(expense_date) as month')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->where('deleted_at', null)
            ->where('is_fund', 0)
            ->where('YEAR(expense_date)', $year)
            ->groupBy('MONTH(expense_date)')
            ->orderBy('MONTH(expense_date)', 'ASC');

        if ($category !== '') {
            $builder->where('category', $category);
        }

        foreach ($builder->get()->getResultArray() as $row) {
            $rows[(int)$row['month']] = (int)$row['total'];
        }

        return array_values($rows);
    }

    private function getCategoryBreakdown(int $year, int $month): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('category')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->where('deleted_at', null)
            ->where('is_fund', 0)
            ->where('YEAR(expense_date)', $year)
            ->groupBy('category')
            ->orderBy('total', 'DESC');

        if ($month > 0) {
            $builder->where('MONTH(expense_date)', $month);
        }

        return $builder->get()->getResultArray();
    }

    private function getTopExpenses(int $year, int $month): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('office_expenses.*, employees.full_name as creator_name')
            ->join('employees', 'employees.id = office_expenses.created_by', 'left')
            ->where('office_expenses.deleted_at', null)
            ->where('office_expenses.is_fund', 0)
            ->where('YEAR(office_expenses.expense_date)', $year)
            ->orderBy('office_expenses.amount', 'DESC')
            ->limit(5);

        if ($month > 0) {
            $builder->where('MONTH(office_expenses.expense_date)', $month);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Tính số dư vận hành từ cùng bảng dữ liệu.
     *
     * Tiền nhận được cộng từ `is_fund = 1`, khoản chi được cộng từ `is_fund = 0`,
     * sau đó lấy tiền nhận trừ tiền chi để ra số còn lại theo kỳ lọc.
     */
    private function getFundSummary(int $year, int $month): array
    {
        $funds = $this->sumFundsFor($year, $month);
        $officeExpenses = $this->sumFor($year, $month, '', '');
        $caseExpenses = $this->sumCaseExpensesFor($year, $month);
        $spentTotal = $officeExpenses['total'] + $caseExpenses['approved_total'];

        return [
            'received' => $funds['total'],
            'entry_count' => $funds['count'],
            'additional_received' => $funds['additional_total'],
            'spent' => $spentTotal,
            'office_spent' => $officeExpenses['total'],
            'office_count' => $officeExpenses['count'],
            'case_spent' => $caseExpenses['approved_total'],
            'case_pending' => $caseExpenses['pending_total'],
            'case_count' => $caseExpenses['approved_count'],
            'remaining' => $funds['total'] - $spentTotal,
            'remaining_after_pending' => $funds['total'] - $spentTotal - $caseExpenses['pending_total'],
            'period_label' => $month > 0 ? 'Tháng ' . $month . '/' . $year : 'Năm ' . $year,
        ];
    }

    /**
     * Lay tong chi phi vu viec de doi soat voi quy tien hanh chinh dang giu.
     */
    private function sumCaseExpensesFor(int $year, int $month): array
    {
        $builder = $this->db->table('case_expenses')
            ->select('COALESCE(SUM(CASE WHEN case_expenses.status = "approved" THEN COALESCE(case_expenses.approved_amount, case_expenses.amount) ELSE 0 END), 0) as approved_total')
            ->select('COALESCE(SUM(CASE WHEN case_expenses.status = "pending" THEN case_expenses.amount ELSE 0 END), 0) as pending_total')
            ->select('SUM(CASE WHEN case_expenses.status = "approved" THEN 1 ELSE 0 END) as approved_count')
            ->join('cases', 'cases.id = case_expenses.case_id', 'inner')
            ->where('case_expenses.deleted_at', null)
            ->where('cases.deleted_at', null)
            ->where('YEAR(case_expenses.expense_date)', $year);

        if ($month > 0) {
            $builder->where('MONTH(case_expenses.expense_date)', $month);
        }

        $row = $builder->get()->getRowArray();

        return [
            'approved_total' => (int)($row['approved_total'] ?? 0),
            'pending_total' => (int)($row['pending_total'] ?? 0),
            'approved_count' => (int)($row['approved_count'] ?? 0),
        ];
    }

    /**
     * Tổng hợp các dòng tiền nhận và phần nhập bổ sung.
     *
     * Với mỗi tháng, dòng tiền nhận đầu tiên là khoản nhận ban đầu; các dòng
     * tiếp theo trong cùng tháng được cộng vào chỉ số nhập bổ sung.
     */
    private function sumFundsFor(int $year, int $month): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->select('COUNT(*) as count')
            ->where('deleted_at', null)
            ->where('is_fund', 1)
            ->where('YEAR(expense_date)', $year);

        if ($month > 0) {
            $builder->where('MONTH(expense_date)', $month);
        }

        $row = $builder->get()->getRowArray();
        $entries = $this->getFundEntries($year, $month, 1000);
        $additionalTotal = 0;

        $seenPeriods = [];
        foreach (array_reverse($entries) as $entry) {
            $periodKey = date('Y-n', strtotime($entry['expense_date']));

            if (isset($seenPeriods[$periodKey])) {
                $additionalTotal += (int)$entry['amount'];
                continue;
            }

            $seenPeriods[$periodKey] = true;
        }

        return [
            'total' => (int)($row['total'] ?? 0),
            'count' => (int)($row['count'] ?? 0),
            'additional_total' => $additionalTotal,
        ];
    }

    /**
     * Lấy lịch sử tiền nhận từ `office_expenses` theo cờ `is_fund`.
     *
     * Danh sách này tách khỏi bảng khoản chi trên giao diện để người dùng
     * thấy rõ nguồn tiền vào nhưng báo cáo vẫn dùng chung một bảng.
     */
    private function getFundEntries(int $year, int $month, int $limit = 8): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('office_expenses.*, employees.full_name as creator_name')
            ->join('employees', 'employees.id = office_expenses.created_by', 'left')
            ->where('office_expenses.deleted_at', null)
            ->where('office_expenses.is_fund', 1)
            ->where('YEAR(office_expenses.expense_date)', $year)
            ->orderBy('office_expenses.expense_date', 'DESC')
            ->orderBy('office_expenses.id', 'DESC')
            ->limit($limit);

        if ($month > 0) {
            $builder->where('MONTH(office_expenses.expense_date)', $month);
        }

        return $builder->get()->getResultArray();
    }

    private function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['year'])) {
            $builder->where('YEAR(office_expenses.expense_date)', (int)$filters['year']);
        }
        if (!empty($filters['month'])) {
            $builder->where('MONTH(office_expenses.expense_date)', (int)$filters['month']);
        }
        if (!empty($filters['category'])) {
            $builder->where('office_expenses.category', $filters['category']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('office_expenses.vendor', $search)
                ->orLike('office_expenses.note', $search)
                ->groupEnd();
        }
    }

    private function percentChange(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function storeReceipt($file): array
    {
        if (!$this->hasUploadedFile($file)) {
            return $this->success();
        }

        $targetDir = WRITEPATH . 'uploads/office_expenses';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            return $this->fail('Khong the tao thu muc luu chung tu chi phi. Vui long kiem tra quyen ghi writable/uploads.');
        }

        $newName = $file->getRandomName();
        $clientName = $file->getClientName();
        $mimeType = $file->getMimeType();
        try {
            $file->move($targetDir, $newName);
        } catch (\Throwable $exception) {
            $this->logError('OFFICE_EXPENSE_RECEIPT_MOVE_FAILED', [
                'file_name' => $clientName,
                'error' => $exception->getMessage(),
            ]);

            return $this->fail('Khong the luu tep chung tu. Vui long thu lai hoac kiem tra quyen ghi thu muc uploads.');
        }

        return $this->success([
            'receipt_file_name' => $clientName,
            'receipt_file_path' => 'office_expenses/' . $newName,
            'receipt_file_type' => $mimeType,
        ]);
    }

    private function parseMoney($value): int
    {
        return (int)preg_replace('/[^\d]/', '', (string)$value);
    }

    /**
     * Chuẩn hóa input ngày `YYYY-MM-DD`; vẫn chấp nhận `YYYY-MM` từ form cũ.
     *
     * Bảng hiện tại dùng `expense_date`, nên tiền nhận được lưu với ngày thực tế
     * trong tháng để giao diện hiển thị được ngày phát sinh.
     */
    private function parseFundDate(string $value): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];

            if ($year >= 2024 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];

            if ($year >= 2024 && $month >= 1 && $month <= 12) {
                return sprintf('%04d-%02d-01', $year, $month);
            }
        }

        return date('Y-m-01');
    }

    private function validateReceipt($file): array
    {
        if (!$this->hasUploadedFile($file)) {
            return $this->success();
        }

        $maxBytes = $this->effectiveUploadLimitBytes($this->maxReceiptBytes);

        if (!$file->isValid()) {
            return $this->fail($this->uploadErrorMessage($file, $maxBytes));
            return $this->fail('Chứng từ tải lên không hợp lệ.');
        }

        $extension = strtolower((string)$file->getClientExtension());
        if (!in_array($extension, $this->allowedReceiptExtensions, true)) {
            return $this->fail('Chứng từ chỉ nhận ảnh JPG, PNG, WEBP hoặc PDF.');
        }

        if ((int)$file->getSize() > $maxBytes) {
            return $this->fail('Chung tu khong duoc vuot qua ' . $this->formatBytes($maxBytes) . '.');
            return $this->fail('Chứng từ không được vượt quá 10MB.');
        }

        return $this->success();
    }

    private function hasUploadedFile($file): bool
    {
        if (!$file) {
            return false;
        }

        return !method_exists($file, 'getError') || $file->getError() !== UPLOAD_ERR_NO_FILE;
    }

    private function uploadErrorMessage($file, int $maxBytes): string
    {
        $error = method_exists($file, 'getError') ? (int)$file->getError() : UPLOAD_ERR_OK;

        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'Chung tu vuot qua gioi han upload cua may chu (' . $this->formatBytes($maxBytes) . ').';
        }

        if ($error === UPLOAD_ERR_PARTIAL) {
            return 'Chung tu moi chi tai len mot phan. Vui long tai lai.';
        }

        return 'Chung tu tai len khong hop le.';
    }

    private function effectiveUploadLimitBytes(int $appLimit): int
    {
        $limits = [$appLimit];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = $this->iniBytes((string)ini_get($key));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }

        return min($limits);
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        if ($unit === 'g') {
            $number *= 1024;
        }
        if (in_array($unit, ['g', 'm'], true)) {
            $number *= 1024;
        }
        if (in_array($unit, ['g', 'm', 'k'], true)) {
            $number *= 1024;
        }

        return (int)$number;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return rtrim(rtrim(number_format($bytes / 1048576, 1, '.', ''), '0'), '.') . 'MB';
        }

        return $bytes . ' bytes';
    }

    private function deleteStoredReceipt(string $relativePath): void
    {
        $baseDir = realpath(WRITEPATH . 'uploads/office_expenses');
        $realPath = realpath(WRITEPATH . 'uploads/' . $relativePath);
        $basePrefix = $baseDir ? rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : false;

        if ($basePrefix && $realPath && strpos($realPath, $basePrefix) === 0 && is_file($realPath)) {
            @unlink($realPath);
        }
    }
}
