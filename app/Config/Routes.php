<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'AuthController::login');
$routes->get('fix', 'FixController::index');
$routes->get('perm-fix', 'PermFixController::index');
$routes->get('perm-fix/sync', 'PermFixController::sync'); // cập nhật lại hệ thống
$routes->get('dump-perms', 'PermFixController::dumpPerms');
$routes->get('run-migrations', 'Migrator::index'); // chạy migrate
$routes->get('check-db', 'Migrator::check'); // kiểm tra cấu trúc
$routes->get('debug-users', 'Migrator::debug_users'); // kiểm tra tài khoản
// Auth Routes

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('register', 'AuthController::register');
$routes->post('register', 'AuthController::attemptRegister');
$routes->get('logout', 'AuthController::logout');
$routes->get('forgot-password', 'AuthController::forgotPassword');
$routes->post('forgot-password', 'AuthController::attemptForgotPassword');
$routes->get('reset-password', 'AuthController::resetPassword');

$routes->post('reset-password', 'AuthController::attemptResetPassword');
$routes->get('impersonate/(:num)', 'AuthController::impersonate/$1');
$routes->get('stop-impersonating', 'AuthController::stopImpersonating');

// Dashboard Routes
$routes->get('dashboard', 'DashboardController::index');

// Employee Management Routes
$routes->group('employees', function($routes) {
    $routes->get('/', 'EmployeeController::index');
    $routes->get('create', 'EmployeeController::create');
    $routes->post('store', 'EmployeeController::store');
    $routes->get('edit/(:num)', 'EmployeeController::edit/$1');
    $routes->post('update/(:num)', 'EmployeeController::update/$1');
    $routes->get('delete/(:num)', 'EmployeeController::delete/$1');
    $routes->post('bulk-delete', 'EmployeeController::bulkDelete');
    $routes->post('change-password', 'EmployeeController::changePassword');
});

// User Management Routes
$routes->group('users', function($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('create', 'UserController::create');
    $routes->post('store', 'UserController::store');
    $routes->get('edit/(:num)', 'UserController::edit/$1');
    $routes->post('update/(:num)', 'UserController::update/$1');
    $routes->get('delete/(:num)', 'UserController::delete/$1');
    $routes->post('bulk-delete', 'UserController::bulkDelete');
    
    // RBAC Permission Overrides
    $routes->get('permissions/matrix/(:num)', 'PermissionController::getUserMatrix/$1');
    $routes->post('permissions/save/(:num)', 'PermissionController::saveUserOverrides/$1');
});

// System Log Routes
$routes->get('system-logs', 'SystemLogController::index');

// Attendance Routes
$routes->group('attendance', function($routes) {
    $routes->get('/', 'AttendanceController::index');     // Camera check-in screen
    $routes->get('list', 'AttendanceController::list');   // Management list (Admin/Manager/Staff)
    $routes->get('status', 'AttendanceController::status');
    $routes->post('submit', 'AttendanceController::submit');
    $routes->get('export', 'AttendanceController::export');
    $routes->post('bulk-update', 'AttendanceController::bulkUpdate');
    $routes->get('get-office-token', 'AttendanceController::getOfficeToken');
});

// Case Management Routes
$routes->group('cases', function($routes) {
    $routes->get('/', 'CaseController::index');
    $routes->get('create', 'CaseController::create');
    $routes->post('store', 'CaseController::store');
    $routes->get('show/(:num)', 'CaseController::show/$1');
    $routes->post('update-status/(:num)', 'CaseController::updateStatus/$1');
    $routes->post('update-members/(:num)', 'CaseController::updateMembers/$1');
    $routes->post('upload-doc/(:num)', 'CaseController::uploadDocument/$1');
    $routes->post('import-doc/(:num)', 'CaseController::importDocument/$1');
    $routes->post('complete-step/(:num)', 'CaseController::completeStep/$1');
    $routes->post('approve-step/(:num)', 'CaseController::approveStep/$1');
    $routes->post('reject-step/(:num)', 'CaseController::rejectStep/$1');
    $routes->post('add-comment/(:num)', 'CaseController::addComment/$1');
    $routes->get('sync-rewards/(:num)', 'CaseController::syncRewards/$1');
    $routes->post('send-reminder/(:num)', 'CaseController::sendReminder/$1');
    $routes->post('update-tags/(:num)', 'CaseController::updateTags/$1');
    $routes->post('create-tag', 'CaseController::createTag');
    $routes->get('my-cases', 'CaseController::myCases');
    $routes->get('edit/(:num)', 'CaseController::edit/$1');
    $routes->post('update/(:num)', 'CaseController::update/$1');
    $routes->get('delete/(:num)', 'CaseController::delete/$1');
    $routes->post('bulk-delete', 'CaseController::bulkDelete');
    $routes->get('purge/(:num)', 'CaseController::purge/$1');
});

// Finance Routes
$routes->group('finance', function($routes) {
    $routes->get('cases', 'FinanceController::index');
});

// Notification Routes
$routes->group('notifications', function($routes) {
    $routes->get('/', 'NotificationController::index');
    $routes->get('create', 'NotificationController::create');
    $routes->post('store', 'NotificationController::store');
    $routes->post('bulk-delete', 'NotificationController::bulkDelete');
    $routes->get('show/(:num)', 'NotificationController::show/$1');
    $routes->get('unread-count', 'NotificationController::getUnreadCount');
    $routes->get('unread', 'NotificationController::getUnread');
    $routes->post('read/(:num)', 'NotificationController::markAsRead/$1');
    $routes->post('read-all', 'NotificationController::markAllAsRead');
});

// Customer CRM Routes
$routes->group('customers', function($routes) {
    $routes->get('/', 'CustomerController::index');
    $routes->get('edit/(:num)', 'CustomerController::edit/$1');
    $routes->get('show/(:num)', 'CustomerController::show/$1');
    $routes->get('create', 'CustomerController::create');
    $routes->post('store', 'CustomerController::store');
    
    // API actions
    $routes->get('check-duplicate', 'CustomerController::checkDuplicate');
    $routes->post('add-interaction/(:num)', 'CustomerController::addInteraction/$1');
    $routes->post('upload-doc/(:num)', 'CustomerController::uploadDocument/$1');
    $routes->post('import-doc/(:num)', 'CustomerController::importDocument/$1');
    $routes->get('stale', 'CustomerController::stale');
    
    $routes->post('update/(:num)', 'CustomerController::update/$1');
    $routes->get('delete/(:num)', 'CustomerController::delete/$1');
    $routes->post('bulk-delete', 'CustomerController::bulkDelete');
});

// Workflow Management Routes
$routes->group('workflows', function($routes) {
    $routes->get('/', 'WorkflowController::index');
    $routes->get('create', 'WorkflowController::create');
    $routes->post('store', 'WorkflowController::store');
    $routes->get('edit/(:num)', 'WorkflowController::edit/$1');
    $routes->post('update/(:num)', 'WorkflowController::update/$1');
    $routes->get('duplicate/(:num)', 'WorkflowController::duplicate/$1');
    $routes->get('delete/(:num)', 'WorkflowController::delete/$1');
    $routes->get('steps/(:num)', 'WorkflowController::steps/$1');
    $routes->post('update-steps/(:num)', 'WorkflowController::updateSteps/$1');
});

// Knowledge Base Routes
$routes->group('knowledge', function($routes) {
    $routes->get('/', 'KnowledgeController::index');
    $routes->get('create', 'KnowledgeController::create');
    $routes->post('store', 'KnowledgeController::store');
    $routes->get('show/(:num)', 'KnowledgeController::show/$1');
    $routes->get('edit/(:num)', 'KnowledgeController::edit/$1');
    $routes->post('update/(:num)', 'KnowledgeController::update/$1');
    $routes->get('delete/(:num)', 'KnowledgeController::delete/$1');
    $routes->post('vote/(:num)', 'KnowledgeController::vote/$1');
    
    // Legacy setup route
    $routes->get('migrate-db', 'KnowledgeController::migrateDb');
});

// Tag Management Routes
$routes->group('tags', function($routes) {
    $routes->get('/', 'TagController::index');
    $routes->post('store', 'TagController::store');
    $routes->post('update/(:num)', 'TagController::update/$1');
    $routes->get('delete/(:num)', 'TagController::delete/$1');
    $routes->post('update-entity-tags', 'TagController::updateEntityTags');
    $routes->get('get-entity-tags', 'TagController::getEntityTags');
    $routes->get('show/(:num)', 'TagController::show/$1');
});

// DMS (Document Management System) Routes
$routes->group('documents', function($routes) {
    $routes->get('/', 'DocumentController::index');
    $routes->post('upload', 'DocumentController::upload');
    $routes->get('edit/(:any)', 'DocumentController::edit/$1');
    $routes->post('share/(:any)', 'DocumentController::share/$1');
    $routes->post('update/(:num)', 'DocumentController::update/$1');
    $routes->get('view/(:num)', 'DocumentController::view/$1');
    $routes->get('delete/(:num)', 'DocumentController::delete/$1');
    $routes->get('vault-list', 'DocumentController::getVaultDocuments');
    $routes->post('bulk-delete', 'DocumentController::bulkDelete');
});

// Leave Request Management Routes
$routes->group('leave-requests', function($routes) {
    $routes->get('/', 'LeaveRequestController::index');
    $routes->get('create', 'LeaveRequestController::create');
    $routes->post('store', 'LeaveRequestController::store');
    $routes->post('approve/(:num)', 'LeaveRequestController::approve/$1');
    $routes->get('cancel/(:num)', 'LeaveRequestController::cancel/$1');
    $routes->get('edit/(:num)', 'LeaveRequestController::edit/$1');
    $routes->post('update/(:num)', 'LeaveRequestController::update/$1');
    $routes->get('delete/(:num)', 'LeaveRequestController::delete/$1');
    $routes->post('bulk-delete', 'LeaveRequestController::bulkDelete');
});

// KPI Management Routes
$routes->group('kpi', function($routes) {
    $routes->get('/', 'KpiController::index');
});

// Payroll Management Routes
$routes->group('payroll', function($routes) {
    $routes->get('/', 'PayrollController::index');
    $routes->get('config/(:any)', 'PayrollController::config/$1');
    $routes->post('config/(:any)', 'PayrollController::config/$1');
    $routes->get('calculate/(:any)', 'PayrollController::calculate/$1');
    $routes->get('close/(:any)', 'PayrollController::close/$1');
    $routes->get('export/(:any)', 'PayrollController::export/$1');
    $routes->post('update-item/(:num)', 'PayrollController::updateItem/$1');
    $routes->get('notes/(:num)', 'PayrollController::getNotes/$1');
    $routes->post('save-notes/(:num)', 'PayrollController::saveNotes/$1');
});


// Utility Routes
$routes->get('db-check', 'DbCheck::index');
$routes->get('tempmigrator', 'TempMigrator::index');
$routes->get('workflowseeder', 'WorkflowSeeder::seed');

// Cron Routes
$routes->group('cron', function($routes) {
    $routes->get('payment-reminders', 'CronController::paymentReminders');
    $routes->get('check-workflows', 'CronController::checkWorkflows');
});
