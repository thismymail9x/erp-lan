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
        $search = $this->request->getGet('search') ?? '';
        $month = (int)$this->request->getGet('month');
        
        // Cải tiến logic lấy năm: 
        // - Nếu không có tham số 'year' (null): mặc định năm hiện tại.
        // - Nếu có tham số 'year' nhưng rỗng (chọn "Tất cả"): lấy giá trị 0.
        // - Nếu có giá trị cụ thể: lấy giá trị đó.
        $yearGet = $this->request->getGet('year');
        if ($yearGet === null) {
            $year = (int)date('Y');
        } else {
            $year = (int)$yearGet;
        }

        $paymentStatus = $this->request->getGet('payment_status') ?? '';

        $cases = $caseService->getCases(
            'updated_at', 
            'desc', 
            20, 
            $search, 
            [], 
            '', 
            0, 
            $month, 
            $year, 
            $paymentStatus,
            true
        );

        // Tính tổng quan tài chính (Toàn bộ dữ liệu theo bộ lọc, không chỉ trang hiện tại)
        $financeStats = $caseService->getFinanceStats($search, $month, $year, $paymentStatus);

        $data = [
            'cases' => $cases,
            'pager' => $caseService->getPager(),
            'totalContracts' => $financeStats['total_contract'],
            'totalPaid' => $financeStats['total_paid'],
            'totalUnpaid' => $financeStats['total_unpaid'],
            'title' => 'Quản lý Tài chính - Kế toán vụ việc | L.A.N ERP',
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
                'payment_status' => $paymentStatus
            ]
        ];

        if ($this->request->isAJAX() || $this->request->getGet('ajax') == '1') {
            return $this->response->setJSON([
                'html' => view('dashboard/finance/index_table', $data),
                'stats' => [
                    'total' => number_format($financeStats['total_contract'], 0, ',', '.') . 'đ',
                    'paid' => number_format($financeStats['total_paid'], 0, ',', '.') . 'đ',
                    'unpaid' => number_format($financeStats['total_unpaid'], 0, ',', '.') . 'đ'
                ]
            ]);
        }

        return view('dashboard/finance/index', $data);
    }
}
