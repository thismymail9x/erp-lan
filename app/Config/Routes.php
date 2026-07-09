<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'AuthController::login'); // Trang chủ, chuyển hướng đến đăng nhập
$routes->get('fix', 'FixController::index'); // Sửa lỗi chung (tạm thời)
$routes->get('perm-fix', 'PermFixController::index'); // Sửa lỗi phân quyền
$routes->get('perm-fix/sync', 'PermFixController::sync'); // Cập nhật lại hệ thống (đồng bộ quyền)
$routes->get('dump-perms', 'PermFixController::dumpPerms'); // Xem cấu trúc quyền hiện tại
$routes->get('run-migrations', 'Migrator::index'); // Chạy migrate cơ sở dữ liệu
$routes->get('check-db', 'Migrator::check'); // Kiểm tra cấu trúc database
$routes->get('debug-users', 'Migrator::debug_users'); // Kiểm tra tài khoản người dùng
$routes->get('temp-import-contacts', 'TempImportController::import'); // Import dữ liệu danh bạ từ XML
// Auth Routes

$routes->get('login', 'AuthController::login'); // Hiển thị trang đăng nhập
$routes->post('login', 'AuthController::attemptLogin'); // Xử lý đăng nhập
$routes->get('register', 'AuthController::register'); // Hiển thị trang đăng ký
$routes->post('register', 'AuthController::attemptRegister'); // Xử lý đăng ký
$routes->get('logout', 'AuthController::logout'); // Đăng xuất
$routes->get('forgot-password', 'AuthController::forgotPassword'); // Quên mật khẩu
$routes->post('forgot-password', 'AuthController::attemptForgotPassword'); // Xử lý quên mật khẩu
$routes->get('reset-password', 'AuthController::resetPassword'); // Đặt lại mật khẩu

$routes->post('reset-password', 'AuthController::attemptResetPassword'); // Xử lý đặt lại mật khẩu
$routes->get('impersonate/(:num)', 'AuthController::impersonate/$1'); // Đăng nhập với tư cách người dùng khác (admin)
$routes->get('stop-impersonating', 'AuthController::stopImpersonating'); // Trở lại tài khoản cũ

// Dashboard Routes
$routes->get('dashboard', 'DashboardController::index'); // Bảng điều khiển tổng quan (Dashboard)

// Employee Management Routes
$routes->group('employees', function($routes) {
    $routes->get('/', 'EmployeeController::index'); // Danh sách nhân sự
    $routes->get('create', 'EmployeeController::create'); // Form thêm nhân sự mới
    $routes->post('store', 'EmployeeController::store'); // Lưu thông tin nhân sự mới
    $routes->get('edit/(:num)', 'EmployeeController::edit/$1'); // Form sửa thông tin nhân sự
    $routes->post('update/(:num)', 'EmployeeController::update/$1'); // Cập nhật thông tin nhân sự
    $routes->get('delete/(:num)', 'EmployeeController::delete/$1'); // Xóa 1 nhân sự
    $routes->post('bulk-delete', 'EmployeeController::bulkDelete'); // Xóa nhiều nhân sự cùng lúc
    $routes->post('change-password', 'EmployeeController::changePassword'); // Thay đổi mật khẩu nhân sự
});

// User Management Routes
$routes->group('users', function($routes) {
    $routes->get('/', 'UserController::index'); // Danh sách tài khoản người dùng
    $routes->get('create', 'UserController::create'); // Form tạo tài khoản
    $routes->post('store', 'UserController::store'); // Lưu tài khoản mới
    $routes->get('edit/(:num)', 'UserController::edit/$1'); // Form sửa tài khoản
    $routes->post('update/(:num)', 'UserController::update/$1'); // Cập nhật tài khoản
    $routes->get('delete/(:num)', 'UserController::delete/$1'); // Xóa tài khoản
    $routes->post('bulk-delete', 'UserController::bulkDelete'); // Xóa nhiều tài khoản
    
    // RBAC Permission Overrides
    $routes->get('permissions/matrix/(:num)', 'PermissionController::getUserMatrix/$1'); // Xem ma trận phân quyền của user
    $routes->post('permissions/save/(:num)', 'PermissionController::saveUserOverrides/$1'); // Lưu ghi đè phân quyền của user
});

// System Log Routes
$routes->get('system-logs', 'SystemLogController::index'); // Xem lịch sử hoạt động hệ thống

// Attendance Routes
$routes->group('attendance', function($routes) {
    $routes->get('/', 'AttendanceController::index');     // Camera check-in screen (Màn hình điểm danh)
    $routes->get('list', 'AttendanceController::list');   // Management list (Admin/Manager/Staff) (Danh sách điểm danh)
    $routes->get('status', 'AttendanceController::status'); // Xem trạng thái điểm danh hiện tại
    $routes->post('submit', 'AttendanceController::submit'); // Gửi thông tin điểm danh
    $routes->get('export', 'AttendanceController::export'); // Xuất file dữ liệu điểm danh
    $routes->post('bulk-update', 'AttendanceController::bulkUpdate'); // Cập nhật trạng thái điểm danh hàng loạt
    $routes->post('update-status/(:num)', 'AttendanceController::updateStatus/$1'); // Admin sửa trạng thái 1 bản ghi
    $routes->get('get-office-token', 'AttendanceController::getOfficeToken'); // Lấy mã token văn phòng
});

// Case Management Routes
$routes->group('cases', function($routes) {
    $routes->get('/', 'CaseController::index'); // Danh sách vụ việc
    $routes->get('create', 'CaseController::create'); // Form tạo vụ việc mới
    $routes->post('store', 'CaseController::store'); // Lưu vụ việc mới
    $routes->get('show/(:num)', 'CaseController::show/$1'); // Chi tiết vụ việc
    $routes->post('update-status/(:num)', 'CaseController::updateStatus/$1'); // Cập nhật trạng thái vụ việc
    $routes->post('update-members/(:num)', 'CaseController::updateMembers/$1'); // Cập nhật thành viên tham gia vụ việc
    $routes->post('upload-doc/(:num)', 'CaseController::uploadDocument/$1'); // Tải tài liệu vụ việc lên
    $routes->post('import-doc/(:num)', 'CaseController::importDocument/$1'); // Import tài liệu vụ việc
    $routes->post('complete-step/(:num)', 'CaseController::completeStep/$1'); // Hoàn thành một bước trong quy trình
    $routes->post('approve-step/(:num)', 'CaseController::approveStep/$1'); // Phê duyệt một bước
    $routes->post('approve-step-kpi/(:num)', 'CaseController::approveStepKpi/$1'); // Ghi nhận KPI ngoại lệ cho step hoàn thành
    $routes->post('reject-step/(:num)', 'CaseController::rejectStep/$1'); // Từ chối một bước
    $routes->post('add-comment/(:num)', 'CaseController::addComment/$1'); // Thêm bình luận vào vụ việc
    $routes->get('sync-rewards/(:num)', 'CaseController::syncRewards/$1'); // Đồng bộ điểm thưởng/KPI vụ việc
    $routes->post('send-reminder/(:num)', 'CaseController::sendReminder/$1'); // Gửi thông báo nhắc nhở công việc
    $routes->post('update-tags/(:num)', 'CaseController::updateTags/$1'); // Cập nhật nhãn (tags) của vụ việc
    $routes->post('create-tag', 'CaseController::createTag'); // Tạo nhãn mới
    $routes->get('my-cases', 'CaseController::myCases'); // Danh sách vụ việc của tôi
    $routes->get('edit/(:num)', 'CaseController::edit/$1'); // Form sửa vụ việc
    $routes->post('update/(:num)', 'CaseController::update/$1'); // Cập nhật thông tin vụ việc
    $routes->get('delete/(:num)', 'CaseController::delete/$1'); // Xóa tạm thời (đưa vào thùng rác) vụ việc
    $routes->post('bulk-delete', 'CaseController::bulkDelete'); // Xóa nhiều vụ việc
    $routes->get('purge/(:num)', 'CaseController::purge/$1'); // Xóa vĩnh viễn vụ việc
    $routes->post('add-step/(:num)', 'CaseController::addStep/$1'); // Thêm bước phát sinh vào vụ việc
    $routes->get('delete-step/(:num)', 'CaseController::deleteStep/$1'); // Xóa bước trong vụ việc
});

// Finance Routes
$routes->group('finance', function($routes) {
    $routes->get('cases', 'FinanceController::index'); // Quản lý tài chính của các vụ việc
});

// Notification Routes
$routes->group('notifications', function($routes) {
    $routes->get('/', 'NotificationController::index'); // Danh sách thông báo
    $routes->get('create', 'NotificationController::create'); // Form tạo thông báo
    $routes->post('store', 'NotificationController::store'); // Lưu thông báo mới
    $routes->post('bulk-delete', 'NotificationController::bulkDelete'); // Xóa nhiều thông báo
    $routes->get('show/(:num)', 'NotificationController::show/$1'); // Xem chi tiết thông báo
    $routes->get('unread-count', 'NotificationController::getUnreadCount'); // Lấy số lượng thông báo chưa đọc
    $routes->get('unread', 'NotificationController::getUnread'); // Lấy danh sách thông báo chưa đọc
    $routes->post('read/(:num)', 'NotificationController::markAsRead/$1'); // Đánh dấu 1 thông báo đã đọc
    $routes->post('read-all', 'NotificationController::markAllAsRead'); // Đánh dấu tất cả thông báo đã đọc
});

// Customer CRM Routes
$routes->group('customers', function($routes) {
    $routes->get('/', 'CustomerController::index'); // Danh sách khách hàng
    $routes->get('edit/(:num)', 'CustomerController::edit/$1'); // Form sửa thông tin khách hàng
    $routes->get('show/(:num)', 'CustomerController::show/$1'); // Chi tiết khách hàng
    $routes->get('create', 'CustomerController::create'); // Form thêm khách hàng mới
    $routes->post('store', 'CustomerController::store'); // Lưu thông tin khách hàng mới
    
    // API actions
    $routes->get('check-duplicate', 'CustomerController::checkDuplicate'); // Kiểm tra trùng lặp số điện thoại/email
    $routes->post('add-interaction/(:num)', 'CustomerController::addInteraction/$1'); // Thêm lịch sử tương tác với khách hàng
    $routes->post('upload-doc/(:num)', 'CustomerController::uploadDocument/$1'); // Tải tài liệu của khách hàng
    $routes->post('import-doc/(:num)', 'CustomerController::importDocument/$1'); // Import tài liệu cho khách hàng
    $routes->get('stale', 'CustomerController::stale'); // Lấy danh sách khách hàng lâu chưa tương tác
    
    $routes->post('update-care-staff/(:num)', 'CustomerController::updateCareStaff/$1'); // Cập nhật nhân sự tư vấn qua AJAX
    $routes->post('update-gift-status/(:num)', 'CustomerController::updateGiftStatus/$1'); // Cập nhật trạng thái quà tặng qua AJAX
    $routes->post('transition-status/(:num)', 'CustomerController::transitionStatus/$1'); // Chuyển đổi trạng thái tư vấn & SLA nhanh qua AJAX
    $routes->post('update/(:num)', 'CustomerController::update/$1'); // Cập nhật thông tin khách hàng
    $routes->get('delete/(:num)', 'CustomerController::delete/$1'); // Xóa khách hàng
    $routes->post('bulk-delete', 'CustomerController::bulkDelete'); // Xóa nhiều khách hàng
});

// Customer Care (CSKH) Routes
$routes->group('customer-care', function($routes) {
    $routes->get('/', 'CustomerCareController::index');
    $routes->get('customers', 'CustomerCareController::customers');
    $routes->get('care-plan/(:num)', 'CustomerCareController::carePlan/$1');
    $routes->post('init-plan/(:num)', 'CustomerCareController::initPlan/$1');
    $routes->post('complete-task/(:num)', 'CustomerCareController::completeTask/$1');
    $routes->post('add-task/(:num)', 'CustomerCareController::addTask/$1');
    $routes->get('delete-task/(:num)', 'CustomerCareController::deleteTask/$1');
    $routes->get('daily-checklist', 'CustomerCareController::dailyChecklist');
    $routes->get('weekly-checklist', 'CustomerCareController::weeklyChecklist');
    $routes->get('monthly-report', 'CustomerCareController::monthlyReport');
    $routes->get('sla-report', 'CustomerCareController::slaReport'); // Báo cáo & Cấu hình SLA chăm sóc khách hàng
    $routes->post('save-sla-setting', 'CustomerCareController::saveSlaSetting'); // API Lưu/Sửa cấu hình SLA
    $routes->post('delete-sla-setting/(:num)', 'CustomerCareController::deleteSlaSetting/$1'); // API Xóa cấu hình SLA
    $routes->get('loyalty/(:num)', 'CustomerCareController::loyalty/$1');
    $routes->post('update-segment/(:num)', 'CustomerCareController::updateSegment/$1');
});


// Workflow Management Routes
$routes->group('workflows', function($routes) {
    $routes->get('/', 'WorkflowController::index'); // Danh sách quy trình (workflows)
    $routes->get('create', 'WorkflowController::create'); // Form thêm quy trình mới
    $routes->post('store', 'WorkflowController::store'); // Lưu quy trình mới
    $routes->get('edit/(:num)', 'WorkflowController::edit/$1'); // Form sửa quy trình
    $routes->post('update/(:num)', 'WorkflowController::update/$1'); // Cập nhật quy trình
    $routes->get('duplicate/(:num)', 'WorkflowController::duplicate/$1'); // Nhân bản quy trình
    $routes->get('delete/(:num)', 'WorkflowController::delete/$1'); // Xóa quy trình
    $routes->get('steps/(:num)', 'WorkflowController::steps/$1'); // Lấy danh sách các bước của một quy trình
    $routes->post('update-steps/(:num)', 'WorkflowController::updateSteps/$1'); // Cập nhật thứ tự/thông tin các bước
});

// Knowledge Base Routes
$routes->group('knowledge', function($routes) {
    $routes->get('/', 'KnowledgeController::index'); // Danh sách bài viết kiến thức
    $routes->get('create', 'KnowledgeController::create'); // Viết bài mới
    $routes->post('store', 'KnowledgeController::store'); // Lưu bài viết mới
    $routes->get('show/(:num)', 'KnowledgeController::show/$1'); // Xem chi tiết bài viết
    $routes->get('edit/(:num)', 'KnowledgeController::edit/$1'); // Sửa bài viết
    $routes->post('update/(:num)', 'KnowledgeController::update/$1'); // Cập nhật bài viết
    $routes->get('delete/(:num)', 'KnowledgeController::delete/$1'); // Xóa bài viết
    $routes->post('vote/(:num)', 'KnowledgeController::vote/$1'); // Đánh giá bài viết (hữu ích/không)
    
    // Legacy setup route
    $routes->get('migrate-db', 'KnowledgeController::migrateDb'); // Khởi tạo database cho knowledge (cũ)
});

// Tag Management Routes
$routes->group('tags', function($routes) {
    $routes->get('/', 'TagController::index'); // Quản lý nhãn (Tags)
    $routes->post('store', 'TagController::store'); // Thêm nhãn mới
    $routes->post('update/(:num)', 'TagController::update/$1'); // Cập nhật nhãn
    $routes->get('delete/(:num)', 'TagController::delete/$1'); // Xóa nhãn
    $routes->post('update-entity-tags', 'TagController::updateEntityTags'); // Cập nhật nhãn cho đối tượng (Khách hàng/Vụ việc)
    $routes->get('get-entity-tags', 'TagController::getEntityTags'); // Lấy danh sách nhãn của đối tượng
    $routes->get('show/(:num)', 'TagController::show/$1'); // Xem chi tiết 1 nhãn
});

// DMS (Document Management System) Routes
$routes->group('documents', function($routes) {
    $routes->get('/', 'DocumentController::index'); // Quản lý kho tài liệu chung
    $routes->post('upload', 'DocumentController::upload'); // Tải tài liệu lên
    $routes->get('edit/(:any)', 'DocumentController::edit/$1'); // Sửa thông tin tài liệu
    $routes->post('share/(:any)', 'DocumentController::share/$1'); // Chia sẻ tài liệu
    $routes->post('update/(:num)', 'DocumentController::update/$1'); // Cập nhật tài liệu
    $routes->get('view/(:num)', 'DocumentController::view/$1'); // Xem/Tải về tài liệu
    $routes->get('delete/(:num)', 'DocumentController::delete/$1'); // Xóa tài liệu
    $routes->get('vault-list', 'DocumentController::getVaultDocuments'); // Lấy danh sách tài liệu bảo mật (Vault)
    $routes->post('bulk-delete', 'DocumentController::bulkDelete'); // Xóa nhiều tài liệu
});

// Leave Request Management Routes
$routes->group('leave-requests', function($routes) {
    $routes->get('/', 'LeaveRequestController::index'); // Danh sách đơn xin nghỉ phép
    $routes->get('create', 'LeaveRequestController::create'); // Mở form tạo đơn nghỉ phép
    $routes->post('store', 'LeaveRequestController::store'); // Lưu đơn xin nghỉ phép
    $routes->post('approve/(:num)', 'LeaveRequestController::approve/$1'); // Phê duyệt/Từ chối đơn
    $routes->get('cancel/(:num)', 'LeaveRequestController::cancel/$1'); // Người dùng hủy đơn
    $routes->get('edit/(:num)', 'LeaveRequestController::edit/$1'); // Sửa đơn
    $routes->post('update/(:num)', 'LeaveRequestController::update/$1'); // Cập nhật đơn
    $routes->get('delete/(:num)', 'LeaveRequestController::delete/$1'); // Xóa đơn
    $routes->post('bulk-delete', 'LeaveRequestController::bulkDelete'); // Xóa nhiều đơn
});

// KPI Management Routes
$routes->group('kpi', function($routes) {
    $routes->get('/', 'KpiController::index'); // Thống kê, Quản lý KPI
    $routes->get('consulting', 'KpiController::consulting'); // KPI tư vấn theo giá trị hợp đồng chốt
});

// Payroll Management Routes
$routes->group('payroll', function($routes) {
    $routes->get('/', 'PayrollController::index'); // Danh sách bảng lương
    $routes->get('config/(:any)', 'PayrollController::config/$1'); // Giao diện cấu hình thiết lập lương
    $routes->post('config/(:any)', 'PayrollController::config/$1'); // Lưu cấu hình thiết lập lương
    $routes->get('calculate/(:any)', 'PayrollController::calculate/$1'); // Tính toán, chốt số liệu lương (GET)
    $routes->post('calculate/(:any)', 'PayrollController::calculate/$1'); // Tính toán, chốt số liệu lương (POST cho chọn nhân sự)
    $routes->get('close/(:any)', 'PayrollController::close/$1'); // Chốt sổ bảng lương tháng
    $routes->get('export/(:any)', 'PayrollController::export/$1'); // Xuất file excel bảng lương
    $routes->post('update-item/(:num)', 'PayrollController::updateItem/$1'); // Cập nhật thủ công một mục lương
    $routes->get('notes/(:num)', 'PayrollController::getNotes/$1'); // Lấy ghi chú của bảng lương cá nhân
    $routes->post('save-notes/(:num)', 'PayrollController::saveNotes/$1'); // Lưu ghi chú lương cá nhân
});



// Work Schedule Management Routes
$routes->group('work-schedules', function($routes) {
    $routes->get('/', 'WorkScheduleController::index'); // Redirect giao diện cũ về Dashboard
    $routes->get('events', 'WorkScheduleController::events'); // API lấy danh sách sự kiện
    $routes->post('store', 'WorkScheduleController::store'); // Lưu lịch trình mới
    $routes->get('detail/(:num)', 'WorkScheduleController::detail/$1'); // Lấy chi tiết lịch trình
    $routes->post('update/(:num)', 'WorkScheduleController::update/$1'); // Cập nhật lịch trình
    $routes->post('delete/(:num)', 'WorkScheduleController::delete/$1'); // Xóa lịch trình
});

// Contact Management Routes
$routes->group('contacts', function($routes) {
    $routes->get('/', 'ContactController::index'); // Danh sách liên hệ
    $routes->post('save', 'ContactController::save'); // Thêm mới
    $routes->post('save/(:num)', 'ContactController::save/$1'); // Cập nhật
    $routes->get('delete/(:num)', 'ContactController::delete/$1'); // Xóa
    $routes->post('toggle-private', 'ContactController::togglePrivateBatch'); // Bật/tắt Private hàng loạt
});

// Unified Chat Center Routes (Trung tâm Tư vấn Hợp nhất Zalo + Messenger)
$routes->group('chat', function($routes) {
    $routes->get('/', 'ChatController::index');                   // Trang chính hợp nhất (dual-mode: HTML + AJAX JSON)
    $routes->get('ajax-chat', 'ChatController::ajaxChat');        // Polling tin nhắn mới + cập nhật danh sách
    $routes->post('send-message', 'ChatController::sendMessage'); // Gửi tin nhắn (tự chọn kênh Zalo/Messenger)
    $routes->post('assign-staff', 'ChatController::assignStaff'); // Gán nhân sự chăm sóc
    $routes->post('update-tags', 'ChatController::updateTags');   // Cập nhật nhãn hội thoại
    $routes->post('create-tag', 'ChatController::createTag');     // Tạo nhãn mới nhanh
    $routes->post('bulk-delete', 'ChatController::bulkDelete');   // Xóa mềm nhiều hội thoại
    $routes->get('load-more', 'ChatController::loadMoreMessages'); // Tải thêm tin nhắn cũ (infinite scroll)
    $routes->post('update-insights', 'ChatController::updateLeadInsights'); // Cập nhật độ nóng & thông tin lead chi tiết
    $routes->post('instant-create-customer', 'ChatController::instantCreateCustomer'); // Tạo nhanh Hồ sơ khách hàng CRM qua AJAX
    $routes->post('upload-media', 'ChatController::uploadMedia'); // Tải lên hình ảnh/tệp tin
    $routes->post('sync-history', 'ChatController::syncHistory'); // Đồng bộ lịch sử tin nhắn Zalo
    $routes->post('sync-profile', 'ChatController::syncProfile'); // Đồng bộ tên + avatar Zalo
    $routes->post('log-call', 'ChatController::logCall');         // Ghi nhận lịch sử cuộc gọi
    $routes->post('log-call-v2', 'ChatController::logCallV2');    // Ghi nhận cuộc gọi V2 (đầy đủ: kết quả, thời lượng, hỗ trợ cả 2 kênh)
});

// Zalo OA Integration Routes
$routes->group('zalo', function($routes) {
    $routes->get('/', 'ZaloController::index'); // Quản lý tập trung Zalo OA
    $routes->get('config', 'ZaloController::config'); // Cấu hình Zalo
    $routes->get('auth', 'ZaloController::auth'); // Bắt đầu xác thực
    $routes->get('callback', 'ZaloController::callback'); // Nhận token từ Zalo
    $routes->post('webhook', 'ZaloController::webhook'); // Webhook nhận sự kiện từ Zalo
    $routes->get('simulate', 'ZaloController::simulateWebhook'); // Giả lập Webhook để test Local
    $routes->get('logs', 'ZaloController::logs'); // Xem log Webhook
    $routes->get('campaigns', 'ZaloController::campaigns'); // Quản lý chiến dịch Remarketing ZNS
    $routes->get('zns-templates', 'ZaloController::znsTemplates'); // Quản lý Zalo ZNS template
    $routes->post('zns-templates/sync', 'ZaloController::znsSyncTemplate'); // Đồng bộ thông tin mẫu tin ZNS từ Zalo API
    $routes->post('zns-templates/save-mappings', 'ZaloController::znsSaveTemplateMappings'); // Lưu cấu hình ánh xạ mặc định cho ZNS template
    $routes->post('zns-templates/delete/(:num)', 'ZaloController::znsDeleteTemplate/$1'); // Xóa ZNS template
    $routes->get('campaigns/create', 'ZaloController::znsCampaignCreate'); // Tạo chiến dịch ZNS mới
    $routes->post('campaigns/save', 'ZaloController::znsCampaignSave'); // Lưu chiến dịch ZNS
    $routes->post('campaigns/execute/(:num)', 'ZaloController::znsCampaignExecute/$1'); // Thực thi chiến dịch ZNS
    $routes->get('campaigns/detail/(:num)', 'ZaloController::znsCampaignDetail/$1'); // Xem chi tiết chiến dịch và log
    $routes->post('zns/send-quick', 'ZaloController::znsSendQuick'); // Gửi ZNS nhanh từ danh sách khách hàng
    $routes->get('performance', 'ZaloController::performance'); // Báo cáo hiệu suất, đánh giá sao
    $routes->get('ajax-chat', 'ZaloController::ajaxChat'); // Lấy dữ liệu chat mới qua AJAX
    $routes->post('assign-staff', 'ZaloController::assignStaff'); // Gán nhân sự chăm sóc
    $routes->post('update-tags', 'ZaloController::updateTags'); // Cập nhật nhãn hội thoại
    $routes->post('create-tag', 'ZaloController::createTag'); // Tạo nhãn mới nhanh từ giao diện chat
    $routes->post('send-message', 'ZaloController::sendMessage'); // Gửi tin nhắn
    $routes->post('sync', 'ZaloController::sync'); // Đồng bộ lịch sử hội thoại
    $routes->get('load-more-messages', 'ZaloController::loadMoreMessages'); // Tải thêm tin nhắn cũ
    $routes->get('download-attachment', 'ZaloController::downloadAttachment'); // Tải tệp tin đính kèm
    $routes->get('view-temp/(:any)', 'ZaloController::viewTemp/$1'); // Xem ảnh tạm thời vừa upload
    $routes->post('upload-media', 'ZaloController::uploadMedia'); // Tải lên hình ảnh/tệp tin
    $routes->post('log-call', 'ZaloController::logCall'); // Ghi nhận cuộc gọi
    $routes->post('sync-profile', 'ZaloController::syncProfile'); // Đồng bộ profile khách hàng

    // Quản lý câu trả lời nhanh
    $routes->group('quick-replies', function($routes) {
        $routes->get('/', 'ConsultationQuickReplyController::index');
        $routes->post('store', 'ConsultationQuickReplyController::store');
        $routes->get('delete/(:num)', 'ConsultationQuickReplyController::delete/$1');
        $routes->get('search', 'ConsultationQuickReplyController::search');
    });
});

// Facebook Messenger Integration Routes
// Lưu ý: webhook GET/POST phải để NGOÀI group auth (public endpoint cho Meta gọi vào)
$routes->match(['GET', 'POST'], 'messenger/webhook', 'MessengerController::webhook'); // Endpoint Webhook công khai cho Meta

$routes->group('messenger', function($routes) {
    $routes->get('/', 'MessengerController::index');             // Trang chat tổng hợp
    $routes->get('config', 'MessengerController::config');      // Cấu hình Page Token
    $routes->post('save-config', 'MessengerController::saveConfig'); // Lưu cấu hình
    $routes->get('simulate', 'MessengerController::simulateWebhook'); // Giả lập webhook để test
    $routes->get('ajax-chat', 'MessengerController::ajaxChat'); // AJAX polling tin nhắn
    $routes->post('send-message', 'MessengerController::sendMessage'); // Gửi tin nhắn
    $routes->post('assign-staff', 'MessengerController::assignStaff'); // Gán nhân sự
    $routes->post('update-tags', 'MessengerController::updateTags');   // Cập nhật nhãn
    $routes->get('load-more', 'MessengerController::loadMoreMessages'); // Tải thêm tin nhắn cũ
});


// Utility Routes
$routes->get('db-check', 'DbCheck::index'); // Kiểm tra kết nối database
$routes->get('tempmigrator', 'TempMigrator::index'); // Migrate tạm thời
$routes->get('workflowseeder', 'WorkflowSeeder::seed'); // Đổ dữ liệu mẫu (seeder) cho workflow

// Cron Routes
$routes->group('cron', function($routes) {
    $routes->get('payment-reminders', 'CronController::paymentReminders'); // Cron job tự động nhắc thanh toán
    $routes->get('check-workflows', 'CronController::checkWorkflows'); // Cron job kiểm tra tiến độ workflow
    $routes->get('sync-zalo-conversations', 'CronController::syncZaloConversations'); // Cron job tự động quét & đồng bộ Zalo
    $routes->get('check-chat-deadlines', 'CronController::checkChatDeadlines'); // Cron job kiểm tra deadline phản hồi 2h chat
    $routes->get('care-reminders', 'CronController::careReminders'); // Cron job tự động nhắc nhở CSKH
    $routes->get('birthday-greetings', 'CronController::birthdayGreetings'); // Cron job chúc mừng sinh nhật khách hàng
});
