<?php

namespace App\Controllers;

use App\Services\CaseExpenseService;

/**
 * CaseExpenseController
 *
 * Controller điều phối module chi phí xử lý vụ việc. Mọi kiểm tra quyền và
 * truy vấn phức tạp được đặt trong CaseExpenseService để giữ Controller mỏng.
 */
class CaseExpenseController extends BaseController
{
    public static $modulePermissions = [
        'group' => 'Chi phí xử lý vụ việc',
        'permissions' => [
            'case_expense.submit' => [
                'desc' => 'Tạo phiếu chi phí cho vụ việc mình được phân công hoặc tham gia',
                'roles' => [1, 2, 3, 4, 5, 6, 7],
            ],
            'case_expense.view_own' => [
                'desc' => 'Xem chi phí vụ việc của cá nhân',
                'roles' => [1, 2, 3, 4, 5, 6, 7],
            ],
            'case_expense.view_team' => [
                'desc' => 'Xem chi phí của nhân sự cấp dưới trực tiếp',
                'roles' => [1, 2, 3],
            ],
            'case_expense.view_all' => [
                'desc' => 'Xem toàn bộ chi phí xử lý vụ việc',
                'roles' => [1, 2],
            ],
            'case_expense.approve' => [
                'desc' => 'Duyệt hoặc từ chối chi phí xử lý vụ việc',
                'roles' => [1, 2],
            ],
        ],
    ];

    protected $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new CaseExpenseService();
    }

    public function index()
    {
        if (!has_permission('case_expense.view_own') && !has_permission('case_expense.view_team') && !$this->service->canSubmit() && !$this->service->canApprove() && !$this->service->canViewAll()) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Bạn chưa có quyền theo dõi chi phí xử lý vụ việc.');
        }

        $filters = [
            'search' => $this->request->getGet('search') ?? '',
            'status' => $this->request->getGet('status') ?? '',
            'month' => (int)$this->request->getGet('month'),
            'year' => (int)($this->request->getGet('year') ?: date('Y')),
            'employee_id' => (int)$this->request->getGet('employee_id'),
            'case_id' => (int)$this->request->getGet('case_id'),
        ];
        $selectedScheduleId = (int)$this->request->getGet('work_schedule_id');
        $schedulePrefill = $this->service->getSchedulePrefill($selectedScheduleId);
        if ($schedulePrefill) {
            $filters['case_id'] = (int)$schedulePrefill['case_id'];
            $selectedScheduleId = (int)$schedulePrefill['id'];
        }

        $result = $this->service->getList($filters, 20);
        $data = [
            'title' => 'Chi phí xử lý vụ việc | L.A.N ERP',
            'expenses' => $result['rows'],
            'pager' => $result['pager'],
            'stats' => $result['stats'],
            'filters' => $filters,
            'categoryLabels' => CaseExpenseService::CATEGORY_LABELS,
            'statusLabels' => CaseExpenseService::STATUS_LABELS,
            'selectableCases' => $this->service->getSelectableCases(),
            'schedulePrefill' => $schedulePrefill,
            'selectedScheduleId' => $selectedScheduleId,
            'scheduleOptions' => !empty($filters['case_id']) ? $this->service->getScheduleOptionsByCase((int)$filters['case_id']) : [],
            'employees' => get_available_employees(),
            'canSubmit' => $this->service->canSubmit(),
            'canApprove' => $this->service->canApprove(),
        ];

        return view('dashboard/case_expenses/index', $data);
    }

    public function schedules()
    {
        if (!$this->service->canSubmit() && !$this->service->canApprove()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn chưa có quyền xem lịch liên quan.']);
        }

        $caseId = (int)$this->request->getGet('case_id');
        return $this->response->setJSON([
            'status' => 'success',
            'rows' => $this->service->getScheduleOptionsByCase($caseId),
        ]);
    }

    public function store()
    {
        if (!$this->service->canSubmit()) {
            return redirect()->back()->with('error', 'Bạn chưa có quyền tạo chi phí xử lý vụ việc.');
        }

        $rules = [
            'case_id' => 'required|numeric',
            'expense_date' => 'required|valid_date[Y-m-d]',
            'category' => 'required',
            'amount' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $files = ['attachments' => $this->request->getFiles()['attachments'] ?? []];
        $result = $this->service->create($this->request->getPost(), $files);

        if ($result['status'] !== 'success') {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function approve(int $id)
    {
        $status = $this->request->getPost('status');
        $approvedAmount = (int)preg_replace('/[^\d]/', '', (string)$this->request->getPost('approved_amount'));
        $note = $this->request->getPost('approval_note');
        $result = $this->service->approve($id, $status, $approvedAmount, $note);

        if ($result['status'] !== 'success') {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function update(int $id)
    {
        $rules = [
            'case_id' => 'required|numeric',
            'expense_date' => 'required|valid_date[Y-m-d]',
            'category' => 'required',
            'amount' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->service->update($id, $this->request->getPost());
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
}
