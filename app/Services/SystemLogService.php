<?php

namespace App\Services;

use App\Models\SystemLogModel;

/**
 * SystemLogService
 * 
 * Dịch vụ cốt lõi đảm nhiệm việc duy trì Nhật ký hệ thống (Audit Trail).
 * Mục đích:
 * 1. Lưu vết mọi thao tác nhạy cảm (Tạo, Sửa, Xóa).
 * 2. Cung cấp dữ liệu phục vụ điều tra lỗi (Debugging) và phân tích hành vi người dùng (Security).
 * 3. Tự động thu thập thông tin ngữ cảnh (IP, Trình duyệt).
 */
class SystemLogService extends BaseService
{
    protected $logModel;

    public function __construct()
    {
        parent::__construct();
        // Khởi tạo model ghi log
        $this->logModel = new SystemLogModel();
    }

    /**
     * Ghi nhận một sự kiện vào Nhật ký hệ thống.
     * 
     * @param string $action Loại hành động: CREATE, UPDATE, DELETE, LOGIN, DOWNLOAD,...
     * @param string $module Tên module ứng dụng (VD: Employees, Auth, Customers).
     * @param int|null $entityId ID của đối tượng dữ liệu bị tác động.
     * @param array|null $details Mảng chứa chi tiết thay đổi (Dữ liệu cũ/mới).
     */
    public function log(string $action, string $module, ?int $entityId = null, ?array $details = null)
    {
        $request = \Config\Services::request();
        
        $data = [
            'user_id'    => session()->get('user_id'),    // Người thực hiện thao tác
            'action'     => strtoupper($action),
            'module'     => $module,
            'entity_id'  => $entityId,
            // Chuyển đổi chi tiết sang JSON để lưu trữ linh hoạt trong DB (Unescaped Unicode để giữ tiếng Việt)
            'details'    => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $request->getIPAddress(),     // Truy vết địa điểm truy cập
            'user_agent' => $request->getUserAgent()->getAgentString(), // Truy vết thiết bị sử dụng
        ];

        return $this->logModel->insert($data);
    }

    /**
     * Truy xuất danh sách nhật ký hệ thống kèm theo các bộ lọc linh hoạt.
     * 
     * @param array $filters Tập hợp các tiêu chí lọc (Ngày tháng, User, Hành động...).
     * @param int $perPage Số lượng bản ghi cho mỗi trang hiển thị.
     * @return mixed
     */
    public function getLogs(array $filters = [], int $perPage = 20)
    {
        $builder = $this->logModel->select('system_logs.*, users.email as user_email')
                                  ->join('users', 'users.id = system_logs.user_id', 'left')
                                  ->orderBy('system_logs.created_at', 'DESC');

        // 1. Phân loại theo thời gian
        if (!empty($filters['date'])) {
            $builder->where('DATE(system_logs.created_at)', $filters['date']);
        }

        // 2. Tra cứu theo tài khoản cụ thể
        if (!empty($filters['user_id'])) {
            $builder->where('system_logs.user_id', $filters['user_id']);
        }
        
        // 3. Phân nhóm theo hành vi
        if (!empty($filters['action'])) {
            $builder->where('system_logs.action', $filters['action']);
        }

        return $builder->paginate($perPage);
    }

    /**
     * Trả về công cụ hỗ trợ phân trang cho View.
     */
    public function getPager()
    {
        return $this->logModel->pager;
    }

    /**
     * Cơ chế dọn dẹp hệ thống (Log Cleanup Strategy).
     * Loại bỏ các bản ghi đã quá cũ để Giải phóng không gian lưu trữ và tăng tốc độ truy vấn DB.
     * 
     * @param int $days Ngưỡng thời gian lưu giữ (Mặc định 90 ngày).
     */
    public function cleanLogs(int $days = 90)
    {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        return $this->logModel->where('created_at <', $date)->delete();
    }
}
