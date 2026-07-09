<?php

namespace App\Controllers;

/**
 * ZaloController
 * 
 * Bộ điều khiển tích hợp Zalo OA vào ERP.
 * 1. Quản lý tập trung (Centralized Management): Nhận tin nhắn webhook, lưu lịch sử.
 * 2. Tự động hóa quy trình (Automation Workflow): Tự cấp MID, tự động bắn tin nhắn.
 * 3. Tiếp thị lại thông minh (Remarketing & SEO): Tagging, gửi ZNS theo chiến dịch.
 * 4. Quản lý hiệu suất 15 nhân sự (Team Performance): Khảo sát 5 sao, báo cáo trả lời.
 * 5. Cá nhân hóa trải nghiệm (Personalization): Pop-up thông tin khách cũ.
 */
class ZaloController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $modulePermissions = [
        'group' => 'Tư Vấn Khách Hàng',
        'permissions' => [
            'zalo.view'        => ['desc' => 'Xem danh sách hội thoại Zalo', 'roles' => [1, 3, 4, 5, 6, 7]],
            'zalo.chat'        => ['desc' => 'Chat trực tiếp với khách hàng', 'roles' => [1, 3, 4, 5, 6, 7]],
            'zalo.campaign'    => ['desc' => 'Quản lý chiến dịch ZNS', 'roles' => [1, 3]],
            'zalo.config'      => ['desc' => 'Cấu hình API Zalo OA (Chỉ Admin)', 'roles' => [1]],
            'zalo.quick_reply' => ['desc' => 'Quản lý câu trả lời nhanh', 'roles' => [1, 3, 4, 5, 6]],
            'zalo.performance' => ['desc' => 'Xem báo cáo hiệu suất', 'roles' => [1, 3]],
            'zalo.assign'      => ['desc' => 'Quyền gán nhân sự chăm sóc', 'roles' => [1, 3]],
            'zalo.send_individual' => ['desc' => 'Gửi ZNS đơn lẻ/hàng loạt cho KH', 'roles' => [1, 3]]
        ]
    ];

    /**
     * Khai báo module có khả năng gắn nhãn (Rule #10).
     */
    public static $taggable = [
        'type'  => 'zalo_followers',
        'label' => 'Khách hàng Zalo'
    ];

    protected $zaloService;

    public function __construct()
    {
        $this->zaloService = new \App\Services\ZaloService();
    }

    public function index()
    {
        $config = new \Config\Zalo();
        
        // Nếu chưa có Access Token: Admin thì chuyển đến trang cấu hình, nhân viên thường thì báo lỗi
        if (empty($config->accessToken)) {
            if (has_permission('sys.admin') || has_permission('zalo.config')) {
                return redirect()->to(base_url('zalo/config'));
            }
            return redirect()->to(base_url('dashboard'))->with('error', 'Zalo OA chưa được kết nối. Vui lòng liên hệ Quản trị viên.');
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $messageModel = new \App\Models\ZaloMessageModel();
        $tagModel = new \App\Models\TagModel();
        
        $currentUserId = session()->get('user_id');
        $role = session()->get('role_name');
        $isAdmin = in_array($role, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]);

        // Lọc theo search, staff, tag
        $search = $this->request->getGet('search');
        $filterStaff = $this->request->getGet('filter_staff');
        $filterTag = $this->request->getGet('filter_tag');

        // Lấy danh sách followers
        $query = $followerModel->select('zalo_followers.*')
                              ->orderBy('zalo_followers.updated_at', 'DESC');
        
        // 1. Phân quyền cơ bản: Nhân viên thường thấy khách của mình VÀ khách chưa được gán
        if (!$isAdmin) {
            $query->groupStart()
                  ->where('assigned_to', $currentUserId)
                  ->orWhere('assigned_to', null)
                  ->orWhere('assigned_to', 0)
                  ->groupEnd();
        } elseif ($filterStaff) {
            $query->where('assigned_to', $filterStaff);
        }
        
        // 2. Tìm kiếm
        if ($search) {
            $query->groupStart()
                  ->like('display_name', $search)
                  ->orLike('mid_code', $search)
                  ->orLike('phone_number', $search)
                  ->groupEnd();
        }

        // 3. Lọc theo Tag
        if ($filterTag) {
            $query->like('zalo_followers.tags', $filterTag);
        }

        $followers = $query->findAll(50);
        
        // Xây dựng bảng tra cứu nhân sự (staff lookup) để tránh query N+1
        $staffLookup = [];
        if ($isAdmin) {
            $userModel = new \App\Models\UserModel();
            $allStaffRaw = $userModel->select('users.id as user_id, employees.full_name, users.email')
                                     ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                                     ->where('users.active_status', 1)
                                     ->where('users.deleted_at', null)
                                     ->findAll();
            foreach ($allStaffRaw as $s) {
                $staffLookup[$s['user_id']] = $s['full_name'] ?: $s['email'];
            }
        }
        
        foreach($followers as &$f) {
            $lastMsg = $messageModel->where('follower_id', $f['id'])->orderBy('created_at', 'DESC')->first();
            $f['last_message'] = $lastMsg ? $lastMsg['message_text'] : 'Chưa có tin nhắn';
            $f['last_time'] = $lastMsg ? date('H:i', strtotime($lastMsg['created_at'])) : '';
            $f['unread_count'] = $messageModel->where('follower_id', $f['id'])
                                              ->where('sender_type', 'user')
                                              ->where('is_read', 0)
                                              ->countAllResults();
            // Tên nhân sự phụ trách (dùng lookup đã tải sẵn hoặc query riêng cho nhân viên thường)
            if (!empty($f['assigned_to'])) {
                if (isset($staffLookup[$f['assigned_to']])) {
                    $f['assigned_staff_name'] = $staffLookup[$f['assigned_to']];
                } elseif (!$isAdmin) {
                    // Nhân viên thường chỉ xem khách của mình — dùng tên từ session
                    $f['assigned_staff_name'] = session()->get('full_name') ?: 'Tôi';
                } else {
                    $f['assigned_staff_name'] = '';
                }
            } else {
                $f['assigned_staff_name'] = '';
            }
        }
        
        // Lấy ID người dùng đang chọn
        $selectedZaloId = $this->request->getGet('mid') ?? ($followers[0]['zalo_id'] ?? null);
        
        $messages = [];
        $selectedFollower = null;
        
        if ($selectedZaloId) {
            $selectedFollower = $followerModel->where('zalo_id', $selectedZaloId)->first();
            
            // Kiểm tra quyền truy cập của nhân sự với khách này
            if (!$isAdmin && $selectedFollower && !empty($selectedFollower['assigned_to']) && $selectedFollower['assigned_to'] != $currentUserId) {
                return redirect()->to(base_url('zalo'))->with('error', 'Bạn không có quyền quản lý khách hàng này.');
            }

            if ($selectedFollower) {
                // Đánh dấu đã đọc khi mở hội thoại
                $messageModel->where('follower_id', $selectedFollower['id'])
                             ->where('sender_type', 'user')
                             ->where('is_read', 0)
                             ->set(['is_read' => 1])
                             ->update();

                // Không tự động gán: Chỉ admin mới có thể gán nhân sự cho hội thoại Zalo

                // Lấy 30 tin nhắn gần nhất theo thời gian thực tế
                $messages = $messageModel->where('follower_id', $selectedFollower['id'])
                                         ->orderBy('created_at', 'DESC')
                                         ->orderBy('id', 'DESC')
                                         ->findAll(30);
                $messages = array_reverse($messages); // Đảo lại để hiển thị đúng thứ tự thời gian (cũ trên mới dưới)

                // Lấy thông tin nhân sự đã gán
                if (!empty($selectedFollower['assigned_to'])) {
                    $employeeModel = new \App\Models\EmployeeModel();
                    $assignedStaff = $employeeModel->where('user_id', $selectedFollower['assigned_to'])->first();
                    $selectedFollower['assigned_staff_name'] = $assignedStaff ? $assignedStaff['full_name'] : 'Nhân sự';
                }
            }
        }

        // Lấy danh sách nhân sự (cho admin filter)
        $staffs = [];
        if ($isAdmin) {
            $userModel = new \App\Models\UserModel();
            $staffs = $userModel->select('users.id as user_id, users.email, employees.full_name')
                                ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                                ->where('users.active_status', 1)
                                ->where('users.deleted_at', null)
                                ->findAll();
        }

        // Lấy danh sách tags hệ thống
        $allTags = $tagModel->orderBy('name', 'ASC')->findAll();

        // Lấy danh sách câu trả lời nhanh qua Service (Rule #2)
        $consultationService = new \App\Services\ConsultationService();
        $quickReplies = $consultationService->getQuickReplies();

        $data = [
            'title' => 'Quản lý Zalo OA | L.A.N ERP',
            'followers' => $followers,
            'selectedFollower' => $selectedFollower,
            'messages' => $messages,
            'selectedZaloId' => $selectedZaloId,
            'staffs' => $staffs,
            'allTags' => $allTags,
            'quickReplies' => $quickReplies,
            'isAdmin' => $isAdmin,
            'filter' => [
                'search' => $search,
                'staff' => $filterStaff,
                'tag' => $filterTag
            ]
        ];
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'sidebar_html' => view('dashboard/zalo/_sidebar', $data),
                'chat_area_html' => view('dashboard/zalo/_chat_area', $data),
                'selectedZaloId' => $selectedZaloId,
                'lastMsgId' => !empty($messages) ? end($messages)['id'] : 0,
                'title' => 'Quản lý Zalo OA | ' . ($selectedFollower ? $selectedFollower['display_name'] : 'L.A.N ERP')
            ]);
        }

        return view('dashboard/zalo/index', $data);
    }

    /**
     * Giao diện cấu hình và kết nối Zalo OA
     */
    public function config()
    {
        // Chỉ Admin hệ thống mới được truy cập trang cấu hình Zalo
        if (!has_permission('sys.admin') && !has_permission('zalo.config')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Chỉ Quản trị viên mới có quyền cấu hình Zalo.');
        }

        $config = new \Config\Zalo();
        
        $data = [
            'title' => 'Cấu hình Zalo OA | L.A.N ERP',
            'config' => $config,
            'authUrl' => (!empty($config->appId) && !empty($config->appSecret)) ? $this->zaloService->getAuthUrl() : null
        ];

        return view('dashboard/zalo/config', $data);
    }

    /**
     * Chuyển hướng đến Zalo để xác thực
     */
    public function auth()
    {
        return redirect()->to($this->zaloService->getAuthUrl());
    }

    /**
     * Xử lý callback từ Zalo
     */
    public function callback()
    {
        $code = $this->request->getGet('code');
        $state = $this->request->getGet('state');
        
        if (empty($code)) {
            return redirect()->to(base_url('zalo/config'))->with('error', 'Không nhận được mã xác thực từ Zalo.');
        }

        // Kiểm tra state để tránh CSRF (tùy chọn nhưng nên làm)
        
        $result = $this->zaloService->exchangeCodeForToken($code);
        
        if (isset($result['access_token'])) {
            // Lưu token vào Database thông qua Service
            $this->zaloService->saveTokensToDb($result['access_token'], $result['refresh_token'], $result['expires_in'] ?? null);
            
            return view('dashboard/zalo/callback_success', [
                'title' => 'Kết nối Zalo thành công',
                'tokens' => $result
            ]);
        } else {
            return redirect()->to(base_url('zalo/config'))->with('error', 'Lỗi kết nối: ' . ($result['error_description'] ?? $result['message'] ?? 'Không xác định'));
        }
    }
    
    /**
     * Endpoint Webhook nhận sự kiện từ Zalo OA (Luôn mở công khai, không cần auth)
     */
    public function webhook()
    {
        // Nhận dữ liệu từ Zalo (có fallback cho raw body)
        $requestData = $this->request->getJSON(true);
        if (!$requestData) {
            $requestData = json_decode($this->request->getBody(), true);
        }
        
        // Log lại toàn bộ dữ liệu nhận được để debug trên hosting
        log_message('error', 'ZALO WEBHOOK RECEIVED: ' . json_encode($requestData));
        
        if ($requestData) {
            $this->zaloService->handleWebhook($requestData);
        }
        
        return $this->response->setStatusCode(200)->setJSON(['status' => 'success']);
    }

    /**
     * Mô phỏng Webhook (Dùng cho môi trường Localhost khi Zalo không thể gọi tới)
     */
    public function simulateWebhook()
    {
        $mockData = [
            'event_name' => 'user_send_text',
            'app_id' => '123456789',
            'sender' => [
                'id' => 'zalo_user_' . rand(1000, 9999)
            ],
            'message' => [
                'text' => 'Chào luật sư, tôi muốn nhờ tư vấn hợp đồng thuê nhà.',
                'msg_id' => 'msg_' . time()
            ],
            'timestamp' => time()
        ];
        
        // Ghi đè thông tin hiển thị để dễ nhận diện
        $followerModel = new \App\Models\ZaloFollowerModel();
        
        // Gọi thẳng handleWebhook để xử lý giả lập
        $this->zaloService->handleWebhook($mockData);
        
        // Cập nhật lại tên giả lập vì không có AccessToken thật để lấy API
        $follower = $followerModel->where('zalo_id', $mockData['sender']['id'])->first();
        if ($follower) {
            $followerModel->update($follower['id'], [
                'display_name' => 'Khách Test ' . rand(10, 99)
            ]);
        }
        
        return redirect()->to(base_url('zalo'))->with('success', 'Đã mô phỏng nhận tin nhắn thành công!');
    }
    
    /**
     * Giao diện chiến dịch ZNS
     */
    public function campaigns()
    {
        if (!has_permission('zalo.campaign')) {
            return redirect()->to(base_url('zalo'))->with('error', 'Bạn không có quyền truy cập Chiến dịch.');
        }

        $campaignModel = new \App\Models\ZnsCampaignModel();
        $logModel = new \App\Models\ZnsLogModel();
        
        // Lấy danh sách chiến dịch kèm phân trang
        $campaigns = $campaignModel->getCampaignsWithTemplate(15);
        $pager = $campaignModel->pager;
        
        // Lấy thống kê tổng quan chiến dịch
        $stats = $campaignModel->getOverallStats();

        // Lấy danh sách log gửi đơn lẻ kèm phân trang
        $individualLogs = $logModel->getIndividualLogs(15);
        $individualPager = $logModel->pager;
        
        // Lấy thống kê gửi đơn lẻ
        $individualStats = $logModel->getIndividualStats();

        $data = [
            'title' => 'Chiến dịch Remarketing Zalo ZNS | L.A.N ERP',
            'campaigns' => $campaigns,
            'pager' => $pager,
            'stats' => $stats,
            'individualLogs' => $individualLogs,
            'individualPager' => $individualPager,
            'individualStats' => $individualStats
        ];

        return view('dashboard/zalo/campaigns', $data);
    }
    
    /**
     * Báo cáo hiệu suất (Thời gian phản hồi, Đánh giá sao)
     */
    public function performance()
    {
        if (!has_permission('zalo.performance')) {
            return redirect()->to(base_url('zalo'))->with('error', 'Bạn không có quyền xem Báo cáo.');
        }
        $data = [
            'title' => 'Báo cáo hiệu suất Zalo | L.A.N ERP'
        ];
        return view('dashboard/zalo/performance', $data);
    }

    /**
     * Tiện ích xem log Webhook để debug trên Hosting
     */
    public function logs()
    {
        $logPath = WRITEPATH . 'logs/';
        $logFile = $logPath . 'log-' . date('Y-m-d') . '.log';
        
        $zaloLogs = [];
        if (file_exists($logFile)) {
            $lines = file($logFile);
            foreach ($lines as $line) {
                if (strpos($line, 'ZALO WEBHOOK RECEIVED') !== false || strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                    $zaloLogs[] = $line;
                }
            }
        }
        
        return $this->response->setJSON([
            'status' => 'success',
            'date' => date('Y-m-d'),
            'log_file_exists' => file_exists($logFile),
            'logs' => array_reverse(array_slice($zaloLogs, -50))
        ]);
    }

    /**
     * AJAX: Lấy dữ liệu chat và danh sách hội thoại mới nhất
     */
    public function ajaxChat()
    {
        $mid = $this->request->getGet('mid');
        $lastMsgId = $this->request->getGet('last_msg_id');
        
        $followerModel = new \App\Models\ZaloFollowerModel();
        $messageModel = new \App\Models\ZaloMessageModel();
        
        $currentUserId = session()->get('user_id');
        $isAdmin = session()->get('is_admin') === true || session()->get('role_id') == 1;

        $data = [
            'new_messages' => [],
            'followers' => []
        ];

        // 1. Lấy tin nhắn mới cho hội thoại hiện tại
        if ($mid) {
            $follower = $followerModel->where('zalo_id', $mid)->first();
            if ($follower) {
                // Đánh dấu đã đọc cho tin nhắn mới (nếu đang ở trang chat này)
                $messageModel->where('follower_id', $follower['id'])
                             ->where('sender_type', 'user')
                             ->where('is_read', 0)
                             ->set(['is_read' => 1])
                             ->update();

                $msgQuery = $messageModel->where('follower_id', $follower['id']);
                if ($lastMsgId) {
                    $msgQuery->where('id >', $lastMsgId);
                }
                $data['new_messages'] = $msgQuery->orderBy('created_at', 'ASC')->findAll();
            }
        }

        // 2. Lấy danh sách hội thoại để cập nhật preview
        $search = $this->request->getGet('search');
        $filterStaff = $this->request->getGet('filter_staff');
        $filterTag = $this->request->getGet('filter_tag');

        $fQuery = $followerModel->orderBy('updated_at', 'DESC');
        
        if (!$isAdmin) {
            $fQuery->groupStart()
                   ->where('assigned_to', $currentUserId)
                   ->orWhere('assigned_to', null)
                   ->orWhere('assigned_to', 0)
                   ->groupEnd();
        } elseif ($filterStaff) {
            $fQuery->where('assigned_to', $filterStaff);
        }

        if ($search) {
            $fQuery->groupStart()
                   ->like('display_name', $search)
                   ->orLike('mid_code', $search)
                   ->orLike('phone_number', $search)
                   ->groupEnd();
        }

        if ($filterTag) {
            $fQuery->like('tags', $filterTag);
        }

        $followers = $fQuery->findAll(50);
        foreach($followers as &$f) {
            $lastMsg = $messageModel->where('follower_id', $f['id'])->orderBy('created_at', 'DESC')->first();
            $f['last_message'] = $lastMsg ? $lastMsg['message_text'] : 'Chưa có tin nhắn';
            $f['last_time'] = $lastMsg ? date('H:i', strtotime($lastMsg['created_at'])) : '';
            $f['unread_count'] = $messageModel->where('follower_id', $f['id'])
                                              ->where('sender_type', 'user')
                                              ->where('is_read', 0)
                                              ->countAllResults();
            
            // Rút gọn dữ liệu gửi về
            $data['followers'][] = [
                'zalo_id' => $f['zalo_id'],
                'display_name' => $f['display_name'],
                'avatar_url' => $f['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($f['display_name']).'&background=random',
                'last_message' => $f['last_message'],
                'last_time' => $f['last_time'],
                'unread_count' => $f['unread_count'],
                'tags' => json_decode($f['tags'], true) ?: [],
                'active' => ($mid == $f['zalo_id'])
            ];
        }

        return $this->response->setJSON($data);
    }

    /**
     * AJAX: Gán nhân sự chăm sóc khách hàng
     */
    public function assignStaff()
    {
        if (session()->get('is_admin') !== true && session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Chỉ Admin mới có quyền gán nhân sự.']);
        }

        $followerId = $this->request->getPost('follower_id');
        $staffId = $this->request->getPost('staff_id');

        $followerModel = new \App\Models\ZaloFollowerModel();
        $updateData = ['assigned_to' => $staffId ?: null];
        
        if ($followerModel->update($followerId, $updateData)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã cập nhật nhân sự chăm sóc.']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể cập nhật.']);
    }

    /**
     * AJAX: Cập nhật Tag cho hội thoại
     */
    public function updateTags()
    {
        $followerId = $this->request->getPost('follower_id');
        $tags = $this->request->getPost('tags'); // Mảng tags

        if (!$followerId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $tagJson = json_encode($tags, JSON_UNESCAPED_UNICODE);
        
        if ($followerModel->update($followerId, ['tags' => $tagJson])) {
            return $this->response->setJSON(['status' => 'success', 'tags' => $tags]);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể cập nhật nhãn.']);
    }

    /**
     * AJAX: Tạo nhãn mới nhanh từ màn hình chat (không cần vào trang quản lý Tags)
     */
    public function createTag()
    {
        $name = trim($this->request->getPost('name'));
        
        if (empty($name) || strlen($name) < 2) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tên nhãn phải có ít nhất 2 ký tự.']);
        }
        
        $tagModel = new \App\Models\TagModel();
        
        // Kiểm tra trùng tên
        $existing = $tagModel->where('name', $name)->first();
        if ($existing) {
            return $this->response->setJSON(['status' => 'success', 'tag' => $existing, 'message' => 'Nhãn đã tồn tại.']);
        }
        
        $tagId = $tagModel->insert([
            'name'  => $name,
            'color' => '#0ea5e9',
            'type'  => 'global',
        ]);
        
        if ($tagId) {
            $tag = $tagModel->find($tagId);
            return $this->response->setJSON(['status' => 'success', 'tag' => $tag]);
        }
        
        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể tạo nhãn: ' . implode(', ', $tagModel->errors())]);
    }

    /**
     * AJAX: Gửi tin nhắn tới khách hàng
     */
    public function sendMessage()
    {
        $mid = $this->request->getPost('mid');
        $message = $this->request->getPost('message');

        if (empty($mid) || empty($message)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $follower = $followerModel->where('zalo_id', $mid)->first();
        
        if (!$follower) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng.']);
        }

        // Gửi qua Zalo API
        $result = $this->zaloService->sendTextMessage($mid, $message);

        if (isset($result['error']) && $result['error'] === 0) {
            // Lấy message_id từ phản hồi Zalo để lưu làm khóa chống trùng lặp.
            // Khi Zalo gọi webhook oa_send_text về, hệ thống sẽ tìm thấy zalo_msg_id này
            // trong DB và BỎ QUA, tránh lưu 2 lần → hiển thị trùng.
            $zaloMsgId = $result['data']['message_id'] ?? null;

            $messageModel = new \App\Models\ZaloMessageModel();
            $messageModel->insert([
                'follower_id'  => $follower['id'],
                'sender_type'  => 'oa',
                'message_text' => $message,
                'zalo_msg_id'  => $zaloMsgId,      // Khóa chống trùng với webhook oa_send_text
                'created_at'   => date('Y-m-d H:i:s')
            ]);

            // Cập nhật thời gian tương tác để hội thoại nhảy lên đầu danh sách
            $followerModel->update($follower['id'], ['updated_at' => date('Y-m-d H:i:s')]);

            return $this->response->setJSON(['status' => 'success']);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Lỗi từ Zalo: ' . ($result['message'] ?? 'Không xác định')
        ]);
    }

    /**
     * Tải tệp tin đính kèm từ Zalo
     */
    public function downloadAttachment()
    {
        if (!has_permission('zalo.view')) {
            return redirect()->to(base_url('zalo'))->with('error', 'Bạn không có quyền xem tệp tin.');
        }

        $msgId = $this->request->getGet('msg_id');
        $token = $this->request->getGet('token');
        $fileName = $this->request->getGet('name') ?: 'attachment';
        $fileSize = (int)$this->request->getGet('size');

        if (!$token || !$msgId) {
            return "Dữ liệu không hợp lệ.";
        }

        // 1. Kiểm tra giới hạn dung lượng (Ví dụ: 20MB)
        if ($fileSize > 20 * 1024 * 1024) {
            return "Tệp tin quá lớn (Giới hạn 20MB). Vui lòng tải trực tiếp trên ứng dụng Zalo.";
        }

        // 2. Kiểm tra tính an toàn (Đuôi file)
        if (!$this->zaloService->isFileSafe($fileName)) {
            return "Tệp tin có đuôi mở rộng không an toàn. Hệ thống đã chặn tải về để bảo vệ máy tính của bạn.";
        }

        // 3. Kiểm tra quyền sở hữu tin nhắn (Tránh việc ai đó lấy token và tải file của khách khác)
        $messageModel = new \App\Models\ZaloMessageModel();
        $message = $messageModel->find($msgId);
        if (!$message) {
            return "Không tìm thấy tin nhắn.";
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $follower = $followerModel->find($message['follower_id']);
        
        $currentUserId = session()->get('user_id');
        $isAdmin = in_array(session()->get('role_name'), [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]);
        
        if (!$isAdmin && $follower['assigned_to'] != $currentUserId) {
            return "Bạn không có quyền truy cập tệp tin của khách hàng này.";
        }

        // 4. Lấy nội dung từ Zalo
        $fileData = $this->zaloService->getFileContent($token);
        if (!$fileData) {
            return "Không thể tải tệp tin từ máy chủ Zalo (Có thể đã hết hạn).";
        }

        // 5. Trả về file cho trình duyệt
        return $this->response
            ->setHeader('Content-Type', $fileData['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setBody($fileData['content']);
    }
    /**
     * AJAX: Tải lên và gửi hình ảnh/tệp tin tới khách hàng
     */
    public function uploadMedia()
    {
        $mid = $this->request->getPost('mid');
        $file = $this->request->getFile('media');

        if (empty($mid) || !$file || !$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $follower = $followerModel->where('zalo_id', $mid)->first();
        if (!$follower) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng.']);
        }

        // Kiểm tra loại file
        $type = 'file';
        $mime = $file->getMimeType();
        if (strpos($mime, 'image/') === 0) {
            $type = 'image';
        }

        // Kiểm tra an toàn
        if (!$this->zaloService->isFileSafe($file->getName())) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Loại tệp tin không được phép.']);
        }

        // Lưu tạm file
        $tempPath = WRITEPATH . 'uploads/zalo_temp/';
        if (!is_dir($tempPath)) mkdir($tempPath, 0777, true);
        $file->move($tempPath);
        $fullPath = $tempPath . $file->getName();

        $result = null;
        $uploadStep = null; // Theo dõi bước thất bại để debug
        if ($type === 'image') {
            $attachmentId = $this->zaloService->uploadImage($fullPath);
            if ($attachmentId) {
                $result = $this->zaloService->sendImageMessage($mid, $attachmentId);
            } else {
                $uploadStep = 'upload_image';
            }
        } else {
            $fileToken = $this->zaloService->uploadFile($fullPath);
            if ($fileToken) {
                $result = $this->zaloService->sendFileMessage($mid, $fileToken);
            } else {
                $uploadStep = 'upload_file';
            }
        }

        // Xóa file tạm
        @unlink($fullPath);

        // Nếu bước upload thất bại (Zalo từ chối file)
        if ($uploadStep) {
            $detailedError = $this->zaloService->lastError ?: 'Không xác định';
            log_message('error', "ZaloController::uploadMedia - {$uploadStep} failed for mid={$mid}, file=" . $file->getName() . ". Error: " . $detailedError);
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => "Zalo từ chối tải lên tệp ({$detailedError}). Vui lòng kiểm tra định dạng/kích thước (Ảnh < 1MB, Tệp < 5MB) hoặc xem log server."
            ]);
        }

        if ($result && isset($result['error']) && $result['error'] === 0) {
            // Lấy message_id để lưu zalo_msg_id, ngăn webhook oa_send_image/file tạo bản sao
            $zaloMsgId = $result['data']['message_id'] ?? null;

            $messageModel = new \App\Models\ZaloMessageModel();
            
            $attachmentData = [];
            if ($type === 'image') {
                $attachmentData[] = [
                    'type'    => 'image',
                    'payload' => [
                        'url' => '',
                        'local_file' => $newName // Lưu tên file cục bộ để có thể hiển thị ngay
                    ]
                ];
            } else {
                $attachmentData[] = [
                    'type'    => 'file',
                    'payload' => ['name' => $file->getName(), 'size' => $file->getSize()]
                ];
            }

            $messageModel->insert([
                'follower_id'  => $follower['id'],
                'sender_type'  => 'oa',
                'message_text' => ($type === 'image' ? '[Hình ảnh]' : '[Tệp tin]'),
                'attachments'  => json_encode($attachmentData),
                'zalo_msg_id'  => $zaloMsgId,   // Chống trùng với webhook oa_send_image/file
                'created_at'   => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON(['status' => 'success']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Lỗi tải lên Zalo: ' . ($result['message'] ?? 'Không xác định')]);
    }

    /**
     * AJAX: Ghi nhận lịch sử cuộc gọi
     */
    public function logCall()
    {
        $mid = $this->request->getPost('mid');
        $notes = $this->request->getPost('notes');

        if (empty($mid)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $follower = $followerModel->where('zalo_id', $mid)->first();
        if (!$follower) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng.']);
        }

        // Ghi vào customer_interactions (Rule #2)
        $interactionModel = new \App\Models\CustomerInteractionModel();
        
        $interactionData = [
            'customer_id'      => $follower['customer_id'] ?: 0, // Nếu chưa có CRM thì tạm để 0 hoặc lưu vào bảng riêng
            'user_id'          => session()->get('user_id'),
            'channel'          => 'call',
            'interaction_date' => date('Y-m-d H:i:s'),
            'summary'          => 'Gọi điện tư vấn (Zalo)',
            'detailed_content' => $notes ?: 'Nhân viên đã gọi điện cho khách hàng qua Zalo.',
        ];

        if ($interactionModel->insert($interactionData)) {
            // Lưu thêm 1 tin nhắn hệ thống vào chat để biết đã gọi
            $messageModel = new \App\Models\ZaloMessageModel();
            $messageModel->insert([
                'follower_id' => $follower['id'],
                'sender_type' => 'oa',
                'message_text' => '📞 Đã thực hiện cuộc gọi: ' . ($notes ?: 'Không có ghi chú'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã ghi nhận cuộc gọi.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể lưu lịch sử cuộc gọi.']);
    }
    /**
     * AJAX: Đồng bộ lại thông tin profile khách hàng từ Zalo
     */
    public function syncProfile()
    {
        $mid = $this->request->getPost('mid');
        if (empty($mid)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $follower = $followerModel->where('zalo_id', $mid)->first();
        if (!$follower) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng trong hệ thống.']);
        }

        $result = $this->zaloService->getProfile($mid);
        if (isset($result['error']) && $result['error'] === 0) {
            $profile = $result['data'];
            $updateData = [
                'display_name' => $profile['display_name'],
                'avatar_url' => $profile['avatars']['240'] ?? ($profile['avatar'] ?? $follower['avatar_url'])
            ];
            
            if (!empty($profile['shared_info']['phone'])) {
                $updateData['phone_number'] = $profile['shared_info']['phone'];
            }

            if ($followerModel->update($follower['id'], $updateData)) {
                return $this->response->setJSON([
                    'status' => 'success', 
                    'message' => 'Đã cập nhật thông tin khách hàng.',
                    'data' => [
                        'display_name' => $updateData['display_name'],
                        'avatar_url' => $updateData['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($updateData['display_name']).'&background=random'
                    ]
                ]);
            }
        }

        $errorMsg = $result['message'] ?? 'Không thể lấy thông tin từ Zalo. Có thể khách chưa quan tâm OA hoặc chưa cấp quyền xem profile.';
        return $this->response->setJSON(['status' => 'error', 'message' => $errorMsg]);
    }

    /**
     * AJAX: Tải thêm tin nhắn cũ (Phân trang)
     */
    public function loadMoreMessages()
    {
        $mid = $this->request->getGet('mid');
        $firstMsgId = $this->request->getGet('first_msg_id');
        
        if (!$mid || !$firstMsgId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu dữ liệu']);
        }
        
        $followerModel = new \App\Models\ZaloFollowerModel();
        $messageModel = new \App\Models\ZaloMessageModel();
        
        $follower = $followerModel->where('zalo_id', $mid)->first();
        if (!$follower) return $this->response->setJSON(['status' => 'error', 'message' => 'Không thấy khách']);
        
        // Lấy thông tin tin nhắn mốc để tìm các tin nhắn cũ hơn thực tế
        $baseMsg = $messageModel->find($firstMsgId);
        if (!$baseMsg) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy tin nhắn mốc']);
        }
        
        $messages = $messageModel->where('follower_id', $follower['id'])
                                 ->groupStart()
                                     ->where('created_at <', $baseMsg['created_at'])
                                     ->orGroupStart()
                                         ->where('created_at =', $baseMsg['created_at'])
                                         ->where('id <', $firstMsgId)
                                     ->groupEnd()
                                 ->groupEnd()
                                 ->orderBy('created_at', 'DESC')
                                 ->orderBy('id', 'DESC')
                                 ->findAll(10);
        
        return $this->response->setJSON([
            'status' => 'success',
            'messages' => array_reverse($messages)
        ]);
    }

    /**
     * AJAX: Đồng bộ lịch sử tin nhắn của một khách hàng từ Zalo (V2.0 Endpoint)
     */
    public function sync()
    {
        $mid = $this->request->getPost('mid');
        if (empty($mid)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        // Ủy quyền toàn bộ logic kinh doanh cho ZaloService (Rule #2 & Quy tắc DRY)
        $result = $this->zaloService->syncConversation($mid, 7);

        if ($result['status'] === 'success') {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $result['message']
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => $result['message']
        ]);
    }

    /**
     * Xem hình ảnh tạm thời (vừa mới upload từ ERP)
     */
    public function viewTemp($filename)
    {
        $path = WRITEPATH . 'uploads/zalo_temp/' . $filename;
        
        if (!file_exists($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mime = mime_content_type($path);
        header("Content-Type: $mime");
        readfile($path);
        exit;
    }

    /**
     * Giao diện quản lý ZNS Templates
     */
    public function znsTemplates()
    {
        if (!has_permission('zalo.campaign')) {
            return redirect()->to(base_url('zalo'))->with('error', 'Bạn không có quyền truy cập Mẫu tin ZNS.');
        }

        $templateModel = new \App\Models\ZnsTemplateModel();
        $templates = $templateModel->where('deleted_at IS NULL')->orderBy('created_at', 'DESC')->findAll();

        $data = [
            'title' => 'Cấu hình Mẫu tin ZNS | L.A.N ERP',
            'templates' => $templates
        ];

        return view('dashboard/zalo/zns_templates', $data);
    }

    /**
     * AJAX: Đồng bộ thông tin mẫu tin ZNS từ Zalo Open API
     */
    public function znsSyncTemplate()
    {
        if (!has_permission('zalo.campaign')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        }

        $templateId = $this->request->getPost('template_id');
        $templateName = $this->request->getPost('template_name');

        if (empty($templateId) || empty($templateName)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vui lòng cung cấp ID và Tên mẫu tin.']);
        }

        $znsService = new \App\Services\ZnsService();
        $apiResult = $znsService->getTemplateInfo($templateId);

        if (isset($apiResult['error']) && $apiResult['error'] !== 0) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'Lỗi từ Zalo API: ' . ($apiResult['message'] ?? 'Không xác định')
            ]);
        }

        // Thành công -> Trích xuất thông tin
        $data = $apiResult['data'] ?? [];
        $templateContent = $data['previewUrl'] ?? ($data['templateName'] ?? '');
        
        // Phân tích các biến (parameters) từ cấu hình template của Zalo
        $params = [];
        if (!empty($data['listParams'])) {
            foreach ($data['listParams'] as $param) {
                if (isset($param['name'])) {
                    $params[] = $param['name'];
                }
            }
        }

        // Lưu hoặc cập nhật vào bảng zns_templates
        $templateModel = new \App\Models\ZnsTemplateModel();
        $existing = $templateModel->where('template_id', $templateId)->first();

        $saveData = [
            'template_id' => $templateId,
            'template_name' => $templateName,
            'template_content' => $templateContent,
            'template_params' => json_encode($params),
            'status' => 'active',
            'created_by' => session()->get('user_id'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            $templateModel->update($existing['id'], $saveData);
            $message = 'Cập nhật mẫu tin ZNS thành công!';
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $templateModel->insert($saveData);
            $message = 'Thêm mới mẫu tin ZNS thành công!';
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $message,
            'template' => [
                'template_id' => $templateId,
                'template_name' => $templateName,
                'params' => $params
            ]
        ]);
    }

    /**
     * AJAX: Lưu cấu hình ánh xạ mặc định của Mẫu tin ZNS (Admin Setup Mappings)
     */
    public function znsSaveTemplateMappings()
    {
        if (!has_permission('zalo.campaign') && !has_permission('sys.admin')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        }

        $id = $this->request->getPost('id');
        $mappings = $this->request->getPost('mapping') ?? [];

        if (empty($id)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vui lòng cung cấp ID mẫu tin.']);
        }

        $templateModel = new \App\Models\ZnsTemplateModel();
        $template = $templateModel->find($id);

        if (!$template) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Mẫu tin không tồn tại.']);
        }

        $templateModel->update($id, [
            'default_mappings' => json_encode($mappings),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Cập nhật cấu hình ánh xạ mặc định cho mẫu tin nhắn thành công!'
        ]);
    }

    /**
     * AJAX/POST: Xóa mềm mẫu tin ZNS
     */
    public function znsDeleteTemplate($id)
    {
        if (!has_permission('zalo.campaign')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        }

        $templateModel = new \App\Models\ZnsTemplateModel();
        $template = $templateModel->find($id);

        if (!$template) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Mẫu tin không tồn tại hoặc đã bị xóa trước đó.']);
        }

        // Kiểm tra xem mẫu tin có đang được dùng trong chiến dịch nào không
        $campaignModel = new \App\Models\ZnsCampaignModel();
        $activeCampaigns = $campaignModel->where('zns_template_id', $id)
                                         ->where('deleted_at IS NULL')
                                         ->findAll();

        if (!empty($activeCampaigns)) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'Không thể xóa mẫu tin này vì đang có ' . count($activeCampaigns) . ' chiến dịch sử dụng.'
            ]);
        }

        if ($templateModel->delete($id)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Đã xóa mẫu tin ZNS thành công!'
            ]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể xóa mẫu tin. Vui lòng thử lại.']);
    }

    /**
     * Giao diện tạo chiến dịch ZNS mới
     */
    public function znsCampaignCreate()
    {
        if (!has_permission('zalo.campaign')) {
            return redirect()->to(base_url('zalo/campaigns'))->with('error', 'Bạn không có quyền tạo Chiến dịch.');
        }

        $templateModel = new \App\Models\ZnsTemplateModel();
        $tagModel = new \App\Models\TagModel();
        $userModel = new \App\Models\UserModel();

        // Lấy danh sách templates hoạt động
        $templates = $templateModel->getActiveTemplates();

        // Lấy danh sách nhân sự (để lọc theo người phụ trách)
        $staffs = $userModel->select('users.id as user_id, employees.full_name, users.email')
                            ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                            ->where('users.active_status', 1)
                            ->where('users.deleted_at', null)
                            ->findAll();

        // Lấy danh sách nhãn (tags) của khách hàng
        $tags = $tagModel->orderBy('name', 'ASC')->findAll();

        // Các cột thông tin khách hàng có thể map
        $customerFields = [
            'name' => 'Tên khách hàng (name)',
            'code' => 'Mã khách hàng (code)',
            'phone' => 'Số điện thoại chính (phone)',
            'zalo_phone' => 'Số điện thoại Zalo (zalo_phone)',
            'email' => 'Email chính (email)',
            'company' => 'Tên công ty (company)',
            'address' => 'Địa chỉ (address)',
            'care_status' => 'Trạng thái tư vấn (care_status)',
            'customer_segment' => 'Phân khúc khách hàng (customer_segment)',
        ];

        $data = [
            'title' => 'Tạo Chiến dịch ZNS mới | L.A.N ERP',
            'templates' => $templates,
            'staffs' => $staffs,
            'tags' => $tags,
            'customerFields' => $customerFields
        ];

        return view('dashboard/zalo/campaign_create', $data);
    }

    /**
     * AJAX/POST: Lưu chiến dịch ZNS mới
     */
    public function znsCampaignSave()
    {
        if (!has_permission('zalo.campaign')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền lưu chiến dịch.']);
        }

        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        $znsTemplateId = $this->request->getPost('zns_template_id');
        
        if (empty($name) || empty($znsTemplateId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ Tên chiến dịch và chọn Mẫu tin ZNS.']);
        }

        // Lấy dữ liệu mapping biến
        $mappingRaw = $this->request->getPost('mapping') ?? [];
        $mapping = [];
        foreach ($mappingRaw as $key => $val) {
            if (!empty($val)) {
                $mapping[$key] = $val;
            }
        }

        // Lấy tiêu chí bộ lọc khách hàng
        $filter = [
            'tag_id' => $this->request->getPost('filter_tag_id'),
            'care_status' => $this->request->getPost('filter_care_status'),
            'customer_segment' => $this->request->getPost('filter_customer_segment'),
            'care_staff_id' => $this->request->getPost('filter_care_staff_id'),
        ];

        // Hoặc danh sách khách hàng chọn tay thủ công (nếu có)
        $customerIdsRaw = $this->request->getPost('customer_ids');
        $customerIds = [];
        if (!empty($customerIdsRaw)) {
            if (is_array($customerIdsRaw)) {
                $customerIds = $customerIdsRaw;
            } else {
                $customerIds = json_decode($customerIdsRaw, true) ?: [];
            }
        }

        // Tính toán sơ bộ tổng số người nhận
        $znsService = new \App\Services\ZnsService();
        $recipients = [];
        if (!empty($customerIds)) {
            $recipients = $customerIds;
        } else {
            $recipientRecords = $znsService->previewRecipients($filter);
            $recipients = array_column($recipientRecords, 'id');
        }

        $campaignModel = new \App\Models\ZnsCampaignModel();
        
        $campaignData = [
            'name' => $name,
            'description' => $description,
            'zns_template_id' => $znsTemplateId,
            'template_data_mapping' => json_encode($mapping),
            'filter_criteria' => json_encode($filter),
            'customer_ids' => json_encode($customerIds),
            'status' => 'draft',
            'total_recipients' => count($recipients),
            'created_by' => session()->get('user_id'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $campaignModel->insert($campaignData);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Lưu chiến dịch ZNS nháp thành công!',
            'redirect' => base_url('zalo/campaigns')
        ]);
    }

    /**
     * AJAX/POST: Thực thi chiến dịch ZNS gửi hàng loạt
     */
    public function znsCampaignExecute($campaignId)
    {
        if (!has_permission('zalo.campaign')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực thi chiến dịch.']);
        }

        $znsService = new \App\Services\ZnsService();
        $result = $znsService->executeCampaign((int)$campaignId, (int)session()->get('user_id'));

        if (isset($result['error']) && $result['error'] === true) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => $result['message']
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Đã thực thi chiến dịch thành công! Đã gửi: {$result['success']} tin thành công, {$result['fail']} tin thất bại.",
            'data' => $result
        ]);
    }

    /**
     * Chi tiết chiến dịch và logs
     */
    public function znsCampaignDetail($campaignId)
    {
        if (!has_permission('zalo.campaign')) {
            return redirect()->to(base_url('zalo/campaigns'))->with('error', 'Bạn không có quyền truy cập thông tin chiến dịch.');
        }

        $campaignModel = new \App\Models\ZnsCampaignModel();
        $logModel = new \App\Models\ZnsLogModel();

        // Lấy thông tin chiến dịch
        $campaign = $campaignModel->select('zns_campaigns.*, zns_templates.template_name, zns_templates.template_id as zalo_template_id')
                                  ->join('zns_templates', 'zns_templates.id = zns_campaigns.zns_template_id', 'left')
                                  ->find($campaignId);

        if (!$campaign) {
            return redirect()->to(base_url('zalo/campaigns'))->with('error', 'Chiến dịch không tồn tại.');
        }

        // Lấy nhật ký gửi tin kèm phân trang
        $logs = $logModel->getLogsByCampaign($campaignId, 30);
        $pager = $logModel->pager;

        $data = [
            'title' => 'Chi tiết Chiến dịch ZNS: ' . esc($campaign['name']) . ' | L.A.N ERP',
            'campaign' => $campaign,
            'logs' => $logs,
            'pager' => $pager
        ];

        return view('dashboard/zalo/campaign_detail', $data);
    }

    /**
     * AJAX/POST: Gửi ZNS nhanh từ danh sách khách hàng
     */
    public function znsSendQuick()
    {
        try {
            // Chỉ giới hạn quyền cho admin, leader, hoặc người được bổ sung quyền zalo.send_individual
            $roleName = session()->get('role_name');
            $canSendZnsQuick = has_permission('sys.admin') || $roleName === \Config\AppConstants::ROLE_ADMIN || $roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || has_permission('zalo.send_individual');
            
            if (!$canSendZnsQuick) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền gửi tin nhắn ZNS cá nhân.']);
            }

            $customerIdsRaw = $this->request->getPost('customer_ids');
            $templateId = $this->request->getPost('template_id');

            if (empty($customerIdsRaw) || empty($templateId)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Vui lòng chọn khách hàng và mẫu tin ZNS.']);
            }

            $customerIds = is_array($customerIdsRaw) ? $customerIdsRaw : (json_decode($customerIdsRaw, true) ?: []);

            if (empty($customerIds)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Danh sách khách hàng trống.']);
            }

            // Lấy mapping biến
            $mappingRaw = $this->request->getPost('mapping') ?? [];

            $mapping = [];
            foreach ($mappingRaw as $key => $val) {
                if (!empty($val)) {
                    $mapping[$key] = $val;
                }
            }

            $znsService = new \App\Services\ZnsService();
            $result = $znsService->sendBulkZns($customerIds, $templateId, $mapping, null, session()->get('user_id'));

            return $this->response->setJSON([
                'status' => 'success',
                'message' => "Gửi nhanh ZNS hoàn thành! Thành công: {$result['success']}, Thất bại: {$result['fail']}.",
                'data' => $result
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'znsSendQuick error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage() . ' tại tệp ' . basename($e->getFile()) . ' dòng ' . $e->getLine() . '.'
            ]);
        }
    }
}
