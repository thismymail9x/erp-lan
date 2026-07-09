<?php

namespace App\Services;

use Config\Zalo as ZaloConfig;

/**
 * ZnsService
 * 
 * Dịch vụ quản lý Zalo Notification Service (ZNS).
 * Phụ trách: Gửi thông báo ZNS qua API, quản lý template, xử lý chiến dịch hàng loạt.
 * 
 * Sử dụng ZBS Template Message API mới nhất:
 * - Endpoint gửi: https://business.openapi.zalo.me/message/template
 * - Endpoint thông tin template: https://business.openapi.zalo.me/template/info/v2
 * 
 * Token truy cập được tái sử dụng từ hệ thống Zalo OA hiện có (system_settings).
 */
class ZnsService
{
    protected $config;
    protected $db;
    public $lastError = '';

    public function __construct()
    {
        $this->config = new ZaloConfig();
        $this->db = \Config\Database::connect();
        
        // Tải token mới nhất từ Database (cùng cơ chế với ZaloService)
        $this->loadTokensFromDb();
    }

    /**
     * Tải Access Token từ bảng system_settings
     * Tái sử dụng token từ hệ thống Zalo OA hiện có
     */
    public function loadTokensFromDb()
    {
        try {
            $settingModel = new \App\Models\SystemSettingModel();
            $accessToken = $settingModel->find('zalo_access_token');
            $refreshToken = $settingModel->find('zalo_refresh_token');
            
            if ($accessToken && !empty($accessToken['value'])) {
                $this->config->accessToken = $accessToken['value'];
            }
            if ($refreshToken && !empty($refreshToken['value'])) {
                $this->config->refreshToken = $refreshToken['value'];
            }
        } catch (\Exception $e) {
            log_message('error', 'ZnsService::loadTokensFromDb error: ' . $e->getMessage());
        }
    }

    /**
     * Gửi một tin nhắn ZNS tới khách hàng qua API ZBS Template Message
     * 
     * @param string $phone Số điện thoại người nhận (format 84xxx hoặc 0xxx)
     * @param string $templateId ID mẫu tin đã được Zalo phê duyệt
     * @param array $templateData Dữ liệu điền vào các biến của template
     * @param string|null $trackingId ID theo dõi nội bộ (tùy chọn)
     * @return array Kết quả từ Zalo API: ['error' => 0, 'message' => 'Success', 'data' => [...]]
     */
    public function sendZns(string $phone, string $templateId, array $templateData = [], ?string $trackingId = null, int $retryCount = 0): array
    {
        $zaloService = new \App\Services\ZaloService();
        $tokens = $zaloService->getValidTokens();
        if (isset($tokens['access_token'])) {
            $this->config->accessToken = $tokens['access_token'];
            $this->config->refreshToken = $tokens['refresh_token'] ?? $this->config->refreshToken;
        }
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) {
            return ['error' => -1, 'message' => 'Access Token rỗng. Vui lòng kết nối lại Zalo OA.'];
        }

        // Chuẩn hóa số điện thoại sang định dạng quốc tế 84xxx
        $phone = $this->formatPhoneVN($phone);
        if (empty($phone)) {
            return ['error' => -2, 'message' => 'Số điện thoại không hợp lệ.'];
        }

        $url = 'https://business.openapi.zalo.me/message/template';
        
        $payload = [
            'phone'         => $phone,
            'template_id'   => $templateId,
            'template_data' => $templateData,
        ];

        // Thêm tracking_id nếu có (dùng cho nội bộ)
        if ($trackingId) {
            $payload['tracking_id'] = $trackingId;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->lastError = 'cURL error: ' . $curlErr;
            log_message('error', 'ZnsService::sendZns cURL error: ' . $curlErr);
            return ['error' => -99, 'message' => $this->lastError];
        }

        $result = json_decode($response, true);
        if (!$result) {
            return ['error' => -98, 'message' => 'Phản hồi không hợp lệ từ Zalo: ' . $response];
        }

        // Tự động refresh token nếu hết hạn (error code -216)
        if ($retryCount < 1 && $this->isTokenInvalidResponse($result)) {
            log_message('notice', 'ZnsService::sendZns: Token hết hạn, đang làm mới...');
            $refreshResult = $zaloService->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                // Thử gửi lại sau khi refresh token
                return $this->sendZns($phone, $templateId, $templateData, $trackingId, $retryCount + 1);
            }
        }

        // Ghi log kết quả gửi
        if (isset($result['error']) && $result['error'] !== 0) {
            $this->lastError = $result['message'] ?? 'Lỗi không xác định';
            log_message('error', 'ZnsService::sendZns failed: ' . json_encode($result));
        } else {
            log_message('info', 'ZnsService::sendZns success: phone=' . $phone . ', template=' . $templateId);
        }

        return $result;
    }

    /**
     * Lấy thông tin chi tiết một template ZNS từ Zalo API
     * 
     * @param string $templateId ID mẫu tin cần tra cứu
     * @return array Thông tin template hoặc mảng lỗi
     */
    public function getTemplateInfo(string $templateId, int $retryCount = 0): array
    {
        $zaloService = new \App\Services\ZaloService();
        $tokens = $zaloService->getValidTokens();
        if (isset($tokens['access_token'])) {
            $this->config->accessToken = $tokens['access_token'];
            $this->config->refreshToken = $tokens['refresh_token'] ?? $this->config->refreshToken;
        }
        $accessToken = $this->config->accessToken;
        if (empty($accessToken)) {
            return ['error' => -1, 'message' => 'Access Token rỗng.'];
        }

        $url = 'https://business.openapi.zalo.me/template/info/v2?template_id=' . urlencode($templateId);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "access_token: {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        
        // Retry nếu token hết hạn
        if ($retryCount < 1 && $this->isTokenInvalidResponse($result ?: [])) {
            $refreshResult = $zaloService->refreshToken($this->config->refreshToken);
            if (isset($refreshResult['access_token'])) {
                $this->config->accessToken = $refreshResult['access_token'];
                $this->config->refreshToken = $refreshResult['refresh_token'];
                return $this->getTemplateInfo($templateId, $retryCount + 1);
            }
        }

        return $result ?: ['error' => -99, 'message' => 'Phản hồi rỗng từ Zalo.'];
    }

    /**
     * Gửi ZNS hàng loạt cho danh sách khách hàng (từ bulk action hoặc chiến dịch)
     * 
     * Quy trình:
     * 1. Lấy thông tin KH từ danh sách ID
     * 2. Với mỗi KH: build template_data → gửi ZNS → ghi log
     * 3. Cập nhật thống kê chiến dịch (nếu có campaign_id)
     * 
     * @param array $customerIds Mảng ID khách hàng
     * @param string $templateId ID mẫu tin Zalo (từ bảng zns_templates hoặc ID mẫu tin Zalo)
     * @param array $dataMapping Mapping biến template → trường KH. Ví dụ: ['customer_name' => 'name', 'phone' => 'phone']
     * @param int|null $campaignId ID chiến dịch (NULL nếu gửi nhanh từ trang KH)
     * @param int|null $sentBy ID nhân sự thực hiện gửi
     * @return array ['total' => X, 'success' => Y, 'fail' => Z, 'errors' => [...]]
     */
    private function isTokenInvalidResponse(array $result): bool
    {
        if (in_array((int)($result['error'] ?? 0), [-216, -201], true)) {
            return true;
        }

        $message = strtolower(implode(' ', array_filter([
            (string)($result['message'] ?? ''),
            (string)($result['error_description'] ?? ''),
            (string)($result['error_name'] ?? ''),
        ])));

        return strpos($message, 'access token') !== false
            && (strpos($message, 'invalid') !== false || strpos($message, 'expired') !== false);
    }

    public function sendBulkZns(array $customerIds, string $templateId, array $dataMapping = [], ?int $campaignId = null, ?int $sentBy = null): array
    {
        $customerModel = new \App\Models\CustomerModel();
        $logModel = new \App\Models\ZnsLogModel();
        $campaignModel = new \App\Models\ZnsCampaignModel();

        // Lấy template_id thực tế từ bảng zns_templates (trường hợp truyền vào là ID record)
        $templateModel = new \App\Models\ZnsTemplateModel();
        
        // Thử tìm theo ID cột khóa chính trước
        $templateRecord = null;
        if (is_numeric($templateId)) {
            $templateRecord = $templateModel->find($templateId);
        }
        
        // Nếu không tìm thấy, thử tìm theo template_id (chuỗi ID từ Zalo)
        if (!$templateRecord) {
            $templateRecord = $templateModel->where('template_id', $templateId)->first();
        }
        
        $zaloTemplateId = $templateRecord ? $templateRecord['template_id'] : $templateId;

        // Fallback: Nếu dataMapping rỗng, dùng default_mappings đã cấu hình trong template
        if (empty($dataMapping) && $templateRecord && !empty($templateRecord['default_mappings'])) {
            $defaultMappings = $templateRecord['default_mappings'];
            if (is_string($defaultMappings)) {
                $defaultMappings = json_decode($defaultMappings, true) ?: [];
            } elseif (is_object($defaultMappings)) {
                $defaultMappings = (array)$defaultMappings;
            }
            if (!empty($defaultMappings)) {
                $dataMapping = $defaultMappings;
                log_message('info', "ZnsService::sendBulkZns - dataMapping rỗng, dùng default_mappings từ template #{$templateRecord['id']}");
            }
        }

        if (empty($dataMapping)) {
            log_message('warning', "ZnsService::sendBulkZns - dataMapping vẫn rỗng sau fallback, template_data sẽ trống. templateId={$zaloTemplateId}");
        }

        $results = ['total' => count($customerIds), 'success' => 0, 'fail' => 0, 'errors' => []];

        // Cập nhật trạng thái chiến dịch sang "đang gửi"
        if ($campaignId) {
            $campaignModel->update($campaignId, [
                'status'     => 'sending',
                'started_at' => date('Y-m-d H:i:s'),
                'total_recipients' => count($customerIds)
            ]);
        }

        foreach ($customerIds as $customerId) {
            $customer = $customerModel->find($customerId);
            if (!$customer) {
                $results['fail']++;
                $results['errors'][] = "KH #{$customerId}: Không tìm thấy.";
                continue;
            }

            // Ưu tiên sử dụng zalo_phone, sau đó phone, cuối cùng phone_secondary
            $phone = (!empty($customer['zalo_phone'])) ? $customer['zalo_phone'] : ((!empty($customer['phone'])) ? $customer['phone'] : ((!empty($customer['phone_secondary'])) ? $customer['phone_secondary'] : ''));
            if (empty($phone)) {
                $results['fail']++;
                $results['errors'][] = esc($customer['name']) . ": Không có SĐT.";
                
                // Ghi log thất bại
                $logModel->insert([
                    'campaign_id'   => $campaignId,
                    'customer_id'   => $customerId,
                    'template_id'   => $zaloTemplateId,
                    'phone'         => '',
                    'template_data' => null,
                    'status'        => 'failed',
                    'error_code'    => -2,
                    'error_message' => 'Khách hàng không có số điện thoại',
                    'sent_by'       => $sentBy,
                    'sent_at'       => date('Y-m-d H:i:s'),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
                continue;
            }

            // Build dữ liệu template từ mapping
            $templateData = $this->buildTemplateData($customer, $dataMapping);

            // Tạo tracking_id duy nhất để theo dõi nội bộ
            $trackingId = 'erp_' . ($campaignId ?: 'quick') . '_' . $customerId . '_' . time();

            // Gửi ZNS qua API Zalo
            $apiResult = $this->sendZns($phone, $zaloTemplateId, $templateData, $trackingId);

            // Xác định trạng thái gửi
            $logStatus = 'failed';
            $errorCode = null;
            $errorMessage = null;
            $zaloMsgId = null;

            if (isset($apiResult['error']) && $apiResult['error'] === 0) {
                $logStatus = 'sent';
                $zaloMsgId = $apiResult['data']['msg_id'] ?? null;
                $results['success']++;
            } else {
                $errorCode = $apiResult['error'] ?? -99;
                $errorMessage = $apiResult['message'] ?? 'Lỗi không xác định';
                $results['fail']++;
                $results['errors'][] = esc($customer['name']) . ": " . $errorMessage;
            }

            // Ghi log từng tin nhắn đã gửi
            $logModel->insert([
                'campaign_id'   => $campaignId,
                'customer_id'   => $customerId,
                'template_id'   => $zaloTemplateId,
                'phone'         => $this->formatPhoneVN($phone),
                'template_data' => json_encode($templateData, JSON_UNESCAPED_UNICODE),
                'status'        => $logStatus,
                'zalo_msg_id'   => $zaloMsgId,
                'error_code'    => $errorCode,
                'error_message' => $errorMessage,
                'sent_by'       => $sentBy,
                'sent_at'       => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            // Tạm dừng 100ms giữa mỗi request để tránh rate limit
            usleep(100000);
        }

        // Cập nhật thống kê chiến dịch khi hoàn thành
        if ($campaignId) {
            $finalStatus = ($results['fail'] === $results['total']) ? 'failed' : 'completed';
            $campaignModel->update($campaignId, [
                'status'        => $finalStatus,
                'sent_count'    => $results['success'] + $results['fail'],
                'success_count' => $results['success'],
                'fail_count'    => $results['fail'],
                'completed_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $results;
    }

    /**
     * Thực thi một chiến dịch ZNS đã tạo
     * Lấy danh sách KH từ filter_criteria hoặc customer_ids → gọi sendBulkZns
     * 
     * @param int $campaignId ID chiến dịch cần thực thi
     * @param int $sentBy ID nhân sự thực hiện
     * @return array Kết quả gửi
     */
    public function executeCampaign(int $campaignId, int $sentBy): array
    {
        $campaignModel = new \App\Models\ZnsCampaignModel();
        $campaign = $campaignModel->find($campaignId);

        if (!$campaign) {
            return ['error' => true, 'message' => 'Chiến dịch không tồn tại.'];
        }

        if ($campaign['status'] !== 'draft') {
            return ['error' => true, 'message' => 'Chiến dịch đã được thực thi trước đó (trạng thái: ' . $campaign['status'] . ').'];
        }

        // Xác định danh sách KH mục tiêu
        $customerIds = $this->resolveCustomerIds($campaign);

        if (empty($customerIds)) {
            return ['error' => true, 'message' => 'Không tìm thấy khách hàng nào phù hợp với tiêu chí lọc.'];
        }

        // Lấy mapping dữ liệu template an toàn
        $dataMapping = [];
        if (isset($campaign['template_data_mapping'])) {
            if (is_array($campaign['template_data_mapping'])) {
                $dataMapping = $campaign['template_data_mapping'];
            } elseif (is_object($campaign['template_data_mapping'])) {
                $dataMapping = (array)$campaign['template_data_mapping'];
            } elseif (is_string($campaign['template_data_mapping'])) {
                $dataMapping = json_decode($campaign['template_data_mapping'], true) ?: [];
            }
        }

        // Gửi hàng loạt
        return $this->sendBulkZns($customerIds, (string)$campaign['zns_template_id'], $dataMapping, $campaignId, $sentBy);
    }

    /**
     * Phân giải danh sách ID khách hàng từ chiến dịch
     * Ưu tiên customer_ids (chọn thủ công) → nếu rỗng thì dùng filter_criteria
     * 
     * @param array $campaign Dữ liệu chiến dịch
     * @return array Mảng ID khách hàng
     */
    private function resolveCustomerIds(array $campaign): array
    {
        // Nếu có danh sách KH đã chọn sẵn → dùng luôn an toàn
        $manualIds = [];
        if (isset($campaign['customer_ids'])) {
            if (is_array($campaign['customer_ids'])) {
                $manualIds = $campaign['customer_ids'];
            } elseif (is_object($campaign['customer_ids'])) {
                $manualIds = (array)$campaign['customer_ids'];
            } elseif (is_string($campaign['customer_ids'])) {
                $manualIds = json_decode($campaign['customer_ids'], true) ?: [];
            }
        }
        if (!empty($manualIds)) {
            return $manualIds;
        }

        // Nếu không → build query từ filter_criteria an toàn
        $filters = [];
        if (isset($campaign['filter_criteria'])) {
            if (is_array($campaign['filter_criteria'])) {
                $filters = $campaign['filter_criteria'];
            } elseif (is_object($campaign['filter_criteria'])) {
                $filters = (array)$campaign['filter_criteria'];
            } elseif (is_string($campaign['filter_criteria'])) {
                $filters = json_decode($campaign['filter_criteria'], true) ?: [];
            }
        }
        if (empty($filters)) {
            return [];
        }

        $customerModel = new \App\Models\CustomerModel();
        $query = $customerModel->select('id')
                               ->where('deleted_at IS NULL');

        // Lọc theo Tag
        if (!empty($filters['tag_id'])) {
            $tagService = new \App\Services\TagService();
            $taggedIds = $tagService->getEntityIdsByTag($filters['tag_id'], 'customers');
            if (!empty($taggedIds)) {
                $query->whereIn('id', $taggedIds);
            } else {
                return []; // Không có KH nào có tag này
            }
        }

        // Lọc theo Trạng thái tư vấn
        if (!empty($filters['care_status'])) {
            $query->where('care_status', $filters['care_status']);
        }

        // Lọc theo Phân khúc KH (VIP, Regular, Potential)
        if (!empty($filters['customer_segment'])) {
            $query->where('customer_segment', $filters['customer_segment']);
        }

        // Lọc theo Nhân sự tư vấn
        if (!empty($filters['care_staff_id'])) {
            $query->where('assigned_care_staff_id', $filters['care_staff_id']);
        }

        // Chỉ lấy KH có SĐT (bắt buộc để gửi ZNS)
        $query->groupStart()
              ->where('phone IS NOT NULL')->where('phone !=', '')
              ->orWhere('zalo_phone IS NOT NULL')->where('zalo_phone !=', '')
              ->orWhere('phone_secondary IS NOT NULL')->where('phone_secondary !=', '')
              ->groupEnd();

        $customers = $query->findAll();
        return array_column($customers, 'id');
    }

    /**
     * Build dữ liệu template từ thông tin KH dựa trên mapping
     * Ví dụ mapping: ['customer_name' => 'name', 'phone_number' => 'phone']
     * → template_data: ['customer_name' => 'Nguyễn Văn A', 'phone_number' => '0901234567']
     * 
     * @param array $customer Dữ liệu khách hàng từ DB
     * @param array $mapping Bảng ánh xạ: [tên_biến_template => tên_cột_KH]
     * @return array Dữ liệu đã map xong để gửi vào API
     */
    public function buildTemplateData(array $customer, array $mapping): array
    {
        $data = [];
        foreach ($mapping as $templateVar => $customerField) {
            // Hỗ trợ giá trị tĩnh (bắt đầu bằng #): vd '#Công ty Luật L.A.N' → truyền chuỗi cố định
            if (strpos($customerField, '#') === 0) {
                $data[$templateVar] = substr($customerField, 1);
            } elseif (array_key_exists($customerField, $customer)) {
                // Nếu là một cột thông tin thực tế của khách hàng trong DB
                $data[$templateVar] = $customer[$customerField] ?? '';
            } else {
                // HÀNG RÀO BẢO VỆ (Fallback): Nếu không tồn tại cột tương ứng trong CSDL KH, coi đây là chuỗi tĩnh luôn
                $data[$templateVar] = $customerField;
            }
        }
        return $data;
    }

    /**
     * Chuyển đổi SĐT Việt Nam sang định dạng quốc tế cho Zalo API
     * 0xxx → 84xxx | +84xxx → 84xxx | 84xxx giữ nguyên
     * 
     * @param string $phone Số điện thoại đầu vào
     * @return string SĐT đã chuẩn hóa (84xxx) hoặc rỗng nếu không hợp lệ
     */
    public function formatPhoneVN(string $phone): string
    {
        // Xóa khoảng trắng, dấu gạch, dấu chấm
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        
        // Xóa dấu + ở đầu
        $phone = ltrim($phone, '+');

        // Chuyển 0xxx → 84xxx
        if (preg_match('/^0(\d{9,10})$/', $phone, $m)) {
            $phone = '84' . $m[1];
        }

        // Kiểm tra định dạng 84 + 9-10 số
        if (!preg_match('/^84\d{9,10}$/', $phone)) {
            return '';
        }

        return $phone;
    }

    /**
     * Lấy danh sách KH phù hợp dựa trên bộ lọc (preview trước khi gửi)
     * 
     * @param array $filters Bộ lọc: tag_id, care_status, customer_segment, care_staff_id
     * @return array Danh sách KH có SĐT hợp lệ
     */
    public function previewRecipients(array $filters): array
    {
        $customerModel = new \App\Models\CustomerModel();
        $query = $customerModel->select('id, code, name, phone, zalo_phone, phone_secondary, care_status, customer_segment')
                               ->where('deleted_at IS NULL');

        // Áp dụng các bộ lọc
        if (!empty($filters['tag_id'])) {
            $tagService = new \App\Services\TagService();
            $taggedIds = $tagService->getEntityIdsByTag($filters['tag_id'], 'customers');
            if (!empty($taggedIds)) {
                $query->whereIn('id', $taggedIds);
            } else {
                return [];
            }
        }

        if (!empty($filters['care_status'])) {
            $query->where('care_status', $filters['care_status']);
        }

        if (!empty($filters['customer_segment'])) {
            $query->where('customer_segment', $filters['customer_segment']);
        }

        if (!empty($filters['care_staff_id'])) {
            $query->where('assigned_care_staff_id', $filters['care_staff_id']);
        }

        // Chỉ lấy KH có SĐT
        $query->groupStart()
              ->where('phone IS NOT NULL')->where('phone !=', '')
              ->orWhere('zalo_phone IS NOT NULL')->where('zalo_phone !=', '')
              ->orWhere('phone_secondary IS NOT NULL')->where('phone_secondary !=', '')
              ->groupEnd();

        return $query->orderBy('name', 'ASC')->findAll();
    }
}
