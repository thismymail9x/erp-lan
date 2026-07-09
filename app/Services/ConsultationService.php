<?php

namespace App\Services;

/**
 * ConsultationService
 * 
 * Lớp nghiệp vụ tập trung xử lý cho module Tư Vấn Khách Hàng.
 * Bao gồm quản lý Câu trả lời nhanh (Quick Replies) và logic phối hợp các kênh (Zalo, Facebook...).
 * Tuân thủ Rule #2: Tách biệt logic khỏi Controller.
 */
class ConsultationService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Lấy danh sách câu trả lời nhanh có phân trang
     * Tuân thủ Rule #11: Phân trang bắt buộc
     */
    public function getQuickReplies($perPage = 20)
    {
        $builder = $this->db->table('zalo_quick_replies');
        $builder->where('deleted_at IS NULL'); // Tuân thủ Rule #6: Soft Delete
        $builder->orderBy('title', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Tìm kiếm câu trả lời nhanh hoặc lấy mặc định 10 câu mới nhất
     */
    public function searchQuickReplies($search = '', $limit = 10)
    {
        $builder = $this->db->table('zalo_quick_replies');
        $builder->where('deleted_at IS NULL');
        
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('title', $search)
                    ->orLike('content', $search)
                    ->groupEnd();
            $builder->orderBy('title', 'ASC');
        } else {
            // Mặc định ban đầu 10 câu trả lời nhanh mới tạo
            $builder->orderBy('id', 'DESC');
        }
        
        return $builder->limit($limit)->get()->getResultArray();
    }

    /**
     * Lưu hoặc cập nhật câu trả lời nhanh
     */
    public function saveQuickReply($data, $id = null)
    {
        $builder = $this->db->table('zalo_quick_replies');
        
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $builder->where('id', $id)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $builder->insert($data);
        }
    }

    /**
     * Xóa mềm câu trả lời nhanh
     * Tuân thủ Rule #6
     */
    public function deleteQuickReply($id)
    {
        return $this->db->table('zalo_quick_replies')
            ->where('id', $id)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }
}
