<?php

namespace App\Services;

use App\Models\ZaloFollowerModel;
use App\Models\MessengerContactModel;
use App\Models\EmployeeModel;
use App\Models\UserModel;
use App\Models\CustomerModel;
use App\Models\NotificationModel;
use App\Models\TagModel;

/**
 * ChatAssignmentService
 * 
 * Lớp xử lý nghiệp vụ trung tâm cho Giai đoạn 2 & 3 của Hệ thống Tư vấn Khách hàng.
 * Đảm nhiệm các vai trò:
 * - Làm sạch và lọc trùng lặp tự động (theo SĐT hoặc Email).
 * - Nhận diện từ khóa pháp lý để tự động gán nhãn lĩnh vực.
 * - Chấm điểm độ nóng của lead (Nóng / Ấm / Lạnh) dựa trên tin nhắn.
 * - Phân công lead tự động (Auto-routing) tối ưu hóa tải công việc & chuyên môn nhân viên.
 * - Quản lý Deadline phản hồi đầu tiên 2h (trong giờ hành chính), cảnh báo đỏ và tự động chuyển đổi lead.
 * 
 * Tuân thủ nghiêm ngặt Bộ 13 Quy tắc phát triển (Strict MVC, Comment tiếng Việt 100%, Giải thích thuật toán chi tiết).
 */
class ChatAssignmentService
{
    protected $db;
    protected $zaloFollowerModel;
    protected $messengerContactModel;
    protected $employeeModel;
    protected $customerModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->db                    = \Config\Database::connect();
        $this->zaloFollowerModel     = new ZaloFollowerModel();
        $this->messengerContactModel = new MessengerContactModel();
        $this->employeeModel         = new EmployeeModel();
        $this->customerModel         = new CustomerModel();
        $this->notificationModel     = new NotificationModel();
    }

    // =========================================================================
    //  GIAI ĐOẠN 2: LÀM SẠCH VÀ PHÂN LOẠI LEAD
    // =========================================================================

    /**
     * Phân tích nội dung tin nhắn để tự động gắn nhãn lĩnh vực và đánh giá độ nóng.
     * 
     * THUẬT TOÁN NHẬN DIỆN:
     * 1. Gắn lĩnh vực pháp lý: Sử dụng Regex khớp từ khóa tiếng Việt không dấu/có dấu để tìm
     *    các chủ đề phổ biến (Đất đai, Ly hôn, Doanh nghiệp, Hình sự, Dân sự).
     * 2. Đánh giá độ nóng:
     *    - Nóng (hot): Hỏi về vụ việc thực tế, có từ khóa về tranh chấp, tòa án, khởi kiện, công an...
     *    - Ấm (warm): Hỏi mang tính chất thăm dò chi phí, thủ tục, thời gian làm việc...
     *    - Lạnh (cold): Chào hỏi chung chung hoặc tin nhắn quá ngắn.
     * 
     * @param string $text Nội dung tin nhắn của khách hàng
     * @return array ['tags' => array, 'lead_warmth' => string]
     */
    public function analyzeLeadContent(string $text): array
    {
        $textLower = mb_strtolower($text, 'UTF-8');
        $suggestedTags = [];
        $leadWarmth = 'cold'; // Mặc định là Lạnh

        // --- 1. NHẬN DIỆN LĨNH VỰC PHÁP LÝ (KEYWORDS TO TAGS) ---
        $rules = [
            'Đất đai' => [
                'đất', 'dat', 'sổ đỏ', 'so do', 'sổ hồng', 'so hong', 'tranh chấp đất', 'tranh chap dat',
                'nhà đất', 'nha dat', 'thu hồi đất', 'thu hoi dat', 'đền bù', 'den bu', 'cấp sổ', 'cap so'
            ],
            'Ly hôn' => [
                'ly hôn', 'ly hon', 'ly dị', 'ly di', 'tòa án', 'toa an', 'chia tài sản', 'chia tai san',
                'giành quyền nuôi con', 'gianh quyen nuoi con', 'nuôi con', 'nuoi con', 'cấp dưỡng', 'cap duong'
            ],
            'Doanh nghiệp' => [
                'thành lập công ty', 'thanh lap cong ty', 'doanh nghiệp', 'doanh nghiep', 'đăng ký kinh doanh',
                'dang ky kinh doanh', 'giấy phép', 'giay phep', 'vốn điều lệ', 'von dieu le', 'giải thể', 'giai the'
            ],
            'Hình sự' => [
                'hình sự', 'hinh su', 'bị can', 'bi can', 'bị cáo', 'bi cao', 'bắt giam', 'bat giam',
                'khởi tố', 'khoi to', 'tù giam', 'tu giam', 'công an', 'cong an', 'triệu tập', 'trieu tap'
            ],
            'Dân sự' => [
                'dân sự', 'dan su', 'hợp đồng', 'hop dong', 'vay tiền', 'vay tien', 'đòi nợ', 'doi no',
                'bồi thường', 'boi thuong', 'thừa kế', 'thua ke', 'di chúc', 'di chuc', 'ủy quyền', 'uy quyen'
            ]
        ];

        foreach ($rules as $tag => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($textLower, $kw, 0, 'UTF-8') !== false) {
                    $suggestedTags[] = $tag;
                    break; // Khớp 1 từ khóa trong nhóm là đủ gán nhãn lĩnh vực này
                }
            }
        }

        // --- 2. ĐÁNH GIÁ ĐỘ NÓNG CỦA LEAD (LEAD WARMTH HEURISTICS) ---
        $hotKeywords = [
            'vụ việc', 'vu viec', 'kiện', 'kien', 'khởi kiện', 'khoi kien', 'tòa án', 'toa an', 'triệu tập', 'trieu tap',
            'bị bắt', 'bi bat', 'tạm giam', 'tam giam', 'công an', 'cong an', 'bồi thường', 'boi thuong', 'tranh chấp', 'tranh chap',
            'đánh nhau', 'danh nhau', 'giật đất', 'giat dat', 'lừa đảo', 'lua dao', 'chiếm đoạt', 'chiem doat'
        ];

        $warmKeywords = [
            'tư vấn', 'tu van', 'hỏi', 'hoi', 'chi phí', 'chi phi', 'phí dịch vụ', 'phi dich vu', 'bao nhiêu', 'bao nhieu',
            'thủ tục', 'thu tuc', 'giấy tờ', 'giay to', 'ở đâu', 'o dau', 'thời gian', 'thoi gian', 'cần những gì', 'can nhung gi'
        ];

        // Kiểm tra từ khóa Nóng trước
        $isHot = false;
        foreach ($hotKeywords as $kw) {
            if (mb_strpos($textLower, $kw, 0, 'UTF-8') !== false) {
                $isHot = true;
                break;
            }
        }

        if ($isHot) {
            $leadWarmth = 'hot';
        } else {
            // Nếu không Nóng, kiểm tra xem có Ấm không
            $isWarm = false;
            foreach ($warmKeywords as $kw) {
                if (mb_strpos($textLower, $kw, 0, 'UTF-8') !== false) {
                    $isWarm = true;
                    break;
                }
            }
            if ($isWarm) {
                $leadWarmth = 'warm';
            } else {
                $leadWarmth = 'cold';
            }
        }

        return [
            'tags'        => array_unique($suggestedTags),
            'lead_warmth' => $leadWarmth
        ];
    }

    /**
     * Kiểm tra trùng lặp thông tin liên hệ và tự động liên kết CRM.
     * 
     * THUẬT TOÁN KIỂM TRA TRÙNG LẶP:
     * 1. Tìm trong bảng `customers` xem có khách hàng CRM nào khớp số điện thoại hoặc email.
     * 2. Tìm trong bảng liên hệ chat (`zalo_followers`, `messenger_contacts`) xem có hội thoại nào khác trùng SĐT/Email.
     * 3. Nếu tìm thấy trùng:
     *    - Đánh dấu `is_duplicate = 1`.
     *    - Ghi nhận `duplicate_of` là ID của liên hệ chính được tạo trước.
     *    - Tự động liên kết `customer_id` với Khách hàng CRM tìm được.
     *    - Đề xuất gán người chăm sóc giống như người phụ trách khách hàng CRM đó (`assigned_care_staff_id`).
     * 
     * @param string $phone Số điện thoại của khách hàng
     * @param string|null $email Email của khách hàng
     * @param string $channel Kênh hiện tại ('zalo' hoặc 'messenger')
     * @param int|null $currentId ID bản ghi hiện tại để loại trừ tự khớp chính mình
     * @return array Thông tin trùng lặp thu thập được
     */
    public function checkDuplicates(string $phone, ?string $email, string $channel, ?int $currentId = null): array
    {
        $phone = trim($phone);
        $email = $email ? trim($email) : null;
        
        $result = [
            'is_duplicate' => false,
            'duplicate_of' => null,
            'customer_id'  => null,
            'assigned_to'  => null, // Đề xuất người phụ trách
        ];

        if (empty($phone) && empty($email)) {
            return $result;
        }

        $variants = !empty($phone) ? get_phone_variants($phone) : [];

        // 1. Quét trong bảng customers (CRM)
        $customerQuery = $this->customerModel->groupStart();
        if (!empty($variants)) {
            $customerQuery->whereIn('phone', $variants)->orWhereIn('phone_secondary', $variants);
        }
        if (!empty($email)) {
            $customerQuery->orWhere('email', $email)->orWhere('email_secondary', $email);
        }
        $customer = $customerQuery->groupEnd()->where('deleted_at', null)->first();

        if ($customer) {
            $result['customer_id'] = $customer['id'];
            
            // Tìm nhân sự phụ trách chăm sóc khách hàng CRM này
            if (!empty($customer['assigned_care_staff_id'])) {
                // Lấy user_id tương ứng với employee_id chăm sóc
                $emp = $this->employeeModel->find($customer['assigned_care_staff_id']);
                if ($emp && !empty($emp['user_id'])) {
                    $result['assigned_to'] = $emp['user_id'];
                }
            }
        }

        // 2. Quét trong các bảng chat liên hệ để tìm liên hệ trùng trước đó
        $duplicateContactId = null;

        if ($channel === 'zalo') {
            $query = $this->zaloFollowerModel->groupStart();
            if (!empty($variants)) {
                $query->whereIn('phone_number', $variants);
            }
            if (!empty($email)) {
                $query->orWhere('email', $email);
            }
            $query->groupEnd();
            
            if ($currentId) {
                $query->where('id !=', $currentId);
            }
            
            $dup = $query->orderBy('created_at', 'ASC')->first();
            if ($dup) {
                $duplicateContactId = $dup['id'];
                if (empty($result['assigned_to']) && !empty($dup['assigned_to'])) {
                    $result['assigned_to'] = $dup['assigned_to'];
                }
            }
        } else { // messenger
            $query = $this->messengerContactModel->groupStart();
            if (!empty($variants)) {
                $query->whereIn('phone_number', $variants);
            }
            if (!empty($email)) {
                $query->orWhere('email', $email);
            }
            $query->groupEnd();
            
            if ($currentId) {
                $query->where('id !=', $currentId);
            }
            
            $dup = $query->where('deleted_at', null)->orderBy('created_at', 'ASC')->first();
            if ($dup) {
                $duplicateContactId = $dup['id'];
                if (empty($result['assigned_to']) && !empty($dup['assigned_to'])) {
                    $result['assigned_to'] = $dup['assigned_to'];
                }
            }
        }

        if ($duplicateContactId) {
            $result['is_duplicate'] = true;
            $result['duplicate_of'] = $duplicateContactId;
        }

        return $result;
    }


    // =========================================================================
    //  GIAI ĐOẠN 3: PHÂN CÔNG CÓ KIỂM SOÁT
    // =========================================================================

    /**
     * Tính toán deadline phản hồi đầu tiên 2 giờ dựa trên Giờ làm việc hành chính.
     * 
     * THUẬT TOÁN GIỜ HÀNH CHÍNH (8:00 - 17:30):
     * - Nếu thời điểm phân công nằm ngoài giờ hành chính (đêm, cuối tuần):
     *   Deadline = 8:00 AM ngày làm việc tiếp theo + 2 tiếng = 10:00 AM ngày hôm sau.
     * - Nếu thời điểm phân công trong giờ hành chính nhưng thời gian 2 tiếng vượt quá 17:30:
     *   Lấy phần dư vượt quá 17:30, cộng tiếp vào lúc 8:00 AM ngày làm việc tiếp theo.
     *   (Ví dụ: gán lúc 16:30 -> hạn chót là 9:00 AM ngày hôm sau).
     * 
     * @param string $assignedAtTime Thời điểm bắt đầu phân công (dạng Y-m-d H:i:s)
     * @return string Hạn phản hồi đầu tiên (dạng Y-m-d H:i:s)
     */
    public function calculateFirstResponseDeadline(string $assignedAtTime, $hours = 2): string
    {
        $assignedAt = strtotime($assignedAtTime);
        
        $startWorkSec = strtotime(date('Y-m-d', $assignedAt) . ' 08:00:00');
        $endWorkSec   = strtotime(date('Y-m-d', $assignedAt) . ' 17:30:00');
        
        $deadlineSec = $assignedAt + ($hours * 3600); 

        // 1. Phân công ngoài giờ hành chính trước 8:00
        if ($assignedAt < $startWorkSec) {
            $deadlineSec = $startWorkSec + ($hours * 3600);
        }
        // 2. Phân công ngoài giờ hành chính sau 17:30 hoặc rơi vào cuối tuần (Thứ 7 & Chủ Nhật)
        elseif ($assignedAt > $endWorkSec || date('N', $assignedAt) >= 6) {
            // Chuyển sang ngày làm việc hành chính tiếp theo
            $nextWorkDay = $this->_getNextWorkingDay(date('Y-m-d', $assignedAt));
            $deadlineSec = strtotime($nextWorkDay . ' 08:00:00') + ($hours * 3600);
        }
        // 3. Phân công trong giờ hành chính nhưng deadline vượt qua 17:30
        elseif ($deadlineSec > $endWorkSec) {
            $overSec = $deadlineSec - $endWorkSec; // Số giây dư thừa vượt 17:30
            $nextWorkDay = $this->_getNextWorkingDay(date('Y-m-d', $assignedAt));
            $deadlineSec = strtotime($nextWorkDay . ' 08:00:00') + $overSec;
        }
        
        // 4. Nếu ngày deadline rơi vào cuối tuần do nhảy ngày, chuyển tiếp sang thứ hai
        $dayOfWeek = date('N', $deadlineSec);
        if ($dayOfWeek == 6) { // Thứ Bảy -> chuyển sang Thứ Hai
            $deadlineSec = strtotime(date('Y-m-d', $deadlineSec) . ' 08:00:00') + (2 * 86400) + ($hours * 3600);
        } elseif ($dayOfWeek == 7) { // Chủ Nhật -> chuyển sang Thứ Hai
            $deadlineSec = strtotime(date('Y-m-d', $deadlineSec) . ' 08:00:00') + (1 * 86400) + ($hours * 3600);
        }

        return date('Y-m-d H:i:s', $deadlineSec);
    }

    public function getOngoingSlaHours(): float
    {
        $setting = $this->db->table('system_settings')->where('key', 'ongoing_sla_hours')->get()->getRowArray();
        if ($setting && is_numeric($setting['value'])) {
            return (float)$setting['value'];
        }
        return 2.0; // Mặc định 2 tiếng
    }

    /**
     * Tự động phân công Lead dựa trên lĩnh vực chuyên môn và tải công việc hiện tại.
     * 
     * THUẬT TOÁN TỰ ĐỘNG PHÂN CÔNG (AUTO-ROUTING):
     * 1. Đọc các Tag pháp lý hiện có của Lead.
     * 2. Tìm nhân viên có `specialties` khớp với ít nhất một trong các Tag đó.
     * 3. Nếu không có nhân sự chuyên môn, tìm tất cả nhân viên thuộc phòng ban Pháp lý (ID 3) hoặc Sale (ID 2).
     * 4. Tính toán tải công việc hiện tại (workload) của từng ứng viên:
     *    - Đếm số lượng cuộc hội thoại Chat chưa phản hồi hoặc số lead đang nhận chăm sóc.
     *    - Loại bỏ các nhân sự đang quá tải (vượt quá `max_workload` của họ).
     * 5. Lọc ra nhân viên có tải công việc hiện tại thấp nhất (Lowest Workload).
     * 6. Tiến hành gán lead, cập nhật `assigned_to`, `assigned_at` và `first_response_deadline`.
     * 
     * @param string $channel Kênh hội thoại ('zalo' hoặc 'messenger')
     * @param int $contactId ID liên hệ chat
     * @param array $excludeUserIds Danh sách user_id loại trừ (ví dụ người vừa bị quá hạn thu hồi)
     * @return bool Trạng thái phân công có thành công hay không
     */
    public function autoAssignLead(string $channel, int $contactId, array $excludeUserIds = []): bool
    {
        $contactModel = ($channel === 'zalo') ? $this->zaloFollowerModel : $this->messengerContactModel;
        $contact = $contactModel->find($contactId);
        
        if (!$contact) {
            return false;
        }

        // Lấy tags của lead để so sánh với chuyên môn nhân viên
        $leadTags = json_decode($contact['tags'] ?? '[]', true) ?: [];
        
        // 1. Lấy tất cả nhân sự đang hoạt động trong các phòng ban được phép (Pháp lý hoặc Sale)
        $candidates = $this->employeeModel->select('employees.*, users.id as user_id')
                                          ->join('users', 'users.id = employees.user_id')
                                          ->where('users.active_status', 1)
                                          ->whereIn('employees.department_id', [\Config\AppConstants::DEPT_PHAP_LY, \Config\AppConstants::DEPT_SALE])
                                          ->where('employees.deleted_at', null);
        
        if (!empty($excludeUserIds)) {
            $candidates->whereNotIn('users.id', $excludeUserIds);
        }

        $employees = $candidates->findAll();

        if (empty($employees)) {
            return false; // Không có nhân viên nào hoạt động phù hợp
        }

        $assignedStaffUserId = null;
        $matchedSpecialists = [];

        // 2. Lọc nhân viên theo chuyên môn (specialties)
        if (!empty($leadTags)) {
            foreach ($employees as $emp) {
                $specs = json_decode($emp['specialties'] ?? '[]', true) ?: [];
                // So khớp giao nhau của 2 mảng nhãn
                $intersect = array_intersect($leadTags, $specs);
                if (!empty($intersect)) {
                    $matchedSpecialists[] = $emp;
                }
            }
        }

        // Nếu có nhân sự khớp chuyên môn, ưu tiên chọn trong nhóm này. Nếu không, chọn toàn bộ ứng viên
        $activeGroup = !empty($matchedSpecialists) ? $matchedSpecialists : $employees;

        // 3. Tính toán tải công việc hiện tại và chọn người ít việc nhất chưa quá tải
        $bestCandidate = null;
        $minWorkload   = 999999;

        foreach ($activeGroup as $emp) {
            $workload = $this->_calculateCurrentWorkload($emp['user_id']);
            $maxLoad  = $emp['max_workload'] ?: 15;

            // Bỏ qua nếu đã vượt quá giới hạn tải tối đa
            if ($workload >= $maxLoad) {
                continue;
            }

            if ($workload < $minWorkload) {
                $minWorkload   = $workload;
                $bestCandidate = $emp;
            }
        }

        // 4. Thực hiện gán lead nếu tìm thấy nhân sự phù hợp
        if ($bestCandidate) {
            $assignedStaffUserId = $bestCandidate['user_id'];
            $now = date('Y-m-d H:i:s');
            $deadline = $this->calculateFirstResponseDeadline($now);

            $updateData = [
                'assigned_to'             => $assignedStaffUserId,
                'assigned_at'             => $now,
                'first_response_deadline' => $deadline,
                'first_responded_at'      => null,
                'is_overdue'              => 0
            ];

            $contactModel->update($contactId, $updateData);

            // Gửi notification báo việc mới cho nhân viên
            $this->notificationModel->insert([
                'user_id'   => $assignedStaffUserId,
                'sender_id' => null, // Hệ thống tự gán
                'type'      => 'chat_assigned',
                'title'     => '📥 Lead tư vấn mới được gán tự động',
                'message'   => "Bạn được gán phụ trách Lead <b>{$contact['display_name']}</b>. Hạn phản hồi đầu tiên: " . date('H:i d/m/Y', strtotime($deadline)),
                'link'      => base_url("chat?channel={$channel}&selected_channel={$channel}&contact_id=" . ($channel === 'zalo' ? $contact['zalo_id'] : $contact['psid'])),
                'is_read'   => 0
            ]);

            return true;
        }

        return false; // Không tìm được ai do tất cả đều quá tải
    }

    /**
     * Cronjob: Kiểm tra và thu hồi các Lead quá hạn phản hồi 2h.
     * 
     * THUẬT TOÁN ĐIỀU PHỐI QUÁ HẠN:
     * 1. Quét cả 2 bảng zalo_followers và messenger_contacts tìm các lead:
     *    - Đã có `assigned_to`
     *    - `first_responded_at` còn đang rỗng (NULL)
     *    - Cờ quá hạn `is_overdue` đang bằng 0
     *    - Hạn chót `first_response_deadline` nhỏ hơn hoặc bằng thời gian hiện tại.
     * 2. Với mỗi trường hợp quá hạn:
     *    - Cập nhật cờ `is_overdue = 1`.
     *    - Bắn cảnh báo đỏ (Red alert) cho Quản lý trực tiếp (`manager_id` của nhân viên cũ) và Admin.
     *    - Thu hồi phân công cũ (`assigned_to = null`).
     *    - Chạy lại thuật toán gán tự động `autoAssignLead`, loại trừ nhân viên cũ ra khỏi danh sách xoay.
     * 
     * @return array Kết quả thống kê số lượng lead quá hạn đã xử lý
     */
    public function processOverdueLeads(): array
    {
        $now = date('Y-m-d H:i:s');
        $processedCount = 0;
        $reassignedCount = 0;
        $details = [];

        // 1. Quét kênh Zalo
        $zaloOverdue = $this->zaloFollowerModel->where('assigned_to IS NOT NULL')
                                              ->where('first_responded_at', null)
                                              ->where('first_response_deadline <=', $now)
                                              ->where('is_overdue', 0)
                                              ->findAll();

        foreach ($zaloOverdue as $lead) {
            $this->_handleOverdueLead('zalo', $lead);
            $processedCount++;
            $reassignedCount++;
            $details[] = "Zalo Lead [ID: {$lead['id']}] - {$lead['display_name']} đã quá hạn phản hồi và tự động gán lại.";
        }

        // 2. Quét kênh Messenger
        $messengerOverdue = $this->messengerContactModel->where('assigned_to IS NOT NULL')
                                                        ->where('first_responded_at', null)
                                                        ->where('first_response_deadline <=', $now)
                                                        ->where('is_overdue', 0)
                                                        ->where('deleted_at', null)
                                                        ->findAll();

        foreach ($messengerOverdue as $lead) {
            $this->_handleOverdueLead('messenger', $lead);
            $processedCount++;
            $reassignedCount++;
            $details[] = "Messenger Lead [ID: {$lead['id']}] - {$lead['display_name']} đã quá hạn phản hồi và tự động gán lại.";
        }

        // 3. Quét kênh Zalo (Ongoing SLA)
        $zaloOngoingOverdue = $this->zaloFollowerModel->where('assigned_to IS NOT NULL')
                                              ->where('ongoing_response_deadline IS NOT NULL')
                                              ->where('ongoing_response_deadline <=', $now)
                                              ->where('ongoing_is_overdue', 0)
                                              ->findAll();

        foreach ($zaloOngoingOverdue as $lead) {
            $this->_handleOngoingOverdueLead('zalo', $lead);
            $processedCount++;
            $details[] = "Zalo Lead [ID: {$lead['id']}] - {$lead['display_name']} vi phạm hạn phản hồi kế tiếp (chỉ cảnh báo).";
        }

        // 4. Quét kênh Messenger (Ongoing SLA)
        $messengerOngoingOverdue = $this->messengerContactModel->where('assigned_to IS NOT NULL')
                                                        ->where('ongoing_response_deadline IS NOT NULL')
                                                        ->where('ongoing_response_deadline <=', $now)
                                                        ->where('ongoing_is_overdue', 0)
                                                        ->where('deleted_at', null)
                                                        ->findAll();

        foreach ($messengerOngoingOverdue as $lead) {
            $this->_handleOngoingOverdueLead('messenger', $lead);
            $processedCount++;
            $details[] = "Messenger Lead [ID: {$lead['id']}] - {$lead['display_name']} vi phạm hạn phản hồi kế tiếp (chỉ cảnh báo).";
        }

        return [
            'status'           => 'success',
            'processed_count'  => $processedCount,
            'reassigned_count' => $reassignedCount,
            'details'          => $details
        ];
    }

    // =========================================================================
    //  HÀM PRIVATE HỖ TRỢ BÊN TRONG (PRIVATE HELPERS)
    // =========================================================================

    /**
     * Xử lý chi tiết một Lead khi bị quá hạn đang trao đổi (Chỉ phát thông báo đỏ, không gán lại).
     */
    private function _handleOngoingOverdueLead(string $channel, array $lead): void
    {
        $contactModel = ($channel === 'zalo') ? $this->zaloFollowerModel : $this->messengerContactModel;
        $oldUserId    = $lead['assigned_to'];

        // 1. Gắn cờ quá hạn ongoing_is_overdue = 1
        $contactModel->update($lead['id'], ['ongoing_is_overdue' => 1]);

        // Lấy thông tin nhân sự bị quá hạn để hiển thị trong báo cáo
        $oldEmployee = $this->employeeModel->where('user_id', $oldUserId)->first();
        $oldEmpName  = $oldEmployee ? $oldEmployee['full_name'] : 'Nhân viên';

        // 2. Tìm người nhận thông báo cảnh báo đỏ (Nhân viên, Quản lý trực tiếp & Admin)
        $notifRecipients = [$oldUserId];
        
        $admins = $this->db->table('users')->select('id')->where('role_id', 1)->get()->getResultArray();
        foreach ($admins as $admin) {
            $notifRecipients[] = $admin['id'];
        }

        if ($oldEmployee && !empty($oldEmployee['manager_id'])) {
            $mgr = $this->employeeModel->find($oldEmployee['manager_id']);
            if ($mgr && !empty($mgr['user_id'])) {
                $notifRecipients[] = $mgr['user_id'];
            }
        }

        $notifRecipients = array_unique($notifRecipients);

        // Gửi cảnh báo đỏ
        foreach ($notifRecipients as $rId) {
            $this->notificationModel->insert([
                'user_id'   => $rId,
                'sender_id' => null,
                'type'      => 'chat_overdue_alert',
                'title'     => '🚨 CẢNH BÁO: Bỏ quên khách hàng đang trao đổi!',
                'message'   => "Nhân viên <b>{$oldEmpName}</b> đã bỏ quên chưa phản hồi tin nhắn mới nhất của Lead <b>{$lead['display_name']}</b> quá hạn quy định. Vui lòng phản hồi ngay!",
                'link'      => base_url("chat?channel={$channel}&selected_channel={$channel}&contact_id=" . ($channel === 'zalo' ? $lead['zalo_id'] : $lead['psid'])),
                'is_read'   => 0
            ]);
        }
    }

    /**
     * Xử lý chi tiết một Lead khi bị quá hạn (Phát thông báo đỏ & Gán lại).
     */
    private function _handleOverdueLead(string $channel, array $lead): void
    {
        $contactModel = ($channel === 'zalo') ? $this->zaloFollowerModel : $this->messengerContactModel;
        $oldUserId    = $lead['assigned_to'];

        // 1. Gắn cờ quá hạn is_overdue = 1
        $contactModel->update($lead['id'], ['is_overdue' => 1]);

        // Lấy thông tin nhân sự bị quá hạn để hiển thị trong báo cáo
        $oldEmployee = $this->employeeModel->where('user_id', $oldUserId)->first();
        $oldEmpName  = $oldEmployee ? $oldEmployee['full_name'] : 'Nhân viên';

        // 2. Tìm người nhận thông báo cảnh báo đỏ (Quản lý trực tiếp & Admin)
        $notifRecipients = [];
        
        // Thêm Admin (Role ID 1) vào danh sách nhận
        $admins = $this->db->table('users')->select('id')->where('role_id', 1)->get()->getResultArray();
        foreach ($admins as $admin) {
            $notifRecipients[] = $admin['id'];
        }

        // Thêm quản lý trực tiếp của nhân viên đó nếu có
        if ($oldEmployee && !empty($oldEmployee['manager_id'])) {
            $mgr = $this->employeeModel->find($oldEmployee['manager_id']);
            if ($mgr && !empty($mgr['user_id'])) {
                $notifRecipients[] = $mgr['user_id'];
            }
        }

        $notifRecipients = array_unique($notifRecipients);

        // Gửi cảnh báo đỏ cho quản lý & Admin
        foreach ($notifRecipients as $rId) {
            $this->notificationModel->insert([
                'user_id'   => $rId,
                'sender_id' => null,
                'type'      => 'chat_overdue_alert',
                'title'     => '🚨 CẢNH BÁO: Quá hạn phản hồi Lead 2h!',
                'message'   => "Lead <b>{$lead['display_name']}</b> được gán cho nhân viên <b>{$oldEmpName}</b> đã quá hạn 2 giờ mà chưa có bất kỳ phản hồi nào. Hệ thống đã thu hồi phân công để Quản lý/Admin gán lại thủ công.",
                'link'      => base_url("chat?channel={$channel}&selected_channel={$channel}&contact_id=" . ($channel === 'zalo' ? $lead['zalo_id'] : $lead['psid'])),
                'is_read'   => 0
            ]);
        }

        // 3. Tiến hành thu hồi phân công (Đã tắt tính năng tự gán lại, Trưởng phòng/Admin gán lại thủ công)
        $contactModel->update($lead['id'], [
            'assigned_to'             => null,
            'assigned_at'             => null,
            'first_response_deadline' => null
        ]);
    }

    /**
     * Tính toán số lượng lead/vụ việc đang xử lý của một nhân sự để xác định tải công việc.
     */
    private function _calculateCurrentWorkload(int $userId): int
    {
        // 1. Số hội thoại zalo gán cho nhân sự chưa có tin nhắn phản hồi cuối cùng của nhân sự
        $zaloLoad = $this->zaloFollowerModel->where('assigned_to', $userId)
                                            ->where('first_responded_at', null)
                                            ->countAllResults();

        // 2. Số hội thoại messenger gán cho nhân sự chưa phản hồi
        $messengerLoad = $this->messengerContactModel->where('assigned_to', $userId)
                                                    ->where('first_responded_at', null)
                                                    ->where('deleted_at', null)
                                                    ->countAllResults();

        // 3. Số vụ việc đang xử lý thực tế của nhân sự (liên kết qua employees.user_id)
        $employee = $this->employeeModel->where('user_id', $userId)->first();
        $caseLoad = 0;
        if ($employee) {
            $caseLoad = $this->db->table('cases')
                                 ->where('assigned_staff_id', $employee['id'])
                                 ->whereIn('status', ['cho_tiep_nhan', 'dang_xu_ly'])
                                 ->where('deleted_at', null)
                                 ->countAllResults();
        }

        // Tải tổng hợp = Số chat chưa phản hồi + Số vụ việc thực tế đang gánh vác
        return $zaloLoad + $messengerLoad + ($caseLoad * 2); // Trọng số vụ việc pháp lý nhân đôi vì nặng hơn chat
    }

    /**
     * Helper tìm ngày làm việc hành chính tiếp theo (Bỏ qua T7, CN).
     */
    private function _getNextWorkingDay(string $dateStr): string
    {
        $current = strtotime($dateStr);
        do {
            $current += 86400; // Cộng thêm 1 ngày
            $dayOfWeek = date('N', $current);
        } while ($dayOfWeek >= 6); // Lặp lại nếu rơi vào thứ 7 (6) hoặc chủ nhật (7)

        return date('Y-m-d', $current);
    }
}
