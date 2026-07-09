<?php

namespace App\Controllers;

use App\Services\ContactService;
use App\Services\AccessControlService;

/**
 * ContactController
 * 
 * Điều khiển nghiệp vụ danh bạ liên hệ.
 * Tuân thủ Quy tắc 10 (Master Sync) và Quy tắc 11 (Phân trang).
 */
class ContactController extends BaseController
{
    // Quy tắc 10: Tự động khai báo quyền hạn để hệ thống quét /perm-fix/sync
    public static $modulePermissions = [
        'group' => 'Danh bạ liên hệ',
        'permissions' => [
            'contact.view'   => ['desc' => 'Xem danh sách liên hệ', 'roles' => [1, 2, 3, 4, 5, 6]],
            'contact.create' => ['desc' => 'Thêm liên hệ mới', 'roles' => [1, 3, 4,5,6]],
            'contact.edit'   => ['desc' => 'Chỉnh sửa liên hệ', 'roles' => [1, 3, 4,5,6]],
            'contact.delete' => ['desc' => 'Xóa liên hệ', 'roles' => [1, 3]],
            'contact.admin'  => ['desc' => 'Quản trị liên hệ (Gắn cờ Private/Xem dữ liệu ẩn)', 'roles' => [1, 3]]
        ]
    ];

    // Danh mục (Nguồn/Tab) định nghĩa cố định dựa trên Excel
    public const CONTACT_SOURCES = [
        'Bộ phận 1 cửa',
        'Cán bộ',
        'Công an',
        'Cơ quan Thuế',
        'Hành chính công',
        'PC10',
        'Thi hành án',
        'Tòa án',
        'Xóa án tích'
    ];

    public const PROVINCES = [
        'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu', 'Bắc Ninh', 'Bến Tre', 'Bình Định', 
        'Bình Dương', 'Bình Phước', 'Bình Thuận', 'Cà Mau', 'Cần Thơ', 'Cao Bằng', 'Đà Nẵng', 'Đắk Lắk', 
        'Đắk Nông', 'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Nội', 
        'Hà Tĩnh', 'Hải Dương', 'Hải Phòng', 'Hậu Giang', 'Hòa Bình', 'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 
        'Kon Tum', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định', 'Nghệ An', 
        'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 
        'Quảng Trị', 'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa', 'Thừa Thiên Huế', 
        'Tiền Giang', 'TP Hồ Chí Minh', 'Trà Vinh', 'Tuyên Quang', 'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'
    ];

    // Quy tắc 10: Hỗ trợ nhãn dán thông minh
    public static $taggable = [
        'type'  => 'contacts',
        'label' => 'Danh bạ'
    ];

    protected $contactService;
    protected $accessService;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->contactService = new ContactService();
        $this->accessService = new AccessControlService();
    }

    /**
     * Trang danh sách liên hệ (Quy tắc 11: Phân trang bắt buộc)
     */
    public function index()
    {
        // Kiểm tra quyền truy cập (Quy tắc 7: Security)
        if (!has_permission('contact.view') && !session()->get('employee_id')) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không có quyền truy cập module này.');
        }

        $filters = [
            'search'     => $this->request->getGet('search'),
            'source'     => $this->request->getGet('source'),
            'province'   => $this->request->getGet('province'),
            'is_private' => $this->request->getGet('is_private')
        ];

        $results = $this->contactService->getContactList($filters);
        
        $isAdmin = has_permission('contact.admin');
        
        // Xử lý ẩn thông tin nhạy cảm (Masking)
        foreach ($results['contacts'] as &$contact) {
            $contact = $this->contactService->formatContactForUser($contact, $isAdmin);
        }

        $data = [
            'title'    => 'Danh bạ liên hệ',
            'contacts' => $results['contacts'],
            'pager'    => $results['pager'],
            'filters'  => $filters,
            'isAdmin'  => $isAdmin,
            'sources'  => self::CONTACT_SOURCES,
            'provinces' => self::PROVINCES
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/contacts/index_table', $data);
        }

        return view('dashboard/contacts/index', $data);
    }

    /**
     * Lưu thông tin (AJAX-friendly hoặc Form POST)
     */
    public function save($id = null)
    {
        // Kiểm tra quyền
        $permission = $id ? 'contact.edit' : 'contact.create';
        if (!has_permission($permission)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $data = $this->request->getPost();
        
        // Loại bỏ CSRF token nếu có trong data POST
        unset($data['csrf_test_name']);

        $userId = session()->get('employee_id') ?? session()->get('user_id');
        $result = $this->contactService->saveContact($data, $userId, $id);

        return $this->response->setJSON($result);
    }

    /**
     * Xóa liên hệ
     */
    public function delete($id)
    {
        if (!has_permission('contact.delete')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền xóa liên hệ.']);
        }

        $result = $this->contactService->deleteContact($id);
        return $this->response->setJSON($result);
    }

    /**
     * Thao tác hàng loạt: Thay đổi trạng thái Private (Admin only)
     */
    public function togglePrivateBatch()
    {
        if (!has_permission('contact.admin')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Chỉ Admin mới có quyền thực hiện thao tác này.']);
        }

        $ids = $this->request->getPost('ids');
        $status = (int)$this->request->getPost('status');

        $result = $this->contactService->togglePrivateBatch($ids, $status);
        return $this->response->setJSON($result);
    }
}
