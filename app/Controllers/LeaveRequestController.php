<?php

namespace App\Controllers;

use App\Models\LeaveRequestModel;
use App\Services\LeaveRequestService;
use App\Models\EmployeeModel;

class LeaveRequestController extends BaseController
{
    public static $modulePermissions = [
        'group' => 'Nhân sự & Tài khoản',
        'permissions' => [
            'leave.view'    => ['desc' => 'Xem danh sách đơn nghỉ phép', 'roles' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]], 
            'leave.manage'  => ['desc' => 'Gửi đơn nghỉ phép cá nhân', 'roles' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]],
            'leave.approve' => ['desc' => 'Phê duyệt đơn xin nghỉ phép (Admin/Manager)', 'roles' => [1, 3]]
        ]
    ];

    /**
     * Khai báo danh mục thuộc thể loại Nhãn dán (Smart Tags).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $taggable = [
        'type'  => 'leave_requests',
        'label' => 'Đơn nghỉ phép'
    ];

    protected $model;
    protected $service;

    public function __construct()
    {
        $this->model = new LeaveRequestModel();
        $this->service = new LeaveRequestService();
    }

    /**
     * Danh sách phiếu nghỉ phép.
     * Áp dụng chính sách bảo mật: Nhân viên chỉ thấy đơn của mình. Trưởng phòng thấy đơn của phòng mình. Admin thấy tất cả.
     */
    public function index()
    {
        if (!has_permission('leave.view')) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không có quyền truy cập module Nghỉ phép.');
        }

        $myEmpId = session()->get('employee_id');
        $roleName = session()->get('role_name');
        $deptId = session()->get('department_id');

        $filters = [
            'status'        => $this->request->getGet('status'),
            'department_id' => $this->request->getGet('department_id'),
            'month'         => $this->request->getGet('month'),
            'search'        => $this->request->getGet('search')
        ];

        $query = $this->model->getLeaveRequests($filters);

        // BẢO VỆ DỮ LIỆU: Phân vùng truy cập (Hierarchy-Centric)
        if (!has_permission('sys.admin') && !has_permission('leave.approve')) {
            // Nhân viên thường: Chỉ thấy đơn của chính mình
            $query->where('leave_requests.employee_id', $myEmpId);
        } elseif (has_permission('leave.approve') && !has_permission('sys.admin')) {
            // QUẢN LÝ (Hierarchy-Centric): Thấy của mình + Nhân viên có manager_id là mình
            $query->groupStart()
                    ->where('leave_requests.employee_id', $myEmpId)
                    ->orWhereIn('leave_requests.employee_id', function($builder) use ($myEmpId) {
                        return $builder->select('id')->from('employees')->where('manager_id', $myEmpId);
                    })
                  ->groupEnd();
        }

        $data = [
            'title'       => 'Quản lý Nghỉ phép | L.A.N ERP',
            'requests'    => $query->paginate(20),
            'pager'       => $this->model->pager,
            'departments' => get_departments(), // Core Function
            'filters'     => $filters,
            'statusLabels' => [
                'pending'   => 'Đang chờ duyệt',
                'approved'  => 'Đã phê duyệt',
                'rejected'  => 'Đã từ chối',
                'cancelled' => 'Đã hủy'
            ],
            'leaveTypeLabels' => [
                'annual'    => 'Nghỉ phép năm',
                'personal'  => 'Nghỉ có lương',
                'unpaid'    => 'Nghỉ không lương',
            ]
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/leave_requests/index_table', $data);
        }

        return view('dashboard/leave_requests/index', $data);
    }

    /**
     * Form tạo đơn nghỉ phép.
     */
    public function create()
    {
        if (!has_permission('leave.manage')) {
            return redirect()->back()->with('error', 'Bạn không được phép gửi đơn nghỉ phép.');
        }

        $data = [
            'title' => 'Gửi đơn xin nghỉ phép | L.A.N ERP',
            'staffs' => get_available_employees(), // Core helper function
            'leaveTypes' => [
                'annual'   => 'Nghỉ phép năm (P)',
                'personal' => 'Nghỉ có lương (Công tác/Việc riêng)',
                'unpaid'   => 'Nghỉ không lương',
            ]
        ];

        return view('dashboard/leave_requests/create', $data);
    }

    /**
     * LƯU TRỮ ĐƠN NGHỈ PHÉP (Rule #2: Controller-to-Service Dispatcher)
     * Chỉ nhận dữ liệu và đẩy xuống Service xử lý.
     */
    public function store()
    {
        if (!has_permission('leave.manage')) {
            return redirect()->back()->with('error', 'Bạn không được phép gửi đơn nghỉ phép.');
        }

        // Nhận dữ liệu sạch từ Request
        $data = $this->request->getPost();
        
        // Gán ID nhân viên từ Session để bảo mật (Rule #7)
        $data['employee_id'] = session()->get('employee_id');

        // Đẩy toàn bộ cho Service xử lý logic và Rule 1
        $result = $this->service->create($data);

        if ($result['status'] === 'success') {
            return redirect()->to('leave-requests')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Phê duyệt / Từ chối đơn.
     */
    public function approve($id)
    {
        if (!has_permission('leave.approve')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có thẩm quyền phê duyệt đơn nghỉ phép.']);
        }

        $action = $this->request->getPost('action'); // approved / rejected
        $note = $this->request->getPost('note');
        $approverId = session()->get('employee_id');

        if ($action === 'approved') {
            $result = $this->service->approve($id, $approverId, $note);
        } else {
            $result = $this->service->reject($id, $approverId, $note);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($result);
        }

        return redirect()->back()->with($result['status'], $result['message']);
    }

    /**
     * Hủy đơn (Dành cho nhân viên khi đơn đang ở trạng thái Pending).
     */
    public function cancel($id)
    {
        $request = $this->model->find($id);
        if (!$request || $request['employee_id'] != session()->get('employee_id')) {
             return redirect()->back()->with('error', 'Không tìm thấy đơn hoặc bạn không phải chủ sở hữu.');
        }

        if ($request['status'] !== 'pending') {
            return redirect()->back()->with('error', 'Đơn đã được xử lý, không thể tự hủy.');
        }

        $this->model->update($id, ['status' => 'cancelled']);
        return redirect()->back()->with('success', 'Đơn nghỉ phép đã được hủy thành công.');
    }

    /**
     * Xóa đơn lẻ (Chỉ Admin).
     */
    public function delete($id)
    {
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Chỉ Quản trị viên mới có quyền xóa đơn đã duyệt.');
        }

        $result = $this->service->delete($id);
        return redirect()->back()->with($result['status'], $result['message']);
    }

    /**
     * Form chỉnh sửa đơn (Chỉ dành cho Admin sửa đơn đã duyệt hoặc của nhân viên sai).
     */
    public function edit($id)
    {
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Bạn không có quyền chỉnh sửa đơn nghỉ phép này.');
        }

        $request = $this->model->select('leave_requests.*, e.full_name as employee_name')
                               ->join('employees e', 'e.id = leave_requests.employee_id')
                               ->find($id);
        if (!$request) {
            return redirect()->to('leave-requests')->with('error', 'Không tìm thấy đơn.');
        }

        $data = [
            'title'     => 'Chỉnh sửa đơn nghỉ phép | L.A.N ERP',
            'request'   => $request,
            'staffs'    => get_available_employees(),
            'leaveTypes' => [
                'annual'   => 'Nghỉ phép năm (P)',
                'personal' => 'Nghỉ có lương (Công tác/Việc riêng)',
                'unpaid'   => 'Nghỉ không lương',
            ]
        ];

        return view('dashboard/leave_requests/edit', $data);
    }

    /**
     * Cập nhật đơn.
     */
    public function update($id)
    {
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Từ chối quyền truy cập.');
        }

        $data = $this->request->getPost();
        
        // Xử lý Checkbox (Trường hợp bỏ tích khẩn cấp sẽ không gửi lên Post)
        $data['is_emergency'] = isset($data['is_emergency']) ? 1 : 0;

        $result = $this->service->update((int)$id, $data);

        if ($result['status'] === 'success') {
            return redirect()->to('leave-requests')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Xóa chọn đơn nghỉ phép (Bulk Action) - Chỉ Admin.
     */
    public function bulkDelete()
    {
        if (!has_permission('sys.admin')) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Chỉ Quản trị viên mới được thực hiện thao tác này.']);
        }

        $ids = $this->request->getPost('ids');
        if (empty($ids) || !is_array($ids)) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Danh sách chọn trống.']);
        }

        $success = 0;
        foreach ($ids as $id) {
            $res = $this->service->delete((int)$id);
            if ($res['status'] === 'success') {
                $success++;
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Đã dọn dẹp {$success} đơn nghỉ phép đã chọn."
        ]);
    }
}
