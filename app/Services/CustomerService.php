<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\CustomerPaymentModel;
use App\Models\CaseModel;

/**
 * CustomerService
 * 
 * Lớp Dịch vụ chuyên sâu quản lý Logic nghiệp vụ khách hàng.
 * Phụ trách:
 * 1. Thuật toán phát hiện hồ sơ trùng lặp (Deduplication).
 * 2. Cơ chế Đồng bộ và Cache dữ liệu thống kê khách hàng (Stats Hub).
 * 3. Phân tích hành vi và chăm sóc hậu mãi (Customer Engagement Analysis).
 */
class CustomerService
{
    protected $customerModel;
    protected $paymentModel;
    protected $caseModel;

    public function __construct()
    {
        // Khởi tạo các Model nòng cốt tham gia vào chuỗi dữ liệu khách hàng
        $this->customerModel = new CustomerModel();
        $this->paymentModel  = new CustomerPaymentModel();
        $this->caseModel     = new CaseModel();
    }

    /**
     * Thuật toán phát hiện trùng lặp hồ sơ khách hàng.
     * Kiểm tra chéo trên 3 tiêu chí định danh độc lập để đảm bảo tính duy nhất.
     * 
     * @param array $data Dữ liệu khách hàng mới (từ form tiếp nhận).
     * @return array Danh sách hồ sơ cũ bị trùng khớp.
     */
    public function findDuplicates(array $data)
    {
        $duplicates = [];

        // 1. TIÊU CHÍ 1: Số điện thoại (Phương thức liên lạc chính)
        if (!empty($data['phone'])) {
            $found = $this->customerModel->where('phone', $data['phone'])->first();
            if ($found) $duplicates['phone'] = $found;
        }

        // 2. TIÊU CHÍ 2: Số CCCD/Hộ chiếu/Mã số thuế (Định danh pháp lý)
        if (!empty($data['identity_number'])) {
            $found = $this->customerModel->where('identity_number', $data['identity_number'])->first();
            if ($found) $duplicates['identity_number'] = $found;
        }

        // 3. TIÊU CHÍ 3: Địa chỉ Email
        if (!empty($data['email'])) {
            $found = $this->customerModel->where('email', $data['email'])->first();
            if ($found) $duplicates['email'] = $found;
        }

        return $duplicates;
    }

    /**
     * Cơ chế Đồng bộ chỉ số (Sync & Cache Stats).
     * Thực hiện tính toán lại toàn bộ doanh thu và số lượng vụ việc của 1 khách hàng.
     * Giúp hệ thống không bị chậm khi xem danh sách khách hàng hàng ngàn bản ghi.
     * 
     * @param int $customerId ID khách hàng cần đồng bộ.
     * @return bool
     */
    public function syncCustomerStats(int $customerId)
    {
        // 1. Tính tổng hóa đơn/thanh toán thực tế từ bảng Payment
        $totalRevenue = $this->paymentModel->getTotalRevenue($customerId);

        // 2. Đếm số lượng hồ sơ vụ việc đang hoặc đã thực hiện
        $totalCases = $this->caseModel->where('customer_id', $customerId)->countAllResults();

        // 3. Ghi đè vào các trường Cache trong bảng Customers để truy xuất tức thời
        return $this->customerModel->update($customerId, [
            'total_revenue' => $totalRevenue,
            'total_cases'   => $totalCases,
            'updated_at'    => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Tổng hợp dữ liệu KPI cho CRM Dashboard.
     * Thống kê theo thời gian thực về tăng trưởng và chất lượng khách hàng.
     */
    public function getDashboardStats()
    {
        return [
            // Tổng quy mô tệp khách hàng
            'total_customers' => $this->customerModel->countAllResults(),
            
            // Số lượng khách hàng mới gia nhập trong tháng (Tốc độ tăng trưởng)
            'new_this_month'  => $this->customerModel->where('MONTH(created_at)', date('m'))
                                                     ->where('YEAR(created_at)', date('Y'))
                                                     ->countAllResults(),
            
            // Danh sách TOP 10 khách hàng VIP (Dựa trên tổng doanh thu mang lại)
            'top_revenue'     => $this->customerModel->orderBy('total_revenue', 'DESC')
                                                     ->limit(10)
                                                     ->findAll(),
            
            // Biểu đồ phân bổ loại hình khách hàng (Group by Category)
            'type_distribution' => $this->customerModel->select('type, COUNT(*) as count')
                                                       ->groupBy('type')
                                                       ->findAll()
        ];
    }

    /**
     * Phân tích tệp khách hàng "Bị bỏ quên" (Dormant/Stale Customer).
     * Lọc ra những người có Engagement thấp để đội ngũ kinh doanh có kế hoạch chăm sóc.
     * 
     * @param int $days Ngưỡng thời gian coi là "bỏ ngỏ" (Mặc định 30 ngày).
     */
    public function getStaleCustomers(int $days = 30)
    {
        // Xác định mốc thời gian tối hạn để coi là mất tương tác
        $thresholdDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $this->customerModel->groupStart()
                                   // Khách hàng có ngày liên lạc cuối > 30 ngày
                                   ->where('last_contact_date <', $thresholdDate)
                                   // HOẶC khách hàng chưa bao giờ phát sinh tương tác (Mới gán nhưng chưa chăm)
                                   ->orWhere('last_contact_date', null)
                                   ->groupEnd()
                                   // Sắp xếp người "cũ nhất" lên ưu tiên hàng đầu
                                   ->orderBy('last_contact_date', 'ASC')
                                   ->findAll();
    }
}
