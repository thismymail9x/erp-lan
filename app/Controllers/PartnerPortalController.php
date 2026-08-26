<?php

namespace App\Controllers;

use App\Services\PartnerCommissionService;

/**
 * PartnerPortalController
 *
 * Cổng tự phục vụ cho đối tác. Người dùng đăng nhập bằng bảng users như bình
 * thường, nhưng chỉ được xem dữ liệu thuộc hồ sơ partners đang liên kết với user_id.
 */
class PartnerPortalController extends BaseController
{
    private PartnerCommissionService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new PartnerCommissionService();
    }

    public function index()
    {
        if (!session()->has('isLoggedIn') || !has_permission('partner.portal')) {
            return redirect()->to(base_url('login'))->with('error', 'Bạn cần đăng nhập bằng tài khoản đối tác.');
        }

        $partner = $this->service->getPartnerByCurrentUser();
        if (!$partner) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Tài khoản chưa được liên kết hồ sơ đối tác.');
        }

        $filters = [
            'status' => $this->request->getGet('status') ?? '',
        ];

        $data = $this->service->getPortalData((int)$partner['id'], $filters, 20);
        $data += [
            'title' => 'Cổng đối tác | L.A.N ERP',
            'partner' => $partner,
            'filters' => $filters,
            'roleLabels' => PartnerCommissionService::ROLE_LABELS,
            'baseLabels' => PartnerCommissionService::BASE_LABELS,
            'entryStatusLabels' => PartnerCommissionService::ENTRY_STATUS_LABELS,
        ];

        return view('dashboard/partners/portal', $data);
    }

    public function customers()
    {
        if (!session()->has('isLoggedIn') || !has_permission('partner.portal')) {
            return redirect()->to(base_url('login'))->with('error', 'Ban can dang nhap bang tai khoan doi tac.');
        }

        $partner = $this->service->getPartnerByCurrentUser();
        if (!$partner) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Tai khoan chua duoc lien ket ho so doi tac.');
        }

        return view('dashboard/partners/customers', [
            'title' => 'Khach hang gioi thieu | L.A.N ERP',
            'partner' => $partner,
            'referredCustomers' => $this->service->getReferredCustomers((int)$partner['id']),
        ]);
    }

    public function requestPayment(int $entryId)
    {
        if (!session()->has('isLoggedIn') || !has_permission('partner.portal')) {
            return redirect()->to(base_url('login'))->with('error', 'Bạn cần đăng nhập bằng tài khoản đối tác.');
        }

        $partner = $this->service->getPartnerByCurrentUser();
        if (!$partner) {
            return redirect()->back()->with('error', 'Tài khoản chưa được liên kết hồ sơ đối tác.');
        }

        $result = $this->service->requestPayment($entryId, (int)$partner['id'], (string)$this->request->getPost('request_note'));
        if ($result['status'] !== 'success') {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }
}
