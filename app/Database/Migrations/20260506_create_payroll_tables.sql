-- Bảng cấu hình công việc hàng tháng
CREATE TABLE IF NOT EXISTS `payroll_configs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `month` VARCHAR(7) NOT NULL, -- Định dạng YYYY-MM
    `working_days_json` TEXT, -- Danh sách các ngày đi làm (JSON array)
    `holidays_json` TEXT, -- Danh sách các ngày lễ (JSON array: date => lý do)
    `total_standard_days` FLOAT DEFAULT 0, -- Tổng ngày công chuẩn của tháng
    `is_closed` TINYINT(1) DEFAULT 0, -- 1 nếu đã chốt sổ
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu trữ bảng lương chi tiết
CREATE TABLE IF NOT EXISTS `payrolls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `month` VARCHAR(7) NOT NULL,
    `salary_base` DECIMAL(15,2) DEFAULT 0, -- Lương cơ bản tại thời điểm chốt
    `salary_kpi` DECIMAL(15,2) DEFAULT 0, -- Lương KPI (tổng kpi_reward trong tháng)
    `salary_allowance` DECIMAL(15,2) DEFAULT 0, -- Phụ cấp cố định
    `salary_bonus` DECIMAL(15,2) DEFAULT 0, -- Thưởng thêm (manual)
    `salary_deduction` DECIMAL(15,2) DEFAULT 0, -- Các khoản trừ (phạt muộn, vi phạm...)
    `total_standard_days` FLOAT DEFAULT 0, -- Ngày công chuẩn (từ config)
    `actual_working_days` FLOAT DEFAULT 0, -- Ngày công thực tế (điểm danh + nghỉ phép có lương)
    `attendance_violations` INT DEFAULT 0, -- Số lần vi phạm (muộn/về sớm)
    `net_salary` DECIMAL(15,2) DEFAULT 0, -- Thực lĩnh
    `status` ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_emp_month` (`employee_id`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cập nhật bảng employees để thêm phụ cấp cố định nếu chưa có
ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `allowance_base` DECIMAL(15,2) DEFAULT 0 AFTER `salary_base`;
