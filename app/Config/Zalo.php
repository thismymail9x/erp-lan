<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Zalo extends BaseConfig
{
    public function __construct()
    {
        parent::__construct();

        // Access/refresh token phai duoc nap tu system_settings hoac bien moi truong.
        // Khong dung token cung trong file lam fallback vi token Zalo duoc xoay dinh ky.
        $this->appId = (string) env('zalo.appId', $this->appId);
        $this->appSecret = (string) env('zalo.appSecret', $this->appSecret);
        $this->accessToken = (string) env('zalo.accessToken', '');
        $this->refreshToken = (string) env('zalo.refreshToken', '');
    }

    /**
     * Zalo OA App ID
     */
    public string $appId = '4318908317306358870';

    /**
     * Zalo OA App Secret
     */
    public string $appSecret = '72XV962RcaBE87FWCd6N';

    /**
     * Zalo OA Access Token (Short-lived, updated via Refresh Token)
     */
    public string $accessToken = '0Qo49yNRJs48fumWpjX8PGwlWYJBpXG4PhZG8UgjGs4eqz8bgEH59nJZ_H2DmrzN8hx_POEPVtyKvibWXC18SWBbr3Man49qDk7YKOwOLMyVXEHFhebwJZdtpK-jp4W87lRCDONoNHG3ZPeLix0hA2B6bm6wsX8o3EEd8SY131rmW8iYwxKB3dsui0_2e10oNAhK8ldNGIDswVe3ilb_5723vnBLfa8-Gupq3UcAD3LPyO0GcDyQ3oxqeX-Bp3vV8S2j7Ost1Z4sZOuNzQuk1awKb2IFh04b5uMmARRyAJqmmgqxXhukVogZZ36yZaae2923PvY81tqK_hjKqUS3MXVTat-mmJXH4iomQD3ACtSMzu1ilEiBNWYLfcUrxX5h0PQGJRcO3LyOaf9GcQy3G0VMktMs-oTAB-M5Q_LFydR0pK9Y';

    /**
     * Zalo OA Refresh Token (Long-lived)
     */
    public string $refreshToken = 'Rymd1QKv_NXcnoy2hZhwV63F8GQhBFO02B9A6fyJt6G2cduQbGJDBpIH6msRRgiM6PKjBDOvYG1vc1C5q3wHU6Y194NhDezWU9zR6D0bZJzitaCW_ckPDMl30XkZUe9YBi4jOxP-XLTwtYz3kIQAJ0cT26Uw0w0VURylDh0VkWX_fXSTxpEoF7YgQIYoDTus4SXRGxfVxK1nes5f-WJiHqBvHMxTK9DR4B0qJDiKX49ZbWrR-I-w83VNEoghRhPIQ9OMLimMftrYjtnuzmtTG3c6J6QmF-0V3yHQ6hq_sZKtdWy8ZWEcQ4UFT6dtSD1TRlviQC9Lmc0EuXKPan-v03kJE3UkQPCY7SeL8QmrcYKGYJq_iHxj6m-aJpUH7Pyu7A0D3Q05soHl_bqmnMhRItcH25EqEwrP-ytZJQ0ly7i';

    /**
     * Webhook Secret Key (Used for signature verification)
     */
    public string $webhookSecret = '';
}
