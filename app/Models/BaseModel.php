<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BaseModel
 * 
 * Lớp cơ sở cho tất cả các model trong hệ thống ERP.
 * Bao gồm các tính năng chung như xóa mềm (soft deletes) và tự động lưu dấu thời gian (timestamps).
 */
abstract class BaseModel extends Model
{
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $useSoftDeletes = true;

    // Các sự kiện tự kích hoạt trước khi ghi dữ liệu (Model Hooks)
    protected $beforeInsert = ['convertEmptyToNull'];
    protected $beforeUpdate = ['convertEmptyToNull'];

    /**
     * Tự động chuẩn hóa dữ liệu: Chuyển chuỗi rỗng sang NULL.
     * Giải quyết triệt để lỗi Khóa ngoại (Foreign Key) khi để trống các ô liên kết (ID).
     */
    protected function convertEmptyToNull(array $data)
    {
        // Nếu không có dữ liệu thực sự (vd: lệnh xóa), bỏ qua
        if (!isset($data['data'])) return $data;

        foreach ($data['data'] as $field => $value) {
            /**
             * LOGIC CHUẨN HÓA:
             * Nếu giá trị gửi lên là chuỗi rỗng hòan toàn (Empty String từ Form)
             * thì tự động chuyển thành NULL để tương thích hòan hảo với Ràng buộc DB.
             */
            if ($value === '' || $value === ' ') {
                $data['data'][$field] = null;
            }
        }

        return $data;
    }

    /**
     * Cập nhật bản ghi kèm theo tiêm ID vào dữ liệu để phục vụ kiểm tra trùng lặp (Validation {id}).
     */
    public function update($id = null, $data = null): bool
    {
        if (is_array($data) && !empty($id) && (is_string($id) || is_numeric($id))) {
            $data[$this->primaryKey] = $id;
        }
        return parent::update($id, $data);
    }

    /**
     * Phương thức chung để lấy các bản ghi đang hoạt động (chưa bị xóa mềm)
     */
    public function getActive()
    {
        return $this->where($this->deletedField, null)->findAll();
    }
}

