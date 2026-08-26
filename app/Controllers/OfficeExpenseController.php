<?php

namespace App\Controllers;

use App\Models\OfficeExpenseModel;
use App\Services\OfficeExpenseService;

/**
 * Controller module chi phí vận hành.
 *
 * Controller chỉ nhận request, validate dữ liệu đầu vào và chuyển nghiệp vụ nhập,
 * xóa, thống kê sang OfficeExpenseService để giữ đúng kiến trúc MVC của hệ thống.
 */
class OfficeExpenseController extends BaseController
{
    public static $modulePermissions = [
        'group' => 'Chi phí vận hành',
        'permissions' => [
            'office_expense.view' => [
                'desc' => 'Xem thống kê và danh sách chi phí vận hành',
                'roles' => [1, 2],
            ],
            'office_expense.manage' => [
                'desc' => 'Nhập và xóa chi phí vận hành',
                'roles' => [1, 2],
            ],
        ],
    ];

    protected $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new OfficeExpenseService();
    }

    public function index()
    {
        if (!$this->service->canView()) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Bạn chưa có quyền xem chi phí vận hành.');
        }

        $yearGet = $this->request->getGet('year');
        $filters = [
            'search' => $this->request->getGet('search') ?? '',
            'category' => $this->request->getGet('category') ?? '',
            'month' => (int)$this->request->getGet('month'),
            'year' => $yearGet === null ? (int)date('Y') : (int)$yearGet,
        ];

        $dashboard = $this->service->getDashboardData($filters, 20);

        return view('dashboard/office_expenses/index', [
            'title' => 'Chi phí vận hành | L.A.N ERP',
            'filters' => $filters,
            'expenses' => $dashboard['rows'],
            'pager' => $dashboard['pager'],
            'summary' => $dashboard['summary'],
            'monthly' => $dashboard['monthly'],
            'previousMonthly' => $dashboard['previous_monthly'],
            'categoryBreakdown' => $dashboard['categories'],
            'topExpenses' => $dashboard['top_expenses'],
            'categoryLabels' => OfficeExpenseService::CATEGORY_LABELS,
            'paymentMethodLabels' => OfficeExpenseService::PAYMENT_METHOD_LABELS,
            'canManage' => $this->service->canManage(),
        ]);
    }

    public function store()
    {
        if (!$this->service->canManage()) {
            return redirect()->back()->with('error', 'Bạn chưa có quyền nhập chi phí vận hành.');
        }

        $rules = [
            'expense_date' => 'required|valid_date[Y-m-d]',
            'category' => 'required',
            'amount' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $files = ['receipt' => $this->request->getFile('receipt')];
        $result = $this->service->create($this->request->getPost(), $files);

        if ($result['status'] !== 'success') {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function delete(int $id)
    {
        $result = $this->service->delete($id);
        if ($result['status'] !== 'success') {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function receipt(int $id, string $mode = 'view')
    {
        if (!$this->service->canView()) {
            return redirect()->back()->with('error', 'Báº¡n chÆ°a cÃ³ quyá»n xem chá»©ng tá»« chi phÃ­ váº­n hÃ nh.');
        }

        $expense = (new OfficeExpenseModel())->find($id);
        if (!$expense || empty($expense['receipt_file_path'])) {
            return redirect()->back()->with('error', 'KhÃ´ng tÃ¬m tháº¥y chá»©ng tá»« cá»§a khoáº£n chi phÃ­ nÃ y.');
        }

        $baseDir = realpath(WRITEPATH . 'uploads/office_expenses');
        $realPath = realpath(WRITEPATH . 'uploads/' . $expense['receipt_file_path']);
        $basePrefix = $baseDir ? rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : false;
        if (!$basePrefix || !$realPath || strpos($realPath, $basePrefix) !== 0 || !is_file($realPath)) {
            return redirect()->back()->with('error', 'KhÃ´ng tÃ¬m tháº¥y tá»‡p chá»©ng tá»« trÃªn há»‡ thá»‘ng lÆ°u trá»¯.');
        }

        $downloadName = $expense['receipt_file_name'] ?: basename($realPath);
        $extension = pathinfo($realPath, PATHINFO_EXTENSION);
        if ($extension !== '' && !str_ends_with(strtolower($downloadName), '.' . strtolower($extension))) {
            $downloadName .= '.' . $extension;
        }

        if ($mode === 'download') {
            return $this->response->download($realPath, null)->setFileName($downloadName);
        }

        $mimeType = $expense['receipt_file_type']
            ?: (function_exists('mime_content_type') ? mime_content_type($realPath) : 'application/octet-stream');

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . str_replace('"', '', $downloadName) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($realPath));
    }
}
