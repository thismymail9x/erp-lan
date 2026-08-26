<?php

namespace App\Controllers;

/**
 * ChatController
 *
 * Trung tâm tư vấn khách hàng hợp nhất (Unified Chat Center).
 * Gộp giao diện Zalo OA và Facebook Messenger vào một màn hình duy nhất tại /chat.
 *
 * Kiến trúc:
 * - Dùng helper methods (_getContacts, _getMessages, ...) để xử lý logic chung cho cả 2 kênh.
 * - Controller chỉ xử lý Request/Response, logic nghiệp vụ nằm trong Service (Rule #2).
 * - Tất cả comment bằng tiếng Việt (Rule #1).
 * - Không dùng endif; endforeach; — luôn dùng ngoặc nhọn { } (Rule #3).
 */
class ChatController extends BaseController
{
    /**
     * Khai báo metadata phân quyền — Cỗ máy /perm-fix/sync sẽ tự đọc (Rule #10)
     */
    public static $modulePermissions = [
        'group' => 'Tư Vấn Khách Hàng',
        'permissions' => [
            'chat.view'   => ['desc' => 'Xem trung tâm tư vấn khách hàng', 'roles' => [1, 3, 4, 5, 6, 7]],
            'chat.send'   => ['desc' => 'Gửi tin nhắn tư vấn khách hàng', 'roles' => [1, 3, 4, 5, 6, 7]],
            'chat.assign' => ['desc' => 'Gán nhân sự chăm sóc hội thoại', 'roles' => [1, 3]],
            'chat.delete' => ['desc' => 'Xóa hội thoại tư vấn khách hàng', 'roles' => [1, 3]],
        ]
    ];

    /**
     * Khai báo module có khả năng gắn nhãn thông minh (Rule #10)
     */
    public static $taggable = [
        'type'  => 'chat_contacts',
        'label' => 'Khách hàng tư vấn'
    ];

    protected $zaloService;
    protected $messengerService;
    protected $assignmentService;

    public function __construct()
    {
        $this->zaloService       = new \App\Services\ZaloService();
        $this->messengerService  = new \App\Services\MessengerService();
        $this->assignmentService = new \App\Services\ChatAssignmentService();
    }

    // =========================================================================
    //  TRANG CHÍNH — Danh sách hội thoại hợp nhất + Khung chat
    // =========================================================================

    /**
     * Trang chính dual-mode: HTML (lần đầu) hoặc JSON (AJAX khi chuyển hội thoại).
     * Gộp dữ liệu từ cả zalo_followers và messenger_contacts vào một danh sách chung.
     */
    public function index()
    {
        // Kiểm tra quyền — cho phép nếu có bất kỳ quyền nào liên quan
        if (!has_permission('chat.view') && !has_permission('zalo.view') && !has_permission('messenger.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Bạn không có quyền truy cập Trung tâm tư vấn.');
        }

        $tagModel = new \App\Models\TagModel();
        $currentUserId = session()->get('user_id');
        $role          = session()->get('role_name');
        $isAdmin       = in_array($role, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]);

        // Đọc tham số lọc từ URL
        $channel     = $this->request->getGet('channel') ?: 'all';
        $contactId   = $this->request->getGet('contact_id');
        $search      = $this->request->getGet('search');
        $filterStaff = $this->request->getGet('filter_staff');
        $filterTag   = $this->request->getGet('filter_tag');
        $filterCreator = $this->request->getGet('filter_creator');

        $filters = [
            'search'  => $search,
            'staff'   => $filterStaff,
            'tag'     => $filterTag,
            'creator' => $filterCreator,
            'is_admin'       => $isAdmin,
            'current_user'   => $currentUserId,
        ];

        // Lấy danh sách liên hệ hợp nhất từ cả 2 kênh
        $contacts = $this->_getContacts($channel, $filters);

        // Xây dựng bảng tra cứu nhân sự (tránh query N+1)
        $staffLookup = $this->_buildStaffLookup($isAdmin);

        // Bổ sung thông tin tin nhắn cuối, unread, tên nhân sự
        foreach ($contacts as &$c) {
            $this->_enrichContactPreview($c, $staffLookup, $isAdmin);
        }
        unset($c);

        // Sắp xếp theo thời gian cập nhật giảm dần (hội thoại mới nhất lên đầu)
        usort($contacts, function ($a, $b) {
            return strtotime($b['updated_at'] ?? '2000-01-01') - strtotime($a['updated_at'] ?? '2000-01-01');
        });

        // Xác định hội thoại đang được chọn
        $selectedChannel   = $this->request->getGet('selected_channel');
        $selectedContactId = $contactId;

        // Nếu không có lựa chọn, mặc định chọn hội thoại đầu tiên
        if (!$selectedContactId && !empty($contacts)) {
            $selectedContactId = $contacts[0]['platform_id'];
            $selectedChannel   = $contacts[0]['channel'];
        }

        $messages        = [];
        $selectedContact = null;

        if ($selectedContactId && $selectedChannel) {
            $selectedContact = $this->_findContact($selectedChannel, $selectedContactId);

            // Kiểm tra quyền truy cập hội thoại
            if (!$isAdmin && $selectedContact && !empty($selectedContact['assigned_to']) && $selectedContact['assigned_to'] != $currentUserId) {
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền xem hội thoại này.']);
                }
                return redirect()->to(base_url('chat'))->with('error', 'Bạn không có quyền xem hội thoại này.');
            }

            if ($selectedContact) {
                // Đánh dấu tin nhắn đã đọc
                $this->_markMessagesAsRead($selectedChannel, $selectedContact['id']);

                // Không tự động gán nữa: Chỉ admin mới có thể gán nhân sự cho hội thoại

                // Lấy 30 tin nhắn gần nhất
                $messages = $this->_getMessages($selectedChannel, $selectedContact['id'], 30);

                // Bổ sung tên nhân sự phụ trách
                if (!empty($selectedContact['assigned_to'])) {
                    $employeeModel = new \App\Models\EmployeeModel();
                    $assignedStaff = $employeeModel->where('user_id', $selectedContact['assigned_to'])->first();
                    $selectedContact['assigned_staff_name'] = $assignedStaff ? $assignedStaff['full_name'] : 'Nhân sự';
                    $selectedContact['assigned_employee_id'] = $assignedStaff ? $assignedStaff['id'] : null;
                }

                // Chuẩn hóa thông tin contact đã chọn
                $selectedContact['channel'] = $selectedChannel;
            }
        }

        // Danh sách nhân sự cho admin (dropdown lọc + dropdown gán)
        $staffs = [];
        $creators = [];
        if ($isAdmin) {
            $userModel = new \App\Models\UserModel();
            $staffs = $userModel->select('users.id as user_id, users.email, employees.full_name')
                                ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                                ->where('users.active_status', 1)
                                ->where('users.deleted_at', null)
                                ->findAll();

            $employeeModel = new \App\Models\EmployeeModel();
            $creators = $employeeModel->select('employees.id as id, employees.full_name, employees.personal_email')
                                      ->join('users', 'users.id = employees.user_id', 'inner')
                                      ->where('users.active_status', 1)
                                      ->where('users.deleted_at', null)
                                      ->orderBy('employees.full_name', 'ASC')
                                      ->findAll();
        }

        // Dữ liệu chung: Tags, Câu trả lời nhanh
        $allTags = $tagModel->orderBy('name', 'ASC')->findAll();
        $consultationSvc = new \App\Services\ConsultationService();
        $quickReplies    = $consultationSvc->getQuickReplies();

        $data = [
            'title'           => 'Trung tâm Tư vấn | L.A.N ERP',
            'contacts'        => $contacts,
            'selectedContact' => $selectedContact,
            'messages'        => $messages,
            'selectedContactId' => $selectedContactId,
            'selectedChannel'   => $selectedChannel,
            'staffs'          => $staffs,
            'creators'        => $creators,
            'allTags'         => $allTags,
            'quickReplies'    => $quickReplies,
            'isAdmin'         => $isAdmin,
            'channel'         => $channel,
            'filter'          => [
                'search' => $search,
                'staff'  => $filterStaff,
                'tag'    => $filterTag,
                'creator' => $filterCreator,
            ],
            'lastMsgId'       => !empty($messages) ? end($messages)['id'] : 0,
        ];

        // Dual-mode: AJAX trả JSON partials, thường trả full HTML
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'sidebar_html'      => view('dashboard/chat/_sidebar', $data),
                'chat_area_html'    => view('dashboard/chat/_chat_area', $data),
                'selectedContactId' => $selectedContactId,
                'selectedChannel'   => $selectedChannel,
                'lastMsgId'         => !empty($messages) ? end($messages)['id'] : 0,
                'title'             => 'Trung tâm Tư vấn | ' . ($selectedContact ? $selectedContact['display_name'] : 'L.A.N ERP'),
            ]);
        }

        return view('dashboard/chat/index', $data);
    }

    // =========================================================================
    //  AJAX ENDPOINTS
    // =========================================================================

    /**
     * AJAX: Polling tin nhắn mới + cập nhật danh sách hội thoại (5s interval).
     * Trả về {new_messages: [], contacts: []} dạng JSON.
     */
    public function ajaxChat()
    {
        $channel     = $this->request->getGet('channel') ?: 'all';
        $contactId   = $this->request->getGet('contact_id'); // platform_id đang mở
        $selectedCh  = $this->request->getGet('selected_channel'); // kênh của hội thoại đang mở
        if (empty($selectedCh) && $channel !== 'all') {
            $selectedCh = $channel;
        }
        $lastMsgId   = $this->request->getGet('last_msg_id');
        $search      = $this->request->getGet('search');
        $filterStaff = $this->request->getGet('filter_staff');
        $filterTag   = $this->request->getGet('filter_tag');
        $filterCreator = $this->request->getGet('filter_creator');

        $currentUserId = session()->get('user_id');
        $isAdmin       = in_array(session()->get('role_name'), [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]);

        $data = ['new_messages' => [], 'contacts' => []];

        // 1. Lấy tin nhắn mới cho hội thoại đang mở
        if ($contactId && $selectedCh) {
            $contact = $this->_findContact($selectedCh, $contactId);
            if ($contact) {
                // Đánh dấu đã đọc
                $this->_markMessagesAsRead($selectedCh, $contact['id']);

                $msgModel = $this->_getMessageModel($selectedCh);
                $fkColumn = $this->_getForeignKeyColumn($selectedCh);

                $msgQuery = $msgModel->where($fkColumn, $contact['id']);
                if ($lastMsgId !== null && $lastMsgId !== '' && $lastMsgId !== '0' && $lastMsgId !== 0) {
                    $msgQuery->where('id >', (int)$lastMsgId);
                }
                $data['new_messages'] = $msgQuery->orderBy('created_at', 'ASC')->findAll();
            }
        }

        // 2. Lấy danh sách hội thoại cập nhật (preview)
        $filters = [
            'search'       => $search,
            'staff'        => $filterStaff,
            'tag'          => $filterTag,
            'creator'      => $filterCreator,
            'is_admin'     => $isAdmin,
            'current_user' => $currentUserId,
        ];

        $contacts = $this->_getContacts($channel, $filters);
        $staffLookup = $this->_buildStaffLookup($isAdmin);

        foreach ($contacts as &$c) {
            $this->_enrichContactPreview($c, $staffLookup, $isAdmin);
        }
        unset($c);

        // Sắp xếp theo thời gian cập nhật giảm dần
        usort($contacts, function ($a, $b) {
            return strtotime($b['updated_at'] ?? '2000-01-01') - strtotime($a['updated_at'] ?? '2000-01-01');
        });

        // Rút gọn dữ liệu trả về cho polling
        foreach ($contacts as $c) {
            $data['contacts'][] = [
                'channel'       => $c['channel'],
                'platform_id'   => $c['platform_id'],
                'display_name'  => $c['display_name'],
                'avatar_url'    => $c['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($c['display_name']) . '&background=random',
                'last_message'  => $c['last_message'] ?? 'Chưa có tin nhắn',
                'last_time'     => $c['last_time'] ?? '',
                'unread_count'  => $c['unread_count'] ?? 0,
                'tags'          => json_decode($c['tags'] ?? '[]', true) ?: [],
                'mid_code'      => $c['mid_code'] ?? '',
                'lead_warmth'   => $c['lead_warmth'],
                'is_duplicate'  => (int)$c['is_duplicate'],
                'is_overdue'    => (int)$c['is_overdue'],
                'ongoing_is_overdue' => (int)$c['ongoing_is_overdue'],
                'active'        => ($contactId == $c['platform_id'] && $selectedCh == $c['channel']),
            ];
        }

        return $this->response->setJSON($data);
    }

    /**
     * AJAX: Gửi tin nhắn tới khách hàng qua kênh tương ứng (Zalo hoặc Messenger).
     */
    /**
     * Xóa mềm nhiều hội thoại đã chọn trong sidebar chat.
     */
    public function bulkDelete()
    {
        $role = session()->get('role_name');
        $isAdmin = in_array($role, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]);

        if (!$isAdmin && !has_permission('chat.delete')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Bạn không có quyền xóa hội thoại.'
            ])->setStatusCode(403);
        }

        $items = $this->request->getPost('items');
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (!is_array($items) || empty($items)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Chưa chọn hội thoại cần xóa.'
            ])->setStatusCode(400);
        }

        $deleted = 0;
        foreach ($items as $item) {
            $channel = $item['channel'] ?? '';
            $platformId = $item['contact_id'] ?? '';

            if (!in_array($channel, ['zalo', 'messenger'], true) || $platformId === '') {
                continue;
            }

            $model = $this->_getContactModel($channel);
            $pkColumn = ($channel === 'zalo') ? 'zalo_id' : 'psid';
            $contact = $model->where($pkColumn, $platformId)->first();

            if (!$contact) {
                continue;
            }

            if ($model->delete((int) $contact['id'])) {
                $deleted++;
            }
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Đã xóa ' . $deleted . ' hội thoại.',
            'deleted' => $deleted
        ]);
    }

    public function sendMessage()
    {
        // Kiểm tra quyền gửi tin nhắn
        if (!has_permission('chat.send') && !has_permission('zalo.chat') && !has_permission('messenger.chat')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền gửi tin nhắn.']);
        }

        $channel   = $this->request->getPost('channel');
        $contactId = $this->request->getPost('contact_id'); // platform_id (zalo_id hoặc psid)
        $message   = $this->request->getPost('message');

        if (empty($channel) || empty($contactId) || empty($message)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
        }

        // Tìm bản ghi liên hệ trong DB
        $contact = $this->_findContact($channel, $contactId);
        if (!$contact) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng.']);
        }

        // Gửi tin nhắn qua API tương ứng và lưu vào DB
        if ($channel === 'zalo') {
            return $this->_sendZaloMessage($contact, $contactId, $message);
        }

        if ($channel === 'messenger') {
            return $this->_sendMessengerMessage($contact, $contactId, $message);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Kênh không hợp lệ.']);
    }

    /**
     * AJAX: Gán nhân sự chăm sóc hội thoại.
     */
    public function assignStaff()
    {
        // Kiểm tra quyền gán nhân sự
        if (!has_permission('chat.assign') && !has_permission('zalo.assign') && !has_permission('messenger.assign')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền gán nhân sự.']);
        }

        $channel  = $this->request->getPost('channel');
        $recordId = $this->request->getPost('id') ?: $this->request->getPost('contact_id'); // ID bản ghi trong bảng tương ứng
        $staffId  = $this->request->getPost('staff_id');

        if (!$channel || !$recordId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu thông tin kênh hoặc ID.']);
        }

        $model = $this->_getContactModel($channel);
        if (!$model) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kênh không hợp lệ.']);
        }

        $updateData = [
            'assigned_to' => $staffId ?: null
        ];

        if ($staffId) {
            $now = date('Y-m-d H:i:s');
            $updateData['assigned_at'] = $now;
            $updateData['first_response_deadline'] = $this->assignmentService->calculateFirstResponseDeadline($now);
            $updateData['first_responded_at'] = null;
            $updateData['is_overdue'] = 0;
        } else {
            $updateData['assigned_at'] = null;
            $updateData['first_response_deadline'] = null;
            $updateData['first_responded_at'] = null;
            $updateData['is_overdue'] = 0;
        }

        if ($model->update($recordId, $updateData)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã cập nhật nhân sự chăm sóc.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể cập nhật.']);
    }

    /**
     * AJAX: Cập nhật Tag cho hội thoại.
     */
    public function updateTags()
    {
        $channel  = $this->request->getPost('channel');
        $recordId = $this->request->getPost('id') ?: $this->request->getPost('contact_id'); // ID bản ghi
        $tags     = $this->request->getPost('tags'); // Mảng tags

        if (!$channel || !$recordId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu thông tin kênh hoặc ID khách hàng.']);
        }

        $model = $this->_getContactModel($channel);
        if (!$model) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kênh không hợp lệ.']);
        }

        $tagJson = json_encode($tags, JSON_UNESCAPED_UNICODE);
        if ($model->update($recordId, ['tags' => $tagJson])) {
            return $this->response->setJSON(['status' => 'success', 'tags' => $tags]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể cập nhật nhãn.']);
    }

    /**
     * AJAX: Tạo nhãn mới nhanh từ giao diện chat (dùng chung cho cả 2 kênh).
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
     * AJAX: Tải thêm tin nhắn cũ (Infinite scroll / Load More).
     */
    public function loadMoreMessages()
    {
        $channel   = $this->request->getGet('channel');
        $contactId = $this->request->getGet('contact_id'); // platform_id
        $beforeId  = $this->request->getGet('before_id');

        if (!$channel || !$contactId) {
            return $this->response->setJSON(['messages' => []]);
        }

        $contact = $this->_findContact($channel, $contactId);
        if (!$contact) {
            return $this->response->setJSON(['messages' => []]);
        }

        $msgModel = $this->_getMessageModel($channel);
        $fkColumn = $this->_getForeignKeyColumn($channel);

        $query = $msgModel->where($fkColumn, $contact['id']);
        if ($beforeId) {
            // Dùng cùng cursor logic với _getMessages (dựa trên created_at)
            $baseMsg = $msgModel->find($beforeId);
            if ($baseMsg) {
                $query->groupStart()
                      ->where('created_at <', $baseMsg['created_at'])
                      ->orGroupStart()
                          ->where('created_at =', $baseMsg['created_at'])
                          ->where('id <', $beforeId)
                      ->groupEnd()
                      ->groupEnd();
            }
        }

        $messages = $query->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll(10);
        $messages = array_reverse($messages);

        return $this->response->setJSON(['messages' => $messages]);
    }

    /**
     * AJAX: Đồng bộ lịch sử tin nhắn Zalo của một khách hàng từ Trung tâm Chat.
     * Chỉ hỗ trợ kênh Zalo. Ủy quyền cho ZaloService::syncConversation().
     */
    public function syncHistory()
    {
        if (!has_permission('chat.view') && !has_permission('zalo.view')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        }

        $channel   = $this->request->getPost('channel');
        $contactId = $this->request->getPost('contact_id'); // platform_id (zalo_id)

        if ($channel !== 'zalo') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Đồng bộ lịch sử chỉ hỗ trợ kênh Zalo.']);
        }

        if (empty($contactId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        // Ủy quyền toàn bộ logic cho ZaloService (Rule #2 & Quy tắc DRY)
        $result = $this->zaloService->syncConversation($contactId, 7);

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
     * AJAX: Đồng bộ lại tên + avatar khách hàng Zalo từ Trung tâm Chat.
     * Chỉ hỗ trợ kênh Zalo.
     */
    public function syncProfile()
    {
        if (!has_permission('chat.view') && !has_permission('zalo.view')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        }

        $channel   = $this->request->getPost('channel');
        $contactId = $this->request->getPost('contact_id'); // zalo_id

        if ($channel !== 'zalo') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Đồng bộ profile chỉ hỗ trợ kênh Zalo.']);
        }

        if (empty($contactId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $follower = $followerModel->where('zalo_id', $contactId)->first();
        if (!$follower) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng trong hệ thống.']);
        }

        $result = $this->zaloService->getProfile($contactId);
        if (isset($result['error']) && $result['error'] === 0) {
            $profile = $result['data'];
            $updateData = [
                'display_name' => $profile['display_name'],
                'avatar_url'   => $profile['avatars']['240'] ?? ($profile['avatar'] ?? $follower['avatar_url'])
            ];

            if (!empty($profile['shared_info']['phone'])) {
                $updateData['phone_number'] = $profile['shared_info']['phone'];
            }

            if ($followerModel->update($follower['id'], $updateData)) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Đã cập nhật thông tin khách hàng.',
                    'data'    => [
                        'display_name' => $updateData['display_name'],
                        'avatar_url'   => $updateData['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($updateData['display_name']) . '&background=random'
                    ]
                ]);
            }
        }

        $errorMsg = $result['message'] ?? 'Không thể lấy thông tin từ Zalo. Có thể khách chưa quan tâm OA hoặc chưa cấp quyền xem profile.';
        return $this->response->setJSON(['status' => 'error', 'message' => $errorMsg]);
    }

    /**
     * AJAX: Ghi nhận lịch sử cuộc gọi từ Trung tâm Chat.
     * Chỉ hỗ trợ kênh Zalo.
     */
    public function logCall()
    {
        if (!has_permission('chat.view') && !has_permission('zalo.view')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        }

        $channel   = $this->request->getPost('channel');
        $contactId = $this->request->getPost('contact_id'); // zalo_id
        $notes     = $this->request->getPost('notes');

        if ($channel !== 'zalo') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Ghi nhận cuộc gọi chỉ hỗ trợ kênh Zalo.']);
        }

        if (empty($contactId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu ID khách hàng.']);
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $follower = $followerModel->where('zalo_id', $contactId)->first();
        if (!$follower) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng.']);
        }

        // Ghi lịch sử tương tác
        $interactionModel = new \App\Models\CustomerInteractionModel();
        $interactionData = [
            'customer_id'      => $follower['customer_id'] ?: 0,
            'user_id'          => session()->get('user_id'),
            'channel'          => 'call',
            'interaction_date' => date('Y-m-d H:i:s'),
            'summary'          => 'Gọi điện tư vấn (Zalo)',
            'detailed_content' => $notes ?: 'Nhân viên đã gọi điện cho khách hàng qua Zalo.',
        ];

        if ($interactionModel->insert($interactionData)) {
            // Lưu tin nhắn hệ thống vào chat
            $messageModel = new \App\Models\ZaloMessageModel();
            $messageModel->insert([
                'follower_id'  => $follower['id'],
                'sender_type'  => 'oa',
                'message_text' => '📞 Đã thực hiện cuộc gọi: ' . ($notes ?: 'Không có ghi chú'),
                'created_at'   => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã ghi nhận cuộc gọi.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể lưu lịch sử cuộc gọi.']);
    }

    /**
     * AJAX: Ghi nhận lịch sử cuộc gọi đầy đủ từ Trung tâm Chat (V2).
     * Hỗ trợ cả Zalo và Messenger.
     * Nhận thêm: call_result (answered/no_answer/callback/rejected), duration (giây).
     */
    public function logCallV2()
    {
        if (!has_permission('chat.view') && !has_permission('zalo.view') && !has_permission('messenger.view')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        }

        $channel    = $this->request->getPost('channel');
        $contactId  = $this->request->getPost('contact_id'); // zalo_id hoặc psid
        $notes      = $this->request->getPost('notes');
        $callResult = $this->request->getPost('call_result') ?: 'answered'; // answered|no_answer|callback|rejected
        $duration   = (int)($this->request->getPost('duration') ?: 0); // giây

        if (empty($channel) || empty($contactId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu thông tin kênh hoặc ID khách hàng.']);
        }

        // Tìm liên hệ theo kênh
        $contact = $this->_findContact($channel, $contactId);
        if (!$contact) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng trong hệ thống.']);
        }

        // Xây dựng nội dung hiển thị cuộc gọi
        $resultLabels = [
            'answered'  => 'Đã nghe máy',
            'no_answer' => 'Không nghe máy',
            'callback'  => 'Hẹn gọi lại',
            'rejected'  => 'Từ chối nghe',
        ];
        $resultLabel = $resultLabels[$callResult] ?? 'Đã thực hiện cuộc gọi';

        // Định dạng thời lượng
        $durationText = '';
        if ($duration > 0) {
            $min = floor($duration / 60);
            $sec = $duration % 60;
            $durationText = ($min > 0 ? "{$min} phút " : '') . "{$sec} giây";
        }

        // Tổng hợp tóm tắt
        $staffName   = session()->get('full_name') ?: 'Nhân viên';
        $summaryText = $resultLabel;
        if ($durationText) {
            $summaryText .= " • {$durationText}";
        }
        if ($notes) {
            $summaryText .= " • {$notes}";
        }

        // Ghi vào customer_interactions nếu đã liên kết CRM
        if (!empty($contact['customer_id'])) {
            $interactionModel = new \App\Models\CustomerInteractionModel();
            $interactionModel->insert([
                'customer_id'      => $contact['customer_id'],
                'user_id'          => session()->get('user_id'),
                'channel'          => 'call',
                'interaction_date' => date('Y-m-d H:i:s'),
                'summary'          => "Gọi điện ({$resultLabel})" . ($channel === 'zalo' ? ' - Zalo OA' : ' - Messenger'),
                'detailed_content' => $notes ?: "Nhân viên đã gọi điện cho khách hàng. Kết quả: {$resultLabel}.",
            ]);
        }

        // Lưu tin nhắn hệ thống dạng JSON có cấu trúc
        $callLogPayload = json_encode([
            'type'         => 'call_log',
            'result'       => $callResult,
            'result_label' => $resultLabel,
            'duration'     => $duration,
            'duration_text'=> $durationText,
            'staff_name'   => $staffName,
            'notes'        => $notes,
            'timestamp'    => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE);

        if ($channel === 'zalo') {
            $messageModel = new \App\Models\ZaloMessageModel();
            $messageModel->insert([
                'follower_id'  => $contact['id'],
                'sender_type'  => 'system',
                'message_text' => "📞 {$summaryText}",
                'attachments'  => $callLogPayload,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } elseif ($channel === 'messenger') {
            $messageModel = new \App\Models\MessengerMessageModel();
            $messageModel->insert([
                'contact_id'   => $contact['id'],
                'sender_type'  => 'system',
                'message_text' => "📞 {$summaryText}",
                'attachments'  => $callLogPayload,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Đã ghi nhận lịch sử cuộc gọi.',
            'data'    => [
                'result'        => $callResult,
                'result_label'  => $resultLabel,
                'duration'      => $duration,
                'duration_text' => $durationText,
                'notes'         => $notes,
                'staff_name'    => $staffName,
                'timestamp'     => date('H:i d/m/Y'),
            ]
        ]);
    }

    // =========================================================================
    //  PRIVATE HELPER METHODS — Xử lý logic chung cho cả 2 kênh
    // =========================================================================

    /**
     * Lấy danh sách liên hệ hợp nhất từ zalo_followers và/hoặc messenger_contacts.
     * Trả về mảng đã chuẩn hóa với cấu trúc thống nhất.
     *
     * @param string $channel 'all', 'zalo', 'messenger'
     * @param array  $filters ['search', 'staff', 'tag', 'is_admin', 'current_user']
     * @return array
     */
    private function _getContacts(string $channel, array $filters): array
    {
        $contacts = [];

        // Lấy từ Zalo
        if ($channel === 'all' || $channel === 'zalo') {
            $zaloContacts = $this->_queryContacts('zalo', $filters);
            foreach ($zaloContacts as $row) {
                $contacts[] = $this->_normalizeContact($row, 'zalo');
            }
        }

        // Lấy từ Messenger
        if ($channel === 'all' || $channel === 'messenger') {
            $messengerContacts = $this->_queryContacts('messenger', $filters);
            foreach ($messengerContacts as $row) {
                $contacts[] = $this->_normalizeContact($row, 'messenger');
            }
        }

        return $contacts;
    }

    /**
     * Truy vấn danh sách liên hệ từ một kênh cụ thể, áp dụng các bộ lọc.
     *
     * @param string $channel 'zalo' hoặc 'messenger'
     * @param array  $filters Bộ lọc tìm kiếm
     * @return array
     */
    private function _queryContacts(string $channel, array $filters): array
    {
        $model     = $this->_getContactModel($channel);
        $isAdmin   = $filters['is_admin'] ?? false;
        $userId    = $filters['current_user'] ?? 0;
        $search    = $filters['search'] ?? '';
        $staffId   = $filters['staff'] ?? '';
        $tag       = $filters['tag'] ?? '';
        $creatorId = $filters['creator'] ?? '';
        $table     = ($channel === 'zalo') ? 'zalo_followers' : 'messenger_contacts';

        $query = $model->select($table . '.*')->orderBy($table . '.updated_at', 'DESC');

        // Phân quyền: Nhân viên thường chỉ thấy hội thoại được gán trực tiếp cho mình
        // Admin/Mod thấy tất cả, hoặc lọc theo filter_staff nếu có
        if (!$isAdmin) {
            $query->where($table . '.assigned_to', $userId);
        } elseif ($staffId) {
            $query->where($table . '.assigned_to', $staffId);
        }

        // Tìm kiếm theo tên, mã MID, số điện thoại
        if ($search) {
            $query->groupStart()
                  ->like($table . '.display_name', $search)
                  ->orLike($table . '.mid_code', $search)
                  ->orLike($table . '.phone_number', $search)
                  ->groupEnd();
        }

        // Lọc theo Tag
        if ($tag) {
            $query->like($table . '.tags', $tag);
        }

        // Người tạo nằm trên hồ sơ CRM được liên kết từ hội thoại.
        if ($creatorId) {
            $query->join('customers', 'customers.id = ' . $table . '.customer_id', 'inner')
                  ->where('customers.created_by', $creatorId)
                  ->where('customers.deleted_at', null);
        }

        return $query->findAll(50);
    }

    /**
     * Chuẩn hóa một bản ghi liên hệ từ bảng riêng sang cấu trúc thống nhất.
     *
     * @param array  $row     Bản ghi thô từ DB
     * @param string $channel 'zalo' hoặc 'messenger'
     * @return array Bản ghi đã chuẩn hóa
     */
    private function _normalizeContact(array $row, string $channel): array
    {
        $platformId = ($channel === 'zalo') ? ($row['zalo_id'] ?? '') : ($row['psid'] ?? '');

        $now = date('Y-m-d H:i:s');
        $isOverdue = $row['is_overdue'] ?? 0;
        if (!$isOverdue && !empty($row['first_response_deadline']) && empty($row['first_responded_at']) && $row['first_response_deadline'] <= $now) {
            $isOverdue = 1;
        }

        $ongoingIsOverdue = $row['ongoing_is_overdue'] ?? 0;
        if (!$ongoingIsOverdue && !empty($row['ongoing_response_deadline']) && $row['ongoing_response_deadline'] <= $now) {
            $ongoingIsOverdue = 1;
        }

        return [
            'id'                      => $row['id'],
            'channel'                 => $channel,
            'platform_id'             => $platformId,
            'zalo_id'                 => $row['zalo_id'] ?? null,
            'psid'                    => $row['psid'] ?? null,
            'display_name'            => $row['display_name'] ?? 'Khách hàng',
            'avatar_url'              => $row['avatar_url'] ?? '',
            'mid_code'                => $row['mid_code'] ?? '',
            'assigned_to'             => $row['assigned_to'] ?? null,
            'assigned_name'           => '', // Sẽ được bổ sung bởi _enrichContactPreview
            'tags'                    => $row['tags'] ?? '[]',
            'updated_at'              => $row['updated_at'] ?? null,
            'last_message'            => '', // Sẽ được bổ sung bởi _enrichContactPreview
            'last_time'               => '',
            'unread_count'            => 0,
            'phone_number'            => $row['phone_number'] ?? '',
            'customer_id'             => $row['customer_id'] ?? null,
            'created_at'              => $row['created_at'] ?? null,
            'lead_warmth'             => $row['lead_warmth'] ?? 'cold',
            'is_duplicate'            => $row['is_duplicate'] ?? 0,
            'duplicate_of'            => $row['duplicate_of'] ?? null,
            'first_response_deadline' => $row['first_response_deadline'] ?? null,
            'first_responded_at'      => $row['first_responded_at'] ?? null,
            'is_overdue'              => $isOverdue,
            'ongoing_response_deadline' => $row['ongoing_response_deadline'] ?? null,
            'ongoing_is_overdue'        => $ongoingIsOverdue,
            // Giữ nguyên dữ liệu gốc cho các thao tác chi tiết
            '_raw'                    => $row,
        ];
    }

    /**
     * Bổ sung thông tin preview (tin nhắn cuối, unread, tên nhân sự) cho mỗi contact.
     *
     * @param array $contact      Bản ghi contact đã chuẩn hóa (pass by reference)
     * @param array $staffLookup  Bảng tra cứu nhân sự [user_id => full_name]
     * @param bool  $isAdmin      Người dùng hiện tại có phải admin không
     */
    private function _enrichContactPreview(array &$contact, array $staffLookup, bool $isAdmin): void
    {
        $channel  = $contact['channel'];
        $msgModel = $this->_getMessageModel($channel);
        $fkColumn = $this->_getForeignKeyColumn($channel);

        // Tin nhắn cuối cùng
        $lastMsg = $msgModel->where($fkColumn, $contact['id'])->orderBy('created_at', 'DESC')->first();
        $contact['last_message'] = $lastMsg ? $lastMsg['message_text'] : 'Chưa có tin nhắn';
        $contact['last_time']    = $lastMsg ? date('H:i', strtotime($lastMsg['created_at'])) : '';

        // Số tin nhắn chưa đọc (chỉ đếm tin từ khách hàng gửi đến)
        $contact['unread_count'] = $msgModel->where($fkColumn, $contact['id'])
                                            ->where('sender_type', 'user')
                                            ->where('is_read', 0)
                                            ->countAllResults();

        // Tên nhân sự phụ trách
        if (!empty($contact['assigned_to'])) {
            if (isset($staffLookup[$contact['assigned_to']])) {
                $contact['assigned_name'] = $staffLookup[$contact['assigned_to']];
            } elseif (!$isAdmin) {
                $contact['assigned_name'] = session()->get('full_name') ?: 'Tôi';
            }
        }
    }

    /**
     * Lấy danh sách tin nhắn đã sắp xếp (cũ → mới) cho một hội thoại.
     *
     * @param string $channel   'zalo' hoặc 'messenger'
     * @param int    $recordId  ID bản ghi contact trong bảng tương ứng
     * @param int    $limit     Số lượng tin nhắn tối đa
     * @param int|null $beforeId Lấy tin nhắn trước ID này (dùng cho load more)
     * @return array
     */
    private function _getMessages(string $channel, int $recordId, int $limit = 30, ?int $beforeId = null): array
    {
        $msgModel = $this->_getMessageModel($channel);
        $fkColumn = $this->_getForeignKeyColumn($channel);

        $query = $msgModel->where($fkColumn, $recordId);

        if ($beforeId) {
            // Lấy các tin nhắn có created_at nhỏ hơn tin nhắn mốc (before_id)
            $baseMsg = $msgModel->find($beforeId);
            if ($baseMsg) {
                $query->groupStart()
                      ->where('created_at <', $baseMsg['created_at'])
                      ->orGroupStart()
                          ->where('created_at =', $baseMsg['created_at'])
                          ->where('id <', $beforeId)
                      ->groupEnd()
                      ->groupEnd();
            }
        }

        // Sắp xếp theo created_at DESC để nhất quán với sidebar preview.
        // Dùng id DESC làm tiebreaker khi 2 tin có cùng created_at.
        // Điều này đảm bảo tin nhắn có timestamp thực mới nhất luôn hiển thị,
        // dù chúng có id thấp hơn (do các tin cũ được sync sau với id mới hơn).
        $messages = $query->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll($limit);

        // Đảo ngược để hiển thị đúng thứ tự thời gian (cũ trên, mới dưới)
        return array_reverse($messages);
    }

    /**
     * Tìm bản ghi liên hệ theo platform_id (zalo_id hoặc psid).
     *
     * @param string $channel    'zalo' hoặc 'messenger'
     * @param string $platformId Mã định danh trên nền tảng
     * @return array|null
     */
    private function _findContact(string $channel, string $platformId): ?array
    {
        $model = $this->_getContactModel($channel);
        if (!$model) {
            return null;
        }

        $pkColumn = ($channel === 'zalo') ? 'zalo_id' : 'psid';
        return $model->where($pkColumn, $platformId)->first();
    }

    /**
     * Đánh dấu tất cả tin nhắn chưa đọc (từ khách gửi đến) là đã đọc.
     *
     * @param string $channel  'zalo' hoặc 'messenger'
     * @param int    $recordId ID bản ghi contact
     */
    private function _markMessagesAsRead(string $channel, int $recordId): void
    {
        $msgModel = $this->_getMessageModel($channel);
        $fkColumn = $this->_getForeignKeyColumn($channel);

        $msgModel->where($fkColumn, $recordId)
                 ->where('sender_type', 'user')
                 ->where('is_read', 0)
                 ->set(['is_read' => 1])
                 ->update();
    }

    /**
     * Trả về Model quản lý liên hệ tương ứng với kênh.
     *
     * @param string $channel 'zalo' hoặc 'messenger'
     * @return \CodeIgniter\Model|null
     */
    private function _getContactModel(string $channel)
    {
        if ($channel === 'zalo') {
            return new \App\Models\ZaloFollowerModel();
        }
        if ($channel === 'messenger') {
            return new \App\Models\MessengerContactModel();
        }
        return null;
    }

    /**
     * Trả về Model quản lý tin nhắn tương ứng với kênh.
     *
     * @param string $channel 'zalo' hoặc 'messenger'
     * @return \CodeIgniter\Model|null
     */
    private function _getMessageModel(string $channel)
    {
        if ($channel === 'zalo') {
            return new \App\Models\ZaloMessageModel();
        }
        if ($channel === 'messenger') {
            return new \App\Models\MessengerMessageModel();
        }
        return null;
    }

    /**
     * Trả về tên cột khóa ngoại liên kết tin nhắn → liên hệ theo kênh.
     * Zalo: follower_id, Messenger: contact_id
     *
     * @param string $channel 'zalo' hoặc 'messenger'
     * @return string
     */
    private function _getForeignKeyColumn(string $channel): string
    {
        return ($channel === 'zalo') ? 'follower_id' : 'contact_id';
    }

    /**
     * Xây dựng bảng tra cứu nhân sự [user_id => full_name] để tránh query N+1.
     * Chỉ tải khi user là Admin/Mod.
     *
     * @param bool $isAdmin
     * @return array
     */
    private function _buildStaffLookup(bool $isAdmin): array
    {
        if (!$isAdmin) {
            return [];
        }

        $userModel   = new \App\Models\UserModel();
        $allStaffRaw = $userModel->select('users.id as user_id, employees.full_name, users.email')
                                 ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                                 ->where('users.active_status', 1)
                                 ->where('users.deleted_at', null)
                                 ->findAll();

        $lookup = [];
        foreach ($allStaffRaw as $s) {
            $lookup[$s['user_id']] = $s['full_name'] ?: $s['email'];
        }

        return $lookup;
    }

    /**
     * Xử lý gửi tin nhắn Zalo: Gọi API, lưu DB, cập nhật thời gian tương tác.
     *
     * @param array  $contact   Bản ghi follower từ DB
     * @param string $zaloId    Zalo User ID
     * @param string $message   Nội dung tin nhắn
     * @return \CodeIgniter\HTTP\Response
     */
    private function _sendZaloMessage(array $contact, string $zaloId, string $message)
    {
        $result = $this->zaloService->sendTextMessage($zaloId, $message);

        if (isset($result['error']) && $result['error'] === 0) {
            $zaloMsgId = $result['data']['message_id'] ?? null;

            $messageModel = new \App\Models\ZaloMessageModel();
            $messageModel->insert([
                'follower_id'  => $contact['id'],
                'sender_type'  => 'oa',
                'message_text' => $message,
                'zalo_msg_id'  => $zaloMsgId, // Khóa chống trùng với webhook oa_send_text
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            // Cập nhật thời gian tương tác để hội thoại nhảy lên đầu danh sách
            $followerModel = new \App\Models\ZaloFollowerModel();
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            if (!empty($contact['assigned_to']) && empty($contact['first_responded_at'])) {
                $updateData['first_responded_at'] = date('Y-m-d H:i:s');
                $updateData['is_overdue'] = 0;
            }
            $followerModel->update($contact['id'], $updateData);

            return $this->response->setJSON(['status' => 'success']);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Lỗi từ Zalo: ' . ($result['message'] ?? 'Không xác định'),
        ]);
    }

    /**
     * Xử lý gửi tin nhắn Messenger: Gọi API, lưu DB, cập nhật thời gian tương tác.
     *
     * @param array  $contact Bản ghi contact từ DB
     * @param string $psid    Page-Scoped ID
     * @param string $message Nội dung tin nhắn
     * @return \CodeIgniter\HTTP\Response
     */
    private function _sendMessengerMessage(array $contact, string $psid, string $message)
    {
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

            $contactModel = new \App\Models\MessengerContactModel();
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            if (!empty($contact['assigned_to']) && empty($contact['first_responded_at'])) {
                $updateData['first_responded_at'] = date('Y-m-d H:i:s');
                $updateData['is_overdue'] = 0;
            }
            $contactModel->update($contact['id'], $updateData);

            return $this->response->setJSON(['status' => 'success']);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Lỗi gửi tin: ' . ($result['message'] ?? 'Không xác định'),
        ]);
    }

    /**
     * AJAX: Cập nhật thủ công các thuộc tính chi tiết của Lead (Độ nóng, Email, Số điện thoại, trùng lặp).
     */
    public function updateLeadInsights()
    {
        $channel  = $this->request->getPost('channel');
        $recordId = $this->request->getPost('id') ?: $this->request->getPost('contact_id');
        $email    = trim($this->request->getPost('email') ?? '');
        $phone    = trim($this->request->getPost('phone_number') ?? '');
        $warmth   = $this->request->getPost('lead_warmth');
        $duplicateOf = $this->request->getPost('duplicate_of');
        
        if (!$channel || !$recordId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu thông tin kênh hoặc ID khách hàng.']);
        }

        $model = $this->_getContactModel($channel);
        if (!$model) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kênh không hợp lệ.']);
        }

        $updateData = [];
        if ($this->request->getPost('email') !== null) {
            $updateData['email'] = empty($email) ? null : $email;
        }
        if ($this->request->getPost('phone_number') !== null) {
            $updateData['phone_number'] = empty($phone) ? null : $phone;
        }
        if ($warmth && in_array($warmth, ['hot', 'warm', 'cold'])) {
            $updateData['lead_warmth'] = $warmth;
        }
        if ($this->request->getPost('duplicate_of') !== null) {
            $updateData['duplicate_of'] = empty($duplicateOf) ? null : (int)$duplicateOf;
            $updateData['is_duplicate'] = empty($duplicateOf) ? 0 : 1;
        }

        // Tự động kiểm tra trùng lặp nếu SĐT hoặc Email thay đổi và người dùng không tự nhập duplicate_of
        if (($this->request->getPost('phone_number') !== null || $this->request->getPost('email') !== null) && empty($duplicateOf)) {
            $checkPhone = $updateData['phone_number'] ?? $this->request->getPost('phone_number');
            $checkEmail = $updateData['email'] ?? $this->request->getPost('email');
            
            if (!empty($checkPhone) || !empty($checkEmail)) {
                $dupInfo = $this->assignmentService->checkDuplicates($checkPhone ?: '', $checkEmail ?: null, $channel, $recordId);
                if ($dupInfo['is_duplicate']) {
                    $updateData['is_duplicate'] = 1;
                    $updateData['duplicate_of'] = $dupInfo['duplicate_of'];
                } else {
                    $updateData['is_duplicate'] = 0;
                    $updateData['duplicate_of'] = null;
                }
                if (!empty($dupInfo['customer_id'])) {
                    $updateData['customer_id'] = $dupInfo['customer_id'];
                }
            }
        }

        if (!empty($updateData)) {
            if ($model->update($recordId, $updateData)) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'Đã cập nhật thông tin Lead.', 'data' => $updateData]);
            }
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể lưu cơ sở dữ liệu.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không có dữ liệu thay đổi.']);
    }

    /**
     * AJAX: Tạo nhanh Hồ sơ khách hàng CRM trực tiếp từ chat mà không cần chuyển trang.
     */
    public function instantCreateCustomer()
    {
        $channel  = $this->request->getPost('channel');
        $recordId = $this->request->getPost('id');
        $phone    = trim($this->request->getPost('phone') ?? '');

        if (!$channel || !$recordId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Thiếu thông tin kênh hoặc ID khách hàng.']);
        }

        $model = $this->_getContactModel($channel);
        if (!$model) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kênh không hợp lệ.']);
        }

        // Tìm contact dựa trên id (khóa chính số) hoặc platform_id (zalo_id hoặc psid dạng chuỗi)
        $contact = null;
        // Chỉ tìm theo khóa chính tự tăng (id) nếu là dạng số nguyên ngắn (< 10 ký tự) để tránh tràn số hoặc lệch sang platform_id
        if (is_numeric($recordId) && strlen($recordId) < 10) {
            $contact = $model->find($recordId);
        }
        if (!$contact) {
            $contact = $this->_findContact($channel, $recordId);
        }

        if (!$contact) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Liên hệ chat không tồn tại.']);
        }

        // Cập nhật SĐT nếu người dùng nhập/thay đổi
        if (!empty($phone)) {
            $contact['phone_number'] = $phone;
            $model->update($contact['id'], ['phone_number' => $phone]);
        }

        $resolvedPhone = trim($contact['phone_number'] ?? '');
        if (empty($resolvedPhone)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Yêu cầu có Số điện thoại để tạo Hồ sơ khách hàng CRM.']);
        }

        // Kiểm tra xem số điện thoại này đã được đăng ký trong CRM chưa
        $customerModel = new \App\Models\CustomerModel();
        
        // Dùng cơ chế normalized variants để kiểm tra trùng trong CRM trước
        $variants = get_phone_variants($resolvedPhone);
        $existingCustomer = $customerModel->groupStart()
            ->whereIn('phone', $variants)
            ->orWhereIn('phone_secondary', $variants)
            ->groupEnd()
            ->where('deleted_at', null)
            ->first();

        if ($existingCustomer) {
            // Đã tồn tại khách hàng CRM trùng số điện thoại này!
            // Tiến hành đồng bộ/liên kết luôn contact này với khách hàng đã có
            $customerId = $existingCustomer['id'];
            $customerService = new \App\Services\CustomerService();
            $customerService->syncChatContactsByPhone($customerId, $resolvedPhone);
            
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => 'Số điện thoại này đã có hồ sơ trên CRM. Hệ thống tự động liên kết thành công!', 
                'customer_id' => $customerId
            ]);
        }

        // Tạo hồ sơ khách hàng mới
        $year = date('y');
        $num = $customerModel->where('YEAR(created_at)', date('Y'))->countAllResults() + 1;
        while ($customerModel->where('code', 'KH-' . $year . '-' . str_pad($num, 3, '0', STR_PAD_LEFT))->countAllResults() > 0) {
            $num++;
        }
        $customerCode = 'KH-' . $year . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);

        // Xác định nhân sự chăm sóc tương ứng từ assigned_to (user_id) sang employee_id
        $assignedCareStaffId = null;
        if (!empty($contact['assigned_to'])) {
            $db = \Config\Database::connect();
            $emp = $db->table('employees')
                ->where('user_id', $contact['assigned_to'])
                ->where('deleted_at IS NULL')
                ->select('id')
                ->get()
                ->getRow();
            if ($emp) {
                $assignedCareStaffId = $emp->id;
            }
        }
        
        if (!$assignedCareStaffId) {
            // Fallback về nhân viên hiện đang đăng nhập
            $assignedCareStaffId = session()->get('employee_id');
        }

        $customerData = [
            'code'                   => $customerCode,
            'type'                   => 'ca_nhan', // Mặc định cá nhân
            'name'                   => !empty($contact['display_name']) ? trim($contact['display_name']) : 'Khách hàng Chat',
            'phone'                  => $resolvedPhone,
            'email'                  => !empty($contact['email']) ? trim($contact['email']) : null,
            'source'                 => ($channel === 'zalo' ? 'zalo' : 'facebook'),
            'created_by'             => session()->get('employee_id'),
            'assigned_care_staff_id' => $assignedCareStaffId
        ];

        try {
            if ($customerModel->save($customerData)) {
                $newCustomerId = $customerModel->getInsertID();

                // Kích hoạt đồng bộ hóa tức thì liên kết các liên hệ chat liên quan theo SĐT
                $customerService = new \App\Services\CustomerService();
                $customerService->syncChatContactsByPhone($newCustomerId, $resolvedPhone);

                return $this->response->setJSON([
                    'status'      => 'success',
                    'message'     => 'Hồ sơ khách hàng CRM đã được tạo thành công!',
                    'customer_id' => $newCustomerId
                ]);
            } else {
                $errors = $customerModel->errors();
                $errorMsg = is_array($errors) ? implode(', ', $errors) : 'Lỗi kiểm tra dữ liệu.';
                return $this->response->setJSON(['status' => 'error', 'message' => $errorMsg]);
            }
        } catch (\Exception $e) {
            log_message('error', '[InstantCreateCustomer] Error: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Lỗi hệ thống khi tạo: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX: Tải lên và gửi hình ảnh/tệp tin tới khách hàng (Hỗ trợ đa kênh, hiện tại mới có Zalo)
     */
    public function uploadMedia()
    {
        $channel = $this->request->getPost('channel');
        $contactId = $this->request->getPost('contact_id');
        $file = $this->request->getFile('media');

        if (empty($channel) || empty($contactId) || !$file || !$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
        }

        $contact = $this->_findContact($channel, $contactId);
        if (!$contact) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy khách hàng.']);
        }

        if ($channel === 'zalo') {
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
            $newName = $file->getName();

            $result = null;
            $uploadStep = null;
            if ($type === 'image') {
                $attachmentId = $this->zaloService->uploadImage($fullPath);
                if ($attachmentId) {
                    $result = $this->zaloService->sendImageMessage($contactId, $attachmentId);
                } else {
                    $uploadStep = 'upload_image';
                }
            } else {
                $fileToken = $this->zaloService->uploadFile($fullPath);
                if ($fileToken) {
                    $result = $this->zaloService->sendFileMessage($contactId, $fileToken);
                } else {
                    $uploadStep = 'upload_file';
                }
            }

            @unlink($fullPath);

            if ($uploadStep) {
                $detailedError = $this->zaloService->lastError ?: 'Không xác định';
                log_message('error', "ChatController::uploadMedia - {$uploadStep} failed for contact_id={$contactId}, file=" . $newName . ". Error: " . $detailedError);
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => "Hệ thống từ chối tải lên tệp ({$detailedError}). Vui lòng kiểm tra định dạng/kích thước (Ảnh < 1MB, Tệp < 5MB)."
                ]);
            }

            if ($result && isset($result['error']) && $result['error'] === 0) {
                $zaloMsgId = $result['data']['message_id'] ?? null;
                $messageModel = new \App\Models\ZaloMessageModel();
                
                $attachmentData = [];
                if ($type === 'image') {
                    $attachmentData[] = [
                        'type'    => 'image',
                        'payload' => [
                            'url' => '',
                            'local_file' => $newName
                        ]
                    ];
                } else {
                    $attachmentData[] = [
                        'type'    => 'file',
                        'payload' => ['name' => $newName, 'size' => $file->getSize()]
                    ];
                }

                $messageModel->insert([
                    'follower_id'  => $contact['id'],
                    'sender_type'  => 'oa',
                    'message_text' => ($type === 'image' ? '[Hình ảnh]' : '[Tệp tin]'),
                    'attachments'  => json_encode($attachmentData),
                    'zalo_msg_id'  => $zaloMsgId,
                    'created_at'   => date('Y-m-d H:i:s')
                ]);

                return $this->response->setJSON(['status' => 'success']);
            }

            return $this->response->setJSON(['status' => 'error', 'message' => 'Lỗi tải lên: ' . ($result['message'] ?? 'Không xác định')]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Kênh này chưa hỗ trợ gửi tệp.']);
    }
}
