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

    /**
     * Phương thức chung để lấy các bản ghi đang hoạt động (chưa bị xóa mềm)
     */
    public function getActive()
    {
        return $this->where($this->deletedField, null)->findAll();
    }
}
