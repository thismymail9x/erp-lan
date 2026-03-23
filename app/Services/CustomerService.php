<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\CustomerPaymentModel;
use App\Models\CaseModel;

/**
 * CustomerService
 * 
 * Lớp dịch vụ chứa logic nghiệp vụ chuyên sâu cho Module Khách hàng.
 * Tách biệt logic xử lý tính toán khỏi Controller để đảm bảo tính tái sử dụng và dễ kiểm trì.
 */
class CustomerService
{
    protected $customerModel;
    protected $paymentModel;
    protected $caseModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->paymentModel  = new CustomerPaymentModel();
        $this->caseModel     = new CaseModel();
    }

    /**
     * Tìm kiếm khách hàng trùng lặp dựa trên SĐT, Email hoặc Số định danh
     * Giúp nhân viên tránh tạo trùng hồ sơ khách hàng đã có trong hệ thống.
     * 
     * @param array $data Dữ liệu khách hàng mới (từ form)
     * @return array Danh sách các trường bị trùng kèm thông tin hồ sơ cũ
     */
    public function findDuplicates(array $data)
    {
        $duplicates = [];

        // 1. Kiểm tra trùng số điện thoại
        if (!empty($data['phone'])) {
            $found = $this->customerModel->where('phone', $data['phone'])->first();
            if ($found) $duplicates['phone'] = $found;
        }

        // 2. Kiểm tra trùng số CCCD/Passport
        if (!empty($data['identity_number'])) {
            $found = $this->customerModel->where('identity_number', $data['identity_number'])->first();
            if ($found) $duplicates['identity_number'] = $found;
        }

        // 3. Kiểm tra trùng địa chỉ email
        if (!empty($data['email'])) {
            $found = $this->customerModel->where('email', $data['email'])->first();
            if ($found) $duplicates['email'] = $found;
        }

        return $duplicates;
    }

    /**
     * Đồng bộ và lưu bộ nhớ đệm (Cache) các chỉ số tài chính và vụ việc
     * Giúp Dashboard tải nhanh hơn bằng cách không phải tính toán lại từ đầu mỗi khi xem.
     * 
     * @param int $customerId ID khách hàng cần đồng bộ
     * @return bool Trạng thái cập nhật thành công hay thất bại
     */
    public function syncCustomerStats(int $customerId)
    {
        // 1. Tính toán tổng doanh thu từ bảng thanh toán
        $totalRevenue = $this->paymentModel->getTotalRevenue($customerId);

        // 2. Đếm tổng số vụ việc mà khách hàng này tham gia
        $totalCases = $this->caseModel->where('customer_id', $customerId)->countAllResults();

        // 3. Cập nhật các trường cache vào bảng Customers
        return $this->customerModel->update($customerId, [
            'total_revenue' => $totalRevenue,
            'total_cases'   => $totalCases,
            'updated_at'    => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Lấy các thông số thống kê phục vụ CRM Dashboard
     * 
     * @return array Mảng chứa thông tin Tổng KH, KH mới, Top doanh thu và Phân loại
     */
    public function getDashboardStats()
    {
        return [
            // Tổng số khách hàng hiện có
            'total_customers' => $this->customerModel->countAllResults(),
            
            // Số lượng khách hàng mới tiếp nhận trong tháng hiện tại
            'new_this_month'  => $this->customerModel->where('MONTH(created_at)', date('m'))
                                                     ->where('YEAR(created_at)', date('Y'))
                                                     ->countAllResults(),
            
            // Danh sách 10 khách hàng đem lại doanh thu cao nhất
            'top_revenue'     => $this->customerModel->orderBy('total_revenue', 'DESC')
                                                     ->limit(10)
                                                     ->findAll(),
            
            // Phân bổ tỉ lệ giữa khách hàng Cá nhân và Doanh nghiệp
            'type_distribution' => $this->customerModel->select('type, COUNT(*) as count')
                                                       ->groupBy('type')
                                                       ->findAll()
        ];
    }

    /**
     * Lọc danh sách khách hàng "bỏ ngỏ" (Stale Customers)
     * Nhận diện các khách hàng quá lâu không có tương tác để triển khai chiến dịch chăm sóc lại.
     * 
     * @param int $days Số ngày không tương tác tối thiểu (mặc định 30 ngày)
     * @return array Danh sách khách hàng cần chăm sóc
     */
    public function getStaleCustomers(int $days = 30)
    {
        // Tính mốc thời gian giới hạn
        $thresholdDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $this->customerModel->groupStart()
                                   ->where('last_contact_date <', $thresholdDate)
                                   ->orWhere('last_contact_date', null)
                                   ->groupEnd()
                                   ->orderBy('last_contact_date', 'ASC') // Ưu tiên những người lâu nhất lên đầu
                                   ->findAll();
    }
}
