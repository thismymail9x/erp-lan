-- ==========================================================
-- 1. BẢNG `roles`: Quản lý các Chức danh / Vai trò (Admin, Trưởng phòng...)
-- ==========================================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
                         `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính định danh Vai trò',
                         `name` varchar(100) NOT NULL COMMENT 'Tên vai trò (vd: Admin, Mod, Nhân viên...)',
                         `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết về phạm vi của vai trò này',
                         `created_at` datetime DEFAULT NULL,
                         `updated_at` datetime DEFAULT NULL,
                         `deleted_at` datetime DEFAULT NULL,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lưu trữ và phân nhánh các vai trò trên hệ thống';

-- ==========================================================
-- 2. BẢNG `departments`: Hệ thống Phòng ban
-- ==========================================================
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
                               `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Định danh Phòng ban',
                               `name` varchar(100) NOT NULL COMMENT 'Tên Phòng ban (Marketing, Kế toán...)',
                               `description` text DEFAULT NULL COMMENT 'Diễn giải chức năng phòng ban',
                               `created_at` datetime DEFAULT NULL,
                               `updated_at` datetime DEFAULT NULL,
                               `deleted_at` datetime DEFAULT NULL,
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Danh mục sơ đồ tổ chức các bộ phận trong công ty';

-- ==========================================================
-- 3. BẢNG `users`: Kho lưu trữ Tài khoản và Ủy quyền truy cập
-- ==========================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
                         `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính tài khoản',
                         `role_id` int(11) unsigned NOT NULL COMMENT 'Tài khoản này mang quyền của Vai trò nào',
                         `email` varchar(255) NOT NULL COMMENT 'Email gốc làm tên đăng nhập',
                         `password` varchar(255) NOT NULL COMMENT 'Chuỗi mật khẩu hash chuẩn BCRYPT',
                         `active_status` tinyint(1) DEFAULT 1 COMMENT 'Cờ để kiểm soát việc cấm đăng nhập (1: Hoạt động, 0: Bị khóa)',
                         `created_at` datetime DEFAULT NULL,
                         `updated_at` datetime DEFAULT NULL,
                         `deleted_at` datetime DEFAULT NULL,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `email` (`email`),
                         KEY `role_id` (`role_id`),
                         CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Hệ tệp tài khoản đăng nhập (Phân hệ bảo mật)';

-- ----------------------------
-- Table structure for permissions (Quyền hạn)
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
                               `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                               `name` varchar(100) NOT NULL COMMENT 'Mã quyền (vd: manage_cases)',
                               `description` text DEFAULT NULL COMMENT 'Mô tả về quyền này',
                               `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo',
                               `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                               `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ các quyền truy cập chi tiết';

-- ----------------------------
-- Table structure for roles_permissions (Phân quyền cho vai trò)
-- ----------------------------
DROP TABLE IF EXISTS `roles_permissions`;
CREATE TABLE `roles_permissions` (
                                     `role_id` int(11) unsigned NOT NULL COMMENT 'ID vai trò',
                                     `permission_id` int(11) unsigned NOT NULL COMMENT 'ID quyền',
                                     PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng trung gian nối vai trò và quyền hạn';

-- ----------------------------
-- Table structure for employees (Thông tin nhân viên)
-- ----------------------------

-- ==========================================================
-- 4. BẢNG `employees`: Thông tin chi tiết, hồ sơ Nhân sự gốc
-- ==========================================================
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
                             `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID hồ sơ nhân viên',
                             `user_id` int(11) unsigned DEFAULT NULL COMMENT 'Liên kết nhân sự với 1 tài khoản đăng nhập',
                             `full_name` varchar(255) NOT NULL COMMENT 'Họ tên thật trên giấy tờ',
                             `dob` date DEFAULT NULL COMMENT 'Ngày tháng năm sinh',
                             `identity_card` varchar(50) DEFAULT NULL COMMENT 'Số Căn cước công dân hoặc Passport',
                             `address` text DEFAULT NULL COMMENT 'Địa chỉ thường trú / tạm trú',
                             `join_date` date DEFAULT NULL COMMENT 'Ngày chính thức onboard vào công ty',
                             `salary_base` decimal(15,2) DEFAULT '0.00' COMMENT 'Lương cơ bản thỏa thuận ban đầu',
                             `position` varchar(100) DEFAULT NULL COMMENT 'Chức danh ghi trên hợp đồng (Khác với Role ở app)',
                             `department_id` int(11) unsigned DEFAULT NULL COMMENT 'Nhân sự này thuộc quản lý của phòng ban nào',
                             `created_at` datetime DEFAULT NULL,
                             `updated_at` datetime DEFAULT NULL,
                             `deleted_at` datetime DEFAULT NULL,
                             PRIMARY KEY (`id`),
                             KEY `user_id` (`user_id`),
                             KEY `department_id` (`department_id`),
                             CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                             CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Học bạ, hồ sơ toàn diện về thông tin định danh của từng nhân viên';

-- ----------------------------
-- Table structure for customers (Khách hàng)
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã khách hàng (KH-2026-001)',
    `type` ENUM('ca_nhan', 'doanh_nghiep') NOT NULL DEFAULT 'ca_nhan' COMMENT 'Phân loại chủ thể',
    
    -- Thông tin cá nhân (Hoặc người đại diện nếu là DN)
    `name` VARCHAR(255) NOT NULL COMMENT 'Họ và tên hoặc tên công ty',
    `date_of_birth` DATE NULL DEFAULT NULL,
    `gender` ENUM('nam', 'nu', 'khac') DEFAULT 'khac',
    
    -- Định danh (PDPL Sensitive)
    `identity_type` ENUM('cccd', 'cmnd', 'passport') DEFAULT 'cccd',
    `identity_number` VARCHAR(50) NULL DEFAULT NULL UNIQUE,
    `issue_date` DATE NULL DEFAULT NULL,
    `expiry_date` DATE NULL DEFAULT NULL,
    `issued_by` VARCHAR(255) NULL DEFAULT NULL,
    
    -- Liên lạc
    `phone` VARCHAR(20) NOT NULL,
    `phone_secondary` VARCHAR(20) NULL DEFAULT NULL,
    `email` VARCHAR(255) NULL DEFAULT NULL,
    `email_secondary` VARCHAR(255) NULL DEFAULT NULL,
    
    -- Địa chỉ
    `address` TEXT NULL DEFAULT NULL COMMENT 'Địa chỉ đầy đủ hiển thị',
    `address_json` JSON NULL DEFAULT NULL COMMENT 'Chi tiết: số nhà, phường, quận, tỉnh',
    
    -- Thông tin doanh nghiệp (Chỉ dùng khi type = doanh_nghiep)
    `company_name` VARCHAR(255) NULL DEFAULT NULL,
    `tax_code` VARCHAR(50) NULL DEFAULT NULL,
    `biz_registration_number` VARCHAR(100) NULL DEFAULT NULL,
    `rep_position` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Chức vụ người đại diện',
    
    -- Quản lý & CRM
    `tags` TEXT NULL DEFAULT NULL COMMENT 'Tags phân loại',
    `source` ENUM('facebook', 'zalo', 'google', 'gioi_thieu', 'website', 'khac') DEFAULT 'khac',
    `referred_by` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID khách hàng giới thiệu hoặc nhân viên',
    `is_blacklist` TINYINT(1) DEFAULT 0,
    `blacklist_reason` TEXT NULL DEFAULT NULL,
    
    -- Thống kê nhanh (Cache fields)
    `total_revenue` DECIMAL(15,2) DEFAULT 0,
    `total_cases` INT(11) DEFAULT 0,
    `success_rate` DECIMAL(5,2) DEFAULT 0,
    `last_contact_date` DATETIME NULL DEFAULT NULL,
    
    `notes_internal` TEXT NULL DEFAULT NULL COMMENT 'Ghi chú nội bộ (Chỉ Quản lý)',
    
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    INDEX (`phone`),
    INDEX (`identity_number`),
    INDEX (`tax_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng hạt nhân quản lý khách hàng (CRM)';

-- ----------------------------
-- Table structure for cases (Vụ việc pháp lý)
-- ----------------------------
DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases` (
                         `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                         `customer_id` int(11) unsigned NOT NULL COMMENT 'ID khách hàng liên quan',
                         `title` varchar(255) NOT NULL COMMENT 'Tên vụ việc',
                         `type` enum('to_tung_dan_su', 'thu_tuc_hanh_chinh', 'xoa_an_tich', 'ly_hon_thuan_tinh', 'tu_van', 'khac') DEFAULT 'khac' COMMENT 'Loại hình vụ việc',
                         `code` varchar(50) NOT NULL COMMENT 'Mã hồ sơ nội bộ',
                         `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết vụ việc',
                         `status` enum('moi_tiep_nhan', 'dang_xu_ly', 'cho_tham_tam', 'da_giai_quyet', 'dong_ho_so', 'huy') DEFAULT 'moi_tiep_nhan' COMMENT 'Trạng thái xử lý',
                         `priority` enum('low','medium', 'high', 'critical') DEFAULT 'medium' COMMENT 'Mức độ ưu tiên',
                         `assigned_lawyer_id` int(11) unsigned DEFAULT NULL COMMENT 'ID luật sư phụ trách chính',
                         `assigned_staff_id` int(11) unsigned DEFAULT NULL COMMENT 'Nhân viên hỗ trợ',
                         `start_date` date DEFAULT NULL COMMENT 'Ngày bắt đầu thụ lý',
                         `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc dự kiến',
                         `deadline` datetime DEFAULT NULL COMMENT 'Hạn chót xử lý tổng',
                         `current_step` varchar(255) DEFAULT NULL COMMENT 'Bước xử lý hiện tại',
                         `workflow_template_id` int(11) unsigned DEFAULT NULL COMMENT 'Link tới bản mẫu quy trình',
                         `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo hồ sơ',
                         `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                         `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `code` (`code`),
                         KEY `customer_id` (`customer_id`),
                         KEY `assigned_lawyer_id` (`assigned_lawyer_id`),
                         KEY `assigned_staff_id` (`assigned_staff_id`),
                         CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                         CONSTRAINT `cases_ibfk_2` FOREIGN KEY (`assigned_lawyer_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                         CONSTRAINT `cases_ibfk_3` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý các hồ sơ vụ việc';

-- ----------------------------
-- Table structure for attendances (Nhật ký chấm công thông minh)
-- ----------------------------
DROP TABLE IF EXISTS `attendances`;
CREATE TABLE `attendances` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) unsigned NOT NULL COMMENT 'ID nhân viên',
  `attendance_date` date NOT NULL COMMENT 'Ngày chấm công',
  `check_in_time` datetime DEFAULT NULL COMMENT 'Thời gian vào',
  `check_in_latitude` decimal(10,8) DEFAULT NULL,
  `check_in_longitude` decimal(11,8) DEFAULT NULL,
  `check_in_photo` varchar(255) DEFAULT NULL COMMENT 'Ảnh chụp lúc vào',
  `check_in_note` text DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL COMMENT 'Thời gian ra',
  `check_out_latitude` decimal(10,8) DEFAULT NULL,
  `check_out_longitude` decimal(11,8) DEFAULT NULL,
  `check_out_photo` varchar(255) DEFAULT NULL COMMENT 'Ảnh chụp lúc ra',
  `check_out_note` text DEFAULT NULL,
  `worked_hours` decimal(5,2) DEFAULT '0.00' COMMENT 'Số giờ làm việc',
  `status` varchar(50) DEFAULT 'REGULAR' COMMENT 'Trạng thái (REGULAR, LATE, EARLY_LEAVE, ...)',
  `is_valid_location` tinyint(1) DEFAULT '1' COMMENT 'Vị trí hợp lệ?',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `idx_attendance_date` (`attendance_date`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng theo dõi chấm công bằng hình ảnh và GPS';

-- ----------------------------
-- Table structure for password_resets (Mã đặt lại mật khẩu)
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
                                   `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                                   `email` varchar(255) NOT NULL COMMENT 'Email yêu cầu reset',
                                   `token` varchar(255) NOT NULL COMMENT 'Mã token bảo mật',
                                   `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo mã',
                                   `expires_at` datetime NOT NULL COMMENT 'Thời gian hết hạn mã',
                                   PRIMARY KEY (`id`),
                                   KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ mã đặt lại mật khẩu';

-- ----------------------------
-- Initial Seeding (Dữ liệu mẫu ban đầu)
-- ----------------------------

INSERT INTO `roles` (`name`, `description`, `created_at`) VALUES
                                                              ('Admin', 'Toàn quyền hệ thống.', NOW()),
                                                              ('Mod', 'Điều hành, được cấp một số quyền cụ thể.', NOW()),
                                                              ('Trưởng phòng', 'Quản lý bộ phận và nhân viên thuộc cấp.', NOW()),
                                                              ('Nhân viên chính thức', 'Thực hiện các nghiệp vụ chuyên môn.', NOW()),
                                                              ('Thực tập sinh', 'Hỗ trợ và học việc.', NOW());

INSERT INTO `departments` (`name`, `description`, `created_at`) VALUES
                                                                    ('Marketing', 'Bộ phận truyền thông và tiếp thị.', NOW()),
                                                                    ('Sale', 'Bộ phận kinh doanh và khách hàng.', NOW()),
                                                                    ('Pháp lý', 'Bộ phận tư vấn và xử lý vụ việc pháp luật.', NOW()),
                                                                    ('Hành chính', 'Bộ phận quản lý nhân sự và văn phòng.', NOW()),
                                                                    ('Luật sư cộng tác', 'Đối tác luật sư bên ngoài.', NOW()),
                                                                    ('Đối tác', 'Các đơn vị đối tác liên kết.', NOW());

INSERT INTO `permissions` (`name`, `description`, `created_at`) VALUES
                                                                    ('manage_employees', 'Có thể tạo/sửa nhân viên', NOW()),
                                                                    ('manage_cases', 'Có thể quản lý vụ việc pháp lý', NOW()),
                                                                    ('view_accounting', 'Có thể xem báo cáo tài chính', NOW()),
                                                                    ('manage_contracts', 'Có thể xử lý hợp đồng pháp lý', NOW());

-- Khởi tạo tài khoản CEO Tối cao
INSERT INTO `users` (`id`, `role_id`, `email`, `password`, `active_status`, `created_at`) VALUES
    (1, 1, 'admin@lawfirm.erp', '$2y$12$Oergst.CYv4Fr/bUMsUFJuO/fuvvDjcw0ZWSWO7kH55x.XWyPgMhS', 1, NOW());

-- Tạo hồ sơ của CEO Tối cao (Trường hợp Admin)
INSERT INTO `employees` (`id`, `user_id`, `full_name`, `position`, `department_id`, `created_at`) VALUES
    (1, 1, 'LawFirm Admin Tổng', 'CEO - System Admin', 4, NOW());

-- ----------------------------
-- Table structure for system_settings (Cấu hình hệ thống)
-- ----------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
                                   `key` varchar(100) NOT NULL COMMENT 'Khóa cấu hình',
                                   `value` text DEFAULT NULL COMMENT 'Giá trị cấu hình (JSON hoặc chuỗi)',
                                   `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                   PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ các cấu hình linh hoạt của hệ thống';

-- Khởi tạo trạng thái cho Quotes
INSERT INTO `system_settings` (`key`, `value`) VALUES ('quote_state', '{\"shuffled_indices\": [], \"current_index\": 0, \"last_updated_at\": \"2000-01-01 00:00:00\"}');

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 1;
ALTER TABLE `employees`
    ADD COLUMN `bank_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tên ngân hàng' AFTER `position`,
ADD COLUMN `bank_account` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Số tài khoản ngân hàng' AFTER `bank_name`;

-- ----------------------------
-- Table structure for system_logs (Nhật ký hệ thống)
-- ----------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL COMMENT 'Người thực hiện',
  `action` varchar(50) NOT NULL COMMENT 'Hành động (LOGIN, CREATE, UPDATE, DELETE)',
  `module` varchar(100) NOT NULL COMMENT 'Tên Module tác động',
  `entity_id` int(11) DEFAULT NULL COMMENT 'ID của bản ghi bị tác động',
  `details` text DEFAULT NULL COMMENT 'Chi tiết dữ liệu (JSON)',
  `ip_address` varchar(45) NOT NULL COMMENT 'Địa chỉ IP người thao tác',
  `user_agent` text NOT NULL COMMENT 'Thông tin trình duyệt/thiết bị',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- BỔ SUNG PHÂN HỆ QUẢN LÝ VỤ VIỆC PHÁP LÝ (CASE MANAGEMENT)
-- Generated: 2026-03-12
-- ==========================================================

-- 1. Cập nhật bảng customers (Đã hợp nhất vào định nghĩa chính ở đầu file)
-- ALTER TABLE `customers` ...

-- 2. Cập nhật bảng cases
ALTER TABLE `cases`
    CHANGE COLUMN `internal_code` `code` VARCHAR(50) NOT NULL COMMENT 'Mã hồ sơ nội bộ (TTDS-2026-001...)',
    MODIFY COLUMN `status` ENUM('moi_tiep_nhan', 'dang_xu_ly', 'cho_tham_tam', 'da_giai_quyet', 'dong_ho_so', 'huy') DEFAULT 'moi_tiep_nhan' COMMENT 'Trạng thái xử lý';

ALTER TABLE `cases` ADD CONSTRAINT `fk_cases_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 3. Bảng case_history (Nhật ký thay đổi vụ việc)
DROP TABLE IF EXISTS `case_history`;
CREATE TABLE `case_history` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID vụ việc',
    `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Người thực hiện thay đổi',
    `action` VARCHAR(100) DEFAULT 'tiep_nhan' COMMENT 'Loại hành động',
    `old_value` TEXT NULL DEFAULT NULL COMMENT 'Giá trị cũ',
    `new_value` TEXT NULL DEFAULT NULL COMMENT 'Giá trị mới',
    `note` TEXT NULL DEFAULT NULL COMMENT 'Ghi chú chi tiết',
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `case_id` (`case_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_history_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu vết tất cả thay đổi trên một vụ việc';

-- 4. Bảng documents (Tài liệu hồ sơ vụ việc)
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` INT(11) UNSIGNED NOT NULL COMMENT 'Vụ việc liên kết',
    `file_name` VARCHAR(255) NOT NULL COMMENT 'Tên file hiển thị',
    `type` VARCHAR(100) DEFAULT NULL COMMENT 'Loại tài liệu (Đơn khởi kiện, quyết định...)',
    `file_path` VARCHAR(255) NOT NULL COMMENT 'Đường dẫn file trên server',
    `uploaded_by` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Người tải lên',
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `case_id` (`case_id`),
    KEY `uploaded_by` (`uploaded_by`),
    CONSTRAINT `fk_doc_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ hồ sơ tài liệu đính kèm vụ việc';

-- 5. Bảng case_steps (Timeline & Deadline Tracker)
DROP TABLE IF EXISTS `case_steps`;
CREATE TABLE `case_steps` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID vụ việc',
    `template_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Link tới bản mẫu quy trình',
    `template_step_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Link tới bước trong bản mẫu',
    `step_name` VARCHAR(255) NOT NULL COMMENT 'Tên bước thực hiện',
    `duration_days` INT(11) DEFAULT 0 COMMENT 'Thời hạn (ngày)',
    `is_working_day_only` TINYINT(1) DEFAULT 1,
    `deadline` DATETIME NULL DEFAULT NULL COMMENT 'Hạn chót cho bước này',
    `completed_at` DATETIME NULL DEFAULT NULL COMMENT 'Ngày hoàn thành thực tế',
    `status` ENUM('pending', 'active', 'completed', 'overdue') DEFAULT 'pending' COMMENT 'Trạng thái bước',
    `sort_order` INT(11) DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
    `responsible_role` VARCHAR(50) DEFAULT NULL,
    `required_documents` TEXT NULL DEFAULT NULL COMMENT 'Danh sách tài liệu yêu cầu (JSON)',
    `next_step_condition` TEXT NULL DEFAULT NULL,
    `notification_template` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `case_id` (`case_id`),
    CONSTRAINT `fk_steps_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng theo dõi tiến độ chi tiết từng bước của vụ việc';

-- 6. Bảng case_comments (Internal Logs / Comments)
DROP TABLE IF EXISTS `case_comments`;
CREATE TABLE `case_comments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID vụ việc',
    `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Người tạo bình luận',
    `content` TEXT NOT NULL COMMENT 'Nội dung bình luận',
    `is_internal` TINYINT(1) DEFAULT 1 COMMENT 'Bình luận nội bộ',
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `case_id` (`case_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_com_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_com_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ bình luận và ghi chú nội bộ của nhân viên';

-- 7. Bảng customer_interactions (Lịch sử tương tác)
DROP TABLE IF EXISTS `customer_interactions`;
CREATE TABLE `customer_interactions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) UNSIGNED NOT NULL,
    `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'Nhân viên thực hiện',
    `channel` ENUM('call', 'zalo', 'email', 'meeting', 'facebook', 'khac') NOT NULL,
    `interaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `summary` VARCHAR(255) NOT NULL,
    `detailed_content` TEXT NULL DEFAULT NULL,
    `next_follow_up` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Nhật ký tương tác khách hàng';

-- 8. Bảng customer_documents (Hồ sơ số hóa)
DROP TABLE IF EXISTS `customer_documents`;
CREATE TABLE `customer_documents` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) UNSIGNED NOT NULL,
    `document_type` VARCHAR(100) NOT NULL COMMENT 'CCCD, GPKD, Hợp đồng, Giấy ủy quyền...',
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `uploaded_by` INT(11) UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tài liệu định danh và pháp lý của khách hàng';

-- 9. Bảng customer_payments (Tài chính khách hàng)
DROP TABLE IF EXISTS `customer_payments`;
CREATE TABLE `customer_payments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) UNSIGNED NOT NULL,
    `case_id` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT 'Liên kết vụ việc (nếu có)',
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `method` ENUM('transfer', 'cash', 'card', 'khac') DEFAULT 'transfer',
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'Kế toán/Nhân viên nhận',
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lịch sử thanh toán của khách hàng';
-- 1. Bảng Workflow Templates (Quy trình mẫu)
CREATE TABLE IF NOT EXISTS `workflow_templates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `case_type` VARCHAR(50) NOT NULL,
  `version` INT(11) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `total_estimated_days` INT(11) NOT NULL DEFAULT 0,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Bảng Workflow Template Steps (Các bước trong mẫu)
CREATE TABLE IF NOT EXISTS `workflow_template_steps` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_id` INT(11) UNSIGNED NOT NULL,
  `step_order` INT(11) NOT NULL,
  `step_name` VARCHAR(255) NOT NULL,
  `duration_days` INT(11) NOT NULL DEFAULT 1,
  `is_working_day_only` TINYINT(1) NOT NULL DEFAULT 1,
  `required_documents` TEXT DEFAULT NULL,
  `responsible_role` VARCHAR(50) NOT NULL,
  `next_step_condition` TEXT DEFAULT NULL,
  `notification_template` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_template_id` FOREIGN KEY (`template_id`) REFERENCES `workflow_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Bảng Workflow Instances (Thực thể quy trình cho từng vụ việc)
CREATE TABLE IF NOT EXISTS `workflow_instances` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT(11) UNSIGNED NOT NULL,
  `template_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('active', 'completed', 'overdue') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_wf_case_id` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wf_template_id` FOREIGN KEY (`template_id`) REFERENCES `workflow_templates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Nâng cấp bảng cases (Thêm workflow_template_id)
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_NAME = 'cases' AND COLUMN_NAME = 'workflow_template_id' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `cases` ADD COLUMN `workflow_template_id` INT(11) UNSIGNED NULL AFTER `end_date`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Nâng cấp bảng case_steps
-- Thêm instance_id
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'case_steps' AND COLUMN_NAME = 'instance_id' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `case_steps` ADD COLUMN `instance_id` INT(11) UNSIGNED NULL AFTER `case_id`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm template_step_id
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'case_steps' AND COLUMN_NAME = 'template_step_id' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `case_steps` ADD COLUMN `template_step_id` INT(11) UNSIGNED NULL AFTER `instance_id`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm responsible_role
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'case_steps' AND COLUMN_NAME = 'responsible_role' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `case_steps` ADD COLUMN `responsible_role` VARCHAR(50) NULL AFTER `status`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm next_step_condition
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'case_steps' AND COLUMN_NAME = 'next_step_condition' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `case_steps` ADD COLUMN `next_step_condition` TEXT NULL AFTER `responsible_role`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm notification_template
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'case_steps' AND COLUMN_NAME = 'notification_template' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `case_steps` ADD COLUMN `notification_template` TEXT NULL AFTER `next_step_condition`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm is_working_day_only
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'case_steps' AND COLUMN_NAME = 'is_working_day_only' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `case_steps` ADD COLUMN `is_working_day_only` TINYINT(1) DEFAULT 1 AFTER `duration_days`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- Table structure for notifications (Hệ thống thông báo nội bộ)
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
                                 `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                                 `user_id` int(11) unsigned NOT NULL COMMENT 'ID người nhận thông báo',
                                 `sender_id` int(11) unsigned DEFAULT NULL COMMENT 'ID người gửi (nếu có)',
                                 `type` varchar(50) DEFAULT 'system' COMMENT 'Loại thông báo',
                                 `title` varchar(255) NOT NULL COMMENT 'Tiêu đề thông báo',
                                 `message` text NOT NULL COMMENT 'Nội dung chi tiết',
                                 `link` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn liên kết',
                                 `is_read` tinyint(1) DEFAULT 0 COMMENT 'Tình trạng đã đọc (1: Có, 0: Không)',
                                 `created_at` datetime DEFAULT NULL,
                                 `updated_at` datetime DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `user_id` (`user_id`),
                                 CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ thông báo hệ thống và xét duyệt';

-- ----------------------------
-- Bổ sung cột step_id cho bảng documents
-- ----------------------------
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_NAME = 'documents' AND COLUMN_NAME = 'step_id' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `documents` ADD COLUMN `step_id` INT(11) UNSIGNED NULL COMMENT ''Tham chiếu bước thực hiện (nếu có)'' AFTER `case_id`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- Bảng Phân công nhiều nhân sự cho 1 Vụ việc (case_members)
-- ----------------------------
DROP TABLE IF EXISTS `case_members`;
CREATE TABLE `case_members` (
                                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                `case_id` int(11) unsigned NOT NULL COMMENT 'ID vụ việc',
                                `employee_id` int(11) unsigned NOT NULL COMMENT 'ID nhân sự phụ trách',
                                `role_in_case` enum('approver', 'assignee', 'supporter') NOT NULL DEFAULT 'supporter' COMMENT 'Quyền hạn: Phê duyệt, Chuyên môn, Hỗ trợ',
                                `created_at` datetime DEFAULT NULL,
                                `updated_at` DATETIME DEFAULT NULL,
                                `deleted_at` DATETIME DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                KEY `case_id` (`case_id`),
                                KEY `employee_id` (`employee_id`),
                                UNIQUE KEY `unique_member` (`case_id`, `employee_id`, `role_in_case`),
                                CONSTRAINT `fk_case_members_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                CONSTRAINT `fk_case_members_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lưu danh sách những người liên quan đến xử lý 1 vụ việc';

-- ----------------------------
-- Seeding Data cho Hệ thống Phân Quyền Mới
-- ----------------------------
INSERT IGNORE INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'case.view', 'Xem danh sách hồ sơ cơ bản'),
(2, 'case.manage', 'Sửa đổi, phân công, xoá hồ sơ'),
(3, 'case.approve', 'Phê duyệt, duyệt chuyển bước công việc'),
(4, 'user.view', 'Xem danh sách tài khoản, nhân sự'),
(5, 'user.manage', 'Tạo, khóa, phân quyền chi tiết cho tài khoản'),
(6, 'contract.view', 'Xem danh sách hợp đồng đã ký'),
(7, 'contract.manage', 'Soạn thảo, sửa đổi, hủy hợp đồng'),
(8, 'finance.manage', 'Xem báo cáo tài chính, lương, thu chi'),
(9, 'sys.admin', 'Toàn quyền Admin tối cao');

-- Gán quyền cho Admin (role_id = 1) (Mọi quyền)
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM permissions;

-- Gán quyền cho Trưởng phòng (role_id = 3)
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM permissions WHERE name IN ('case.view', 'case.manage', 'case.approve', 'user.view', 'contract.view', 'contract.manage', 'finance.manage');

-- Gán quyền cho Luật sư (role_id = 4)
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM permissions WHERE name IN ('case.view', 'case.approve', 'contract.view', 'contract.manage');

-- Gán quyền cho Nhân viên (role_id = 5)
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM permissions WHERE name IN ('case.view', 'contract.view');

-- ----------------------------
-- Nâng cấp hệ thống phân quyền mới (Thêm vào phần cuối để dễ dàng chạy đè)
-- ----------------------------

-- Thêm cột module_group vào permissions nếu chưa có
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_NAME = 'permissions' AND COLUMN_NAME = 'module_group' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `permissions` ADD COLUMN `module_group` VARCHAR(100) NULL DEFAULT ''Hệ thống'' COMMENT ''Nhóm module chức năng để phân loại UI'' AFTER `name`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cập nhật module_group cho các quyền đã chèn
UPDATE `permissions` SET `module_group` = 'Vụ việc pháp lý' WHERE `name` IN ('case.view', 'case.manage', 'case.approve');
UPDATE `permissions` SET `module_group` = 'Nhân sự & Tài khoản' WHERE `name` IN ('user.view', 'user.manage', 'manage_employees');
UPDATE `permissions` SET `module_group` = 'Hợp đồng' WHERE `name` IN ('contract.view', 'contract.manage', 'manage_contracts');
UPDATE `permissions` SET `module_group` = 'Kế toán' WHERE `name` IN ('finance.manage', 'view_accounting');
UPDATE `permissions` SET `module_group` = 'Hệ thống' WHERE `name` = 'sys.admin';

-- Tạo bảng user_permissions để ghi đè quyền cá nhân
CREATE TABLE IF NOT EXISTS `user_permissions` (
                                                  `user_id` int(11) unsigned NOT NULL COMMENT 'ID người dùng',
    `permission_id` int(11) unsigned NOT NULL COMMENT 'ID quyền',
    `is_granted` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Ép cấp quyền, 0 = Ép tước quyền (mặc cho Role có hay ko)',
    PRIMARY KEY (`user_id`,`permission_id`),
    CONSTRAINT `user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `user_permissions_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng ghi đè ngoại lệ phân quyền cấp trực tiếp cho User';

-- ----------------------------
-- Cập nhật quyền thực tế (Chỉ giữ lại các Module đang có)
-- Chạy script này để dọn dẹp các quyền không hợp lệ và cập nhật lại mảng dữ liệu.
-- ----------------------------

-- 1. Xóa các quyền không tồn tại trên thực tế hoặc data cũ
DELETE FROM `permissions` WHERE `name` IN ('contract.view', 'contract.manage', 'finance.manage', 'manage_employees', 'manage_cases', 'view_accounting', 'manage_contracts');

-- 2. Thêm các quyền mới thực tế
INSERT IGNORE INTO `permissions` (`name`, `description`, `module_group`) VALUES
('customer.view', 'Xem danh sách khách hàng', 'Khách hàng'),
('customer.manage', 'Tạo, sửa, xoá thông tin khách hàng', 'Khách hàng'),
('attendance.view', 'Xem lịch sử chấm công', 'Chấm công'),
('workflow.manage', 'Cấu hình quy trình vụ việc', 'Hệ thống');

-- 3. Cập nhật quyền cho Trưởng phòng (role_id = 3)
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE `name` IN ('case.view', 'case.manage', 'case.approve', 'user.view', 'customer.view', 'customer.manage', 'attendance.view');

-- 4. Cập nhật quyền cho Luật sư (role_id = 4)
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE `name` IN ('case.view', 'case.approve', 'customer.view', 'attendance.view');

-- 5. Cập nhật quyền cho Nhân viên (role_id = 5)
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE `name` IN ('case.view', 'customer.view', 'attendance.view');

-- --------------------------------------------------------
-- BỔ SUNG CỘT CHO CASE_MEMBERS MỚI
-- --------------------------------------------------------
SET @col_upd = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'case_members' AND COLUMN_NAME = 'updated_at' AND TABLE_SCHEMA = DATABASE());
SET @sql_upd = IF(@col_upd = 0, 'ALTER TABLE `case_members` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`', 'SELECT 1');
PREPARE stmt_upd FROM @sql_upd; EXECUTE stmt_upd; DEALLOCATE PREPARE stmt_upd;

SET @col_del = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'case_members' AND COLUMN_NAME = 'deleted_at' AND TABLE_SCHEMA = DATABASE());
SET @sql_del = IF(@col_del = 0, 'ALTER TABLE `case_members` ADD COLUMN `deleted_at` DATETIME NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt_del FROM @sql_del; EXECUTE stmt_del; DEALLOCATE PREPARE stmt_del;

-- --------------------------------------------------------
-- SỬA LỖI PHÂN QUYỀN "THẤY MỖI DASHBOARD"
-- Đảm bảo quyền Xem (case.view) được bắt buộc thêm vào Role Nhân viên và Luật sư
-- --------------------------------------------------------
INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE `name` = 'case.view';

INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE `name` = 'case.view';

-- [ADD-ON] Cập nhật thông tin bổ sung cho nhân viên (Hồ sơ & Ngân hàng)
ALTER TABLE `employees` ADD COLUMN `bank_name` varchar(255) DEFAULT NULL COMMENT 'Tên ngân hàng';
ALTER TABLE `employees` ADD COLUMN `bank_account` varchar(50) DEFAULT NULL COMMENT 'Số tài khoản ngân hàng';
ALTER TABLE `employees` ADD COLUMN `bank_owner` varchar(255) DEFAULT NULL COMMENT 'Tên chủ tài khoản (nếu khác họ tên)';
ALTER TABLE `employees` ADD COLUMN `personal_email` varchar(255) DEFAULT NULL COMMENT 'Email cá nhân';
ALTER TABLE `employees` ADD COLUMN `phone_number` varchar(20) DEFAULT NULL COMMENT 'Số điện thoại liên lạc';


-- --------------------------------------------------------
-- REMOVE VERSION COLUMN FROM WORKFLOW TEMPLATES
-- --------------------------------------------------------
ALTER TABLE workflow_templates DROP COLUMN version;

-- --------------------------------------------------------
-- ATTENDANCE OFFICE TOKEN CONFIGURATION
-- --------------------------------------------------------
INSERT IGNORE INTO system_settings (`key`, `value`) VALUES ('office_security_token', 'OFFICE_AUTO_GEN');

-- ==========================================================
-- 10. PHÂN HỆ QUẢN LÝ TÀI LIỆU NÂNG CAO (Advanced DMS)
-- Generated: 2026-03-26
-- ==========================================================

-- Cập nhật bảng documents: Thêm Metadata & Linking đa tầng
ALTER TABLE `documents` 
    ADD COLUMN `customer_id` INT(11) UNSIGNED NULL COMMENT 'Khách hàng sở hữu tài liệu' AFTER `id`,
    ADD COLUMN `document_category` ENUM('client_intake', 'case_file', 'correspondence', 'financial', 'template', 'internal') DEFAULT 'case_file' COMMENT 'Phân loại tài liệu (Hồ sơ KH, Vụ việc, Thư từ...)' AFTER `step_id`,
    ADD COLUMN `file_type` VARCHAR(10) COMMENT 'Phần mở rộng tệp (.pdf, .docx, .png...)' AFTER `file_name`,
    ADD COLUMN `mime_type` VARCHAR(100) COMMENT 'Kiểu MIME của tệp tin' AFTER `file_type`,
    ADD COLUMN `size` BIGINT(20) DEFAULT 0 COMMENT 'Kích thước tệp tính bằng Byte' AFTER `mime_type`,
    ADD COLUMN `version_number` INT(5) DEFAULT 1 COMMENT 'Số hiệu phiên bản hiện tại' AFTER `uploaded_by`,
    ADD COLUMN `is_encrypted` TINYINT(1) DEFAULT 0 COMMENT 'Trạng thái mã hóa tệp (0: không, 1: có)' AFTER `version_number`,
    ADD COLUMN `is_confidential` TINYINT(1) DEFAULT 0 COMMENT 'Trạng thái bảo mật cao (chỉ định quyền xem)' AFTER `is_encrypted`,
    ADD COLUMN `tags` JSON NULL COMMENT 'Thẻ từ khóa để tìm kiếm nhanh' AFTER `is_confidential`,
    ADD COLUMN `description` TEXT NULL COMMENT 'Mô tả chi tiết nội dung tài liệu' AFTER `tags`,
    ADD COLUMN `retention_period` INT(3) DEFAULT 10 COMMENT 'Thời gian lưu trữ tối thiểu (số năm)' AFTER `description`,
    ADD COLUMN `expiry_date` DATE NULL COMMENT 'Ngày tệp tin hết hạn hiệu lực' AFTER `retention_period`,
    ADD CONSTRAINT `fk_doc_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `documents` MODIFY COLUMN `case_id` INT(11) UNSIGNED NULL COMMENT 'Vụ việc liên quan';

-- Bảng lưu vết phiên bản cũ (Versioning System)
CREATE TABLE IF NOT EXISTS `document_versions` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID của tài liệu gốc',
  `version_number` INT(5) NOT NULL COMMENT 'Số hiệu phiên bản cũ',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Tên tệp của phiên bản này',
  `file_path` VARCHAR(255) NOT NULL COMMENT 'Đường dẫn thực tế trong storage',
  `uploaded_by` INT(11) UNSIGNED NOT NULL COMMENT 'ID người tải lên phiên bản này',
  `uploaded_at` DATETIME NOT NULL COMMENT 'Thời điểm cập nhật phiên bản',
  `change_log` TEXT NULL COMMENT 'Ghi chú về các thay đổi trong phiên bản này',
  CONSTRAINT `fk_ver_doc` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lưu trữ lịch sử các phiên bản của tài liệu trong hệ thống';

-- Bảng nhật ký truy cập bảo mật (Audit Log)
CREATE TABLE IF NOT EXISTS `document_access_logs` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID tài liệu được truy cập',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID nhân viên truy cập',
  `action` ENUM('view', 'download', 'edit', 'delete', 'upload') NOT NULL COMMENT 'Hành động cụ thể',
  `ip_address` VARCHAR(45) NULL COMMENT 'Địa chỉ IP truy cập',
  `user_agent` VARCHAR(255) NULL COMMENT 'Thông tin trình duyệt/thiết bị',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm thao tác',
  CONSTRAINT `fk_log_doc` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lịch sử chi tiết hành động của người dùng với tài liệu nhạy cảm';
