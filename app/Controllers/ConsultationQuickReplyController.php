<?php

namespace App\Controllers;

use App\Services\ConsultationService;

/**
 * ConsultationQuickReplyController
 * 
 * Quản lý danh mục câu trả lời nhanh cho hệ thống Tư vấn khách hàng.
 * Tuân thủ Rule #10: Khai báo metadata quyền hạn.
 */
class ConsultationQuickReplyController extends BaseController
{
    protected $consultationService;

    // Metadata cho hệ thống tự động đồng bộ (Rule #10)
    public static $modulePermissions = [
        'group' => 'Tư Vấn Khách Hàng',
        'permissions' => [
            'zalo.quick_reply' => ['desc' => 'Quản lý câu trả lời nhanh (Chat tư vấn)', 'roles' => [1, 3, 4, 5, 6]]
        ]
    ];

    public function __construct()
    {
        $this->consultationService = new ConsultationService();
    }

    /**
     * Danh sách câu trả lời nhanh
     */
    public function index()
    {
        if (!has_permission('zalo.quick_reply') && !has_permission('zalo.config')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Bạn không có quyền truy cập.');
        }

        $data = [
            'title' => 'Quản lý Câu trả lời nhanh | L.A.N ERP',
            'quickReplies' => $this->consultationService->getQuickReplies()
        ];

        return view('dashboard/zalo/quick_replies_manage', $data);
    }

    /**
     * Lưu câu trả lời nhanh (AJAX hoặc Form)
     */
    public function store()
    {
        if (!has_permission('zalo.quick_reply') && !has_permission('zalo.config')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Từ chối truy cập.']);
        }

        $id = $this->request->getPost('id');
        $data = [
            'title'   => $this->request->getPost('title'),
            'content' => $this->request->getPost('content')
        ];

        // Validate cơ bản
        if (empty($data['title']) || empty($data['content'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin.']);
        }

        if ($this->consultationService->saveQuickReply($data, $id)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã lưu câu trả lời nhanh.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Lỗi hệ thống khi lưu.']);
    }

    public function delete($id)
    {
        if (!has_permission('zalo.quick_reply') && !has_permission('zalo.config')) {
            return redirect()->to(base_url('zalo/quick-replies'))->with('error', 'Từ chối truy cập.');
        }

        if ($this->consultationService->deleteQuickReply($id)) {
            return redirect()->to(base_url('zalo/quick-replies'))->with('success', 'Đã xóa câu trả lời nhanh.');
        }

        return redirect()->to(base_url('zalo/quick-replies'))->with('error', 'Lỗi không thể xóa.');
    }

    /**
     * Tìm kiếm câu trả lời nhanh qua AJAX (Dùng chung cho chat)
     */
    public function search()
    {
        if (!has_permission('chat.view') && !has_permission('zalo.view') && !has_permission('messenger.view') && !has_permission('zalo.config')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Từ chối truy cập.']);
        }

        $search = $this->request->getGet('q') ?: '';
        $results = $this->consultationService->searchQuickReplies($search, 10);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $results
        ]);
    }
}
