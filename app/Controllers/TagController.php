<?php

namespace App\Controllers;

use App\Services\TagService;
use CodeIgniter\Controller;

/**
 * TagController
 * 
 * Trung tâm quản trị Nhãn dán (Tags Management).
 * Cho phép nhân viên quản lý nhãn cá nhân và Admin/Quản lý điều phối nhãn toàn hệ thống.
 */
class TagController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $modulePermissions = [
        'group' => 'Hệ thống',
        'permissions' => [
            'tag.manage' => 'Quản trị hệ thống Nhãn dán (Phân loại & Ghi nhãn)'
        ]
    ];

    protected $tagService;

    public function __construct()
    {
        $this->tagService = new TagService();
    }

    /**
     * Danh sách toàn bộ nhãn dán thuộc quyền quản lý/truy cập của người dùng.
     */
    public function index()
    {
        $currentEmpId = session()->get('employee_id');
        $roleName = session()->get('role_name');
        
        // Admin: Xem được TẤT CẢ mọi tag để điều phối
        // Người dùng khác: Chỉ xem được Global + Private của chính mình
        if (has_permission('sys.admin')) {
            $tags = model('TagModel')->findAll();
        } else {
            $tags = $this->tagService->getAvailableTags('all', $currentEmpId);
        }

        // Lấy danh sách Module được phép gắn nhãn từ Master Registry (Auto-Sync)
        $registryPath = WRITEPATH . 'tag_modules.json';
        $taggableModules = [];
        if (file_exists($registryPath)) {
            $taggableModules = json_decode(file_get_contents($registryPath), true);
        }

        $data = [
            'tags'            => $tags,
            'taggableModules' => $taggableModules,
            'title'           => 'Quản lý Nhãn dán | L.A.N ERP',
            'isPowerUser'     => (has_permission('sys.admin') || has_permission('case.manage') || strpos(strtolower($roleName), 'trưởng phòng') !== false)
        ];

        return view('dashboard/tags/index', $data);
    }

    /**
     * Khởi tạo nhãn dán mới.
     */
    public function store()
    {
        $postData = $this->request->getPost();
        
        $result = $this->tagService->createTag([
            'name' => $postData['name'],
            'color' => $postData['color'],
            'type' => $postData['type'] ?? 'private',
            'module_scope' => $postData['module_scope'] ?? 'all'
        ], session()->get('employee_id'), session()->get('role_name'));

        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', 'Nhãn dán đã được tạo thành công.');
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Cập nhật thông tin nhãn dán.
     */
    public function update($id)
    {
        $postData = $this->request->getPost();
        $result = $this->tagService->updateTag($id, [
            'name' => $postData['name'],
            'color' => $postData['color'],
            'type' => $postData['type'] ?? 'private',
            'module_scope' => $postData['module_scope'] ?? 'all'
        ], session()->get('employee_id'));

        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', 'Nhãn dán đã được cập nhật.');
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Xóa bỏ hoàn toàn một nhãn dán.
     */
    public function delete($id)
    {
        $result = $this->tagService->deleteTag($id, session()->get('employee_id'));

        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', 'Nhãn dán đã được xóa.');
        }

        return redirect()->back()->with('error', $result['message']);
    }
    /**
     * Xem danh sách tất cả các đối tượng (Vụ việc, Khách hàng...) được gắn vào Nhãn này.
     */
    public function show($id)
    {
        $tag = model('TagModel')->find($id);
        if (!$tag) {
            return redirect()->to(base_url('tags'))->with('error', 'Nhãn dán không tồn tại.');
        }

        // Kiểm tra quyền truy cập tag
        if ($tag['type'] === 'private' && $tag['owner_id'] != session()->get('employee_id')) {
            return redirect()->to(base_url('tags'))->with('error', 'Bạn không có quyền xem nhãn dán cá nhân này.');
        }

        $entities = $this->tagService->getTaggedEntities($id);

        $data = [
            'tag'      => $tag,
            'entities' => $entities,
            'title'    => 'Các mục được gắn nhãn: ' . $tag['name'] . ' | L.A.N ERP'
        ];

        return view('dashboard/tags/view', $data);
    }

    /**
     * AJAX/POST: Cập nhật nhanh nhãn dán cho một đối tượng (Quick Tagging).
     */
    public function updateEntityTags()
    {
        $entityId = $this->request->getPost('entity_id');
        $entityType = $this->request->getPost('entity_type');
        $tagIds = $this->request->getPost('tag_ids');
        if (!is_array($tagIds)) $tagIds = []; // Quan trọng: Đảm bảo luôn là mảng

        if (!$entityId || !$entityType) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Dữ liệu không đầy đủ.']);
        }

        // Thực hiện đồng bộ (Dịch vụ đã được nâng cấp để bảo vệ Tag Private của người khác)
        $result = $this->tagService->syncTags((int)$entityId, $entityType, $tagIds);

        if ($result['status'] === 'success') {
            // Trả về danh sách nhãn mới để UI cập nhật
            $newTags = $this->tagService->getTagsByEntity($entityId, $entityType);
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => $result['message'],
                'tags' => $newTags
            ]);
        }

        return $this->response->setJSON($result);
    }

    /**
     * AJAX/GET: Lấy danh sách tag hiện tại của một thực thể.
     * Dùng để điền dữ liệu (pre-load) vào Select2 khi mở Modal.
     */
    public function getEntityTags()
    {
        $entityId = $this->request->getGet('entity_id');
        $entityType = $this->request->getGet('entity_type');
        
        if (!$entityId || !$entityType) {
            return $this->response->setJSON([]);
        }
        
        $tags = $this->tagService->getTagsByEntity((int)$entityId, $entityType);
        return $this->response->setJSON($tags);
    }
}
