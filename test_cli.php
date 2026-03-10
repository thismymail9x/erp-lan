<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$userModel = new \App\Models\UserModel();
$roleModel = new \App\Models\RoleModel();
$deptModel = new \App\Models\DepartmentModel();

$user = $userModel->find(1);
$role = $user ? $roleModel->find($user['role_id']) : null;
$roles = $roleModel->findAll();

print_r(['user' => $user, 'find_role' => $role, 'all_roles' => $roles]);
