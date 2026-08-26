<?php

namespace App\Controllers;

use App\Services\ViolationFundService;

/**
 * Controller module quỹ vi phạm nội bộ.
 *
 * Controller chịu trách nhiệm nhận request, kiểm tra quyền và validate form;
 * toàn bộ logic tính tiền, lọc báo cáo và gửi thông báo nằm trong Service.
 */
class ViolationFundController extends BaseController
{
    public static $modulePermissions = [
        'group' => 'Quỹ vi phạm nội bộ',
        'permissions' => [
            'violation_fund.view' => [
                'desc' => 'Xem toàn bộ báo cáo quỹ vi phạm nội bộ',
                'roles' => [1, 2],
            ],
            'violation_fund.view_own' => [
                'desc' => 'Xem các khoản vi phạm của bản thân',
                'roles' => [1, 2, 3, 4, 5],
            ],
            'violation_fund.manage' => [
                'desc' => 'Ghi nhận và xóa khoản vi phạm',
                'roles' => [1, 2],
            ],
            'violation_fund.collect' => [
                'desc' => 'Cập nhật trạng thái hành chính đã thu',
                'roles' => [1, 2],
            ],
        ],
    ];

    protected ViolationFundService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new ViolationFundService();
    }

    public function index()
    {
        if (!$this->service->canView()) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Bạn chưa có quyền xem quỹ vi phạm.');
        }

        $filters = [
            'due_month' => $this->request->getGet('due_month') ?? date('Y-m'),
            'employee_id' => (int)$this->request->getGet('employee_id'),
            'category' => $this->request->getGet('category') ?? '',
            'status' => $this->request->getGet('status') ?? '',
            'search' => $this->request->getGet('search') ?? '',
        ];

        $dashboard = $this->service->getDashboardData($filters, 20);

        return view('dashboard/violation_funds/index', [
            'title' => 'Quỹ vi phạm nội bộ | L.A.N ERP',
            'filters' => $filters,
            'records' => $dashboard['rows'],
            'pager' => $dashboard['pager'],
            'summary' => $dashboard['summary'],
            'categoryBreakdown' => $dashboard['category_breakdown'],
            'employeeBreakdown' => $dashboard['employee_breakdown'],
            'employees' => $dashboard['employees'],
            'categoryLabels' => ViolationFundService::CATEGORY_LABELS,
            'statusLabels' => ViolationFundService::STATUS_LABELS,
            'collectionMethodLabels' => ViolationFundService::COLLECTION_METHOD_LABELS,
            'rankLabels' => ViolationFundService::RANK_LABELS,
            'canCreate' => $this->service->canCreate(),
            'canManage' => $this->service->canManage(),
            'canCollect' => $this->service->canCollect(),
            'canViewAll' => $this->service->canViewAll(),
        ]);
    }

    public function store()
    {
        if (!$this->service->canCreate()) {
            return redirect()->back()->with('error', 'Bạn chưa có quyền ghi nhận vi phạm.');
        }

        $rules = [
            'employee_id' => 'required|is_natural_no_zero',
            'violation_date' => 'required|valid_date[Y-m-d]',
            'due_month' => 'required|regex_match[/^\d{4}-\d{2}$/]',
            'category' => 'required',
            'rank_level' => 'required|in_list[1,2,3]',
            'explanation' => 'required|min_length[3]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->service->create($this->request->getPost());
        if ($result['status'] !== 'success') {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function updateCollection(int $id)
    {
        $result = $this->service->updateCollection($id, $this->request->getPost());
        if ($result['status'] !== 'success') {
            return redirect()->back()->with('error', $result['message']);
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
}
