<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DepartmentController
 * 
 * Bộ điều khiển quản lý danh mục Phòng ban.
 * (Hiện tại đang sử dụng các phương thức mặc định từ BaseController hoặc CRUD đơn giản).
 */
class DepartmentController extends BaseController
{
    /**
     * Hiển thị danh sách các phòng ban trong công ty.
     */
    public function index()
    {
        // Hiện tại logic hiển thị và quản lý phòng ban đang được tích hợp linh hoạt
        // trong các module Nhân sự và Phân quyền. 
        // File này dự phòng cho các tính năng mở rộng trong tương lai.
    }
}
