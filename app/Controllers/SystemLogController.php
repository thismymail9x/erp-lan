<?php

namespace App\Controllers;

use App\Services\SystemLogService;

class SystemLogController extends BaseController
{
    protected $logService;

    public function __construct()
    {
        $this->logService = new SystemLogService();
    }

    public function index()
    {
        // Chỉ Admin được xem log
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không có quyền truy cập khu vực nhật ký hệ thống.');
        }

        $filters = [
            'date'    => $this->request->getGet('date'),
            'action'  => $this->request->getGet('action'),
            'user_id' => $this->request->getGet('user_id'),
        ];

        $userModel = new \App\Models\UserModel();

        $data = [
            'title'   => 'Nhật ký hệ thống | L.A.N ERP',
            'logs'    => $this->logService->getLogs($filters),
            'pager'   => $this->logService->getPager(),
            'filters' => $filters,
            'users'   => $userModel->select('id, email')->findAll()
        ];

        return view('dashboard/system_logs/index', $data);
    }
}
