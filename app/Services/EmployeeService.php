<?php

namespace App\Services;

use App\Models\EmployeeModel;
use App\Models\UserModel;
use App\Models\DepartmentModel;

/**
 * EmployeeService
 * 
 * Lớp Dịch vụ quản trị nguồn nhân lực (Human Resource Service).
 * Giải quyết các bài toán:
 * 1. Phân cấp dữ liệu nhân sự (Ai được thấy hồ sơ của ai).
 * 2. Tìm kiếm và liên kết Hồ sơ nhân viên với Tài khoản (User Mapping).
 * 3. Quản lý lịch sử thay đổi thông tin nhân sự (Audit Trail).
 */
class EmployeeService extends BaseService
{
    protected $employeeModel;
    protected $userModel;
    protected $logService;

    public function __construct()
    {
        parent::__construct();
        // Khởi tạo các Model nòng cốt phục vụ quản lý nhân sự
        $this->employeeModel = new EmployeeModel();
        $this->userModel = new UserModel();
        $this->logService = new SystemLogService();
    }

    /**
     * Truy xuất danh bạ nhân sự kèm theo logic phân quyền (Security Listing).
     * 
     * @param string $sort Tiêu chí xếp hạng.
     * @param string $order Hướng sắp xếp.
     * @param int $perPage Số bản ghi trên trang.
     * @param string $search Từ khóa tìm kiếm (Tên, Chức vụ, Phòng ban).
     * @return array
     */
    public function getAllEmployees(string $sort = 'id', string $order = 'desc', int $perPage = 10, string $search = '')
    {
        $roleName = session()->get('role_name');
        $departmentName = session()->get('department_name');

        // Bảng ánh xạ các trường sắp xếp từ giao diện sang SQL
        $sortMap = [
            'name' => 'employees.full_name',
            'position' => 'employees.position',
            'dept' => 'departments.name',
            'join_date' => 'employees.join_date',
            'salary' => 'employees.salary_base',
            'id' => 'employees.id'
        ];

        $orderField = $sortMap[$sort] ?? 'employees.id';
        $direction  = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        // Xây dựng câu truy vấn kết hợp thông tin Nhân viên và Phòng ban
        $query = $this->employeeModel->select('employees.*, departments.name as department_name')
                            ->join('departments', 'departments.id = employees.department_id', 'left');

        // 1. Áp dụng bộ lọc tìm kiếm (Full-text search cơ bản)
        if (!empty($search)) {
            $query->groupStart()
                  ->like('employees.full_name', $search)
                  ->orLike('employees.position', $search)
                  ->orLike('departments.name', $search)
                  ->groupEnd();
        }

        $query->orderBy($orderField, $direction);

        // 2. LOGIC PHÂN TÁCH DỮ LIỆU (Contextual Data Fetching):
        if ($roleName === \Config\AppConstants::ROLE_ADMIN || 
            $roleName === \Config\AppConstants::ROLE_MOD || 
            $departmentName === \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            
            // Nhóm Đặc quyền: Xem được hồ sơ của toàn bộ nhân viên công ty
            return $query->paginate($perPage);
        }

        // Nhóm Thành viên: Tuyệt đối chỉ xem được duy nhất hồ sơ cá nhân của mình thông qua liên kết Session
        $myEmpId = session()->get('employee_id');
        if ($myEmpId) {
            $query->where('employees.id', $myEmpId);
            return $query->paginate($perPage);
        }

        return [];
    }

    /**
     * Trả về công cụ hỗ trợ phân trang phục vụ View Search/List.
     */
    public function getPager()
    {
        return $this->employeeModel->pager;
    }

    /**
     * Lấy chi tiết hồ sơ nhân sự theo mã ID.
     */
    public function getEmployeeById(int $id)
    {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            return $this->fail('Hồ sơ nhân viên không tồn tại hoặc đã bị xóa khỏi hệ thống.');
        }
        return $this->success($employee);
    }

    /**
     * Khởi tạo thông tin nhân sự mới.
     * 
     * @param array $data Thông tin hồ sơ (Họ tên, bộ phận, lương, CCCD...)
     */
    public function createEmployee(array $data)
    {
        // Thực hiện ghi dữ liệu mới vào DB
        if ($this->employeeModel->insert($data)) {
            $empId = $this->employeeModel->getInsertID();
            
            // Ghi nhận hành động vào Nhật ký hệ thống (Audit Trail)
            $this->logService->log('CREATE', 'Employees', $empId, ['full_name' => $data['full_name']]);
            
            return $this->success(['id' => $empId], 'Hồ sơ nhân sự đã được thiết lập thành công.');
        }

        return $this->fail('Đã có lỗi xảy ra khi lưu trữ thông tin. Vui lòng kiểm tra lại tính hợp lệ của dữ liệu.');
    }

    /**
     * Cập nhật thông tin hồ sơ hiện có.
     * Bao gồm logic so sánh sự thay đổi để ghi nhật ký chi tiết.
     */
    public function updateEmployee(int $id, array $data)
    {
        // 1. Sao lưu dữ liệu cũ để phục vụ đối soát thay đổi
        $oldData = $this->employeeModel->find($id);
        
        // 2. Thực thi cập nhật
        if ($this->employeeModel->update($id, $data)) {
            $newData = $this->employeeModel->find($id);
            
            // 3. Tính toán sự khác biệt (Chỉ lấy các trường thực sự bị sửa đổi)
            $changes = [
                'before' => array_diff_assoc($oldData, $newData),
                'after'  => array_diff_assoc($newData, $oldData)
            ];

            // 4. Ghi log sự kiện sửa đổi kèm dữ liệu cũ/mới để truy vết khi cần
            $this->logService->log('UPDATE', 'Employees', $id, $changes);
            
            return $this->success(null, 'Hồ sơ nhân sự đã được cập nhật chính xác.');
        }
        return $this->fail('Lệnh cập nhật bị từ chối bởi Cơ sở dữ liệu.');
    }

    /**
     * Gỡ bỏ vĩnh viễn hồ sơ nhân viên (Sử dụng lệnh Delete trực tiếp).
     */
    public function deleteEmployee(int $id)
    {
        $oldData = $this->employeeModel->find($id);
        
        if ($this->employeeModel->delete($id)) {
            // Lưu vết tài khoản vừa xóa vào log
            $this->logService->log('DELETE', 'Employees', $id, ['deleted_record' => $oldData]);
            return $this->success(null, 'Đã gỡ bỏ hồ sơ nhân viên hoàn toàn khỏi hệ thống.');
        }
        return $this->fail('Thao tác xóa thất bại do ràng buộc dữ liệu hoặc lỗi server.');
    }

    /**
     * Tìm kiếm danh sách các Tài khoản (User) đang "Mồ côi" 
     * (Tài khoản đã tạo nhưng chưa được liên kết với hồ sơ nhân sự cụ thể).
     */
    public function getUnlinkedUsers()
    {
        return $this->userModel->select('users.id, users.email')
                               ->join('employees', 'employees.user_id = users.id', 'left')
                               ->where('employees.user_id', null)
                               ->findAll();
    }

    /**
     * Lấy danh sách toàn bộ phòng ban có trong công ty.
     */
    public function getDepartments()
    {
        $departmentModel = new DepartmentModel();
        return $departmentModel->findAll();
    }
}
