<?php

namespace App\Services;

use App\Models\TagModel;
use App\Models\EntityTagModel;

/**
 * TagService
 * 
 * Lớp điều phối logic Gắn nhãn thông minh (Smart Tagging Service).
 * Hỗ trợ phân loại Thẻ Chung/Riêng và Liên kết đa hình cho Vụ việc/Khách hàng.
 */
class TagService extends BaseService
{
    protected $tagModel;
    protected $entityTagModel;

    public function __construct()
    {
        parent::__construct();
        $this->tagModel = new TagModel();
        $this->entityTagModel = new EntityTagModel();
    }

    /**
     * Lấy danh mục thẻ khả dụng (Thẻ Chung + Thẻ Cá nhân của User hiện tại).
     */
    public function getAvailableTags(string $module = 'all', int $ownerId = null)
    {
        $isAdmin = has_permission('sys.admin');
        $ownerId = $ownerId ?? session()->get('employee_id');

        $query = $this->tagModel->select('tags.*');
        
        // Tối ưu: Đếm số lượng (Sửa lỗi cột id và lọc theo module để số lượng chính xác với bối cảnh)
        $countFilter = ($module !== 'all') ? " AND entity_type = '{$module}'" : "";
        $query->select("(SELECT COUNT(*) FROM entity_tags WHERE tag_id = tags.id {$countFilter}) as usage_count");

        if (!$isAdmin || ($ownerId !== null && $ownerId !== -1)) {
            $query->groupStart()
                ->where('type', 'global')
                ->orWhere('owner_id', $ownerId)
            ->groupEnd();
        }

        if ($module !== 'all') {
            $query->groupStart()
                ->where('module_scope', 'all')
                ->orWhere('module_scope', $module)
            ->groupEnd();
        }

        return $query->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Lấy danh sách thẻ đã gắn cho một thực thể cụ thể.
     */
    /**
     * Lấy danh sách các nhãn đang được gắn vào một đối tượng cụ thể.
     * Cảnh báo: Chỉ trả về nhãn Global hoặc nhãn Private của chính người dùng đó.
     * 
     * @param int $entityId ID của đối tượng (Vụ việc, Khách hàng...)
     * @param string $entityType Loại đối tượng ('cases', 'customers', 'documents')
     * @param int|null $currentUserId ID người dùng hiện tại để lọc nhãn Private
     */
    public function getTagsByEntity(int $entityId, string $entityType, int $currentUserId = null)
    {
        $currentUserId = $currentUserId ?? session()->get('employee_id');

        return $this->tagModel->select('tags.*')
            ->join('entity_tags', 'entity_tags.tag_id = tags.id')
            ->where('entity_tags.entity_id', $entityId)
            ->where('entity_tags.entity_type', $entityType)
            ->groupStart()
                ->where('tags.type', 'global')
                ->orWhere('tags.owner_id', $currentUserId)
            ->groupEnd()
            ->findAll();
    }

    /**
     * Lấy danh sách toàn bộ các đối tượng đang sử dụng nhãn dán này.
     * @param int $tagId ID của nhãn
     */
    public function getTaggedEntities(int $tagId)
    {
        $db = \Config\Database::connect();
        $tag = $this->tagModel->find($tagId);
        if (!$tag) return [];

        // Kiểm tra quyền xem tag (Nếu là Private thì phải là chủ sở hữu)
        if ($tag['type'] === 'private' && $tag['owner_id'] != session()->get('employee_id')) {
            return [];
        }

        $results = [];

        // Truy vấn tất cả liên kết từ bảng trung gian
        $links = $db->table('entity_tags')->where('tag_id', $tagId)->get()->getResultArray();

        foreach ($links as $link) {
            $entity = null;
            switch ($link['entity_type']) {
                case 'customers':
                    $entity = $db->table('customers')->where('id', $link['entity_id'])->get()->getRowArray();
                    if ($entity) {
                        $results[] = [
                            'type' => 'Khách hàng',
                            'name' => $entity['name'],
                            'code' => $entity['code'],
                            'url'  => base_url('customers/show/' . $entity['id']),
                            'date' => null
                        ];
                    }
                    break;
                case 'cases':
                    $entity = $db->table('cases')
                        ->select('cases.*, customers.name as customer_name, customers.id as customer_id')
                        ->join('customers', 'customers.id = cases.customer_id', 'left')
                        ->where('cases.id', $link['entity_id'])
                        ->get()->getRowArray();

                    if ($entity) {
                        $results[] = [
                            'type' => 'Vụ việc',
                            'name' => $entity['title'],
                            'code' => $entity['code'],
                            'url'  => base_url('cases/show/' . $entity['id']),
                            'customer_name' => $entity['customer_name'] ?? null,
                            'customer_url'  => $entity['customer_id'] ? base_url('customers/show/' . $entity['customer_id']) : null,
                            'date' => $link['created_at'] ?? null 
                        ];
                    }
                    break;
                case 'documents':
                    $entity = $db->table('documents')->where('id', $link['entity_id'])->get()->getRowArray();
                    if ($entity) {
                        $results[] = [
                            'type' => 'Tài liệu',
                            'name' => $entity['file_name'],
                            'code' => 'DMS-' . $entity['id'],
                            'url'  => base_url('documents/view/' . $entity['id']),
                            'date' => null
                        ];
                    }
                    break;
            }
        }

        return $results;
    }

    /**
     * Đồng bộ hóa nhãn dán cho một đối tượng (Vụ việc, Khách hàng...).
     * CHÚ Ý BẢO MẬT: Chỉ đồng bộ (Thêm/Xóa) những nhãn mà người dùng hiện tại THẤY ĐƯỢC.
     * Tránh việc xóa nhãn Private của người dùng khác đang cùng gán vào đối tượng này.
     * 
     * @param int $entityId ID đối tượng
     * @param string $entityType Loại đối tượng
     * @param array $tagIds Danh sách ID các nhãn mới muốn gán
     */
    public function syncTags(int $entityId, string $entityType, array $tagsInput)
    {
        $currentEmpId = session()->get('employee_id');
        $resolvedTagIds = [];
        
        // 1. Phân loại và chuyển đổi Input sang ID nhãn
        foreach ($tagsInput as $input) {
            if (is_numeric($input)) {
                $resolvedTagIds[] = (int)$input;
            } else {
                // Nếu là chuỗi (Tên nhãn), tìm hoặc tạo mới
                $tag = $this->tagModel->where('name', trim($input))->first();
                if ($tag) {
                    $resolvedTagIds[] = (int)$tag['id'];
                } else {
                    // Tạo nhãn mới (Mặc định là Global hoặc theo quyền - tham khảo logic createTag)
                    $newTagId = $this->tagModel->insert([
                        'name' => trim($input),
                        'type' => 'global', // Mặc định cho DMS
                        'color' => '#6c757d',
                        'module_scope' => $entityType
                    ]);
                    if ($newTagId) $resolvedTagIds[] = (int)$newTagId;
                }
            }
        }
        
        $resolvedTagIds = array_unique($resolvedTagIds);

        // 2. Xác định các nhãn nhân viên này có quyền quản lý để đồng bộ (Global + Cá nhân)
        $availableTags = $this->getAvailableTags('all', $currentEmpId);
        $availableTagIds = array_column($availableTags, 'id');

        // 3. Thực thi truy vấn đồng bộ hóa
        $db = \Config\Database::connect();
        try {
            $db->transBegin();

            // Bước A: XÓA các liên kết cũ mà User có quyền nhìn thấy trong phạm vi quản lý
            $builder = $db->table('entity_tags')
                ->where('entity_id', $entityId)
                ->where('entity_type', $entityType);
            
            if (!empty($availableTagIds)) {
                $builder->whereIn('tag_id', $availableTagIds);
            }
            $builder->delete();

            // Bước B: GÁN các liên kết mới
            if (!empty($resolvedTagIds)) {
                $insertData = [];
                foreach ($resolvedTagIds as $tid) {
                    $insertData[] = [
                        'tag_id'      => $tid,
                        'entity_id'   => $entityId,
                        'entity_type' => $entityType
                    ];
                }
                $db->table('entity_tags')->insertBatch($insertData);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return ['status' => 'error', 'message' => 'Không thể đồng bộ nhãn dán.'];
            }

            $db->transCommit();
            return ['status' => 'success', 'message' => 'Cập nhật nhãn dán thành công.'];

        } catch (\Exception $e) {
            if ($db->transStatus() === false) $db->transRollback();
            return ['status' => 'error', 'message' => 'Lỗi hệ thống khi lưu nhãn: ' . $e->getMessage()];
        }
    }

    /**
     * Khởi tạo nhãn dán mới (Hỗ trợ phân quyền chặt chẽ).
     * Rule: Chỉ Admin/Trưởng phòng được tạo Tag Toàn Cục (Global).
     */
    public function createTag(array $data, int $currentEmployeeId, string $currentRole)
    {
        // Tự động gán màu xám nhạt nếu không chọn màu
        if (empty($data['color'])) $data['color'] = '#6c757d';
        
        $isPowerUser = (has_permission('sys.admin') || strpos(strtolower($currentRole), 'trưởng phòng') !== false || $currentRole === 'admin');

        // Logic phân loại tag:
        // Nếu yêu cầu tạo Global nhưng không có quyền -> Chuyển về Private
        if ($data['type'] === 'global' && !$isPowerUser) {
            $data['type'] = 'private';
            $data['owner_id'] = $currentEmployeeId;
        }

        // Nếu là Private -> Bắt buộc gán owner_id
        if ($data['type'] === 'private') {
            $data['owner_id'] = $currentEmployeeId;
        }

        $id = $this->tagModel->insert($data);
        if ($id) {
            return $this->success(['id' => $id], 'Nhãn dán đã được tạo và sẵn sàng sử dụng.');
        }

        return $this->fail('Lỗi khởi tạo nhãn dán. Vui lòng kiểm tra lại tên nhãn.');
    }

    /**
     * Cập nhật thông tin nhãn dán.
     * Rule: Chỉ Admin hoặc Người tạo mới được sửa.
     */
    public function updateTag(int $id, array $data, int $currentEmployeeId)
    {
        $tag = $this->tagModel->find($id);
        if (!$tag) return $this->fail('Không tìm thấy nhãn dán.');

        // Quyền hạn: Admin có toàn quyền. Nếu không phải Admin, phải là người sở hữu.
        $isAdmin = has_permission('sys.admin');
        if (!$isAdmin && $tag['owner_id'] != $currentEmployeeId) {
            return $this->fail('Bạn không có quyền chỉnh sửa nhãn dán của người khác.');
        }

        if ($this->tagModel->update($id, $data)) {
            return $this->success(null, 'Nhãn dán đã được cập nhật.');
        }
        return $this->fail('Cập nhật nhãn dán thất bại.');
    }

    /**
     * Xóa bỏ hoàn toàn một nhãn dán khỏi danh mục (Soft Delete).
     * Rule: Chỉ Admin hoặc Người tạo mới được xóa.
     */
    public function deleteTag(int $id, int $currentEmployeeId)
    {
        $tag = $this->tagModel->find($id);
        if (!$tag) return $this->fail('Không tìm thấy nhãn dán.');

        // Quyền hạn: Admin có toàn quyền. Nếu không phải Admin, phải là người sở hữu.
        $isAdmin = has_permission('sys.admin');
        if (!$isAdmin && $tag['owner_id'] != $currentEmployeeId) {
            return $this->fail('Bạn không có quyền xóa nhãn dán của người khác.');
        }

        if ($this->tagModel->delete($id)) {
            return $this->success(null, 'Nhãn dán đã được gỡ bỏ khỏi hệ thống.');
        }
        return $this->fail('Xóa nhãn dán thất bại.');
    }
}
