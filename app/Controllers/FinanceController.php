<?php

namespace App\Controllers;

use App\Services\CaseService;

class FinanceController extends BaseController
{
    public function index()
    {
        $roleName = session()->get('role_name');
        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && session()->get('department_id') != \Config\AppConstants::DEPT_HANH_CHINH) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Cảnh báo bảo mật: Bạn không có quyền truy cập module Tài chính - Kế toán.');
        }

        $caseService = new CaseService();
        $cases = $caseService->getCases('updated_at', 'desc', 20, $this->request->getGet('search') ?? '');

        // Tính tổng quan tài chính
        $totalContracts = 0;
        foreach ($cases as $c) {
            $totalContracts += (float)($c['contract_value'] ?? 0);
        }

        $data = [
            'cases' => $cases,
            'pager' => $caseService->getPager(),
            'totalContracts' => $totalContracts,
            'title' => 'Quản lý Tài chính - Kế toán vụ việc | L.A.N ERP'
        ];

        return view('dashboard/finance/index', $data);
    }
}
