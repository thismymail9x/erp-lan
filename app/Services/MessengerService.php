<?php

namespace App\Services;

use Config\Messenger as MessengerConfig;

/**
 * MessengerService
 * 
 * Xử lý toàn bộ nghiệp vụ tích hợp Facebook Messenger vào ERP:
 * 1. Xác thực OAuth (App Review không cần — dùng Page Token thủ công)
 * 2. Nhận và phân tích Webhook từ Meta
 * 3. Gửi tin nhắn qua Send API
 * 4. Lấy profile người dùng qua Graph API
 * 5. Lưu trữ và đồng bộ dữ liệu vào DB
 * 
 * Kiến trúc tương đồng ZaloService để đảm bảo tính nhất quán của hệ thống.
 */
class MessengerService
{
    protected $config;
    protected $db;
    public $lastError = '';

    // URL gốc Facebook Graph API
    const GRAPH_API_BASE = 'https://graph.facebook.com/v19.0';

    public function __construct()
    {
        $this->config = new MessengerConfig();
        $this->db = \Config\Database::connect();

        // Tải Page Access Token từ DB, ưu tiên hơn giá trị trong file Config
        $this->loadTokenFromDb();
    }

    /**
     * Tải Page Access Token từ bảng system_settings.
     * Lý do: Token có thể được cập nhật từ giao diện quản trị mà không cần sửa file code.
     */
    private function loadTokenFromDb()
    {
        try {
            $settingModel = new \App\Models\SystemSettingModel();
            $token = $settingModel->find('messenger_page_access_token');
            $appId = $settingModel->find('messenger_app_id');
            $appSecret = $settingModel->find('messenger_app_secret');
            $verifyToken = $settingModel->find('messenger_verify_token');

            if ($token && !empty($token['value'])) {
                $this->config->pageAccessToken = $token['value'];
            }
            if ($appId && !empty($appId['value'])) {
                $this->config->appId = $appId['value'];
            }
            if ($appSecret && !empty($appSecret['value'])) {
                $this->config->appSecret = $appSecret['value'];
            }
            if ($verifyToken && !empty($verifyToken['value'])) {
                $this->config->verifyToken = $verifyToken['value'];
            }
        } catch (\Exception $e) {
            log_message('error', 'MessengerService::loadTokenFromDb error: ' . $e->getMessage());
        }
    }

    /**
     * Lưu cấu hình Messenger vào DB (được gọi từ MessengerController::saveConfig)
     * 
     * @param array $settings Mảng ['key' => 'value']
     */
    public function saveConfigToDb(array $settings)
    {
        try {
            $settingModel = new \App\Models\SystemSettingModel();
            foreach ($settings as $key => $value) {
                $settingModel->save([
                    'key'        => $key,
                    'value'      => $value,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            log_message('notice', 'MessengerService: Config updated in database.');
            return true;
        } catch (\Exception $e) {
            log_message('error', 'MessengerService::saveConfigToDb error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem Messenger đã được cấu hình đầy đủ chưa.
     * Cần có Page Access Token mới hoạt động được.
     */
    public function isConfigured()
    {
        return !empty($this->config->pageAccessToken);
    }

    /**
     * Lấy profile người dùng từ Facebook Graph API bằng PSID
     * 
     * PSID (Page-Scoped ID): Định danh riêng của mỗi người dùng với từng Page.
     * Chỉ lấy được: first_name, last_name, profile_pic, locale, timezone.
     * (Facebook đã giới hạn dữ liệu profile từ 2019 — không còn lấy được email, phone)
     *
     * @param string $psid Page-Scoped User ID
     * @return array|null
     */
    public function getUserProfile(string $psid)
    {
        if (empty($this->config->pageAccessToken)) {
            return null;
        }

        $url = self::GRAPH_API_BASE . "/{$psid}?fields=first_name,last_name,name,profile_pic,locale,timezone&access_token=" . urlencode($this->config->pageAccessToken);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            log_message('error', 'MessengerService::getUserProfile cURL error: ' . $curlErr);
            return null;
        }

        $result = json_decode($response, true);

        // Nếu token lỗi, log cảnh báo
        if (isset($result['error'])) {
            log_message('error', 'MessengerService::getUserProfile API error: ' . json_encode($result['error']));
            return null;
        }

        return $result;
    }

    /**
     * Xử lý Webhook từ Meta (Facebook/Messenger)
     * 
     * Meta gửi dữ liệu dạng POST JSON khi có sự kiện (tin nhắn mới, seen, v.v.)
     * Cấu trúc payload: { object: "page", entry: [{ messaging: [...] }] }
     * 
     * Luồng xử lý:
     * 1. Xác minh chữ ký X-Hub-Signature-256 để chống giả mạo
     * 2. Duyệt qua từng entry và messaging event
     * 3. Tìm/tạo contact tương ứng
     * 4. Lưu tin nhắn vào DB
     *
     * @param array $payload Dữ liệu JSON đã decode từ webhook
     * @param string $rawBody Body thô để xác minh chữ ký
     * @param string $signature Giá trị header X-Hub-Signature-256
     */
    public function handleWebhook(array $payload, string $rawBody = '', string $signature = '')
    {
        // 1. Xác minh chữ ký bảo mật (nếu có app secret)
        if (!empty($this->config->appSecret) && !empty($signature)) {
            if (!$this->verifySignature($rawBody, $signature)) {
                log_message('error', 'MessengerService::handleWebhook - Chữ ký không hợp lệ! Từ chối xử lý.');
                return false;
            }
        }

        // 2. Kiểm tra đây là sự kiện của Page (không phải User hay Group)
        if (!isset($payload['object']) || $payload['object'] !== 'page') {
            return false;
        }

        $contactModel = new \App\Models\MessengerContactModel();
        $messageModel = new \App\Models\MessengerMessageModel();

        foreach ($payload['entry'] as $entry) {
            $pageId = $entry['id'] ?? '';

            // Duyệt qua từng messaging event trong entry
            $messagingEvents = $entry['messaging'] ?? [];
            foreach ($messagingEvents as $event) {
                $senderId    = $event['sender']['id']    ?? null;
                $recipientId = $event['recipient']['id'] ?? null;

                if (!$senderId) {
                    continue;
                }

                // Phân biệt: User gửi vào Page hay Page gửi ra ngoài
                // Nếu sender = page thì đây là tin Page tự gửi (echo)
                $isFromUser = ($senderId !== $pageId);

                // PSID của khách hàng luôn là phía "user"
                $userPsid = $isFromUser ? $senderId : $recipientId;
                if (!$userPsid) continue;

                // 3. Tìm hoặc tạo contact
                $contact = $contactModel->where('psid', $userPsid)->first();
                if (!$contact) {
                    $profile  = $this->getUserProfile($userPsid);
                    $name     = ($profile && isset($profile['name'])) ? $profile['name'] : 'Khách Facebook';
                    $avatar   = $profile['profile_pic'] ?? null;
                    $locale   = $profile['locale'] ?? 'vi_VN';
                    $timezone = $profile['timezone'] ?? 7;

                    $contactId = $contactModel->insert([
                        'psid'         => $userPsid,
                        'display_name' => $name,
                        'avatar_url'   => $avatar,
                        'mid_code'     => 'FB-' . strtoupper(substr(md5($userPsid . time()), 0, 6)),
                        'tags'         => json_encode(['New']),
                        'locale'       => $locale,
                        'timezone'     => $timezone,
                        'page_id'      => $pageId,
                        'created_at'   => date('Y-m-d H:i:s'),
                    ]);

                    if (!$contactId) {
                        log_message('error', 'MessengerService::handleWebhook - Không tạo được contact PSID=' . $userPsid);
                        continue;
                    }
                    $contact = $contactModel->find($contactId);
                } else {
                    // Cập nhật lại tên/avatar nếu vẫn là "Khách Facebook"
                    if ($contact['display_name'] === 'Khách Facebook' || empty($contact['avatar_url'])) {
                        $profile = $this->getUserProfile($userPsid);
                        if ($profile && isset($profile['name'])) {
                            $contactModel->update($contact['id'], [
                                'display_name' => $profile['name'],
                                'avatar_url'   => $profile['profile_pic'] ?? $contact['avatar_url'],
                            ]);
                        }
                    }
                }

                // 4. Xử lý sự kiện tin nhắn
                if (isset($event['message'])) {
                    $this->processMessageEvent($event, $contact, $isFromUser, $messageModel, $contactModel);
                }

                // 5. Xử lý sự kiện "seen" (bỏ qua — chỉ log)
                if (isset($event['read'])) {
                    log_message('debug', 'MessengerService: Tin nhắn đã được đọc bởi khách: PSID=' . $userPsid);
                }

                // 6. Xử lý sự kiện delivery
                if (isset($event['delivery'])) {
                    log_message('debug', 'MessengerService: Tin nhắn đã được giao tới khách: PSID=' . $userPsid);
                }
            }
        }

        return true;
    }

    /**
     * Xử lý một sự kiện tin nhắn cụ thể từ Webhook
     * Tách riêng để tái sử dụng và giảm phức tạp của handleWebhook
     */
    private function processMessageEvent(array $event, array $contact, bool $isFromUser, $messageModel, $contactModel)
    {
        $msg    = $event['message'];
        $fbMsgId = $msg['mid'] ?? null;

        // Kiểm tra trùng lặp (Webhook có thể gửi lại)
        if ($fbMsgId) {
            $existing = $messageModel->where('fb_msg_id', $fbMsgId)->first();
            if ($existing) {
                // Nếu đã có nhưng chưa có attachments, cập nhật lại
                if (empty($existing['attachments']) && isset($msg['attachments'])) {
                    $messageModel->update($existing['id'], [
                        'attachments' => json_encode($msg['attachments'])
                    ]);
                }
                return; // Bỏ qua, đã xử lý rồi
            }
        }

        // Phân loại nội dung tin nhắn
        $messageText = '';
        $attachments = null;

        if (!empty($msg['text'])) {
            $messageText = $msg['text'];
        }

        if (!empty($msg['attachments'])) {
            $attData = $msg['attachments'];
            $attachments = json_encode($attData);

            // Đặt text mô tả nếu không có text
            if (empty($messageText)) {
                $type = $attData[0]['type'] ?? 'file';
                $typeMap = [
                    'image'    => '[Hình ảnh]',
                    'video'    => '[Video]',
                    'audio'    => '[Âm thanh]',
                    'file'     => '[Tệp tin]',
                    'template' => '[Mẫu tin nhắn]',
                    'fallback' => '[Nội dung chia sẻ]',
                ];
                $messageText = $typeMap[$type] ?? '[Đính kèm]';
            }
        }

        // Tin nhắn echo từ Page (Page tự gửi) — Bỏ qua nếu không phải từ ERP
        // (Meta gửi echo khi Page gửi từ Inbox của Facebook)
        if (isset($msg['is_echo']) && $msg['is_echo']) {
            // Chỉ bỏ qua nếu đây là echo (không phải tin do ERP gửi)
            // Tin do ERP gửi đã được lưu trong MessengerController::sendMessage
            return;
        }

        // Lưu tin nhắn vào DB
        $insertId = $messageModel->insert([
            'contact_id'   => $contact['id'],
            'fb_msg_id'    => $fbMsgId,
            'sender_type'  => $isFromUser ? 'user' : 'page',
            'message_text' => $messageText,
            'attachments'  => $attachments,
            'is_read'      => 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        if (!$insertId) {
            log_message('error', 'MessengerService::processMessageEvent - Lưu tin nhắn thất bại. Errors: ' . json_encode($messageModel->errors()));
            return;
        }

        // Cập nhật thời gian tương tác để hội thoại nhảy lên đầu danh sách
        $updateContactData = ['updated_at' => date('Y-m-d H:i:s')];

        // --- GIAI ĐOẠN 2 & 3: LÀM SẠCH, PHÂN LOẠI & PHÂN CÔNG TỰ ĐỘNG ---
        if ($isFromUser) {
            // 1. Trích xuất SĐT/Email nếu khách nhập trực tiếp trong nội dung chat
            $extractedPhone = null;
            if (preg_match('/(0[3|5|7|8|9]\d{8}|\+84[3|5|7|8|9]\d{8}|84[3|5|7|8|9]\d{8})/', $messageText, $matches)) {
                $extractedPhone = $matches[1];
            }

            $extractedEmail = null;
            if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $messageText, $matches)) {
                $extractedEmail = $matches[1];
            }

            if ($extractedPhone && empty($contact['phone_number'])) {
                $updateContactData['phone_number'] = $extractedPhone;
                $contact['phone_number'] = $extractedPhone;
            }
            if ($extractedEmail && empty($contact['email'])) {
                $updateContactData['email'] = $extractedEmail;
                $contact['email'] = $extractedEmail;
            }

            // 2. Phân tích nội dung (gắn nhãn lĩnh vực + chấm độ nóng)
            $assignmentService = new \App\Services\ChatAssignmentService();
            $analysis = $assignmentService->analyzeLeadContent($messageText);

            if (!empty($analysis['tags'])) {
                $existingTags = json_decode($contact['tags'] ?? '[]', true) ?: [];
                $mergedTags = array_unique(array_merge($existingTags, $analysis['tags']));
                $updateContactData['tags'] = json_encode(array_values($mergedTags), JSON_UNESCAPED_UNICODE);
            }

            if (!empty($analysis['lead_warmth']) && ($contact['lead_warmth'] === 'cold' || empty($contact['lead_warmth']))) {
                $updateContactData['lead_warmth'] = $analysis['lead_warmth'];
            }

            // 3. Kiểm tra trùng lặp dữ liệu và liên kết CRM
            if (!empty($contact['phone_number']) || !empty($contact['email'])) {
                $dupInfo = $assignmentService->checkDuplicates($contact['phone_number'] ?? '', $contact['email'] ?? null, 'messenger', $contact['id']);
                if ($dupInfo['is_duplicate']) {
                    $updateContactData['is_duplicate'] = 1;
                    $updateContactData['duplicate_of'] = $dupInfo['duplicate_of'];
                }
                if (!empty($dupInfo['customer_id'])) {
                    $updateContactData['customer_id'] = $dupInfo['customer_id'];
                }
                if (!empty($dupInfo['assigned_to']) && empty($contact['assigned_to'])) {
                    $updateContactData['assigned_to']             = $dupInfo['assigned_to'];
                    $updateContactData['assigned_at']             = date('Y-m-d H:i:s');
                    $updateContactData['first_response_deadline'] = $assignmentService->calculateFirstResponseDeadline(date('Y-m-d H:i:s'));
                    $updateContactData['first_responded_at']      = null;
                    $updateContactData['is_overdue']              = 0;
                }
            }

            // Lưu thay đổi phân loại
            if (!empty($updateContactData)) {
                $contactModel->update($contact['id'], $updateContactData);
                $contact = array_merge($contact, $updateContactData);
            }

            // 4. Tự động phân công nếu chưa gán cho ai (Đã tắt theo yêu cầu: Trưởng phòng/Admin gán thủ công)
            /*
            if (empty($contact['assigned_to'])) {
                $assignmentService->autoAssignLead('messenger', $contact['id']);
            }
            */
            else if (!empty($contact['first_responded_at'])) {
                // 5. Cập nhật Ongoing SLA nếu khách nhắn tiếp sau lần đầu
                $ongoingHours = $assignmentService->getOngoingSlaHours();
                $contactModel->update($contact['id'], [
                    'ongoing_response_deadline' => $assignmentService->calculateFirstResponseDeadline(date('Y-m-d H:i:s'), $ongoingHours),
                    'last_customer_msg_at'      => date('Y-m-d H:i:s'),
                    'ongoing_is_overdue'        => 0
                ]);
            }
        } else {
            // OA phản hồi khách (Outbound message từ webhook echo)
            if (!empty($contact['assigned_to'])) {
                if (empty($contact['first_responded_at'])) {
                    $updateContactData['first_responded_at'] = date('Y-m-d H:i:s');
                    $updateContactData['is_overdue'] = 0;
                } else {
                    // Xóa deadline ongoing vì nhân viên đã trả lời
                    $updateContactData['ongoing_response_deadline'] = null;
                    $updateContactData['ongoing_is_overdue'] = 0;
                }
                if (!empty($updateContactData)) {
                    $contactModel->update($contact['id'], $updateContactData);
                }
            }
        }
    }

    /**
     * Gửi tin nhắn văn bản tới người dùng qua Facebook Send API
     *
     * @param string $psid  PSID của người nhận
     * @param string $text  Nội dung tin nhắn
     * @return array        Kết quả từ API
     */
    public function sendTextMessage(string $psid, string $text)
    {
        if (empty($this->config->pageAccessToken)) {
            return ['error' => true, 'message' => 'Chưa cấu hình Page Access Token.'];
        }

        $url = self::GRAPH_API_BASE . '/me/messages?access_token=' . urlencode($this->config->pageAccessToken);

        $payload = [
            'recipient'      => ['id' => $psid],
            'message'        => ['text' => $text],
            'messaging_type' => 'RESPONSE', // Trả lời trong vòng 24h sau khi khách nhắn
        ];

        return $this->callSendApi($url, $payload);
    }

    /**
     * Gửi hình ảnh tới người dùng qua URL
     *
     * @param string $psid     PSID của người nhận
     * @param string $imageUrl URL hình ảnh công khai
     * @return array
     */
    public function sendImageByUrl(string $psid, string $imageUrl)
    {
        if (empty($this->config->pageAccessToken)) {
            return ['error' => true, 'message' => 'Chưa cấu hình Page Access Token.'];
        }

        $url = self::GRAPH_API_BASE . '/me/messages?access_token=' . urlencode($this->config->pageAccessToken);

        $payload = [
            'recipient'      => ['id' => $psid],
            'message'        => [
                'attachment' => [
                    'type'    => 'image',
                    'payload' => [
                        'url'         => $imageUrl,
                        'is_reusable' => true,
                    ]
                ]
            ],
            'messaging_type' => 'RESPONSE',
        ];

        return $this->callSendApi($url, $payload);
    }

    /**
     * Hàm nội bộ gọi Facebook Send API
     * Tất cả phương thức gửi đều qua đây để thống nhất xử lý lỗi
     *
     * @param string $url     Endpoint đầy đủ
     * @param array  $payload Dữ liệu JSON
     * @return array
     */
    private function callSendApi(string $url, array $payload)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->lastError = 'cURL error: ' . $curlErr;
            log_message('error', 'MessengerService::callSendApi ' . $this->lastError);
            return ['error' => true, 'message' => $this->lastError];
        }

        $result = json_decode($response, true);
        if (!$result) {
            return ['error' => true, 'message' => 'Phản hồi không hợp lệ từ Facebook: ' . $response];
        }

        if (isset($result['error'])) {
            $this->lastError = $result['error']['message'] ?? 'Lỗi không xác định từ Facebook';
            log_message('error', 'MessengerService::callSendApi Facebook error: ' . json_encode($result['error']));
            return ['error' => true, 'message' => $this->lastError, 'fb_error' => $result['error']];
        }

        return ['error' => false, 'message_id' => $result['message_id'] ?? null, 'recipient_id' => $result['recipient_id'] ?? null];
    }

    /**
     * Xác minh chữ ký X-Hub-Signature-256 từ Meta
     * 
     * Meta ký payload bằng HMAC-SHA256 với App Secret.
     * Nếu chữ ký không khớp → payload bị giả mạo, phải từ chối.
     *
     * @param string $rawBody   Body thô của HTTP request
     * @param string $signature Giá trị header X-Hub-Signature-256 (dạng sha256=xxxx)
     * @return bool
     */
    public function verifySignature(string $rawBody, string $signature)
    {
        if (empty($this->config->appSecret)) {
            return true; // Bỏ qua xác minh nếu chưa cấu hình App Secret
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $this->config->appSecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Xác minh Verify Token cho yêu cầu đăng ký Webhook của Meta
     * 
     * Khi admin đăng ký Webhook trên Meta App Dashboard,
     * Meta gửi GET request với hub.verify_token để kiểm tra.
     *
     * @param string|null $token Token nhận từ Meta
     * @return bool
     */
    public function verifyWebhookToken(?string $token)
    {
        if (empty($token)) {
            return false;
        }
        return trim($token) === trim($this->config->verifyToken);
    }

    /**
     * Lấy token xác minh hiện tại trong cấu hình hệ thống
     *
     * @return string
     */
    public function getVerifyToken()
    {
        return $this->config->verifyToken;
    }

    /**
     * Lấy thông tin Facebook Page được liên kết với Page Access Token
     * Dùng để hiển thị xác nhận kết nối thành công trong trang Config
     *
     * @return array|null
     */
    public function getPageInfo()
    {
        if (empty($this->config->pageAccessToken)) {
            return null;
        }

        $url = self::GRAPH_API_BASE . '/me?fields=id,name,picture&access_token=' . urlencode($this->config->pageAccessToken);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['error']) || !$result) {
            return null;
        }
        return $result;
    }
}
