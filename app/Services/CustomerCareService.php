<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\CustomerCarePlanModel;
use App\Models\CustomerCareTaskModel;
use App\Models\CustomerLoyaltyModel;
use App\Models\EmployeeModel;

/**
 * CustomerCareService
 * 
 * Lớp dịch vụ trung tâm xử lý 100% logic nghiệp vụ Chăm sóc khách hàng cũ (CSKH).
 * Đảm bảo quy trình chăm sóc 3 giai đoạn, tự động tạo checklist công việc,
 * tính toán chỉ số KPI hiệu suất và quản lý chương trình Loyalty/VIP.
 * 
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #2 (Strict MVC), Rule #6 (Soft Delete).
 */
class CustomerCareService
{
    protected $customerModel;
    protected $carePlanModel;
    protected $careTaskModel;
    protected $loyaltyModel;
    protected $employeeModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->carePlanModel = new CustomerCarePlanModel();
        $this->careTaskModel = new CustomerCareTaskModel();
        $this->loyaltyModel  = new CustomerLoyaltyModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Khởi tạo một kế hoạch chăm sóc mới theo Giai đoạn (Phase).
     * Tự động sinh danh sách các công việc mặc định cho giai đoạn đó.
     * 
     * @param int $customerId ID khách hàng
     * @param string $phase Giai đoạn chăm sóc ('phase1', 'phase2', 'phase3')
     * @param int|null $assignedStaffId Nhân viên được chỉ định (mặc định lấy assigned_care_staff_id của khách hàng)
     * @return int ID của kế hoạch chăm sóc vừa khởi tạo
     */
    public function initializeCarePlan(int $customerId, string $phase, ?int $assignedStaffId = null): int
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            throw new \RuntimeException('Không tìm thấy thông tin khách hàng để lập kế hoạch.');
        }

        // Lấy nhân viên phụ trách mặc định từ khách hàng nếu không được truyền vào
        if ($assignedStaffId === null) {
            $assignedStaffId = $customer['assigned_care_staff_id'] ?? null;
        }

        // Đóng các plan cũ của giai đoạn này hoặc đang dang dở để tránh trùng lặp
        $db = \Config\Database::connect();
        $db->table('customer_care_plans')
           ->where('customer_id', $customerId)
           ->where('status', 'in_progress')
           ->where('deleted_at', null)
           ->update([
               'status' => 'skipped',
               'result_notes' => 'Tự động đóng để khởi tạo kế hoạch mới.',
               'updated_at' => date('Y-m-d H:i:s')
           ]);

        // Cấu hình tiêu đề kế hoạch tương ứng
        $phaseTitles = [
            'phase1' => 'Giai đoạn 1: Chăm sóc sau dịch vụ (Ngày 1 - 7)',
            'phase2' => 'Giai đoạn 2: Nuôi dưỡng & Hỗ trợ giá trị (Ngày 7 - 30)',
            'phase3' => 'Giai đoạn 3: Kết nối & Remarketing dài hạn (Trên 30 ngày)'
        ];
        $title = $phaseTitles[$phase] ?? 'Kế hoạch chăm sóc khách hàng';

        // Hạn chót kế hoạch (Phase 1: 7 ngày, Phase 2: 30 ngày, Phase 3: 90 ngày)
        $days = ($phase === 'phase1') ? 7 : (($phase === 'phase2') ? 30 : 90);
        $dueDate = date('Y-m-d', strtotime("+$days days"));

        $planData = [
            'customer_id'       => $customerId,
            'phase'             => $phase,
            'title'             => $title,
            'description'       => 'Hệ thống tự động thiết lập quy trình chăm sóc tiêu chuẩn.',
            'assigned_staff_id' => $assignedStaffId,
            'status'            => 'in_progress',
            'due_date'          => $dueDate,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s')
        ];

        $this->carePlanModel->save($planData);
        $planId = $this->carePlanModel->getInsertID();

        // Cập nhật trạng thái CSKH của khách hàng
        $this->customerModel->update($customerId, [
            'care_status' => $phase,
            'updated_at'  => date('Y-m-d H:i:s')
        ]);

        // Tự động tạo checklist tasks mặc định tùy theo Phase
        if ($phase === 'phase1') {
            $this->generatePhase1Tasks($planId, $customerId);
        } elseif ($phase === 'phase2') {
            $this->generatePhase2Tasks($planId, $customerId);
        } else {
            $this->generatePhase3Tasks($planId, $customerId);
        }

        // Tự động khởi tạo thẻ loyalty nếu chưa có
        $this->initializeLoyaltyCard($customerId);

        return $planId;
    }

    /**
     * Tạo checklist Giai đoạn 1: Chăm sóc sau hoàn thành hồ sơ (Cảm ơn, Feedback, Phân nhóm)
     */
    public function generatePhase1Tasks(int $planId, int $customerId): void
    {
        $tasks = [
            [
                'task_type'   => 'thank_you',
                'title'       => 'Gửi thư/tin nhắn cảm ơn đã tin dùng dịch vụ',
                'description' => 'Gửi tin nhắn Zalo OA hoặc gọi điện cảm ơn khách hàng sau khi hoàn tất hồ sơ/vụ việc vụ án.',
                'channel'     => 'zalo',
                'due_date'    => date('Y-m-d', strtotime('+1 day')),
                'sort_order'  => 1
            ],
            [
                'task_type'   => 'feedback',
                'title'       => 'Khảo sát xin ý kiến phản hồi về chất lượng phục vụ',
                'description' => 'Gọi điện hoặc gửi link khảo sát mức độ hài lòng về luật sư/nhân sự phục trách.',
                'channel'     => 'call',
                'due_date'    => date('Y-m-d', strtotime('+3 days')),
                'sort_order'  => 2
            ],
            [
                'task_type'   => 'segment_check',
                'title'       => 'Đánh giá & cập nhật phân nhóm khách hàng (A/B/C)',
                'description' => 'Dựa trên giá trị hợp đồng và khả năng kết nối để kiểm tra xem khách thuộc VIP (A), Phổ thông (B) hay Tiềm năng nguội (C).',
                'channel'     => 'meeting',
                'due_date'    => date('Y-m-d', strtotime('+5 days')),
                'sort_order'  => 3
            ]
        ];

        foreach ($tasks as $t) {
            $t['care_plan_id'] = $planId;
            $t['customer_id'] = $customerId;
            $t['is_completed'] = 0;
            $t['created_at'] = date('Y-m-d H:i:s');
            $t['updated_at'] = date('Y-m-d H:i:s');
            $this->careTaskModel->save($t);
        }
    }

    /**
     * Tạo checklist Giai đoạn 2: Nuôi dưỡng & Hỗ trợ giá trị (Hỏi thăm, Hướng dẫn pháp lý, Quà tặng)
     */
    public function generatePhase2Tasks(int $planId, int $customerId): void
    {
        $tasks = [
            [
                'task_type'   => 'follow_up',
                'title'       => 'Liên hệ hỏi thăm tình trạng vận hành thực tế',
                'description' => 'Kiểm tra xem khách hàng có gặp vướng mắc gì sau khi nhận kết quả hồ sơ/vụ việc hay không.',
                'channel'     => 'call',
                'due_date'    => date('Y-m-d', strtotime('+7 days')),
                'sort_order'  => 1
            ],
            [
                'task_type'   => 'content',
                'title'       => 'Gửi tài liệu/Cập nhật quy định pháp luật hữu ích',
                'description' => 'Gửi các văn bản quy phạm, cẩm nang nghiệp vụ hoặc tin tức pháp lý liên quan đến lĩnh vực hoạt động của khách.',
                'channel'     => 'email',
                'due_date'    => date('Y-m-d', strtotime('+15 days')),
                'sort_order'  => 2
            ],
            [
                'task_type'   => 'gift',
                'title'       => 'Gửi quà tặng tri ân hoặc ưu đãi đặc biệt',
                'description' => 'Nếu khách là VIP (Nhóm A), chuẩn bị quà vật lý hoặc voucher ưu đãi dịch vụ tiếp theo.',
                'channel'     => 'letter',
                'due_date'    => date('Y-m-d', strtotime('+25 days')),
                'sort_order'  => 3
            ]
        ];

        foreach ($tasks as $t) {
            $t['care_plan_id'] = $planId;
            $t['customer_id'] = $customerId;
            $t['is_completed'] = 0;
            $t['created_at'] = date('Y-m-d H:i:s');
            $t['updated_at'] = date('Y-m-d H:i:s');
            $this->careTaskModel->save($t);
        }
    }

    /**
     * Tạo checklist Giai đoạn 3: Kết nối & Remarketing dài hạn (Tin tức định kỳ, Remarketing)
     */
    public function generatePhase3Tasks(int $planId, int $customerId): void
    {
        $tasks = [
            [
                'task_type'   => 'content',
                'title'       => 'Gửi bản tin pháp lý định kỳ hàng tháng',
                'description' => 'Tự động gửi email bản tin, thông điệp chia sẻ từ luật sư điều hành.',
                'channel'     => 'email',
                'due_date'    => date('Y-m-d', strtotime('+30 days')),
                'sort_order'  => 1
            ],
            [
                'task_type'   => 'call',
                'title'       => 'Gọi điện thăm hỏi định kỳ 60 ngày',
                'description' => 'Kết nối duy trì mối quan hệ tốt đẹp, lắng nghe nhu cầu phát sinh mới.',
                'channel'     => 'call',
                'due_date'    => date('Y-m-d', strtotime('+60 days')),
                'sort_order'  => 2
            ],
            [
                'task_type'   => 'remarketing',
                'title'       => 'Giới thiệu dịch vụ mới hoặc chương trình tri ân cuối năm',
                'description' => 'Remarketing bán chéo (Cross-selling) dịch vụ hoặc mời tham gia workshop/hội thảo.',
                'channel'     => 'zalo',
                'due_date'    => date('Y-m-d', strtotime('+80 days')),
                'sort_order'  => 3
            ]
        ];

        foreach ($tasks as $t) {
            $t['care_plan_id'] = $planId;
            $t['customer_id'] = $customerId;
            $t['is_completed'] = 0;
            $t['created_at'] = date('Y-m-d H:i:s');
            $t['updated_at'] = date('Y-m-d H:i:s');
            $this->careTaskModel->save($t);
        }
    }

    /**
     * Hoàn thành một công việc chăm sóc khách hàng.
     * Tự động kiểm tra: Nếu tất cả công việc trong plan đã xong thì tự động hoàn tất kế hoạch chăm sóc đó.
     * 
     * @param int $taskId ID công việc
     * @param int $staffId ID nhân viên thực hiện
     * @param string|null $notes Ghi chú kết quả thực tế
     * @return bool
     */
    public function completeTask(int $taskId, int $staffId, ?string $notes = null): bool
    {
        $task = $this->careTaskModel->find($taskId);
        if (!$task) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // 1. Cập nhật trạng thái hoàn thành của task
        $this->careTaskModel->update($taskId, [
            'is_completed' => 1,
            'completed_by' => $staffId,
            'completed_at' => $now,
            'description'  => $task['description'] . ($notes ? "\n[Ghi chú CSKH: $notes]" : ''),
            'updated_at'   => $now
        ]);

        // Cập nhật ngày liên hệ gần nhất của khách hàng
        $this->customerModel->update($task['customer_id'], [
            'last_contact_date' => $now
        ]);

        // 2. Kiểm tra các task còn lại trong cùng plan
        $planId = $task['care_plan_id'];
        $pendingTasksCount = $this->careTaskModel->where('care_plan_id', $planId)
                                                 ->where('is_completed', 0)
                                                 ->where('deleted_at', null)
                                                 ->countAllResults();

        if ($pendingTasksCount === 0) {
            // Tự động hoàn thành kế hoạch nếu toàn bộ checklist đã xong
            $this->carePlanModel->update($planId, [
                'status'       => 'completed',
                'completed_at' => $now,
                'result_notes' => 'Hoàn tất toàn bộ công việc trong quy trình chăm sóc.',
                'updated_at'   => $now
            ]);

            // Cập nhật trạng thái CSKH trên khách hàng
            $plan = $this->carePlanModel->find($planId);
            if ($plan) {
                $nextStatus = ($plan['phase'] === 'phase1') ? 'phase2' : (($plan['phase'] === 'phase2') ? 'phase3' : 'completed');
                $this->customerModel->update($task['customer_id'], [
                    'care_status' => $nextStatus,
                    'updated_at'  => $now
                ]);
            }
        }

        // Tự động cộng điểm loyalty/VIP cho khách hàng (thưởng điểm vì tương tác thành công)
        $this->awardLoyaltyPoints($task['customer_id'], 10); // Thưởng 10 điểm cho mỗi lần tương tác

        return true;
    }

    /**
     * Khởi tạo thẻ thành viên Loyalty/VIP nếu khách chưa có.
     * 
     * @param int $customerId
     * @return void
     */
    public function initializeLoyaltyCard(int $customerId): void
    {
        $existing = $this->loyaltyModel->getByCustomer($customerId);
        if ($existing) {
            return;
        }

        $referralCode = $this->loyaltyModel->generateReferralCode($customerId);
        $benefits = [
            'standard' => ['Ưu tiên hỗ trợ qua tổng đài', 'Nhận tài liệu cập nhật pháp luật miễn phí'],
            'silver'   => ['Giảm 5% phí dịch vụ tư vấn tiếp theo', 'Ưu tiên hỗ trợ qua tổng đài', 'Nhận tài liệu cập nhật pháp luật'],
            'gold'     => ['Giảm 10% phí dịch vụ tư vấn', 'Nhân viên chăm sóc riêng', 'Quà tặng sinh nhật', 'Hỗ trợ khẩn cấp 24/7'],
            'vip'      => ['Giảm 15% phí toàn bộ vụ việc', 'Sếp tổng hỗ trợ trực tiếp', 'Quà tặng lễ tết đặc biệt', 'Phòng chờ tiếp đón VIP']
        ];

        $loyaltyData = [
            'customer_id'     => $customerId,
            'loyalty_tier'    => 'standard',
            'benefits'        => json_encode($benefits['standard'], JSON_UNESCAPED_UNICODE),
            'points'          => 0,
            'referral_code'   => $referralCode,
            'total_referrals' => 0,
            'activated_at'    => date('Y-m-d H:i:s'),
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s')
        ];

        $this->loyaltyModel->save($loyaltyData);
    }

    /**
     * Cộng điểm loyalty cho khách hàng.
     * 
     * @param int $customerId
     * @param int $points
     * @return void
     */
    public function awardLoyaltyPoints(int $customerId, int $points): void
    {
        $loyalty = $this->loyaltyModel->getByCustomer($customerId);
        if (!$loyalty) {
            $this->initializeLoyaltyCard($customerId);
            $loyalty = $this->loyaltyModel->getByCustomer($customerId);
        }

        if ($loyalty) {
            $newPoints = (int)$loyalty['points'] + $points;
            $this->loyaltyModel->update($loyalty['id'], [
                'points'     => $newPoints,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Tính toán lại hạng thẻ VIP tự động sau khi có điểm mới
            $this->calculateLoyaltyTier($customerId);
        }
    }

    /**
     * Tự động tính toán hạng thành viên (Standard/Silver/Gold/VIP) dựa trên Doanh thu và Điểm tích lũy.
     * Tiêu chí:
     * - VIP (Nhóm A): Doanh thu >= 100tr HOẶC điểm >= 1000
     * - Gold (Vàng): Doanh thu >= 50tr HOẶC điểm >= 500
     * - Silver (Bạc): Doanh thu >= 20tr HOẶC điểm >= 200
     * - Standard (Tiêu chuẩn): Dưới 20tr và dưới 200 điểm
     * 
     * @param int $customerId
     * @return string Hạng mới được gán
     */
    public function calculateLoyaltyTier(int $customerId): string
    {
        $customer = $this->customerModel->find($customerId);
        $loyalty = $this->loyaltyModel->getByCustomer($customerId);

        if (!$customer || !$loyalty) {
            return 'standard';
        }

        $revenue = (float)($customer['total_revenue'] ?? 0);
        $points = (int)($loyalty['points'] ?? 0);

        $tier = 'standard';
        if ($revenue >= 100000000 || $points >= 1000) {
            $tier = 'vip';
        } elseif ($revenue >= 50000000 || $points >= 500) {
            $tier = 'gold';
        } elseif ($revenue >= 20000000 || $points >= 200) {
            $tier = 'silver';
        }

        $benefits = [
            'standard' => ['Ưu tiên hỗ trợ qua tổng đài', 'Nhận tài liệu cập nhật pháp luật miễn phí'],
            'silver'   => ['Giảm 5% phí dịch vụ tư vấn tiếp theo', 'Ưu tiên hỗ trợ qua tổng đài', 'Nhận tài liệu cập nhật pháp luật'],
            'gold'     => ['Giảm 10% phí dịch vụ tư vấn', 'Nhân viên chăm sóc riêng', 'Quà tặng sinh nhật', 'Hỗ trợ khẩn cấp 24/7'],
            'vip'      => ['Giảm 15% phí toàn bộ vụ việc', 'Sếp tổng hỗ trợ trực tiếp', 'Quà tặng lễ tết đặc biệt', 'Phòng chờ tiếp đón VIP']
        ];

        $this->loyaltyModel->update($loyalty['id'], [
            'loyalty_tier' => $tier,
            'benefits'     => json_encode($benefits[$tier], JSON_UNESCAPED_UNICODE),
            'updated_at'   => date('Y-m-d H:i:s')
        ]);

        return $tier;
    }

    /**
     * Xử lý giới thiệu khách hàng mới.
     * Khi khách hàng A giới thiệu khách hàng B thành công:
     * 1. Cộng 100 điểm loyalty cho A.
     * 2. Tăng referral_count và total_referrals của A.
     * 3. Ghi nhận referred_by trên hồ sơ B.
     * 
     * @param int $referrerId ID của Khách hàng giới thiệu (Khách cũ)
     * @param int $newCustomerId ID của Khách hàng được giới thiệu (Khách mới)
     * @return bool
     */
    public function processReferral(int $referrerId, int $newCustomerId): bool
    {
        $referrer = $this->customerModel->find($referrerId);
        $newCustomer = $this->customerModel->find($newCustomerId);

        if (!$referrer || !$newCustomer) {
            return false;
        }

        // Cập nhật người giới thiệu trên hồ sơ khách mới
        $this->customerModel->update($newCustomerId, [
            'referred_by' => $referrer['name'],
            'updated_at'  => date('Y-m-d H:i:s')
        ]);

        // Cập nhật số lần giới thiệu của người giới thiệu cũ
        $newCount = (int)($referrer['referral_count'] ?? 0) + 1;
        $this->customerModel->update($referrerId, [
            'referral_count' => $newCount,
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        // Cập nhật bảng loyalty của người giới thiệu
        $loyalty = $this->loyaltyModel->getByCustomer($referrerId);
        if (!$loyalty) {
            $this->initializeLoyaltyCard($referrerId);
            $loyalty = $this->loyaltyModel->getByCustomer($referrerId);
        }

        if ($loyalty) {
            $newPoints = (int)$loyalty['points'] + 100; // Tặng 100 điểm thưởng giới thiệu
            $newTotalReferrals = (int)$loyalty['total_referrals'] + 1;
            
            $this->loyaltyModel->update($loyalty['id'], [
                'points'          => $newPoints,
                'total_referrals' => $newTotalReferrals,
                'updated_at'      => date('Y-m-d H:i:s')
            ]);

            $this->calculateLoyaltyTier($referrerId);
        }

        // Tự động cập nhật lại phân nhóm của người giới thiệu
        $customerService = new CustomerService();
        $customerService->autoSegmentCustomer($referrerId);

        return true;
    }

    /**
     * Lấy các chỉ số KPI hiệu suất CSKH phục vụ dashboard.
     * KPI bao gồm:
     * - Tỷ lệ quay lại mua dịch vụ (Khách phát sinh vụ việc >= 2)
     * - Tỷ lệ khách giới thiệu thành công (referral_count > 0)
     * - Tỷ lệ phản hồi khảo sát thành công (is_completed của feedback tasks)
     * 
     * @param int|null $staffId
     * @return array
     */
    public function getCareKPI(?int $staffId = null): array
    {
        $db = \Config\Database::connect();

        // 1. Tổng số khách hàng
        $customerQuery = $db->table('customers')->where('deleted_at', null);
        if ($staffId) {
            $customerQuery->where('assigned_care_staff_id', $staffId);
        }
        $totalCustomers = $customerQuery->countAllResults();

        if ($totalCustomers === 0) {
            return [
                'retention_rate'  => 0,
                'referral_rate'   => 0,
                'feedback_rate'   => 0,
                'total_referrals' => 0
            ];
        }

        // 2. Khách hàng quay lại (Có số vụ việc >= 2)
        $retentionQuery = $db->table('customers')
                             ->where('deleted_at', null)
                             ->where('total_cases >=', 2);
        if ($staffId) {
            $retentionQuery->where('assigned_care_staff_id', $staffId);
        }
        $retentionCustomers = $retentionQuery->countAllResults();
        $retentionRate = round(($retentionCustomers / $totalCustomers) * 100, 1);

        // 3. Khách hàng có giới thiệu (referral_count > 0)
        $referralQuery = $db->table('customers')
                            ->where('deleted_at', null)
                            ->where('referral_count >', 0);
        if ($staffId) {
            $referralQuery->where('assigned_care_staff_id', $staffId);
        }
        $referralCustomers = $referralQuery->countAllResults();
        $referralRate = round(($referralCustomers / $totalCustomers) * 100, 1);

        // 4. Tỷ lệ khảo sát phản hồi thành công (Feedback tasks)
        $feedbackTasksQuery = $db->table('customer_care_tasks')
                                 ->where('task_type', 'feedback')
                                 ->where('deleted_at', null);
        
        if ($staffId) {
            $feedbackTasksQuery->whereIn('care_plan_id', function($sub) use ($staffId) {
                return $sub->select('id')->from('customer_care_plans')->where('assigned_staff_id', $staffId)->where('deleted_at', null);
            });
        }
        
        $totalFeedbackTasks = $feedbackTasksQuery->countAllResults();
        
        $completedFeedbackQuery = $db->table('customer_care_tasks')
                                     ->where('task_type', 'feedback')
                                     ->where('is_completed', 1)
                                     ->where('deleted_at', null);
        
        if ($staffId) {
            $completedFeedbackQuery->whereIn('care_plan_id', function($sub) use ($staffId) {
                return $sub->select('id')->from('customer_care_plans')->where('assigned_staff_id', $staffId)->where('deleted_at', null);
            });
        }
        
        $completedFeedbackTasks = $completedFeedbackQuery->countAllResults();
        $feedbackRate = $totalFeedbackTasks > 0 ? round(($completedFeedbackTasks / $totalFeedbackTasks) * 100, 1) : 0;

        // 5. Tổng lượt giới thiệu thành công
        $totalReferralSumQuery = $db->table('customers')
                                    ->where('deleted_at', null)
                                    ->selectSum('referral_count');
        if ($staffId) {
            $totalReferralSumQuery->where('assigned_care_staff_id', $staffId);
        }
        $totalReferrals = (int)$totalReferralSumQuery->get()->getRow()->referral_count;

        return [
            'retention_rate'  => $retentionRate,
            'referral_rate'   => $referralRate,
            'feedback_rate'   => $feedbackRate,
            'total_referrals' => $totalReferrals
        ];
    }

    /**
     * Lấy các chỉ số thống kê hàng tháng (KPI, xu hướng, phân phối).
     * 
     * @param int|null $staffId
     * @return array
     */
    public function getMonthlyStats(?int $staffId = null): array
    {
        $db = \Config\Database::connect();
        
        // Phân phối segment A/B/C
        $segmentQuery = $db->table('customers')
                           ->select('customer_segment, COUNT(*) as count')
                           ->where('deleted_at', null);
        if ($staffId) {
            $segmentQuery->where('assigned_care_staff_id', $staffId);
        }
        $segmentDistribution = $segmentQuery->groupBy('customer_segment')->get()->getResultArray();

        // Xu hướng CSKH theo tháng (Số lượng plans hoàn thành)
        $trendQuery = $db->table('customer_care_plans')
                         ->select("MONTH(completed_at) as month, COUNT(*) as count")
                         ->where('status', 'completed')
                         ->where('deleted_at', null)
                         ->where("YEAR(completed_at)", date('Y'));
        if ($staffId) {
            $trendQuery->where('assigned_staff_id', $staffId);
        }
        $trends = $trendQuery->groupBy('MONTH(completed_at)')->get()->getResultArray();

        return [
            'segments' => $segmentDistribution,
            'trends'   => $trends
        ];
    }

    /**
     * Lấy danh sách khách hàng cần CSKH sau khi hoàn tất hồ sơ/dịch vụ.
     * (Khách hàng có service_completed_date gần đây nhưng chưa có care plan nào đang chạy).
     */
    public function getCustomersNeedingFollowUp(int $days = 7): array
    {
        $thresholdDate = date('Y-m-d', strtotime("-$days days"));
        
        // Query những khách hàng có ngày hoàn tất dịch vụ gần nhất nhưng chưa có care plan active
        return $this->customerModel->where('service_completed_date >=', $thresholdDate)
                                   ->where('care_status', 'new')
                                   ->where('deleted_at', null)
                                   ->findAll();
    }

    /**
     * Lấy sinh nhật sắp tới trong vòng N ngày tới.
     * 
     * @param int $days Số ngày cần nhắc trước
     * @return array
     */
    public function getUpcomingBirthdays(int $days = 7): array
    {
        $db = \Config\Database::connect();
        
        $todayMonth = (int)date('m');
        $todayDay = (int)date('d');
        
        $targetDate = date('Y-m-d', strtotime("+$days days"));
        $targetMonth = (int)date('m', strtotime($targetDate));
        $targetDay = (int)date('d', strtotime($targetDate));

        $builder = $db->table('customers')
                      ->select('id, name, phone, date_of_birth, assigned_care_staff_id')
                      ->where('date_of_birth IS NOT NULL')
                      ->where('deleted_at', null);

        // Trường hợp cùng tháng
        if ($todayMonth === $targetMonth) {
            $builder->where('MONTH(date_of_birth)', $todayMonth)
                    ->where('DAY(date_of_birth) >=', $todayDay)
                    ->where('DAY(date_of_birth) <=', $targetDay);
        } else {
            // Trường hợp vắt qua tháng tiếp theo
            $builder->groupStart()
                        ->groupStart()
                            ->where('MONTH(date_of_birth)', $todayMonth)
                            ->where('DAY(date_of_birth) >=', $todayDay)
                        ->groupEnd()
                        ->orGroupStart()
                            ->where('MONTH(date_of_birth)', $targetMonth)
                            ->where('DAY(date_of_birth) <=', $targetDay)
                        ->groupEnd()
                    ->groupEnd();
        }

        return $builder->orderBy('MONTH(date_of_birth)', 'ASC')
                       ->orderBy('DAY(date_of_birth)', 'ASC')
                       ->get()
                       ->getResultArray();
    }
}
