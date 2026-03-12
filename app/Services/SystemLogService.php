<?php

namespace App\Services;

use App\Models\SystemLogModel;

/**
 * SystemLogService
 * 
 * Dịch vụ ghi log hệ thống để theo dõi các thao tác của người dùng.
 */
class SystemLogService extends BaseService
{
    protected $logModel;

    public function __construct()
    {
        $this->logModel = new SystemLogModel();
    }

    /**
     * Ghi một bản ghi log mới
     * 
     * @param string $action Hành động (CREATE, UPDATE, DELETE, LOGIN, LOGOUT...)
     * @param string $module Tên module (Users, Employees, Customers...)
     * @param int|null $entityId ID của bản ghi bị tác động
     * @param array|null $details Chi tiết thay đổi (JSON)
     */
    public function log(string $action, string $module, ?int $entityId = null, ?array $details = null)
    {
        $request = \Config\Services::request();
        
        $data = [
            'user_id'    => session()->get('user_id'),
            'action'     => strtoupper($action),
            'module'     => $module,
            'entity_id'  => $entityId,
            'details'    => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
        ];

        return $this->logModel->insert($data);
    }

    /**
     * Lấy danh sách log kèm phân trang và lọc
     */
    public function getLogs(array $filters = [], int $perPage = 20)
    {
        $builder = $this->logModel->select('system_logs.*, users.email as user_email')
                                  ->join('users', 'users.id = system_logs.user_id', 'left')
                                  ->orderBy('system_logs.created_at', 'DESC');

        if (!empty($filters['date'])) {
            $builder->where('DATE(system_logs.created_at)', $filters['date']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('system_logs.user_id', $filters['user_id']);
        }
        
        if (!empty($filters['action'])) {
            $builder->where('system_logs.action', $filters['action']);
        }

        return $builder->paginate($perPage);
    }

    public function getPager()
    {
        return $this->logModel->pager;
    }

    /**
     * Tối ưu hóa: Xóa các bản ghi log cũ để giảm dung lượng DB.
     * Mặc định xóa log cũ hơn 90 ngày.
     */
    public function cleanLogs(int $days = 90)
    {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        return $this->logModel->where('created_at <', $date)->delete();
    }
}
