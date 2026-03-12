<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'AuthController::login');

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
});
