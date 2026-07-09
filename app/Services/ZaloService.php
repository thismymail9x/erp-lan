<?php

namespace App\Services;

use Config\Zalo as ZaloConfig;

class ZaloService
{
    protected $config;
    protected $db;
    public $lastError = '';

    public function __construct()
    {
        $this->config = new ZaloConfig();
        $this->db = \Config\Database::connect();
        
        // Tải token mới nhất từ Database để tránh phải sửa file Config thủ công
        $this->loadTokensFromDb();
    }

    /**
     * Tải Access Token và Refresh Token từ bảng system_settings
     */
    public function loadTokensFromDb(): array
    {
        $accessExpiresAt = null;
        try {
            $settingModel = new \App\Models\SystemSettingModel();
            $accessToken = $settingModel->find('zalo_access_token');
            $refreshToken = $settingModel->find('zalo_refresh_token');
            $accessExpiresAt = $settingModel->find('zalo_access_token_expires_at');
            
            if ($accessToken && !empty($accessToken['value'])) {
                $this->config->accessToken = $accessToken['value'];
            }
            if ($refreshToken && !empty($refreshToken['value'])) {
                $this->config->refreshToken = $refreshToken['value'];
            }
        } catch (\Exception $e) {
            log_message('error', 'ZaloService::loadTokensFromDb error: ' . $e->getMessage());
        }

        return [
            'access_token' => $this->config->accessToken,
            'refresh_token' => $this->config->refreshToken,
            'access_token_expires_at' => $accessExpiresAt['value'] ?? null,
        ];
    }

    public function getValidTokens(int $minimumValiditySeconds = 600): array
    {
        $tokens = $this->loadTokensFromDb();
        $expiresAt = !empty($tokens['access_token_expires_at'])
            ? strtotime($tokens['access_token_expires_at'])
            : false;

        if ($expiresAt !== false && $expiresAt <= time() + $minimumValiditySeconds) {
            return $this->refreshToken($tokens['refresh_token'] ?? null);
        }

        return $tokens;
    }

    /**
     * Lưu Access Token và Refresh Token mới vào Database
     */
    public function saveTokensToDb($accessToken, $refreshToken, $expiresIn = null): bool
    {
        try {
            $settingModel = new \App\Models\SystemSettingModel();
            $this->db->transStart();
            $settingModel->save([
                'key' => 'zalo_access_token',
                'value' => $accessToken,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $settingModel->save([
                'key' => 'zalo_refresh_token',
                'value' => $refreshToken,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if (is_numeric($expiresIn) && (int)$expiresIn > 0) {
                $settingModel->save([
                    'key' => 'zalo_access_token_expires_at',
                    'value' => date('Y-m-d H:i:s', time() + (int)$expiresIn),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return false;
            }

            $this->config->accessToken = $accessToken;
            $this->config->refreshToken = $refreshToken;
            log_message('notice', 'ZaloService: Tokens updated in database.');
            return true;
        } catch (\Exception $e) {
            log_message('error', 'ZaloService::saveTokensToDb error: ' . $e->getMessage());
            return false;
        }
    }

    public function getAuthUrl()
    {
        $appId = $this->config->appId;
        $redirectUri = urlencode(base_url('zalo/callback'));
        $state = bin2hex(random_bytes(16));
        
        // Save state to session to verify callback
        session()->set('zalo_auth_state', $state);

        return "https://oauth.zaloapp.com/v4/oa/permission?app_id={$appId}&redirect_uri={$redirectUri}&state={$state}";
    }

    public function exchangeCodeForToken($code)
    {
        $appId = $this->config->appId;
        $appSecret = $this->config->appSecret;

        $url = "https://oauth.zaloapp.com/v4/oa/access_token";
        
        $data = [
            'code' => $code,
            'app_id' => $appId,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "secret_key: {$appSecret}",
            "Content-Type: application/x-www-form-urlencoded"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
    
    public function refreshToken($refreshToken = null)
    {
        $lockName = 'erp_zalo_oa_token_refresh';
        $lockAcquired = false;

        try {
            $lockRow = $this->db->query('SELECT GET_LOCK(?, 10) AS acquired', [$lockName])->getRowArray();
            $lockAcquired = (int)($lockRow['acquired'] ?? 0) === 1;

            if (!$lockAcquired) {
                log_message('warning', 'ZaloService::refreshToken could not acquire refresh lock.');
                return $this->loadTokensFromDb();
            }

            $latestTokens = $this->loadTokensFromDb();
            if (!empty($refreshToken) && !empty($latestTokens['refresh_token']) && $latestTokens['refresh_token'] !== $refreshToken) {
                return $latestTokens;
            }

            $result = $this->refreshTokenWithoutLock($latestTokens['refresh_token'] ?? $refreshToken);
            if (isset($result['access_token'], $result['refresh_token'])) {
                $this->config->accessToken = $result['access_token'];
                $this->config->refreshToken = $result['refresh_token'];
            }

            return $result;
        } finally {
            if ($lockAcquired) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        }
    }

    private function refreshTokenWithoutLock($refreshToken)
    {
        $appId = $this->config->appId;
        $appSecret = $this->config->appSecret;

        $url = "https://oauth.zaloapp.com/v4/oa/access_token";
        
        $data = [
            'refresh_token' => $refreshToken,
            'app_id' => $appId,
            'grant_type' => 'refresh_token'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "secret_key: {$appSecret}",
            "Content-Type: application/x-www-form-urlencoded"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        
        // Nếu refresh thành công, lưu lại vào DB ngay lập tức
        if (isset($result['access_token']) && isset($result['refresh_token'])) {
            $this->saveTokensToDb($result['access_token'], $result['refresh_token'], $result['expires_in'] ?? null);
        }

        return $result;
    }

    /**
     * Lấy thông tin profile người dùng từ Zalo
     */
    public function getProfile($zaloId)
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) return ['error' => -1, 'message' => 'Access Token is empty'];

        // Thử Endpoint 1: V2 (GET)
        $result = $this->callZaloApi("https://openapi.zalo.me/v2.0/oa/getprofile", ['user_id' => $zaloId], 'GET');
        
        // Nếu bị lỗi "Shut down", thử Endpoint 2: V3 User Detail (GET)
        if (isset($result['error']) && ($result['error'] === -201 || strpos(strtolower($result['message'] ?? ''), 'shut down') !== false)) {
            log_message('notice', 'ZaloService: V2 API shut down, trying V3 User Detail (GET)...');
            $result = $this->callZaloApi("https://openapi.zalo.me/v3.0/oa/user/detail", ['user_id' => $zaloId], 'GET');
        }

        // Nếu token hết hạn (-216), refresh và thử lại một lần
        if (isset($result['error']) && $result['error'] === -216) {
            log_message('notice', 'ZaloService::getProfile: Token expired, refreshing...');
            $refreshResult = $this->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->getProfile($zaloId);
            }
        }

        return $result;
    }

    /**
     * Hàm trợ giúp gọi API Zalo linh hoạt (GET/POST)
     */
    private function callZaloApi($url, $data, $method = 'POST')
    {
        $accessToken = $this->config->accessToken;
        $jsonData = json_encode($data);
        
        if ($method === 'GET') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . "data=" . urlencode($jsonData);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($jsonData));
            $headers = [
                "Authorization: Bearer {$accessToken}",
                "access_token: {$accessToken}",
                "Content-Type: application/x-www-form-urlencoded"
            ];
        } else {
            $headers = [
                "Authorization: Bearer {$accessToken}",
                "access_token: {$accessToken}"
            ];
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        if (!$decoded) {
            return ['error' => -99, 'message' => 'Raw Response: ' . $response];
        }
        return $decoded;
    }

    /**
     * Xử lý webhook từ Zalo OA để đồng bộ dữ liệu
     */
    public function handleWebhook($data)
    {
        if (!isset($data['event_name'])) {
            return false;
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        $messageModel = new \App\Models\ZaloMessageModel();

        // Xử lý sự kiện người dùng gửi tin nhắn HOẶC OA gửi tin nhắn HOẶC cuộc gọi
        $msgEvents = [
            'user_send_text', 'user_send_image', 'user_send_file', 'user_send_video', 'user_send_sticker', 'user_send_audio',
            'oa_send_text', 'oa_send_image', 'oa_send_file', 'oa_send_video', 'oa_send_sticker', 'oa_send_audio',
            'user_call_oa'
        ];

        if (in_array($data['event_name'], $msgEvents)) {
            $isFromUser = (strpos($data['event_name'], 'user_') === 0);
            $zaloId = $isFromUser ? ($data['sender']['id'] ?? null) : ($data['recipient']['id'] ?? null);
            
            if (!$zaloId) return false;

            // Tìm hoặc tạo Follower
            $follower = $followerModel->where('zalo_id', $zaloId)->first();
            
            if (!$follower) {
                // Lấy profile từ Zalo
                $profile = $this->getProfile($zaloId);
                
                $followerId = $followerModel->insert([
                    'zalo_id' => $zaloId,
                    'display_name' => (!empty($profile['display_name']) && $profile['display_name'] !== 'Khách Zalo') ? $profile['display_name'] : 'Khách Zalo',
                    'avatar_url' => $profile['avatars']['240'] ?? ($profile['avatar'] ?? null),
                    'mid_code' => 'ZALO-' . strtoupper(substr(md5($zaloId . time()), 0, 6)),
                    'tags' => json_encode(['New']),
                ]);
                
                if (!$followerId) return false;
                $follower = $followerModel->find($followerId);
            } else {
                // Nếu đã tồn tại nhưng tên vẫn là 'Khách Zalo', thử cập nhật lại profile
                if ($follower['display_name'] === 'Khách Zalo' || empty($follower['avatar_url'])) {
                    $profile = $this->getProfile($zaloId);
                    if ($profile && isset($profile['error']) && $profile['error'] === 0) {
                        $pData = $profile['data'];
                        $followerModel->update($follower['id'], [
                            'display_name' => $pData['display_name'],
                            'avatar_url' => $pData['avatars']['240'] ?? ($pData['avatar'] ?? $follower['avatar_url'])
                        ]);
                    }
                }
            }

            // Xử lý sự kiện cuộc gọi nhỡ
            if ($data['event_name'] === 'user_call_oa') {
                $status = $data['info']['status'] ?? '';
                if ($status === 'missed') {
                    $messageModel->insert([
                        'follower_id' => $follower['id'],
                        'sender_type' => 'user',
                        'message_text' => '📞 [Cuộc gọi nhỡ] Khách hàng đã gọi cho OA nhưng không có người nghe máy.',
                        'zalo_msg_id' => $data['info']['call_id'] ?? 'call_'.time(),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    return true;
                }
                return true; // Bỏ qua các trạng thái khác của cuộc gọi nếu không cần
            }
            
            // Kiểm tra tin nhắn đã tồn tại chưa (tránh trùng lặp do Webhook gọi lại hoặc ERP đã lưu)
            $existingMsg = $messageModel->where('zalo_msg_id', $data['message']['msg_id'] ?? '')->first();
            if ($existingMsg && !empty($data['message']['msg_id'])) {
                // Nếu tin nhắn đã tồn tại nhưng chưa có attachments (do ERP gửi trước khi có URL từ Zalo)
                // thì cập nhật lại attachments từ Webhook để hiển thị hình ảnh/file
                if (empty($existingMsg['attachments']) && isset($data['message']['attachments'])) {
                    $newAttachments = json_encode($data['message']['attachments']);
                    $messageModel->update($existingMsg['id'], ['attachments' => $newAttachments]);
                }
                return true;
            }

            // Lưu tin nhắn
            $messageText = '';
            $attachments = null;
            
            $eventType = str_replace(['user_send_', 'oa_send_'], '', $data['event_name']);
            
            switch ($eventType) {
                case 'text':
                    $messageText = $data['message']['text'] ?? '';
                    break;
                case 'image':
                    $messageText = '[Hình ảnh]';
                    if (isset($data['message']['attachments'])) {
                        $attachments = json_encode($data['message']['attachments']);
                    }
                    break;
                case 'file':
                case 'video':
                    $messageText = ($eventType === 'video') ? '[Video]' : '[Tệp tin]';
                    if (isset($data['message']['attachments'])) {
                        $attachments = json_encode($data['message']['attachments']);
                    }
                    break;
                case 'sticker':
                    $messageText = '[Sticker]';
                    if (isset($data['message']['attachments'])) {
                        $attachments = json_encode($data['message']['attachments']);
                    }
                    break;
                case 'audio':
                    $messageText = '[Tin nhắn thoại]';
                    if (isset($data['message']['attachments'])) {
                        $attachments = json_encode($data['message']['attachments']);
                    }
                    break;
            }
            
            $msgId = $messageModel->insert([
                'zalo_msg_id' => $data['message']['msg_id'] ?? '',
                'follower_id' => $follower['id'],
                'sender_type' => $isFromUser ? 'user' : 'oa',
                'message_text' => $messageText,
                'attachments' => $attachments
            ]);
            
            if (!$msgId) {
                log_message('error', 'Zalo Webhook: Failed to insert message. Errors: ' . json_encode($messageModel->errors()));
                return false;
            }
            
            // Cập nhật thời gian tương tác mới nhất để hội thoại nhảy lên đầu danh sách
            $updateFollowerData = ['updated_at' => date('Y-m-d H:i:s')];

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

                if ($extractedPhone && empty($follower['phone_number'])) {
                    $updateFollowerData['phone_number'] = $extractedPhone;
                    $follower['phone_number'] = $extractedPhone;
                }
                if ($extractedEmail && empty($follower['email'])) {
                    $updateFollowerData['email'] = $extractedEmail;
                    $follower['email'] = $extractedEmail;
                }

                // 2. Phân tích nội dung (gắn nhãn lĩnh vực + chấm độ nóng)
                $assignmentService = new \App\Services\ChatAssignmentService();
                $analysis = $assignmentService->analyzeLeadContent($messageText);

                if (!empty($analysis['tags'])) {
                    $existingTags = json_decode($follower['tags'] ?? '[]', true) ?: [];
                    $mergedTags = array_unique(array_merge($existingTags, $analysis['tags']));
                    $updateFollowerData['tags'] = json_encode(array_values($mergedTags), JSON_UNESCAPED_UNICODE);
                }

                if (!empty($analysis['lead_warmth']) && ($follower['lead_warmth'] === 'cold' || empty($follower['lead_warmth']))) {
                    $updateFollowerData['lead_warmth'] = $analysis['lead_warmth'];
                }

                // 3. Kiểm tra trùng lặp dữ liệu và liên kết CRM
                if (!empty($follower['phone_number']) || !empty($follower['email'])) {
                    $dupInfo = $assignmentService->checkDuplicates($follower['phone_number'] ?? '', $follower['email'] ?? null, 'zalo', $follower['id']);
                    if ($dupInfo['is_duplicate']) {
                        $updateFollowerData['is_duplicate'] = 1;
                        $updateFollowerData['duplicate_of'] = $dupInfo['duplicate_of'];
                    }
                    if (!empty($dupInfo['customer_id'])) {
                        $updateFollowerData['customer_id'] = $dupInfo['customer_id'];
                    }
                    if (!empty($dupInfo['assigned_to']) && empty($follower['assigned_to'])) {
                        $updateFollowerData['assigned_to']             = $dupInfo['assigned_to'];
                        $updateFollowerData['assigned_at']             = date('Y-m-d H:i:s');
                        $updateFollowerData['first_response_deadline'] = $assignmentService->calculateFirstResponseDeadline(date('Y-m-d H:i:s'));
                        $updateFollowerData['first_responded_at']      = null;
                        $updateFollowerData['is_overdue']              = 0;
                    }
                }

                // Lưu thay đổi phân loại
                if (!empty($updateFollowerData)) {
                    $followerModel->update($follower['id'], $updateFollowerData);
                    $follower = array_merge($follower, $updateFollowerData);
                }

                // 4. Tự động phân công nếu chưa gán cho ai (Đã tắt theo yêu cầu: Trưởng phòng/Admin gán thủ công)
                /*
                if (empty($follower['assigned_to'])) {
                    $assignmentService->autoAssignLead('zalo', $follower['id']);
                }
                */
                else if (!empty($follower['first_responded_at'])) {
                    // 5. Cập nhật Ongoing SLA nếu khách nhắn tiếp sau lần đầu
                    $ongoingHours = $assignmentService->getOngoingSlaHours();
                    $followerModel->update($follower['id'], [
                        'ongoing_response_deadline' => $assignmentService->calculateFirstResponseDeadline(date('Y-m-d H:i:s'), $ongoingHours),
                        'last_customer_msg_at'      => date('Y-m-d H:i:s'),
                        'ongoing_is_overdue'        => 0
                    ]);
                }
            } else {
                // OA phản hồi khách (Outbound message)
                if (!empty($follower['assigned_to'])) {
                    if (empty($follower['first_responded_at'])) {
                        $updateFollowerData['first_responded_at'] = date('Y-m-d H:i:s');
                        $updateFollowerData['is_overdue'] = 0;
                    } else {
                        // Xóa deadline ongoing vì nhân viên đã trả lời
                        $updateFollowerData['ongoing_response_deadline'] = null;
                        $updateFollowerData['ongoing_is_overdue'] = 0;
                    }
                    if (!empty($updateFollowerData)) {
                        $followerModel->update($follower['id'], $updateFollowerData);
                    }
                }
            }
            
            return true;
        }
        return false;
    }

    /**
     * Gửi tin nhắn văn bản tới khách hàng
     */
    public function sendTextMessage($zaloId, $text)
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) {
            return ['error' => -1, 'message' => 'Missing Access Token'];
        }

        $url = "https://openapi.zalo.me/v3.0/oa/message/cs";
        
        $payload = [
            'recipient' => ['user_id' => $zaloId],
            'message' => ['text' => $text]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        // Retry nếu token hết hạn
        if (isset($result['error']) && $result['error'] === -216) {
            log_message('notice', 'ZaloService::sendTextMessage: Token expired, refreshing...');
            $refreshResult = $this->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->sendTextMessage($zaloId, $text);
            }
        }

        return $result;
    }

    /**
     * Lấy nội dung file từ Zalo Token
     */
    public function getFileContent($token)
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) {
            return null;
        }

        $url = "https://openapi.zalo.me/v2.0/oa/getfilecontent?data=" . urlencode(json_encode(['token' => $token]));
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode === 200) {
            return [
                'content' => $response,
                'mime' => $contentType
            ];
        }
        return null;
    }

    /**
     * Tải hình ảnh lên Zalo Media (V2 API)
     */
    public function uploadImage($filePath)
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) return null;

        $url = "https://openapi.zalo.me/v2.0/oa/upload/image";
        
        $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
        $cfile = new \CURLFile($filePath, $mimeType, basename($filePath));
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}",
            "Authorization: Bearer {$accessToken}"
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->lastError = 'cURL error: ' . $curlErr;
            log_message('error', 'ZaloService::uploadImage ' . $this->lastError);
            return null;
        }

        $result = json_decode($response, true);
        
        // Nếu token hết hạn (-216), refresh và thử lại một lần
        if (isset($result['error']) && $result['error'] === -216) {
            log_message('notice', 'ZaloService::uploadImage: Token expired, refreshing...');
            $refreshResult = $this->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->uploadImage($filePath);
            }
        }

        if (!isset($result['error']) || $result['error'] !== 0) {
            $this->lastError = $result['message'] ?? $response;
            log_message('error', 'ZaloService::uploadImage failed: ' . $this->lastError);
        } else {
            log_message('debug', 'ZaloService::uploadImage success: ' . ($result['data']['attachment_id'] ?? 'no-id'));
        }

        return (isset($result['error']) && $result['error'] === 0) ? $result['data']['attachment_id'] : null;
    }

    /**
     * Tải tệp tin lên Zalo Media (V2 API)
     */
    public function uploadFile($filePath)
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) return null;

        $url = "https://openapi.zalo.me/v2.0/oa/upload/file";
        
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $cfile = new \CURLFile($filePath, $mimeType, basename($filePath));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}",
            "Authorization: Bearer {$accessToken}"
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->lastError = 'cURL error: ' . $curlErr;
            log_message('error', 'ZaloService::uploadFile ' . $this->lastError);
            return null;
        }

        $result = json_decode($response, true);

        // Nếu token hết hạn (-216), refresh và thử lại một lần
        if (isset($result['error']) && $result['error'] === -216) {
            log_message('notice', 'ZaloService::uploadFile: Token expired, refreshing...');
            $refreshResult = $this->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->uploadFile($filePath);
            }
        }

        if (!isset($result['error']) || $result['error'] !== 0) {
            $this->lastError = $result['message'] ?? $response;
            log_message('error', 'ZaloService::uploadFile failed: ' . $this->lastError);
        } else {
            log_message('debug', 'ZaloService::uploadFile success: ' . ($result['data']['token'] ?? 'no-token'));
        }

        return (isset($result['error']) && $result['error'] === 0) ? $result['data']['token'] : null;
    }

    /**
     * Gửi tin nhắn hình ảnh
     */
    public function sendImageMessage($zaloId, $attachmentId, $text = '')
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) return null;

        $url = "https://openapi.zalo.me/v3.0/oa/message/cs";
        
        $payload = [
            'recipient' => ['user_id' => $zaloId],
            'message' => [
                'text' => $text,
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'media',
                        'elements' => [
                            [
                                'media_type' => 'image',
                                'attachment_id' => $attachmentId
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        // Retry nếu token hết hạn
        if (isset($result['error']) && $result['error'] === -216) {
            log_message('notice', 'ZaloService::sendImageMessage: Token expired, refreshing...');
            $refreshResult = $this->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->sendImageMessage($zaloId, $attachmentId, $text);
            }
        }

        return $result;
    }

    /**
     * Gửi tin nhắn tệp tin
     */
    public function sendFileMessage($zaloId, $fileToken)
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) return null;

        $url = "https://openapi.zalo.me/v3.0/oa/message/cs";
        
        $payload = [
            'recipient' => ['user_id' => $zaloId],
            'message' => [
                'attachment' => [
                    'type' => 'file',
                    'payload' => ['token' => $fileToken]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        // Retry nếu token hết hạn
        if (isset($result['error']) && $result['error'] === -216) {
            log_message('notice', 'ZaloService::sendFileMessage: Token expired, refreshing...');
            $refreshResult = $this->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->sendFileMessage($zaloId, $fileToken);
            }
        }

        return $result;
    }

    /**
     * Kiểm tra tính an toàn của file dựa trên đuôi mở rộng
     */
    public function isFileSafe($fileName)
    {
        $forbiddenExtensions = ['exe', 'php', 'js', 'sh', 'bat', 'cmd', 'msi', 'vbs', 'scr'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (empty($ext) || in_array($ext, $forbiddenExtensions)) {
            return false;
        }
        return true;
    }

    /**
     * Lấy lịch sử hội thoại từ Zalo API (V2.0 Endpoint)
     */
    public function getConversation($zaloId, $offset = 0, $count = 10)
    {
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) {
            return ['error' => -1, 'message' => 'Access Token is empty'];
        }

        $url = "https://openapi.zalo.me/v2.0/oa/conversation";
        $data = [
            'user_id' => $zaloId,
            'offset'  => $offset,
            'count'   => $count
        ];

        $result = $this->callZaloApi($url, $data, 'GET');

        // Retry nếu token hết hạn (-216)
        if (isset($result['error']) && $result['error'] === -216) {
            log_message('notice', 'ZaloService::getConversation: Token expired, refreshing...');
            $refreshResult = $this->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->getConversation($zaloId, $offset, $count);
            }
        }

        return $result;
    }

    /**
     * Đồng bộ lịch sử tin nhắn của một khách hàng từ Zalo (V2.0 Endpoint)
     * Thích hợp cho cả nút bấm thủ công và Cronjob tự động quét.
     * 
     * @param string $zaloId ID của khách hàng trên Zalo
     * @param int $days Số ngày gần nhất cần đồng bộ (mặc định là 7 ngày)
     * @return array ['status' => 'success/error', 'count' => X, 'message' => '...']
     */
    public function syncConversation($zaloId, $days = 7)
    {
        $followerModel = new \App\Models\ZaloFollowerModel();
        $messageModel = new \App\Models\ZaloMessageModel();

        $follower = $followerModel->where('zalo_id', $zaloId)->first();
        if (!$follower) {
            // Thử tạo follower mới nếu chưa có
            $profile = $this->getProfile($zaloId);
            $followerId = $followerModel->insert([
                'zalo_id' => $zaloId,
                'display_name' => (!empty($profile['display_name']) && $profile['display_name'] !== 'Khách Zalo') ? $profile['display_name'] : 'Khách Zalo',
                'avatar_url' => $profile['avatars']['240'] ?? ($profile['avatar'] ?? null),
                'mid_code' => 'ZALO-' . strtoupper(substr(md5($zaloId . time()), 0, 6)),
                'tags' => json_encode(['New']),
            ]);
            if (!$followerId) {
                return ['status' => 'error', 'message' => 'Không thể khởi tạo khách hàng.'];
            }
            $follower = $followerModel->find($followerId);
        }

        $minTime = strtotime("-$days days");
        $allZaloMsgs = [];

        // Lấy 2 trang tin nhắn gần nhất từ Zalo (đồng bộ tối đa 20 tin để tối ưu)
        $pages = [
            ['offset' => 0, 'count' => 10],
            ['offset' => 10, 'count' => 10]
        ];

        foreach ($pages as $p) {
            $result = $this->getConversation($zaloId, $p['offset'], $p['count']);
            if (isset($result['error']) && $result['error'] === 0 && !empty($result['data'])) {
                $allZaloMsgs = array_merge($allZaloMsgs, $result['data']);
            } else {
                // Nếu trang đầu tiên lỗi thì trả về lỗi luôn
                if ($p['offset'] === 0) {
                    $errorMsg = $result['message'] ?? 'Không thể kết nối API Zalo để đồng bộ.';
                    return ['status' => 'error', 'message' => $errorMsg];
                }
            }
        }

        if (empty($allZaloMsgs)) {
            return ['status' => 'success', 'count' => 0, 'message' => 'Không có tin nhắn nào trên Zalo.'];
        }

        // 1. Sắp xếp toàn bộ tin nhắn từ Zalo theo thời gian tăng dần (cũ trước mới sau)
        // để khi chèn vào DB, các ID tự tăng sẽ trùng khớp với trình tự thời gian
        usort($allZaloMsgs, function($a, $b) {
            $t1 = $a['time'] ?? 0;
            $t2 = $b['time'] ?? 0;
            return $t1 <=> $t2;
        });

        $newMsgsCount = 0;

        foreach ($allZaloMsgs as $zMsg) {
            $zaloMsgId = $zMsg['message_id'] ?? '';
            if (empty($zaloMsgId)) continue;

            $msgTime = isset($zMsg['time']) ? ($zMsg['time'] / 1000) : time();
            
            // Chỉ đồng bộ tin nhắn trong phạm vi số ngày chỉ định
            if ($msgTime < $minTime) {
                continue;
            }

            // Kiểm tra trùng lặp
            $existingMsg = $messageModel->where('zalo_msg_id', $zaloMsgId)->first();
            if ($existingMsg) {
                continue;
            }

            // Ánh xạ nội dung tin nhắn
            $messageText = '';
            $attachments = null;
            $msgType = $zMsg['type'] ?? 'text';

            switch ($msgType) {
                case 'text':
                    $messageText = $zMsg['message'] ?? '';
                    break;
                case 'photo':
                case 'image':
                case 'GIF':
                    $messageText = '[Hình ảnh]';
                    $imageUrl = $zMsg['url'] ?? ($zMsg['thumb'] ?? '');
                    if ($imageUrl) {
                        $attachments = json_encode([[
                            'type' => 'image',
                            'payload' => ['url' => $imageUrl]
                        ]]);
                    }
                    break;
                case 'sticker':
                    $messageText = '[Sticker]';
                    $stickerUrl = $zMsg['url'] ?? '';
                    if ($stickerUrl) {
                        $attachments = json_encode([[
                            'type' => 'sticker',
                            'payload' => ['url' => $stickerUrl]
                        ]]);
                    }
                    break;
                case 'file':
                    $messageText = '[Tệp tin]';
                    $fileUrl = $zMsg['url'] ?? '';
                    if ($fileUrl) {
                        $attachments = json_encode([[
                            'type' => 'file',
                            'payload' => [
                                'url' => $fileUrl,
                                'name' => $zMsg['message'] ?? 'Tệp đính kèm'
                            ]
                        ]]);
                    }
                    break;
                case 'voice':
                case 'audio':
                    $messageText = '[Tin nhắn thoại]';
                    $voiceUrl = $zMsg['url'] ?? '';
                    if ($voiceUrl) {
                        $attachments = json_encode([[
                            'type' => 'audio',
                            'payload' => [
                                'url' => $voiceUrl,
                                'name' => 'Tin nhắn thoại.mp3'
                            ]
                        ]]);
                    }
                    break;
                default:
                    $messageText = $zMsg['message'] ?? '';
                    break;
            }

            $senderType = ($zMsg['src'] == 1) ? 'user' : 'oa';
            $createdAt = date('Y-m-d H:i:s', $msgTime);

            $messageModel->insert([
                'zalo_msg_id'  => $zaloMsgId,
                'follower_id'  => $follower['id'],
                'sender_type'  => $senderType,
                'message_text' => $messageText,
                'attachments'  => $attachments,
                'created_at'   => $createdAt
            ]);

            $newMsgsCount++;
        }

        // Cập nhật lại thời gian tương tác cuối cùng của khách hàng
        $followerModel->update($follower['id'], ['updated_at' => date('Y-m-d H:i:s')]);

        return [
            'status' => 'success',
            'count' => $newMsgsCount,
            'message' => "Đồng bộ thành công! Đã tải về {$newMsgsCount} tin nhắn mới phát sinh."
        ];
    }
}
