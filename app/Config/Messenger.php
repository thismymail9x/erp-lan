<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Messenger Config
 * 
 * Lưu trữ cấu hình kết nối Facebook Messenger API.
 * Tương tự kiến trúc Zalo.php - các token nhạy cảm được lưu vào DB thông qua MessengerService.
 * File này chỉ chứa thông tin cơ bản (App ID, App Secret). 
 * Token thực tế được tải từ bảng system_settings để có thể cập nhật linh hoạt.
 */
class Messenger extends BaseConfig
{
    /**
     * Facebook App ID (Lấy từ Meta for Developers > Your App > App Dashboard)
     */
    public string $appId = '1311206061156213';

    /**
     * Facebook App Secret (Lấy từ Meta for Developers > Your App > Settings > Basic)
     */
    public string $appSecret = '6f85db26ed5049779538f2849dfc2e2c';

    /**
     * Page Access Token (Cấp quyền cho OA tương tác với Messenger, thay thế User Token)
     * Token này được tải động từ DB trong MessengerService::__construct()
     */
    public string $pageAccessToken = 'EAASoiPOAp3UBRrIKXyK1p0VGD9AVXGLUEwEJItS2FBWfq4MeWLLDHykhb7yKAdZCxevw7TGwT06yIyJF1u7cMkg1LwAsvY2iV6Y740uDga7xT2P0ehY2rWwq2L2GG3yylL7w5GZCLRyrSR5vjvtevaEq0Qk1CJRIpnKCZAZASIPVmpspMlAgR51HdZAY0NnXsiEOXEiPWKOVSheLZCPC3TdMk45QZDZD';

    /**
     * Verify Token do mình tự định nghĩa (Dùng để xác minh Webhook với Meta)
     * Phải khớp với giá trị khai báo trên Meta App Dashboard > Webhooks
     */
    public string $verifyToken = 'lan_erp_messenger_verify_2026';

    /**
     * App Secret Proof (HMAC-SHA256 của pageAccessToken với appSecret, dùng cho bảo mật API calls)
     * Không cần khai báo thủ công - MessengerService tự tính khi gọi API.
     */
    public string $appSecretProof = '';
}
