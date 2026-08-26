<?php

namespace App\Controllers;

use App\Services\PartnerCommissionService;

/**
 * PartnerController
 *
 * Màn quản trị đối tác và hoa hồng vụ việc. Đối tác vẫn là tài khoản user bình
 * thường; module này chỉ bổ sung hồ sơ đối tác, cấu hình %/số tiền theo vụ việc
 * và xử lý duyệt chi trả.
 */
class PartnerController extends BaseController
{
    public static $modulePermissions = [
        'group' => 'Đối tác',
        'permissions' => [
            'partner.portal' => [
                'desc' => 'Đối tác xem doanh thu được nhận và gửi yêu cầu thanh toán',
                'roles' => [],
            ],
            'partner.manage' => [
                'desc' => 'Quản lý hồ sơ đối tác và cấu hình hợp tác theo vụ việc',
                'roles' => [1, 2],
            ],
            'partner.payout' => [
                'desc' => 'Duyệt và cập nhật trạng thái chi trả hoa hồng đối tác',
                'roles' => [1, 2],
            ],
        ],
    ];

    private PartnerCommissionService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new PartnerCommissionService();
    }

    public function index()
    {
        if (!$this->service->canManage() && !$this->service->canPayout()) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Bạn chưa có quyền quản lý đối tác.');
        }

        $filters = [
            'search' => $this->request->getGet('search') ?? '',
            'status' => $this->request->getGet('status') ?? '',
            'entry_status' => $this->request->getGet('entry_status') ?? '',
        ];

        $data = $this->service->getAdminData($filters, 20);
        $data += [
            'title' => 'Đối tác & hoa hồng vụ việc | L.A.N ERP',
            'filters' => $filters,
            'roleLabels' => PartnerCommissionService::ROLE_LABELS,
            'baseLabels' => PartnerCommissionService::BASE_LABELS,
            'entryStatusLabels' => PartnerCommissionService::ENTRY_STATUS_LABELS,
            'selectableCases' => $this->service->getSelectableCases(),
            'selectableUsers' => $this->service->getSelectableUsers(),
            'canManage' => $this->service->canManage(),
            'canPayout' => $this->service->canPayout(),
        ];

        return view('dashboard/partners/index', $data);
    }

    public function storePartner()
    {
        if (!$this->service->canManage()) {
            return redirect()->back()->with('error', 'Bạn chưa có quyền tạo đối tác.');
        }

        $rules = [
            'name' => 'required|min_length[2]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->service->createPartner($this->request->getPost());
        if ($result['status'] !== 'success') {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function storeCasePartner()
    {
        if (!$this->service->canManage()) {
            return redirect()->back()->with('error', 'Bạn chưa có quyền cấu hình hợp tác vụ việc.');
        }

        $rules = [
            'case_id' => 'required|numeric',
            'partner_id' => 'required|numeric',
            'calculation_base' => 'required|in_list[contract,paid]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->service->createCasePartner($this->request->getPost());
        if ($result['status'] !== 'success') {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function updateEntryStatus(int $entryId)
    {
        if (!$this->service->canPayout()) {
            return redirect()->back()->with('error', 'Bạn chưa có quyền cập nhật chi trả đối tác.');
        }

        $status = (string)$this->request->getPost('status');
        $note = (string)$this->request->getPost('admin_note');
        $result = $this->service->updateEntryStatus($entryId, $status, $note);

        if ($result['status'] !== 'success') {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }
}
