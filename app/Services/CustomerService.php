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
     * @param int|null $excludeId ID cần loại trừ khi kiểm tra (dùng cho trường hợp UPDATE).
     * @return array Danh sách hồ sơ cũ bị trùng khớp.
     */
    public function findDuplicates(array $data, ?int $excludeId = null)
    {
        $duplicates = [];

        // 1. TIÊU CHÍ 1: Số điện thoại (Phương thức liên lạc chính)
        if (!empty($data['phone'])) {
            $query = $this->customerModel->where('phone', $data['phone'])->where('deleted_at', null);
            if ($excludeId) $query->where('id !=', $excludeId);
            $found = $query->first();
            if ($found) $duplicates['phone'] = $found;
        }

        // 2. TIÊU CHÍ 2: Số CCCD/Hộ chiếu/Mã số thuế (Định danh pháp lý)
        if (!empty($data['identity_number'])) {
            $query = $this->customerModel->where('identity_number', $data['identity_number'])->where('deleted_at', null);
            if ($excludeId) $query->where('id !=', $excludeId);
            $found = $query->first();
            if ($found) $duplicates['identity_number'] = $found;
        }

        // 3. TIÊU CHÍ 3: Địa chỉ Email
        if (!empty($data['email'])) {
            $query = $this->customerModel->where('email', $data['email'])->where('deleted_at', null);
            if ($excludeId) $query->where('id !=', $excludeId);
            $found = $query->first();
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
     * 
     * @param int|null $employeeId Nếu truyền vào, chỉ thống kê khách hàng thuộc phạm vi nhân sự này.
     * @param int|null $departmentId Nếu truyền vào, thống kê khách hàng thuộc vụ việc của phòng ban này.
     */
    public function getDashboardStats($employeeId = null, $departmentId = null, $managerId = null)
    {
        // 1. Khai báo hàm helper để áp dụng bộ lọc phân quyền (Data Scoping)
        $applyScope = function($query) use ($employeeId, $departmentId, $managerId) {
            $db = \Config\Database::connect();

            // Sửa lỗi: Do dùng raw builder() thay vì model nên phải tự handle soft deletes
            $query->where('customers.deleted_at', null);

            if ($managerId) {
                // TRƯỞNG PHÒNG (TEAM-BASED): Thống kê dựa trên "Quân" của chính sếp đó
                $myTeamIds = $db->table('employees')->where('manager_id', $managerId)->select('id')->get()->getResultArray();
                $myTeamIds = array_column($myTeamIds, 'id');
                $myTeamIds[] = $managerId; // Bao gồm cả sếp

                $query->whereIn('customers.id', function($builder) use ($myTeamIds, $departmentId) {
                    $builder->select('customer_id')->from('cases')->groupStart();
                        // A. Hồ sơ của đội
                        $builder->groupStart()
                            ->whereIn('assigned_lawyer_id', $myTeamIds)
                            ->orWhereIn('assigned_staff_id', $myTeamIds)
                            ->orWhereIn('id', function($sub) use ($myTeamIds) {
                                return $sub->select('case_id')->from('case_members')->whereIn('employee_id', $myTeamIds);
                            })
                        ->groupEnd();

                            // B. NGOẠI LỆ PHÁP LÝ: Thấy khách hàng mồ côi
                            if ($departmentId == \Config\AppConstants::DEPT_PHAP_LY) {
                                $builder->orGroupStart()
                                    ->where('assigned_lawyer_id IS NULL')
                                    ->where('assigned_staff_id IS NULL')
                                ->groupEnd();
                            }
                        $builder->groupEnd();
                });
            } elseif ($employeeId) {
                // NHÂN VIÊN: Là người phụ trách chính (Lawyer/Staff) hoặc thành viên
                $query->whereIn('customers.id', function($builder) use ($employeeId) {
                    $builder->select('customer_id')->from('cases')
                        ->groupStart()
                            ->where('assigned_lawyer_id', $employeeId)
                            ->orWhere('assigned_staff_id', $employeeId)
                            ->orWhereIn('id', function($sub) use ($employeeId) {
                                return $sub->select('case_id')->from('case_members')->where('employee_id', $employeeId);
                            })
                        ->groupEnd();
                });
            }
            // Trường hợp Admin: Không add where => Thấy hết
            return $query;
        };

        return [
            // Tổng quy mô tệp khách hàng thuộc phạm vi quản lý
            'total_customers' => $applyScope($this->customerModel->builder())->countAllResults(),
            
            // Số lượng khách hàng mới gia nhập trong tháng
            'new_this_month'  => $applyScope($this->customerModel->builder())
                                    ->where('MONTH(customers.created_at)', date('m'))
                                    ->where('YEAR(customers.created_at)', date('Y'))
                                    ->countAllResults(),
            
            // Tổng khách hàng công ty (Doanh nghiệp)
            'total_corporate' => $applyScope($this->customerModel->builder())
                                    ->where('type', 'doanh_nghiep')
                                    ->countAllResults(),
                                    
            // Khách VIP (Doanh thu > 100tr chẳng hạn) - Thống kê heuristic
            'total_vip'       => $applyScope($this->customerModel->builder())
                                    ->where('total_revenue >=', 100000000)
                                    ->countAllResults(),

            // Danh sách TOP 5 khách hàng VIP (Lấy ít hơn cho Dashboard gọn)
            'top_revenue'     => $applyScope($this->customerModel->builder())
                                    ->select('customers.*')
                                    ->orderBy('total_revenue', 'DESC')
                                    ->limit(5)
                                    ->get()->getResultArray(),
            
            // Biểu đồ phân bổ loại hình khách hàng
            'type_distribution' => $applyScope($this->customerModel->builder())
                                    ->select('type, COUNT(*) as count')
                                    ->groupBy('type')
                                    ->get()->getResultArray()
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
