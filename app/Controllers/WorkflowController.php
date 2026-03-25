<?php

namespace App\Controllers;

use App\Services\WorkflowService;
use CodeIgniter\API\ResponseTrait;

/**
 * WorkflowController
 * 
 * Bộ điều khiển quản lý các Quy trình mẫu (Workflow Templates).
 * Chức năng:
 * 1. Định nghĩa các luồng làm việc chuẩn (Standard Workflows).
 * 2. Cấp cấu hình danh sách các bước, thời gian thực hiện dự kiến và tài liệu bắt buộc.
 * 3. Cho phép Admin sao chép và tinh chỉnh quy trình để phục vụ tự động hóa quản lý vụ việc.
 */
class WorkflowController extends BaseController
{
    use ResponseTrait;

    protected $workflowService;

    public function __construct()
    {
        // Khởi tạo Service xử lý nghiệp vụ quy trình
        $this->workflowService = new WorkflowService();
    }

    /**
     * Hiển thị danh sách toàn bộ các quy trình mẫu đang có trên hệ thống.
     */
    public function index()
    {
        // BIỆN PHÁP BẢO VỆ: Chỉ Admin mới có quyền truy cập cấu trúc ERP
        $this->checkAdmin();

        $data = [
            'title'     => 'Quản lý Quy trình mẫu | L.A.N ERP',
            'templates' => $this->workflowService->getAllTemplates() // Lấy dữ liệu từ DB
        ];

        return view('dashboard/workflows/index', $data);
    }

    /**
     * Hiển thị giao diện tạo mới một quy trình chung (Thông tin cơ bản).
     */
    public function create()
    {
        $this->checkAdmin();

        $data = [
            'title' => 'Khởi tạo Quy trình nghiệp vụ mới'
        ];

        return view('dashboard/workflows/create', $data);
    }

    /**
     * Xử lý lưu trữ thông tin cơ bản của quy trình mẫu mới.
     */
    public function store()
    {
        $this->checkAdmin();

        // 1. Thu thập dữ liệu từ Form gửi lên
        $data = $this->request->getPost();
        $data['created_by'] = session()->get('user_id'); // Ghi nhận định danh người tạo

        try {
            // 2. Lưu vào Database thông qua Service
            $templateId = $this->workflowService->createTemplate($data);
            
            if (!$templateId) {
                return redirect()->back()->withInput()->with('error', 'Lỗi: Không thể khởi tạo bản mẫu. Mã hoặc tên quy trình có thể đã tồn tại.');
            }

            // 3. Sau khi tạo thông tin chung thành công, chuyển hướng đến trang thiết lập chi tiết các bước thực hiện.
            return redirect()->to(base_url('workflows/steps/' . $templateId))->with('success', 'Đã khởi tạo quy trình thành công. Vui lòng thiết lập danh sách các bước thực hiện.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Lỗi ngoại lệ: ' . $e->getMessage());
        }
    }

    /**
     * Giao diện Thiết lập Bước (Steps Configuration Matrix).
     * Đây là tính năng quan trọng nhất để định nghĩa trình tự thực hiện của một vụ việc.
     * 
     * @param int $id ID của bản mẫu quy trình.
     */
    public function steps($id)
    {
        $this->checkAdmin();

        // 1. Kiểm tra tính hợp lệ của quy trình
        $template = $this->workflowService->getTemplateById($id);
        if (!$template) {
            return redirect()->to(base_url('workflows'))->with('error', 'Hệ thống không tìm thấy bản mẫu yêu cầu.');
        }

        // 2. Chuẩn bị dữ liệu hiển thị (Roles, Employees, Hiện trạng các bước)
        $data = [
            'title'     => 'Cấu hình trình tự các bước: ' . $template['name'],
            'template'  => $template,
            'steps'     => $this->workflowService->getStepsByTemplateId($id),
            'roles'     => [
                'admin'        => 'Admin (Phê duyệt cấp cao)',
                'truong_phong' => 'Trưởng phòng (Quản lý trực tiếp)',
                'nhan_vien'    => 'Nhân viên (Trực tiếp xử lý)',
                'tu_van'       => 'Tư vấn viên (Hỗ trợ hồ sơ)'
            ],
            // Lấy danh sách nhân sự để có thể chỉ định người mặc định xử lý một bước (Assignment)
            'employees' => model('EmployeeModel')->select('id, full_name, position')->findAll()
        ];

        return view('dashboard/workflows/steps', $data);
    }

    /**
     * Tiếp nhận và đồng bộ các thay đổi về danh sách bước quy trình.
     * Cập nhật theo cơ chế Sync (Xóa cũ - Chèn mới theo thứ tự gán nhãn).
     */
    public function updateSteps($id)
    {
        $this->checkAdmin();

        // 1. Phân tích mảng các bước gửi từ UI (Client-side JSON/Form)
        $steps = $this->request->getPost('steps');
        if (empty($steps)) {
            return redirect()->back()->with('error', 'Cảnh báo: Dữ liệu trình tự bước đang bị để trống.');
        }

        try {
            // 2. Thực hiện đồng bộ logic (Cập nhật order, duration, docs...)
            $this->workflowService->syncSteps($id, $steps);
            return redirect()->to(base_url('workflows'))->with('success', 'Dữ liệu quy trình đã được đồng bộ hóa thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi đồng bộ: ' . $e->getMessage());
        }
    }

    /**
     * Chỉnh sửa thông tin định danh của quy trình mẫu.
     */
    public function edit($id)
    {
        $this->checkAdmin();
        $template = $this->workflowService->getTemplateById($id);
        if (!$template) return redirect()->to(base_url('workflows'))->with('error', 'Không tìm thấy quy trình.');

        return view('dashboard/workflows/edit', [
            'template'   => $template, 
            'title'      => 'Cập nhật thông tin quy trình'
        ]);
    }

    /**
     * Lưu trữ các thay đổi của bản mẫu quy trình.
     */
    public function update($id)
    {
        $this->checkAdmin();
        $data = $this->request->getPost();
        
        // Chế biến dữ liệu trạng thái kích hoạt (Active/Inactive)
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;

        try {
            $this->workflowService->updateTemplate($id, $data);
            return redirect()->to(base_url('workflows'))->with('success', 'Thông tin quy trình đã được cập nhật.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Lỗi cập nhật: ' . $e->getMessage());
        }
    }

    /**
     * Xóa bỏ quy trình mẫu khỏi danh mục quản lý.
     */
    public function delete($id)
    {
        $this->checkAdmin();

        if ($this->workflowService->deleteTemplate($id)) {
            return redirect()->to(base_url('workflows'))->with('success', 'Đã loại bỏ quy trình khỏi hệ thống.');
        }

        return redirect()->to(base_url('workflows'))->with('error', 'Lỗi: Quy trình đang được sử dụng hoặc không thể xóa.');
    }

    /**
     * CƠ CHẾ KIỂM TRA QUYỀN TRỊ (Security Guard).
     * Đảm bảo chỉ người dùng tối cao mới được can thiệp vào Logic hệ thống.
     */
    private function checkAdmin()
    {
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN) {
            // Ném lỗi 404 để kẻ gian không biết trang này tồn tại (Security by Obscurity)
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }
}
