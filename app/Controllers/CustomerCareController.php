<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\CustomerCarePlanModel;
use App\Models\CustomerCareTaskModel;
use App\Models\CustomerLoyaltyModel;
use App\Services\CustomerCareService;
use App\Services\CustomerService;
use App\Models\CustomerSlaSettingModel;
use App\Models\CustomerSlaHistoryModel;
use App\Services\CustomerSlaService;
use App\Services\CustomerMonitoringStatusService;

/**
 * CustomerCareController
 * 
 * Bộ điều khiển trung tâm quản lý hoạt động Chăm sóc khách hàng cũ (CSKH).
 * Cho phép lập kế hoạch chăm sóc theo 3 giai đoạn, quản lý công việc hàng ngày,
 * theo dõi KPI hiệu suất và chương trình Khách hàng VIP/Loyalty.
 * 
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #2 (MVC Strict), Rule #7 (Maximum Security), Rule #10 (Permissions Registry).
 */
class CustomerCareController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $modulePermissions = [
        'group' => 'Chăm sóc khách hàng',
        'permissions' => [
            'care.view'     => 'Xem dashboard và danh sách CSKH',
            'care.manage'   => 'Quản lý kế hoạch và hoàn thành checklist CSKH',
            'care.view_all' => 'Xem CSKH toàn hệ thống (Bypass isolation)',
        ]
    ];

    protected $customerModel;
    protected $carePlanModel;
    protected $careTaskModel;
    protected $loyaltyModel;
    protected $careService;
    protected $customerService;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->carePlanModel = new CustomerCarePlanModel();
        $this->careTaskModel = new CustomerCareTaskModel();
        $this->loyaltyModel  = new CustomerLoyaltyModel();
        $this->careService   = new CustomerCareService();
        $this->customerService = new CustomerService();
    }

    /**
     * Dashboard CSKH tổng quan + Chỉ số KPI thực tế.
     */
    public function index()
    {
        // 1. Phân quyền truy cập
        if (!has_permission('care.view') && !has_permission('sys.admin')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Cảnh báo bảo mật: Bạn không có quyền truy cập Module CSKH.');
        }

        // 2. Định danh nhân viên để áp dụng Data Isolation (Rule #3)
        $myEmpId = session()->get('employee_id');
        $isAdmin = has_permission('sys.admin') || has_permission('care.view_all');

        $staffFilterId = $isAdmin ? null : $myEmpId;

        // 3. Lấy dữ liệu thống kê từ Service
        $kpis = $this->careService->getCareKPI($staffFilterId);
        $monthlyStats = $this->careService->getMonthlyStats($staffFilterId);
        
        // 4. Lấy danh sách việc cần làm gấp (Quá hạn hoặc Hôm nay)
        $pendingTasks = $this->careTaskModel->getPendingTasks($myEmpId);
        $upcomingBirthdays = $this->careService->getUpcomingBirthdays(7);

        // 5. Thống kê phân nhóm A/B/C
        $segmentStats = $this->customerService->getSegmentStats($staffFilterId);

        // 6. Top nhân viên xuất sắc nhất dựa trên số lượng plans hoàn thành
        $db = \Config\Database::connect();
        $topStaff = $db->table('customer_care_plans')
                       ->select('employees.full_name as staff_name, COUNT(*) as completed_count')
                       ->join('employees', 'employees.id = customer_care_plans.assigned_staff_id AND employees.deleted_at IS NULL')
                       ->where('customer_care_plans.status', 'completed')
                       ->where('customer_care_plans.deleted_at', null)
                       ->groupBy('customer_care_plans.assigned_staff_id')
                       ->orderBy('completed_count', 'DESC')
                       ->limit(5)
                       ->get()
                       ->getResultArray();

        // Tính toán số lượng cảnh báo đỏ SLA quá hạn
        $slaService = new \App\Services\CustomerSlaService();
        $overdueSlaCount = count($slaService->getOverdueCustomersList($staffFilterId));

        $data = [
            'kpis'              => $kpis,
            'monthlyStats'      => $monthlyStats,
            'pendingTasks'      => $pendingTasks,
            'upcomingBirthdays' => $upcomingBirthdays,
            'segmentStats'      => $segmentStats,
            'topStaff'          => $topStaff,
            'overdueSlaCount'   => $overdueSlaCount,
            'title'             => 'Dashboard Chăm sóc khách hàng | L.A.N ERP'
        ];

        return view('dashboard/customer_care/index', $data);
    }

    /**
     * Danh sách khách hàng theo phân nhóm A/B/C.
     */
    public function customers()
    {
        if (!has_permission('care.view') && !has_permission('sys.admin')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Cảnh báo bảo mật: Bạn không có quyền xem danh sách phân nhóm.');
        }

        $myEmpId = session()->get('employee_id');
        $isAdmin = has_permission('sys.admin') || has_permission('care.view_all');

        $keyword = trim((string) $this->request->getGet('q'));
        $careStatus = (string) $this->request->getGet('care_status');
        $activeSegment = (string) ($this->request->getGet('segment') ?: 'potential');
        $perPage = (int) ($this->request->getGet('per_page') ?: 12);

        if (!in_array($perPage, [12, 24, 48], true)) {
            $perPage = 12;
        }

        if (!in_array($activeSegment, ['vip', 'regular', 'potential'], true)) {
            $activeSegment = 'potential';
        }

        $buildSegmentQuery = function (string $segment) use ($isAdmin, $myEmpId, $keyword, $careStatus): CustomerModel {
            $query = (new CustomerModel())
                ->where('deleted_at', null);

            if ($segment === 'potential') {
                $query->groupStart()
                    ->where('customer_segment', 'potential')
                    ->orWhere('customer_segment', null)
                    ->orWhere('customer_segment', '')
                    ->groupEnd();
            } else {
                $query->where('customer_segment', $segment);
            }

            if (!$isAdmin) {
                $query->where('assigned_care_staff_id', $myEmpId);
            }

            if ($keyword !== '') {
                $query->groupStart()
                    ->like('name', $keyword)
                    ->orLike('code', $keyword)
                    ->orLike('phone', $keyword)
                    ->orLike('email', $keyword)
                    ->groupEnd();
            }

            if ($careStatus !== '' && in_array($careStatus, ['new', 'phase1', 'phase2', 'phase3', 'completed', 'dormant'], true)) {
                $query->where('care_status', $careStatus);
            }

            return $query->orderBy('updated_at', 'DESC')->orderBy('created_at', 'DESC');
        };

        $segmentCounts = [
            'vip'       => $buildSegmentQuery('vip')->countAllResults(),
            'regular'   => $buildSegmentQuery('regular')->countAllResults(),
            'potential' => $buildSegmentQuery('potential')->countAllResults(),
        ];

        $vipCustomers = [];
        $regularCustomers = [];
        $potentialCustomers = [];
        ${$activeSegment . 'Customers'} = $buildSegmentQuery($activeSegment)->paginate($perPage, $activeSegment);

        $data = [
            'vipCustomers'      => $vipCustomers,
            'regularCustomers'  => $regularCustomers,
            'potentialCustomers'=> $potentialCustomers,
            'segmentCounts'     => $segmentCounts,
            'pager'             => service('pager'),
            'filters'           => [
                'q'           => $keyword,
                'care_status' => $careStatus,
                'segment'     => $activeSegment,
                'per_page'    => $perPage,
            ],
            'title'             => 'Phân loại khách hàng A/B/C | L.A.N ERP'
        ];

        return view('dashboard/customer_care/customers', $data);
    }

    /**
     * Chi tiết kế hoạch CSKH 3 giai đoạn của một khách hàng cụ thể.
     */
    public function carePlan($customerId)
    {
        if (!has_permission('care.view') && !has_permission('sys.admin')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Không có quyền truy cập kế hoạch chăm sóc.');
        }

        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return redirect()->back()->with('error', 'Khách hàng không tồn tại.');
        }

        // Lấy tất cả plans & tasks của khách hàng
        $plans = $this->carePlanModel->getByCustomer($customerId);
        
        $planTasks = [];
        foreach ($plans as $p) {
            $planTasks[$p['id']] = $this->careTaskModel->getByPlan($p['id']);
        }

        // Lấy thông tin loyalty
        $loyalty = $this->loyaltyModel->getByCustomer($customerId);

        $data = [
            'customer'  => $customer,
            'plans'     => $plans,
            'planTasks' => $planTasks,
            'loyalty'   => $loyalty,
            'employees' => get_available_employees(),
            'title'     => 'Kế hoạch chăm sóc: ' . $customer['name'] . ' | L.A.N ERP'
        ];

        return view('dashboard/customer_care/care_plan', $data);
    }

    /**
     * POST: Khởi tạo quy trình chăm sóc mới theo Giai đoạn.
     */
    public function initPlan($customerId)
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Từ chối thao tác: Bạn không có quyền khởi tạo kế hoạch CSKH.');
        }

        $phase = $this->request->getPost('phase');
        $assignedStaffId = $this->request->getPost('assigned_staff_id');

        if (!in_array($phase, ['phase1', 'phase2', 'phase3'])) {
            return redirect()->back()->with('error', 'Giai đoạn CSKH không hợp lệ.');
        }

        try {
            $this->careService->initializeCarePlan($customerId, $phase, $assignedStaffId);
            return redirect()->to(base_url('customer-care/care-plan/' . $customerId))->with('success', 'Đã khởi tạo thành công kế hoạch chăm sóc mới.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khi khởi tạo kế hoạch: ' . $e->getMessage());
        }
    }

    /**
     * POST: Hoàn thành 1 task CSKH.
     */
    public function completeTask($taskId)
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện công việc này.']);
        }

        $notes = $this->request->getPost('notes');
        $myEmpId = session()->get('employee_id');

        if (empty($myEmpId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Lỗi phiên đăng nhập: Không tìm thấy ID nhân viên.']);
        }

        $success = $this->careService->completeTask($taskId, $myEmpId, $notes);

        if ($success) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã xác nhận hoàn thành công việc chăm sóc.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể hoàn thành công việc. Vui lòng kiểm tra lại.']);
    }

    /**
     * POST: Thêm một công việc (task) tùy chỉnh vào kế hoạch.
     */
    public function addTask($planId)
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Từ chối thao tác: Bạn không có quyền thêm công việc chăm sóc.');
        }

        $plan = $this->carePlanModel->find($planId);
        if (!$plan) {
            return redirect()->back()->with('error', 'Kế hoạch không tồn tại.');
        }

        $data = $this->request->getPost();
        $data['care_plan_id'] = $planId;
        $data['customer_id']  = $plan['customer_id'];
        $data['is_completed'] = 0;
        $data['created_at']   = date('Y-m-d H:i:s');
        $data['updated_at']   = date('Y-m-d H:i:s');

        // Chuẩn hóa null
        if (empty($data['channel'])) {
            $data['channel'] = null;
        }

        if ($this->careTaskModel->save($data)) {
            return redirect()->to(base_url('customer-care/care-plan/' . $plan['customer_id']))->with('success', 'Đã thêm công việc mới vào kế hoạch.');
        }

        return redirect()->back()->with('error', 'Không thể lưu công việc. Vui lòng kiểm tra lại dữ liệu nhập.');
    }

    /**
     * GET: Xóa 1 task.
     */
    public function deleteTask($taskId)
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Từ chối thao tác: Bạn không có quyền xóa công việc.');
        }

        $task = $this->careTaskModel->find($taskId);
        if (!$task) {
            return redirect()->back()->with('error', 'Công việc không tồn tại.');
        }

        $this->careTaskModel->delete($taskId);

        return redirect()->to(base_url('customer-care/care-plan/' . $task['customer_id']))->with('success', 'Đã xóa công việc khỏi kế hoạch.');
    }

    /**
     * GET: Checklist công việc hôm nay/quá hạn của nhân viên.
     */
    public function dailyChecklist()
    {
        if (!has_permission('care.view') && !has_permission('sys.admin')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Không có quyền truy cập checklist.');
        }

        $myEmpId = session()->get('employee_id');
        $checklist = $this->careTaskModel->getDailyChecklist($myEmpId);

        $data = [
            'checklist' => $checklist,
            'title'     => 'Checklist công việc hôm nay | L.A.N ERP'
        ];

        return view('dashboard/customer_care/daily_checklist', $data);
    }

    /**
     * Báo cáo tháng hiệu suất CSKH.
     */
    public function monthlyReport()
    {
        if (!has_permission('care.view') && !has_permission('sys.admin')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Không có quyền xem báo cáo.');
        }

        $myEmpId = session()->get('employee_id');
        $isAdmin = has_permission('sys.admin') || has_permission('care.view_all');
        $staffFilterId = $isAdmin ? null : $myEmpId;

        $kpis = $this->careService->getCareKPI($staffFilterId);
        $monthlyStats = $this->careService->getMonthlyStats($staffFilterId);

        $data = [
            'kpis'         => $kpis,
            'monthlyStats' => $monthlyStats,
            'title'        => 'Báo cáo hiệu suất CSKH tháng | L.A.N ERP'
        ];

        return view('dashboard/customer_care/monthly_report', $data);
    }

    /**
     * Quản lý Loyalty / Chương trình VIP khách hàng.
     */
    public function loyalty($customerId)
    {
        if (!has_permission('care.view') && !has_permission('sys.admin')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Không có quyền xem thông tin Loyalty.');
        }

        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return redirect()->back()->with('error', 'Khách hàng không tồn tại.');
        }

        // Đảm bảo có loyalty card
        $this->careService->initializeLoyaltyCard($customerId);
        $loyalty = $this->loyaltyModel->getByCustomer($customerId);

        // Lấy danh sách khách hàng được giới thiệu bởi khách này
        $referredCustomers = $this->customerModel->where('referred_by', $customer['name'])
                                                 ->where('deleted_at', null)
                                                 ->findAll();

        $data = [
            'customer'          => $customer,
            'loyalty'           => $loyalty,
            'referredCustomers' => $referredCustomers,
            'title'             => 'Thông tin Loyalty & Thẻ VIP: ' . $customer['name'] . ' | L.A.N ERP'
        ];

        return view('dashboard/customer_care/loyalty', $data);
    }

    /**
     * POST: Cập nhật phân nhóm khách hàng bằng tay (Manual segment).
     */
    public function updateSegment($customerId)
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Từ chối thao tác: Bạn không có quyền phân nhóm khách hàng.');
        }

        $segment = $this->request->getPost('customer_segment');
        $assignedStaffId = $this->request->getPost('assigned_care_staff_id');
        if (!in_array($segment, ['vip', 'regular', 'potential'])) {
            return redirect()->back()->with('error', 'Nhóm khách hàng không hợp lệ.');
        }

        $updateData = [
            'customer_segment' => $segment,
            'updated_at'       => date('Y-m-d H:i:s')
        ];

        if ($assignedStaffId !== null && $assignedStaffId !== '') {
            $activeStaff = \Config\Database::connect()
                ->table('employees')
                ->select('employees.id')
                ->join('users', 'users.id = employees.user_id', 'inner')
                ->where('employees.id', (int) $assignedStaffId)
                ->where('employees.deleted_at', null)
                ->where('users.active_status', 1)
                ->where('users.deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$activeStaff) {
                return redirect()->back()->with('error', 'Nhan su CSKH khong hop le hoac da nghi/bi khoa.');
            }

            $updateData['assigned_care_staff_id'] = (int) $assignedStaffId;
        } else {
            $updateData['assigned_care_staff_id'] = null;
        }

        $this->customerModel->update($customerId, $updateData);

        return redirect()->to(base_url('customer-care/care-plan/' . $customerId))->with('success', 'Đã cập nhật phân nhóm khách hàng thủ công thành công.');
    }

    /**
     * GET: Báo cáo hiệu suất SLA và Giao diện Cấu hình Trạng thái CSKH Động.
     * Hỗ trợ Data Isolation (Rule #3) cho cấp quản lý.
     */
    public function slaReport()
    {
        // 1. Kiểm tra phân quyền truy cập
        if (!has_permission('care.view') && !has_permission('sys.admin')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Cảnh báo bảo mật: Bạn không có quyền truy cập Báo cáo SLA.');
        }

        $myEmpId = session()->get('employee_id');
        $isAdmin = has_permission('sys.admin') || has_permission('care.view_all');
        $roleName = session()->get('role_name');
        
        // Cấu hình lọc theo phân lập dữ liệu (Data Isolation)
        $staffFilterId = null;
        if (!$isAdmin) {
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                $staffFilterId = $myEmpId; // Sẽ lọc theo quân của sếp trong Service
            } else {
                return redirect()->to(base_url('dashboard'))->with('error', 'Bạn không có quyền quản lý để xem báo cáo SLA tổng thể.');
            }
        }

        $slaService = new CustomerSlaService();
        $settingModel = new CustomerSlaSettingModel();
        $monitoringStatusService = new CustomerMonitoringStatusService();

        // Lấy báo cáo bảng hiệu suất SLA và các cảnh báo trễ hạn (Báo đỏ)
        $leaderboard = $slaService->getStaffSlaPerformance($staffFilterId);
        $overdueAlerts = $slaService->getOverdueCustomersList($staffFilterId);

        // Lấy cấu hình các trạng thái động hiện tại (Rule #6 - Bắt buộc deleted_at IS NULL)
        $slaSettings = $settingModel->where('deleted_at', null)->orderBy('sort_order', 'ASC')->findAll();
        $monitoringSettings = $monitoringStatusService->getSettings();

        $data = [
            'leaderboard'   => $leaderboard,
            'overdueAlerts' => $overdueAlerts,
            'slaSettings'   => $slaSettings,
            'monitoringSettings' => $monitoringSettings,
            'isAdmin'       => $isAdmin,
            'title'         => 'Báo cáo & Cấu hình SLA Chăm sóc Khách hàng | L.A.N ERP'
        ];

        return view('dashboard/customer_care/sla_report', $data);
    }

    /**
     * POST: Lưu hoặc chỉnh sửa cấu hình trạng thái SLA động.
     * Chỉ Admin hoặc người có quyền care.manage được phép thực thi (Rule #7).
     */
    public function saveSlaSetting()
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Từ chối thao tác: Bạn không có quyền cấu hình hệ thống SLA.'
            ]);
        }

        $settingModel = new CustomerSlaSettingModel();
        $data = $this->request->getPost();
        
        // Chuẩn hóa null và ép kiểu
        $id = $this->request->getPost('id');
        if (empty($id)) {
            $id = null;
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['is_active']  = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $data['sla_hours']  = (int)($data['sla_hours'] ?? 0);
        $data['sort_order'] = (int)($data['sort_order'] ?? 0);

        // Thực thi kiểm tra & lưu
        if ($settingModel->save($data)) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Đã lưu cấu hình trạng thái SLA thành công.'
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Dữ liệu không hợp lệ: ' . implode(' ', $settingModel->errors())
        ]);
    }

    /**
     * POST: Xóa mềm cấu hình trạng thái SLA động.
     */
    public function deleteSlaSetting($id)
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Từ chối thao tác: Bạn không có quyền cấu hình hệ thống SLA.'
            ]);
        }

        $settingModel = new CustomerSlaSettingModel();
        $setting = $settingModel->find($id);
        if (!$setting) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Trạng thái cấu hình không tồn tại.'
            ]);
        }

        // Thực thi xóa mềm (Soft Delete - Rule #6)
        if ($settingModel->delete($id)) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Đã gỡ bỏ trạng thái cấu hình thành công.'
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Không thể xóa cấu hình trạng thái.'
        ]);
    }

    public function saveMonitoringStatusSetting()
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Bạn không có quyền cấu hình trạng thái giám sát.'
            ]);
        }

        $monitoringStatusService = new CustomerMonitoringStatusService();
        $result = $monitoringStatusService->saveSetting($this->request->getPost());

        return $this->response->setJSON($result);
    }

    public function deleteMonitoringStatusSetting($id)
    {
        if (!has_permission('care.manage') && !has_permission('sys.admin')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Bạn không có quyền cấu hình trạng thái giám sát.'
            ]);
        }

        $monitoringStatusService = new CustomerMonitoringStatusService();
        $result = $monitoringStatusService->deleteSetting((int) $id);

        return $this->response->setJSON($result);
    }
}
