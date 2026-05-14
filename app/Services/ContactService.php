<?php

namespace App\Services;

use App\Models\ContactModel;

/**
 * ContactService
 * 
 * Lớp xử lý nghiệp vụ cho Module Liên hệ.
 * Tuân thủ quy tắc 2 (Logic nghiệp vụ đưa vào Service) và quy tắc 6 (deleted_at IS NULL).
 */
class ContactService extends BaseService
{
    protected $contactModel;

    public function __construct()
    {
        parent::__construct();
        $this->contactModel = new ContactModel();
    }

    /**
     * Lấy danh sách liên hệ có phân trang và lọc.
     * 
     * @param array $filters Mảng các điều kiện lọc (search, province, is_private)
     * @param int $perPage Số bản ghi trên mỗi trang (Quy tắc 11: Mặc định 20)
     * @return array
     */
    public function getContactList(array $filters = [], int $perPage = 20)
    {
        $builder = $this->contactModel->builder();
        
        // QUY TẮC VÀNG (Số 6): Luôn kiểm tra deleted_at IS NULL
        $builder->where('deleted_at IS NULL');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                    ->like('unit_name', $search)
                    ->orLike('phone', $search)
                    ->orLike('position', $search)
                    ->orLike('address', $search)
                ->groupEnd();
        }

        if (!empty($filters['province'])) {
            $builder->where('province', $filters['province']);
        }

        if (!empty($filters['source'])) {
            $builder->like('source', $filters['source']);
        }

        if (isset($filters['is_private']) && $filters['is_private'] !== '') {
            $builder->where('is_private', $filters['is_private']);
        }

        $builder->orderBy('created_at', 'DESC');

        // Thực hiện phân trang
        $data = $this->contactModel->paginate($perPage, 'default');
        $pager = $this->contactModel->pager;

        return [
            'contacts' => $data,
            'pager'    => $pager
        ];
    }

    /**
     * Lưu thông tin liên hệ (Tạo mới hoặc Cập nhật)
     */
    public function saveContact(array $data, int $userId, $id = null)
    {
        try {
            $data['created_by'] = $userId;
            
            if ($id) {
                // Kiểm tra tồn tại và chưa xóa
                $existing = $this->contactModel->where('id', $id)->where('deleted_at IS NULL')->first();
                if (!$existing) {
                    return $this->fail('Liên hệ không tồn tại hoặc đã bị xóa.');
                }
                
                if ($this->contactModel->update($id, $data)) {
                    return $this->success($id, 'Cập nhật liên hệ thành công.');
                }
            } else {
                if ($newId = $this->contactModel->insert($data)) {
                    return $this->success($newId, 'Thêm liên hệ mới thành công.');
                }
            }

            return $this->fail('Không thể lưu dữ liệu. Vui lòng kiểm tra lại.');
        } catch (\Exception $e) {
            $this->logError('Lỗi saveContact: ' . $e->getMessage(), ['id' => $id, 'data' => $data]);
            return $this->fail('Đã xảy ra lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * Xóa mềm liên hệ
     */
    public function deleteContact(int $id)
    {
        try {
            if ($this->contactModel->delete($id)) {
                return $this->success($id, 'Đã xóa liên hệ thành công.');
            }
            return $this->fail('Không thể xóa liên hệ.');
        } catch (\Exception $e) {
            $this->logError('Lỗi deleteContact: ' . $e->getMessage(), ['id' => $id]);
            return $this->fail('Lỗi hệ thống khi xóa.');
        }
    }

    /**
     * Cập nhật trạng thái Private hàng loạt
     */
    public function togglePrivateBatch(array $ids, int $status)
    {
        try {
            if (empty($ids)) {
                return $this->fail('Vui lòng chọn ít nhất một bản ghi.');
            }

            $this->contactModel->whereIn('id', $ids)
                               ->set(['is_private' => $status])
                               ->update();

            return $this->success(null, 'Đã cập nhật trạng thái Private cho ' . count($ids) . ' bản ghi.');
        } catch (\Exception $e) {
            $this->logError('Lỗi togglePrivateBatch: ' . $e->getMessage(), ['ids' => $ids]);
            return $this->fail('Lỗi hệ thống khi cập nhật hàng loạt.');
        }
    }

    /**
     * Xử lý ẩn thông tin nhạy cảm (Masking - Quy tắc 7)
     * 
     * @param array $contact Bản ghi liên hệ
     * @param bool $isAdmin Có phải là admin không
     * @return array
     */
    public function formatContactForUser(array $contact, bool $isAdmin)
    {
        // Nếu là private và KHÔNG phải admin, ẩn SĐT
        if ($contact['is_private'] == 1 && !$isAdmin) {
            if (!empty($contact['phone'])) {
                $len = strlen($contact['phone']);
                if ($len > 4) {
                    $contact['phone'] = substr($contact['phone'], 0, $len - 4) . '****';
                } else {
                    $contact['phone'] = '****';
                }
            }
            // Gắn cờ để view biết là không được sửa
            $contact['_can_edit'] = false;
        } else {
            $contact['_can_edit'] = true;
        }
        
        return $contact;
    }
}
