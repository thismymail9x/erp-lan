<?php

namespace App\Controllers;

use App\Services\WorkflowService;
use CodeIgniter\API\ResponseTrait;

class WorkflowController extends BaseController
{
    use ResponseTrait;

    protected $workflowService;

    public function __construct()
    {
        $this->workflowService = new WorkflowService();
    }

    /**
     * Danh sách các quy trình mẫu
     */
    public function index()
    {
        $this->checkAdmin();

        $data = [
            'title' => 'Quản lý Quy trình mẫu | L.A.N ERP',
            'templates' => $this->workflowService->getAllTemplates()
        ];

        return view('dashboard/workflows/index', $data);
    }

    /**
     * Form tạo quy trình mới
     */
    public function create()
    {
        $this->checkAdmin();

        $data = [
            'title' => 'Tạo Quy trình mới',
            'case_types' => [
                'to_tung_dan_su' => 'Tố tụng Dân sự',
                'thu_tuc_hanh_chinh' => 'Thủ tục Hành chính',
                'xoa_an_tich' => 'Xóa án tích',
                'ly_hon_thuan_tinh' => 'Ly hôn thuận tình',
                'tu_van' => 'Tư vấn pháp lý'
            ]
        ];

        return view('dashboard/workflows/create', $data);
    }

    /**
     * Lưu quy trình mới
     */
    public function store()
    {
        $this->checkAdmin();

        $data = $this->request->getPost();
        $data['created_by'] = session()->get('user_id');

        try {
            $templateId = $this->workflowService->createTemplate($data);
            
            if (!$templateId) {
                return redirect()->back()->withInput()->with('error', 'Không thể tạo bản mẫu. Vui lòng kiểm tra lại dữ liệu (Mã hoặc tên có thể bị trùng).');
            }

            return redirect()->to(base_url('workflows/steps/' . $templateId))->with('success', 'Đã tạo quy trình. Hãy thiết lập các bước.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * Giao diện thiết lập các bước cho quy trình
     */
    public function steps($id)
    {
        $this->checkAdmin();

        $template = $this->workflowService->getTemplateById($id);
        if (!$template) return redirect()->to(base_url('workflows'))->with('error', 'Không tìm thấy quy trình.');

        $data = [
            'title' => 'Thiết lập bước: ' . $template['name'],
            'template' => $template,
            'steps' => $this->workflowService->getStepsByTemplateId($id),
            'roles' => [
                'admin' => 'Admin (Toàn quyền)',
                'truong_phong' => 'Trưởng phòng (Quản lý)',
                'nhan_vien' => 'Nhân viên (Thực hiện)',
                'tu_van' => 'Tư vấn viên (Hỗ trợ)'
            ],
            'employees' => model('EmployeeModel')->select('id, full_name, position')->findAll()
        ];

        return view('dashboard/workflows/steps', $data);
    }

    /**
     * Cập nhật danh sách các bước (AJAX hoặc Post)
     */
    public function updateSteps($id)
    {
        $this->checkAdmin();

        $steps = $this->request->getPost('steps');
        if (empty($steps)) return redirect()->back()->with('error', 'Dữ liệu bước không hợp lệ.');

        try {
            $this->workflowService->syncSteps($id, $steps);
            return redirect()->to(base_url('workflows'))->with('success', 'Đã cập nhật các bước quy trình.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Form chỉnh sửa thông tin bản mẫu
     */
    public function edit($id)
    {
        $this->checkAdmin();
        $template = $this->workflowService->getTemplateById($id);
        if (!$template) return redirect()->to(base_url('workflows'))->with('error', 'Không tìm thấy quy trình.');

        return view('dashboard/workflows/edit', ['template' => $template, 'title' => 'Sửa quy trình']);
    }

    /**
     * Cập nhật thông tin bản mẫu
     */
    public function update($id)
    {
        $this->checkAdmin();
        $data = $this->request->getPost();
        
        // Xử lý checkbox is_active
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;

        try {
            $this->workflowService->updateTemplate($id, $data);
            return redirect()->to(base_url('workflows'))->with('success', 'Đã cập nhật thông tin quy trình.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Xóa quy trình
     */
    public function delete($id)
    {
        $this->checkAdmin();

        if ($this->workflowService->deleteTemplate($id)) {
            return redirect()->to(base_url('workflows'))->with('success', 'Đã xóa quy trình.');
        }

        return redirect()->to(base_url('workflows'))->with('error', 'Không thể xóa quy trình này.');
    }

    /**
     * Phương thức kiểm tra quyền Admin nhanh
     */
    private function checkAdmin()
    {
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }
}
