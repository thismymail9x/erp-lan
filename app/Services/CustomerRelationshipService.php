<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\CustomerOpportunityModel;

/**
 * CustomerRelationshipService
 *
 * Xu ly nghiep vu CRM quan he khach hang: ho so quan he, diem suc khoe, canh
 * bao cham soc va co hoi phat trien dich vu.
 */
class CustomerRelationshipService
{
    private CustomerModel $customerModel;
    private CustomerOpportunityModel $opportunityModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->opportunityModel = new CustomerOpportunityModel();
    }

    public function getProfile(int $customerId): array
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return [];
        }

        $opportunities = $this->opportunityModel->getByCustomer($customerId);
        $metrics = $this->calculateMetrics($customer, $opportunities);

        return [
            'metrics' => $metrics,
            'suggestions' => $this->buildNextActions($customer, $opportunities, $metrics),
            'opportunity_stats' => $this->getOpportunityStats($opportunities),
        ];
    }

    public function updateProfile(int $customerId, array $input): bool
    {
        $allowed = [
            'relationship_level',
            'relationship_status',
            'relationship_score',
            'health_score',
            'next_interaction_date',
            'relationship_manager_id',
            'referred_by_customer_id',
            'referral_score',
            'interests',
            'identified_issues',
            'notes_internal',
        ];

        $data = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field] === '' ? null : $input[$field];
            }
        }

        foreach (['relationship_score', 'health_score', 'referral_score'] as $scoreField) {
            if (isset($data[$scoreField])) {
                $data[$scoreField] = max(0, min(100, (int) $data[$scoreField]));
            }
        }

        if (!empty($data['relationship_manager_id']) && !$this->isActiveEmployee((int) $data['relationship_manager_id'])) {
            return false;
        }

        if (empty($data)) {
            return true;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->customerModel->update($customerId, $data);
    }

    public function createOpportunity(int $customerId, array $input): bool
    {
        $estimatedValue = str_replace([',', '.'], '', (string) ($input['estimated_value'] ?? 0));

        $assignedStaffId = empty($input['assigned_staff_id']) ? null : (int) $input['assigned_staff_id'];
        if ($assignedStaffId !== null && !$this->isActiveEmployee($assignedStaffId)) {
            return false;
        }

        return (bool) $this->opportunityModel->save([
            'customer_id' => $customerId,
            'issue_title' => trim((string) ($input['issue_title'] ?? '')),
            'issue_description' => trim((string) ($input['issue_description'] ?? '')),
            'service_suggestion' => trim((string) ($input['service_suggestion'] ?? '')),
            'estimated_value' => is_numeric($estimatedValue) ? (float) $estimatedValue : 0,
            'probability' => max(0, min(100, (int) ($input['probability'] ?? 0))),
            'assigned_staff_id' => $assignedStaffId,
            'discovered_at' => empty($input['discovered_at']) ? date('Y-m-d') : $input['discovered_at'],
            'follow_up_date' => empty($input['follow_up_date']) ? null : $input['follow_up_date'],
            'stage' => $input['stage'] ?? 'detected',
            'status' => $input['status'] ?? 'active',
            'source_type' => $input['source_type'] ?? 'manual',
            'created_by' => session()->get('employee_id'),
        ]);
    }

    public function getOpportunities(int $customerId): array
    {
        return $this->opportunityModel->getByCustomer($customerId);
    }

    public function syncAfterInteraction(int $customerId, ?string $nextFollowUp = null): void
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return;
        }

        $updateData = [
            'last_contact_date' => date('Y-m-d H:i:s'),
        ];

        if (!empty($nextFollowUp)) {
            $updateData['next_interaction_date'] = date('Y-m-d', strtotime($nextFollowUp));
        }

        $opportunities = $this->opportunityModel->getByCustomer($customerId);
        $metrics = $this->calculateMetrics(array_merge($customer, $updateData), $opportunities);
        $updateData['relationship_score'] = $metrics['relationship_score'];
        $updateData['health_score'] = $metrics['health_score'];
        $updateData['relationship_status'] = $metrics['status_key'];

        $this->customerModel->update($customerId, $updateData);
    }

    private function calculateMetrics(array $customer, array $opportunities): array
    {
        $lastContactDate = $customer['last_contact_date'] ?? null;
        $daysSinceLastContact = null;
        if (!empty($lastContactDate)) {
            $daysSinceLastContact = max(0, (int) floor((time() - strtotime($lastContactDate)) / 86400));
        }

        $score = 50;
        $score += min(25, ((int) ($customer['total_cases'] ?? 0)) * 5);
        $score += min(20, ((float) ($customer['total_revenue'] ?? 0)) >= 100000000 ? 20 : ((float) ($customer['total_revenue'] ?? 0) / 5000000));
        $score += min(10, ((int) ($customer['referral_count'] ?? 0)) * 5);

        if ($daysSinceLastContact === null) {
            $score -= 25;
        } elseif ($daysSinceLastContact > 90) {
            $score -= 35;
        } elseif ($daysSinceLastContact > 60) {
            $score -= 25;
        } elseif ($daysSinceLastContact > 30) {
            $score -= 15;
        } elseif ($daysSinceLastContact <= 7) {
            $score += 10;
        }

        $activeOpportunities = array_filter($opportunities, static fn($item) => ($item['status'] ?? '') === 'active');
        if (!empty($activeOpportunities)) {
            $score += 5;
        }

        $score = max(0, min(100, (int) round($score)));
        $healthScore = max(0, min(100, (int) (($customer['health_score'] ?? 0) ?: $score)));

        $statusKey = 'healthy';
        $statusLabel = 'Ổn định';
        $alertLevel = 'normal';

        if ($daysSinceLastContact === null || $daysSinceLastContact > 90) {
            $statusKey = 'critical';
            $statusLabel = 'Cần kích hoạt lại';
            $alertLevel = '90';
        } elseif ($daysSinceLastContact > 60) {
            $statusKey = 'risk';
            $statusLabel = 'Rủi ro nguội';
            $alertLevel = '60';
        } elseif ($daysSinceLastContact > 30) {
            $statusKey = 'watch';
            $statusLabel = 'Cần chăm sóc';
            $alertLevel = '30';
        }

        return [
            'relationship_score' => $score,
            'health_score' => $healthScore,
            'days_since_last_contact' => $daysSinceLastContact,
            'status_key' => $customer['relationship_status'] ?? $statusKey,
            'status_label' => $statusLabel,
            'alert_level' => $alertLevel,
            'active_opportunity_count' => count($activeOpportunities),
        ];
    }

    private function buildNextActions(array $customer, array $opportunities, array $metrics): array
    {
        $actions = [];

        if ($metrics['alert_level'] === '90') {
            $actions[] = 'Gọi kích hoạt lại và xác nhận nhu cầu pháp lý hiện tại.';
        } elseif ($metrics['alert_level'] === '60') {
            $actions[] = 'Đặt lịch chăm sóc lại trong tuần này, ưu tiên kênh đã từng phản hồi tốt.';
        } elseif ($metrics['alert_level'] === '30') {
            $actions[] = 'Gửi nội dung hữu ích hoặc hỏi thăm tiến độ sau dịch vụ.';
        }

        foreach ($opportunities as $opportunity) {
            if (($opportunity['status'] ?? '') === 'active' && !empty($opportunity['follow_up_date'])) {
                $actions[] = 'Theo dõi cơ hội "' . $opportunity['issue_title'] . '" trước ngày ' . date('d/m/Y', strtotime($opportunity['follow_up_date'])) . '.';
                break;
            }
        }

        if ((int) ($customer['referral_count'] ?? 0) > 0 || (int) ($customer['referral_score'] ?? 0) >= 70) {
            $actions[] = 'Đề nghị khách giới thiệu thêm người quen phù hợp.';
        }

        if (empty($actions)) {
            $actions[] = 'Duy trì nhịp chăm sóc định kỳ và cập nhật vấn đề mới sau mỗi lần trao đổi.';
        }

        return array_slice($actions, 0, 4);
    }

    private function getOpportunityStats(array $opportunities): array
    {
        $active = 0;
        $estimatedValue = 0;

        foreach ($opportunities as $opportunity) {
            if (($opportunity['status'] ?? '') === 'active') {
                $active++;
                $estimatedValue += (float) ($opportunity['estimated_value'] ?? 0);
            }
        }

        return [
            'active' => $active,
            'estimated_value' => $estimatedValue,
        ];
    }

    private function isActiveEmployee(int $employeeId): bool
    {
        if ($employeeId <= 0) {
            return false;
        }

        return \Config\Database::connect()
            ->table('employees')
            ->join('users', 'users.id = employees.user_id', 'inner')
            ->where('employees.id', $employeeId)
            ->where('employees.deleted_at', null)
            ->where('users.deleted_at', null)
            ->where('users.active_status', 1)
            ->countAllResults() > 0;
    }
}
