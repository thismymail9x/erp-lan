<?php

namespace App\Controllers;

/**
 * MessengerController
 *
 * Bộ điều khiển tích hợp Facebook Messenger vào ERP.
 * Kiến trúc tương đồng ZaloController để đảm bảo tính nhất quán toàn hệ thống.
 *
 * 1. Quản lý hội thoại tập trung: Nhận webhook, lưu lịch sử, hiển thị chat.
 * 2. Phân quyền chăm sóc: Gán nhân sự, tự động nhận hội thoại khi click.
 * 3. Cấu hình linh hoạt: Nhập Page Token và App Secret qua giao diện admin.
 * 4. Chat real-time: AJAX polling 5s cập nhật tin nhắn mới, không reload trang.
 */
class MessengerController extends BaseController
{
    /**
     * Khai báo metadata phân quyền — Cỗ máy /perm-fix/sync sẽ tự đọc (Rule #10)
     */
    public static $modulePermissions = [
        'group'       => 'Tư Vấn Khách Hàng',
        'permissions' => [
            'messenger.view'     => ['desc' => 'Xem danh sách hội thoại Messenger', 'roles' => [1, 3, 4, 5, 6, 7]],
            'messenger.chat'     => ['desc' => 'Chat với khách hàng qua Messenger',  'roles' => [1, 3, 4, 5, 6, 7]],
            'messenger.config'   => ['desc' => 'Cấu hình kết nối Facebook Page',     'roles' => [1]],
            'messenger.assign'   => ['desc' => 'Gán nhân sự chăm sóc hội thoại',    'roles' => [1, 3]],
        ]
    ];

    /**
     * Khai báo module có thể gắn nhãn thông minh (Rule #10)
     */
    public static $taggable = [
        'type'  => 'messenger_contacts',
        'label' => 'Khách hàng Messenger'
    ];

    protected $messengerService;

    public function __construct()
    {
        $this->messengerService = new \App\Services\MessengerService();
    }

    // =========================================================================
    // TRANG CHÍNH — Danh sách hội thoại + Khung chat
    // =========================================================================

    /**
     * Trang chính: Danh sách hội thoại bên trái, khung chat bên phải.
     * Tự động chọn hội thoại đầu tiên nếu không có ?psid= trên URL.
     */
    public function index()
    {
        if (!has_permission('messenger.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Bạn không có quyền truy cập Messenger.');
        }

        // Kiểm tra đã cấu hình chưa
        if (!$this->messengerService->isConfigured()) {
            return redirect()->to(base_url('messenger/config'));
        }

        $contactModel = new \App\Models\MessengerContactModel();
        $messageModel = new \App\Models\MessengerMessageModel();
        $tagModel     = new \App\Models\TagModel();

        $currentUserId = session()->get('user_id');
        $role          = session()->get('role_name');
        $isAdmin       = in_array($role, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]);

        // Lọc tìm kiếm
        $search      = $this->request->getGet('search');
        $filterStaff = $this->request->getGet('filter_staff');
        $filterTag   = $this->request->getGet('filter_tag');

        // Query danh sách contacts (giống Zalo)
        $query = $contactModel->orderBy('updated_at', 'DESC');

        // Phân quyền: Nhân viên chỉ thấy khách của mình + chưa gán
        if (!$isAdmin) {
            $query->groupStart()
                  ->where('assigned_to', $currentUserId)
                  ->orWhere('assigned_to', null)
                  ->orWhere('assigned_to', 0)
                  ->groupEnd();
        } elseif ($filterStaff) {
            $query->where('assigned_to', $filterStaff);
        }

        if ($search) {
            $query->groupStart()
                  ->like('display_name', $search)
                  ->orLike('mid_code', $search)
                  ->orLike('phone_number', $search)
                  ->groupEnd();
        }

        if ($filterTag) {
            $query->like('tags', $filterTag);
        }

        $contacts = $query->findAll(50);

        // Bổ sung thông tin tin nhắn cuối + số unread vào mỗi contact
        $staffLookup = [];
        if ($isAdmin) {
            $userModel   = new \App\Models\UserModel();
            $allStaffRaw = $userModel->select('users.id as user_id, employees.full_name, users.email')
                                     ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                                     ->where('users.active_status', 1)
                                     ->where('users.deleted_at', null)
                                     ->findAll();
            foreach ($allStaffRaw as $s) {
                $staffLookup[$s['user_id']] = $s['full_name'] ?: $s['email'];
            }
        }

        foreach ($contacts as &$c) {
            $lastMsg = $messageModel->where('contact_id', $c['id'])->orderBy('created_at', 'DESC')->first();
            $c['last_message']  = $lastMsg ? $lastMsg['message_text'] : 'Chưa có tin nhắn';
            $c['last_time']     = $lastMsg ? date('H:i', strtotime($lastMsg['created_at'])) : '';
            $c['unread_count']  = $messageModel->where('contact_id', $c['id'])
                                               ->where('sender_type', 'user')
                                               ->where('is_read', 0)
                                               ->countAllResults();

            if (!empty($c['assigned_to'])) {
                $c['assigned_staff_name'] = $staffLookup[$c['assigned_to']] ?? ((!$isAdmin) ? (session()->get('full_name') ?: 'Tôi') : '');
            } else {
                $c['assigned_staff_name'] = '';
            }
        }

        // Chọn contact đang xem
        $selectedPsid    = $this->request->getGet('psid') ?? ($contacts[0]['psid'] ?? null);
        $messages        = [];
        $selectedContact = null;

        if ($selectedPsid) {
            $selectedContact = $contactModel->where('psid', $selectedPsid)->first();

            // Kiểm tra quyền truy cập
            if (!$isAdmin && $selectedContact && !empty($selectedContact['assigned_to']) && $selectedContact['assigned_to'] != $currentUserId) {
                return redirect()->to(base_url('messenger'))->with('error', 'Bạn không có quyền xem hội thoại này.');
            }

            if ($selectedContact) {
                // Đánh dấu đã đọc
                $messageModel->where('contact_id', $selectedContact['id'])
                             ->where('sender_type', 'user')
                             ->where('is_read', 0)
                             ->set(['is_read' => 1])
                             ->update();

                // Tự động gán nếu nhân viên click vào hội thoại chưa có ai nhận
                if (!$isAdmin && empty($selectedContact['assigned_to'])) {
                    $contactModel->update($selectedContact['id'], ['assigned_to' => $currentUserId]);
                    $selectedContact['assigned_to'] = $currentUserId;
                }

                // Lấy 30 tin nhắn gần nhất
                $messages = $messageModel->where('contact_id', $selectedContact['id'])
                                         ->orderBy('id', 'DESC')
                                         ->findAll(30);
                $messages = array_reverse($messages);

                // Lấy tên nhân sự đang phụ trách
                if (!empty($selectedContact['assigned_to'])) {
                    $employeeModel = new \App\Models\EmployeeModel();
                    $assignedStaff = $employeeModel->where('user_id', $selectedContact['assigned_to'])->first();
                    $selectedContact['assigned_staff_name'] = $assignedStaff ? $assignedStaff['full_name'] : 'Nhân sự';
                }
            }
        }

        // Danh sách nhân sự (cho admin filter + dropdown gán)
        $staffs = [];
        if ($isAdmin) {
            $userModel = new \App\Models\UserModel();
            $staffs    = $userModel->select('users.id as user_id, users.email, employees.full_name')
                                   ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                                   ->where('users.active_status', 1)
                                   ->where('users.deleted_at', null)
                                   ->findAll();
        }

        $allTags         = $tagModel->orderBy('name', 'ASC')->findAll();
        $consultationSvc = new \App\Services\ConsultationService();
        $quickReplies    = $consultationSvc->getQuickReplies();

        $data = [
            'title'           => 'Facebook Messenger | L.A.N ERP',
            'contacts'        => $contacts,
            'selectedContact' => $selectedContact,
            'messages'        => $messages,
            'selectedPsid'    => $selectedPsid,
            'staffs'          => $staffs,
            'allTags'         => $allTags,
            'quickReplies'    => $quickReplies,
            'isAdmin'         => $isAdmin,
            'filter'          => ['search' => $search, 'staff' => $filterStaff, 'tag' => $filterTag],
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'sidebar_html'   => view('dashboard/messenger/_sidebar', $data),
                'chat_area_html' => view('dashboard/messenger/_chat_area', $data),
                'selectedPsid'   => $selectedPsid,
                'lastMsgId'      => !empty($messages) ? end($messages)['id'] : 0,
            ]);
        }

        return view('dashboard/messenger/index', $data);
    }

    // =========================================================================
    // CẤU HÌNH
    // =========================================================================

    /**
     * Trang cấu hình kết nối Facebook Page
     */
    public function config()
    {
        if (!has_permission('messenger.config')) {
            return redirect()->to(base_url('messenger'))->with('error', 'Bạn không có quyền cấu hình Messenger.');
        }

        $pageInfo = $this->messengerService->getPageInfo();

        $data = [
            'title'    => 'Cấu hình Facebook Messenger | L.A.N ERP',
            'pageInfo' => $pageInfo,
            'config'   => $this->messengerService,
        ];

        return view('dashboard/messenger/config', $data);
    }

    /**
     * Lưu cấu hình Facebook App (POST)
     */
    public function saveConfig()
    {
        if (!has_permission('messenger.config')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không có quyền.']);
        }

        $pageToken   = trim($this->request->getPost('page_access_token'));
        $appId       = trim($this->request->getPost('app_id'));
        $appSecret   = trim($this->request->getPost('app_secret'));
        $verifyToken = trim($this->request->getPost('verify_token'));

        if (empty($pageToken)) {
            return redirect()->back()->with('error', 'Page Access Token không được để trống.');
        }

        $this->messengerService->saveConfigToDb([
            'messenger_page_access_token' => $pageToken,
            'messenger_app_id'            => $appId,
            'messenger_app_secret'        => $appSecret,
            'messenger_verify_token'      => $verifyToken ?: 'lan_erp_messenger_verify_2026',
        ]);

        return redirect()->to(base_url('messenger/config'))->with('success', 'Đã lưu cấu hình Facebook Messenger thành công!');
    }

    // =========================================================================
    // WEBHOOK
    // =========================================================================

    /**
     * Endpoint Webhook nhận sự kiện từ Meta (GET = verify, POST = event)
     * URL này phải PUBLIC (không qua auth filter), đặt trong Routes ngoài group auth.
     */
    public function webhook()
    {
        // --- GET: Meta xác minh webhook khi admin đăng ký ---
        if (strtolower($this->request->getMethod()) === 'get') {
            // Hỗ trợ cả định dạng dấu chấm (Meta gửi hub.mode) và dấu gạch dưới (PHP tự chuyển hóa)
            $mode      = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? $this->request->getGet('hub_mode') ?? $this->request->getGet('hub.mode');
            $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? $this->request->getGet('hub_verify_token') ?? $this->request->getGet('hub.verify_token');
            $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? $this->request->getGet('hub_challenge') ?? $this->request->getGet('hub.challenge');

            // Đảm bảo ép kiểu chuỗi an toàn
            $mode      = is_string($mode) ? $mode : '';
            $token     = is_string($token) ? $token : '';
            $challenge = is_string($challenge) ? $challenge : '';

            // Ghi log chẩn đoán sớm để biết request đã tiếp cận hệ thống (Sử dụng mức error để đảm bảo ghi log trên production)
            log_message('error', sprintf('MessengerController: Incoming Webhook GET request. Mode: "%s", Token: "%s", Challenge: "%s"', $mode, $token, $challenge));

            $expectedToken = $this->messengerService->getVerifyToken();

            if ($mode === 'subscribe' && $this->messengerService->verifyWebhookToken($token)) {
                log_message('error', 'MessengerController: Webhook verification thành công. Mode: ' . $mode);
                // Trả về thẳng challenge thô không có bất kỳ ký tự dư thừa nào với Header text/plain theo chuẩn Meta
                return $this->response->setStatusCode(200)
                                      ->setContentType('text/plain')
                                      ->setBody($challenge);
            }

            log_message('error', sprintf(
                'MessengerController: Webhook verification thất bại. Nhận được [Mode: %s, Token: %s], Kỳ vọng [Token: %s]',
                $mode,
                $token,
                $expectedToken
            ));
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }
        // --- POST: Nhận sự kiện từ Meta ---
        $rawBody   = $this->request->getBody();
        $signature = $this->request->getHeaderLine('X-Hub-Signature-256');
        $payload   = json_decode($rawBody, true);

        log_message('error', 'MESSENGER WEBHOOK RECEIVED: ' . $rawBody);

        if ($payload) {
            $this->messengerService->handleWebhook($payload, $rawBody, $signature);
        }

        // Meta yêu cầu phải trả về 200 ngay lập tức
        return $this->response->setStatusCode(200)->setJSON(['status' => 'EVENT_RECEIVED']);
    }

    // =========================================================================
    // AJAX ENDPOINTS
    // =========================================================================

    /**
     * AJAX: Lấy tin nhắn mới + cập nhật danh sách hội thoại (polling 5s)
     */
    public function ajaxChat()
    {
        $psid       = $this->request->getGet('psid');
        $lastMsgId  = $this->request->getGet('last_msg_id');

        $contactModel  = new \App\Models\MessengerContactModel();
        $messageModel  = new \App\Models\MessengerMessageModel();
        $currentUserId = session()->get('user_id');
        $isAdmin       = in_array(session()->get('role_name'), [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]);

        $data = ['new_messages' => [], 'contacts' => []];

        // 1. Tin nhắn mới của hội thoại đang mở
        if ($psid) {
            $contact = $contactModel->where('psid', $psid)->first();
            if ($contact) {
                // Đánh dấu đã đọc
                $messageModel->where('contact_id', $contact['id'])
                             ->where('sender_type', 'user')
                             ->where('is_read', 0)
                             ->set(['is_read' => 1])
                             ->update();

                $msgQuery = $messageModel->where('contact_id', $contact['id']);
                if ($lastMsgId) {
                    $msgQuery->where('id >', $lastMsgId);
                }
                $data['new_messages'] = $msgQuery->orderBy('created_at', 'ASC')->findAll();
            }
        }

        // 2. Danh sách hội thoại cập nhật
        $fQuery = $contactModel->orderBy('updated_at', 'DESC');
        if (!$isAdmin) {
            $fQuery->groupStart()
                   ->where('assigned_to', $currentUserId)
                   ->orWhere('assigned_to', null)
                   ->orWhere('assigned_to', 0)
                   ->groupEnd();
        }

        $contacts = $fQuery->findAll(50);
        foreach ($contacts as &$c) {
            $lastMsg = $messageModel->where('contact_id', $c['id'])->orderBy('created_at', 'DESC')->first();
            $c['last_message'] = $lastMsg ? $lastMsg['message_text'] : 'Chưa có tin nhắn';
            $c['last_time']    = $lastMsg ? date('H:i', strtotime($lastMsg['created_at'])) : '';
            $c['unread_count'] = $messageModel->where('contact_id', $c['id'])
                                              ->where('sender_type', 'user')
                                              ->where('is_read', 0)
                                              ->countAllResults();
            $data['contacts'][] = [
                'psid'          => $c['psid'],
                'display_name'  => $c['display_name'],
                'avatar_url'    => $c['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($c['display_name']) . '&background=1877f2&color=fff',
                'last_message'  => $c['last_message'],
                'last_time'     => $c['last_time'],
                'unread_count'  => $c['unread_count'],
                'tags'          => json_decode($c['tags'], true) ?: [],
                'active'        => ($psid == $c['psid']),
            ];
        }

        return $this->response->setJSON($data);
    }

    /**
     * AJAX: Gửi tin nhắn văn bản tới khách hàng
     */
    public function sendMessage()
    {
        if (!has_permission('messenger.chat')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền chat.']);
        }

        $psid    = $this->request->getPost('psid');
        $message = $this->request->getPost('message');

        if (empty($psid) || empty($message)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
        }

        $contactModel = new \App\Models\MessengerContactModel();
        $contact      = $contactModel->where('psid', $psid)->first();
        if (!$contact) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng.']);
        }

        // Gửi qua Facebook Send API
        $result = $this->messengerService->sendTextMessage($psid, $message);

        if (isset($result['error']) && $result['error'] === false) {
            $messageModel = new \App\Models\MessengerMessageModel();
            $messageModel->insert([
                'contact_id'   => $contact['id'],
                'fb_msg_id'    => $result['message_id'] ?? null,
                'sender_type'  => 'page',
                'message_text' => $message,
                'mid_staff_id' => session()->get('user_id'),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            $contactModel->update($contact['id'], ['updated_at' => date('Y-m-d H:i:s')]);
            return $this->response->setJSON(['status' => 'success']);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Lỗi gửi tin: ' . ($result['message'] ?? 'Không xác định'),
        ]);
    }

    /**
     * AJAX: Gán nhân sự chăm sóc hội thoại
     */
    public function assignStaff()
    {
        if (!has_permission('messenger.assign')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không có quyền gán nhân sự.']);
        }

        $contactId = $this->request->getPost('contact_id');
        $staffId   = $this->request->getPost('staff_id');

        $contactModel = new \App\Models\MessengerContactModel();
        if ($contactModel->update($contactId, ['assigned_to' => $staffId ?: null])) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã cập nhật nhân sự chăm sóc.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể cập nhật.']);
    }

    /**
     * AJAX: Cập nhật Tag cho hội thoại
     */
    public function updateTags()
    {
        $contactId = $this->request->getPost('contact_id');
        $tags      = $this->request->getPost('tags');

        if (!$contactId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        $contactModel = new \App\Models\MessengerContactModel();
        $tagJson      = json_encode($tags, JSON_UNESCAPED_UNICODE);

        if ($contactModel->update($contactId, ['tags' => $tagJson])) {
            return $this->response->setJSON(['status' => 'success', 'tags' => $tags]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể cập nhật nhãn.']);
    }

    /**
     * AJAX: Tải thêm tin nhắn cũ (Load More)
     */
    public function loadMoreMessages()
    {
        $psid       = $this->request->getGet('psid');
        $beforeId   = $this->request->getGet('before_id');

        $contactModel = new \App\Models\MessengerContactModel();
        $messageModel = new \App\Models\MessengerMessageModel();

        $contact = $contactModel->where('psid', $psid)->first();
        if (!$contact) {
            return $this->response->setJSON(['messages' => []]);
        }

        $query = $messageModel->where('contact_id', $contact['id']);
        if ($beforeId) {
            $query->where('id <', $beforeId);
        }

        $messages = $query->orderBy('id', 'DESC')->findAll(10);
        $messages = array_reverse($messages);

        return $this->response->setJSON(['messages' => $messages]);
    }

    /**
     * Giả lập Webhook để test trên localhost (không có HTTPS)
     */
    public function simulateWebhook()
    {
        $mockPayload = [
            'object' => 'page',
            'entry'  => [[
                'id'        => 'PAGE_ID_MOCK',
                'messaging' => [[
                    'sender'    => ['id' => 'PSID_TEST_' . rand(1000, 9999)],
                    'recipient' => ['id' => 'PAGE_ID_MOCK'],
                    'timestamp' => time() * 1000,
                    'message'   => [
                        'mid'  => 'mid_test_' . time(),
                        'text' => 'Chào luật sư, tôi muốn nhờ tư vấn hợp đồng thuê nhà.',
                    ]
                ]]
            ]]
        ];

        $this->messengerService->handleWebhook($mockPayload);
        return redirect()->to(base_url('messenger'))->with('success', 'Đã giả lập nhận tin nhắn Messenger thành công!');
    }
}
