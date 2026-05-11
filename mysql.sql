-- ==========================================================
-- ERP L.A.N - HỆ THỐNG QUẢN TRỊ VỤ VIỆC PHÁP LÝ & NHÂN SỰ
-- DATABASE SCHEMA CONSOLIDATED (Updated: 2026-03-30)
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. BẢNG `permissions` (Quyền hạn chi tiết)
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT 'Mã quyền (vd: case.view)',
    `module_group` varchar(100) DEFAULT 'Hệ thống' COMMENT 'Nhóm module chức năng để phân loại UI',
    `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết quyền',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Danh mục các quyền hạn hạt nhân trong hệ thống';

-- ----------------------------
-- 2. BẢNG `roles` (Vai trò/Chức danh)
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT 'Tên vai trò (Admin, Trưởng phòng...)',
    `description` text DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Phân nhánh các vai trò người dùng';

-- ----------------------------
-- 3. BẢNG `roles_permissions` (Ma trận quyền theo vai trò)
-- ----------------------------
DROP TABLE IF EXISTS `roles_permissions`;
CREATE TABLE `roles_permissions` (
    `role_id` int(11) unsigned NOT NULL,
    `permission_id` int(11) unsigned NOT NULL,
    PRIMARY KEY (`role_id`,`permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 4. BẢNG `departments` (Sơ đồ phòng ban)
-- ----------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description?` text DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 5. BẢNG `users` (Tài khoản đăng nhập)
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `role_id` int(11) unsigned NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `active_status` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 6. BẢNG `user_permissions` (Ghi đè quyền cá nhân)
-- ----------------------------
DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
    `user_id` int(11) unsigned NOT NULL,
    `permission_id` int(11) unsigned NOT NULL,
    `is_granted` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Grant, 0 = Deny',
    PRIMARY KEY (`user_id`,`permission_id`),
    CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_up_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 7. BẢNG `employees` (Hồ sơ nhân sự)
-- ----------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned DEFAULT NULL,
    `full_name` varchar(255) NOT NULL,
    `dob` date DEFAULT NULL,
    `identity_card` varchar(50) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `join_date` date DEFAULT NULL,
    `salary_base` decimal(15,2) DEFAULT '0.00',
    `position` varchar(100) DEFAULT NULL,
    `department_id` int(11) unsigned DEFAULT NULL,
    -- Banking & Contact info consolidated
    `bank_name` varchar(255) DEFAULT NULL COMMENT 'Tên ngân hàng',
    `bank_account` varchar(50) DEFAULT NULL COMMENT 'Số tài khoản ngân hàng',
    `bank_owner` varchar(255) DEFAULT NULL COMMENT 'Tên chủ tài khoản (nếu khác họ tên)',
    `personal_email` varchar(255) DEFAULT NULL COMMENT 'Email cá nhân',
    `phone_number` varchar(20) DEFAULT NULL COMMENT 'Số điện thoại liên lạc',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 8. BẢNG `customers` (Dữ liệu khách hàng CRM)
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL UNIQUE COMMENT 'Mã khách hàng (KH-2026-001)',
    `type` enum('ca_nhan', 'doanh_nghiep') NOT NULL DEFAULT 'ca_nhan',
    `name` varchar(255) NOT NULL COMMENT 'Họ và tên hoặc tên doanh nghiệp',
    `date_of_birth` date DEFAULT NULL,
    `gender` enum('nam', 'nu', 'khac') DEFAULT 'khac',
    `identity_type` enum('cccd', 'cmnd', 'passport') DEFAULT 'cccd',
    `identity_number` varchar(50) DEFAULT NULL UNIQUE,
    `issue_date` date DEFAULT NULL,
    `expiry_date` date DEFAULT NULL,
    `issued_by` varchar(255) DEFAULT NULL,
    `phone` varchar(20) NOT NULL,
    `phone_secondary` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `email_secondary` varchar(255) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `address_json` json DEFAULT NULL,
    `company_name` varchar(255) DEFAULT NULL,
    `tax_code` varchar(50) DEFAULT NULL,
    `biz_registration_number` varchar(100) DEFAULT NULL,
    `rep_position` varchar(100) DEFAULT NULL,
    `tags` text DEFAULT NULL,
    `source` enum('facebook', 'zalo', 'google', 'gioi_thieu', 'website', 'khac') DEFAULT 'khac',
    `referred_by` int(11) unsigned DEFAULT NULL,
    `is_blacklist` tinyint(1) DEFAULT 0,
    `blacklist_reason` text DEFAULT NULL,
    `total_revenue` decimal(15,2) DEFAULT 0.00,
    `total_cases` int(11) DEFAULT 0,
    `success_rate` decimal(5,2) DEFAULT 0.00,
    `last_contact_date` datetime DEFAULT NULL,
    `notes_internal` text DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX (`phone`),
    INDEX (`identity_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 9. BẢNG `workflow_templates` (Quy trình mẫu)
-- ----------------------------
DROP TABLE IF EXISTS `workflow_templates`;
CREATE TABLE `workflow_templates` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL UNIQUE,
    `name` varchar(255) NOT NULL,
    `case_type` varchar(50) NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `total_estimated_days` int(11) NOT NULL DEFAULT 0,
    `created_by` int(11) unsigned DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 10. BẢNG `workflow_template_steps` (Chi tiết bước trong mẫu)
-- ----------------------------
DROP TABLE IF EXISTS `workflow_template_steps`;
CREATE TABLE `workflow_template_steps` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `template_id` int(11) unsigned NOT NULL,
    `step_order` int(11) NOT NULL,
    `step_name` varchar(255) NOT NULL,
    `duration_days` int(11) NOT NULL DEFAULT 1,
    `is_working_day_only` tinyint(1) NOT NULL DEFAULT 1,
    `required_documents` text DEFAULT NULL,
    `responsible_role` varchar(50) NOT NULL,
    `kpi_reward` decimal(15,2) DEFAULT 0.00 COMMENT 'KPI thưởng theo bước',
    `next_step_condition` text DEFAULT NULL,
    `notification_template` text DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_wts_template` FOREIGN KEY (`template_id`) REFERENCES `workflow_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 11. BẢNG `cases` (Hồ sơ vụ việc pháp lý)
-- ----------------------------
DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) unsigned NOT NULL,
    `code` varchar(50) NOT NULL UNIQUE COMMENT 'Mã hồ sơ (vd: TTDS-2026-001)',
    `title` varchar(255) NOT NULL,
    `type` varchar(50) DEFAULT 'khac',
    `description` text DEFAULT NULL,
    `status` enum('moi_tiep_nhan', 'dang_xu_ly', 'cho_tham_tam', 'da_giai_quyet', 'dong_ho_so', 'huy', 'open', 'in_progress', 'pending', 'closed', 'cancelled') DEFAULT 'moi_tiep_nhan',
    `priority` enum('low','medium', 'high', 'critical') DEFAULT 'medium',
    `assigned_lawyer_id` int(11) unsigned DEFAULT NULL,
    `assigned_staff_id` int(11) unsigned DEFAULT NULL,
    `workflow_template_id?` int(11) unsigned DEFAULT NULL,
    `start_date` date DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `deadline` datetime DEFAULT NULL,
    `current_step` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cases_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cases_lawyer` FOREIGN KEY (`assigned_lawyer_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cases_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cases_workflow` FOREIGN KEY (`workflow_template_id`) REFERENCES `workflow_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 12. BẢNG `workflow_instances` (Tiến trình thực tế)
-- ----------------------------
DROP TABLE IF EXISTS `workflow_instances`;
CREATE TABLE `workflow_instances` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `case_id` int(11) unsigned NOT NULL,
    `template_id` int(11) unsigned NOT NULL,
    `status` enum('active', 'completed', 'overdue') NOT NULL DEFAULT 'active',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_wi_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wi_template` FOREIGN KEY (`template_id`) REFERENCES `workflow_templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 13. BẢNG `case_steps` (Timeline & Deadline Tracker)
-- ----------------------------
DROP TABLE IF EXISTS `case_steps`;
CREATE TABLE `case_steps` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `case_id` int(11) unsigned NOT NULL,
    `instance_id` int(11) unsigned DEFAULT NULL,
    `template_step_id` int(11) unsigned DEFAULT NULL,
    `step_name` varchar(255) NOT NULL,
    `duration_days` int(11) DEFAULT 0,
    `is_working_day_only` tinyint(1) DEFAULT 1,
    `deadline` datetime DEFAULT NULL,
    `completed_at?` datetime DEFAULT NULL,
    `status` enum('pending', 'active', 'completed', 'overdue') DEFAULT 'pending',
    `responsible_role` varchar(50) DEFAULT NULL,
    `kpi_reward` decimal(15,2) DEFAULT 0.00,
    `overdue_notified` tinyint(1) DEFAULT 0,
    `sort_order` int(11) DEFAULT 0,
    `required_documents` text DEFAULT NULL,
    `next_step_condition?` text DEFAULT NULL,
    `notification_template` text DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at?` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cs_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 14. BẢNG `case_members` (Đội ngũ tương tác vụ việc)
-- ----------------------------
DROP TABLE IF EXISTS `case_members`;
CREATE TABLE `case_members` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `case_id?` int(11) unsigned NOT NULL,
    `employee_id` int(11) unsigned NOT NULL,
    `role_in_case` enum('approver', 'assignee', 'supporter') NOT NULL DEFAULT 'supporter',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_member` (`case_id`, `employee_id`, `role_in_case`),
    CONSTRAINT `fk_cm_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cm_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 15. BẢNG `documents` (advanced DMS - Quản lý tài liệu)
-- ----------------------------
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) unsigned DEFAULT NULL,
    `case_id` int(11) unsigned DEFAULT NULL,
    `step_id` int(11) unsigned DEFAULT NULL,
    `document_category` enum('client_intake', 'case_file', 'correspondence', 'financial', 'template', 'internal') DEFAULT 'case_file',
    `file_name` varchar(255) NOT NULL,
    `file_type` varchar(10),
    `mime_type` varchar(100),
    `size` bigint(20) DEFAULT 0,
    `file_path` varchar(255) NOT NULL,
    `uploaded_by` int(11) unsigned DEFAULT NULL,
    `version_number` int(5) DEFAULT 1,
    `is_encrypted?` tinyint(1) DEFAULT 0,
    `is_confidential` tinyint(1) DEFAULT 0,
    `tags` json DEFAULT NULL,
    `description` text DEFAULT NULL,
    `retention_period` int(3) DEFAULT 10,
    `expiry_date` date DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_doc_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_doc_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 16. BẢNG `document_versions`
-- ----------------------------
DROP TABLE IF EXISTS `document_versions`;
CREATE TABLE `document_versions` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `document_id` int(11) unsigned NOT NULL,
    `version_number` int(5) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path?` varchar(255) NOT NULL,
    `uploaded_by` int(11) unsigned NOT NULL,
    `uploaded_at` datetime NOT NULL,
    `change_log` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ver_doc` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 17. BẢNG `attendances` (Chấm công GPS/IP)
-- ----------------------------
DROP TABLE IF EXISTS `attendances`;
CREATE TABLE `attendances` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) unsigned NOT NULL,
    `attendance_date` date NOT NULL,
    `check_in_time` datetime DEFAULT NULL,
    `check_in_latitude` decimal(10,8) DEFAULT NULL,
    `check_in_longitude` decimal(11,8) DEFAULT NULL,
    `check_in_photo` varchar(255) DEFAULT NULL,
    `check_in_note` text DEFAULT NULL,
    `check_out_time` datetime DEFAULT NULL,
    `check_out_latitude` decimal(10,8) DEFAULT NULL,
    `check_out_longitude` decimal(11,8) DEFAULT NULL,
    `check_out_photo` varchar(255) DEFAULT NULL,
    `check_out_note` text DEFAULT NULL,
    `worked_hours` decimal(5,2) DEFAULT '0.00',
    `status` varchar(50) DEFAULT 'REGULAR',
    `is_valid_location` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_att_date` (`attendance_date`),
    CONSTRAINT `fk_att_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 18. BẢNG `system_settings` (Cấu hình hệ thống)
-- ----------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `key` varchar(100) NOT NULL,
    `value?` text DEFAULT NULL,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 19. BẢNG `system_logs` (Audit Trails)
-- ----------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned DEFAULT NULL,
    `action` varchar(50) NOT NULL,
    `module` varchar(100) NOT NULL,
    `entity_id` int(11) DEFAULT NULL,
    `details` text DEFAULT NULL,
    `ip_address` varchar(45) NOT NULL,
    `user_agent?` text NOT NULL,
    `created_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 20. BẢNG `notifications`
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL,
    `sender_id` int(11) unsigned DEFAULT NULL,
    `type` varchar(50) DEFAULT 'system',
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `link` varchar(255) DEFAULT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_notify_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 21. BẢNG `case_comments`
-- ----------------------------
DROP TABLE IF EXISTS `case_comments`;
CREATE TABLE `case_comments` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `case_id` int(11) unsigned NOT NULL,
    `user_id` int(11) unsigned DEFAULT NULL,
    `content` text NOT NULL,
    `is_internal` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_comm_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 22. BẢNG `case_history`
-- ----------------------------
DROP TABLE IF EXISTS `case_history`;
CREATE TABLE `case_history` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `case_id` int(11) unsigned NOT NULL,
    `user_id` int(11) unsigned DEFAULT NULL,
    `action?` varchar(100) DEFAULT 'tiep_nhan',
    `old_value` text,
    `new_value` text,
    `note` text,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_hist_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------------------------------------------------
-- DỮ LIỆU KHỞI TẠO (SEEDING)
-- ----------------------------------------------------------------------------------------------------------------------

-- Roles
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Admin', 'Toàn quyền hệ thống.', NOW()),
(2, 'Mod', 'Điều hành, được cấp một số quyền cụ thể.', NOW()),
(3, 'Trưởng phòng', 'Quản lý bộ phận và nhân viên thuộc cấp.', NOW()),
(4, 'Nhân viên chính thức', 'Thực hiện các nghiệp vụ chuyên môn.', NOW()),
(5, 'Thực tập sinh', 'Hỗ trợ và học việc.', NOW());

-- Departments
INSERT INTO `departments` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Marketing', 'Bộ phận truyền thông và tiếp thị.', NOW()),
(2, 'Sale', 'Bộ phận kinh doanh và khách hàng.', NOW()),
(3, 'Pháp lý', 'Bộ phận tư vấn và xử lý vụ việc pháp luật.', NOW()),
(4, 'Hành chính', 'Bộ phận quản lý nhân sự và văn phòng.', NOW()),
(5, 'Luật sư cộng tác', 'Đối tác luật sư bên ngoài.', NOW()),
(6, 'Đối tác', 'Các đơn vị đối tác liên kết.', NOW());

-- Permissions
-- Permissions (Deprecated manual inserts - Now managed via Auto-Sync Metadata in Controllers)
-- To sync: Visit /perm-fix/sync in your browser.

-- Roles Permissions Matrix (Now handled by Auto-Sync default logic [Roles 1 & 3])

-- Default Admin Account: admin@lawfirm.erp / lawfirm_erp_2026
INSERT INTO `users` (`id`, `role_id`, `email`, `password`, `active_status`, `created_at`) VALUES
(1, 1, 'admin@lawfirm.erp', '$2y$12$Oergst.CYv4Fr/bUMsUFJuO/fuvvDjcw0ZWSWO7kH55x.XWyPgMhS', 1, NOW());

INSERT INTO `employees` (`id`, `user_id`, `full_name`, `position`, `department_id`, `created_at`) VALUES
(1, 1, 'Admin', 'CEO - System Admin', 4, NOW());

-- System Settings initial
INSERT INTO `system_settings` (`key`, `value`) VALUES 
('quote_state', '{\"shuffled_indices\": [], \"current_index\": 0, \"last_updated_at\": \"2000-01-01 00:00:00\"}'),
('office_security_token', 'OFFICE_AUTO_GEN');


-- UPDATE LOG: 31/03/2026 - Phân quyền theo Tổ đội (Manager hierarchy)
-- Luôn thêm dòng mới vào cuối file để không ảnh hưởng dữ liệu đang chạy
-- ----------------------------
ALTER TABLE `employees` ADD COLUMN `manager_id` int(11) unsigned DEFAULT NULL COMMENT 'ID của quản lý trực tiếp (ID sếp)' AFTER `department_id`;
ALTER TABLE `employees` ADD CONSTRAINT `fk_emp_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;


-- ----------------------------
-- UPDATE LOG: 31/03/2026 - Thêm vai trò "Thử việc" và "Học việc"
-- ----------------------------
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(6, 'Thử việc', 'Nhân sự đang trong thời gian thử việc.', NOW()),
(7, 'Học việc', 'Nhân sự đang trong thời gian học việc.', NOW());

-- Sao chép toàn bộ quyền của "Thực tập sinh" (ID 5) cho vai trò mới
INSERT INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 6, permission_id FROM roles_permissions WHERE role_id = 5;

INSERT INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT 7, permission_id FROM roles_permissions WHERE role_id = 5;


-- ----------------------------
-- UPDATE LOG: 31/03/2026 - Hệ thống Nhãn dán thông minh (Smart Tagging System)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `tags` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `color` varchar(20) DEFAULT '#6c757d' COMMENT 'Mã màu hiển thị (HEX)',
    `type` enum('global', 'private') DEFAULT 'global',
    `owner_id` int(11) unsigned DEFAULT NULL COMMENT 'ID nhân viên sở hữu nếu là tag cá nhân',
    `module_scope` varchar(50) DEFAULT 'all' COMMENT 'Phạm vi áp dụng (cases, customers, documents, all)',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX (`name`),
    INDEX (`type`),
    CONSTRAINT `fk_tag_owner` FOREIGN KEY (`owner_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Danh mục thẻ phân loại thông minh';

CREATE TABLE IF NOT EXISTS `entity_tags` (
    `tag_id` int(11) unsigned NOT NULL,
    `entity_id` int(11) unsigned NOT NULL COMMENT 'ID của Vụ việc/Khách hàng/Tài liệu...',
    `entity_type` varchar(50) NOT NULL COMMENT 'Phân loại (cases, customers, documents)',
    PRIMARY KEY (`tag_id`, `entity_id`, `entity_type`),
    CONSTRAINT `fk_et_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng liên kết đa hình Nhãn dán';


-- SEED: Các nhãn dán Hệ thống mặc định (Global Tags)
INSERT INTO `tags` (`name`, `color`, `type`, `module_scope`, `created_at`) VALUES
('Quan trọng', '#dc3545', 'global', 'all', NOW()),
('Ưu tiên', '#fd7e14', 'global', 'all', NOW()),
('Đang chờ Tòa', '#007bff', 'global', 'cases', NOW()),
('Dân sự', '#28a745', 'global', 'cases', NOW()),
('Hình sự', '#bd2130', 'global', 'cases', NOW()),
('Ly hôn', '#6f42c1', 'global', 'cases', NOW()),
('VIP', '#ffc107', 'global', 'customers', NOW()),
('Nợ xấu', '#856404', 'global', 'customers', NOW());


SET FOREIGN_KEY_CHECKS = 1;

/* ========================================================================================= */
/* LỆNH CẢI CÁCH HẠ TẦNG DATABASE (MIGRATIONS) - CẬP NHẬT 31-03-2026                         */
/* ========================================================================================= */

-- 1. SỬA LỖI TÊN CỘT NGÀY TẠO (TRỪ KHỬ DẤU HỎI CHẤM TAI HẠI)
ALTER TABLE `workflow_templates` CHANGE `created_at?` `created_at` DATETIME DEFAULT NULL;

-- 2. LOẠI BỎ CỘT LOẠI VỤ VIỆC (DỊ VẬT LỖI THỜI KHÔNG CÒN SỬ DỤNG)
ALTER TABLE `workflow_templates` DROP COLUMN `case_type`;

-- 3. NÂNG CẤP ĐỘ DÀI MÃ ĐỊNH DANH (PHỤC VỤ TÍNH NĂNG NHÂN BẢN QUY TRÌNH HÒAN HÒAN HẢO)
ALTER TABLE `workflow_templates` MODIFY COLUMN `code` VARCHAR(150) NOT NULL;

-- 4. CHO PHÉP VAI TRÒ CHỊU TRÁCH NHIỆM ĐƯỢC ĐỂ TRỐNG (LINH HOẠT HÓA HÀNH CHÍNH)
ALTER TABLE `workflow_template_steps` MODIFY COLUMN `responsible_role` VARCHAR(50) DEFAULT NULL;

-- 5. LOẠI BỎ CỘT LOẠI VỤ VIỆC Ở BẢNG CASES (Hệ thống giờ dùng Quy trình linh hoạt)
ALTER TABLE `cases` DROP COLUMN `type`;

-- 6. THÊM CỘT NGƯỜI TẠO HỒ SƠ KHÁCH HÀNG (PHỤC VỤ BẢO MẬT DỮ LIỆU) - 01/04/2026
ALTER TABLE `customers` ADD COLUMN `created_by` int(11) unsigned DEFAULT NULL AFTER `referred_by`;

-- ----------------------------
-- 23. BẢNG knowledge_base (Cẩm nang tri thức ERP)
-- Nơi lưu trữ tài liệu nghiệp vụ, đúc kết kinh nghiệm và kiến thức nội bộ, phân quyền công khai.
-- ----------------------------
DROP TABLE IF EXISTS `knowledge_base`;
CREATE TABLE `knowledge_base` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính, tự động tăng',
    `case_id` int(11) unsigned DEFAULT NULL COMMENT 'Khóa ngoại móc lỏng tới Vụ việc (Context ID). Nếu NULL là bài viết tự do',
    `author_id` int(11) unsigned NOT NULL COMMENT 'Định danh nhân viên tạo bài (Dùng để tính KPI training)',
    `title` varchar(255) NOT NULL COMMENT 'Tiêu đề trọng tâm của bài phân tích / cẩm nang',
    `content` longtext NOT NULL COMMENT 'Nội dung văn bản mở rộng, lưu trữ chuỗi Rich-Text hoặc HTML',
    `category` enum('case_study', 'skill', 'legal_update', 'general') DEFAULT 'general' COMMENT 'Phân loại để hiển thị các Tab trên News Feed Cẩm nang',
    `view_count` int(11) DEFAULT 0 COMMENT 'Chỉ số đo lường: Số lượt người đã bấm vào xem (Trừ tác giả)',
    `helpful_count` int(11) DEFAULT 0 COMMENT 'Chỉ số tương tác: Số lượt nhân sự trong công ty bấm nút Hữu ích',
    `is_pinned` tinyint(1) DEFAULT 0 COMMENT 'Cờ đánh dấu ghim bài lên đầu trang (1 = Pinned. Dành cho TB quan trọng)',
    `created_at` datetime DEFAULT NULL COMMENT 'Chữ ký thời gian khởi tạo',
    `updated_at` datetime DEFAULT NULL COMMENT 'Chữ ký thời gian thao tác nút Sửa',
    `deleted_at` datetime DEFAULT NULL COMMENT 'Thời gian thùng rác (Soft Delete) - Bảo toàn vĩnh viễn dữ liệu',
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_kb_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kb_author` FOREIGN KEY (`author_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lưu trữ và phân nhóm hệ thống tri thức (Wiki) công ty';

-- ----------------------------
-- 24. BẢNG knowledge_votes (Lịch sử bình chọn Hữu Ích)
-- Cản chặn nhân sự buff vote lố cho 1 bài viết để thao túng xếp hạng thi đua.
-- ----------------------------
DROP TABLE IF EXISTS `knowledge_votes`;
CREATE TABLE `knowledge_votes` (
    `knowledge_id` int(11) unsigned NOT NULL COMMENT 'ID bài viết được nhận bầu chọn',
    `employee_id` int(11) unsigned NOT NULL COMMENT 'Nhân viên thực hiện thả sao Hữu ích',
    `created_at` datetime DEFAULT NULL COMMENT 'Thời gian nhân sự đó thả sao',
    PRIMARY KEY (`knowledge_id`, `employee_id`),
    CONSTRAINT `fk_kv_kb` FOREIGN KEY (`knowledge_id`) REFERENCES `knowledge_base` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kv_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng Pivot ghi nhận lịch sử tương tác 1:1 tránh trùng lặp vote ảo';

-- ----------------------------
-- UPDATE LOG: 06/04/2026 - Module Quản lý Nghỉ phép (Leave Management)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) unsigned NOT NULL,
    `leave_type` enum('annual', 'sick', 'personal', 'unpaid', 'maternity', 'wedding', 'funeral') DEFAULT 'annual',
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `total_days` decimal(5,1) DEFAULT 0.0,
    `reason` text NOT NULL,
    `status` enum('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    `approver_id` int(11) unsigned DEFAULT NULL,
    `approval_note` text DEFAULT NULL,
    `approved_at` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_leave_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_leave_approver` FOREIGN KEY (`approver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- UPDATE LOG: 07/04/2026 - Module Cẩm nang tri thức: Cấu trúc 3 vùng (Problem/Solution/Red Flags)
-- Quy tắc 2: Không sửa CREATE TABLE gốc, chỉ ALTER TABLE tại đây.
-- ----------------------------
ALTER TABLE `knowledge_base`
ADD COLUMN `summary` VARCHAR(500) DEFAULT NULL COMMENT 'Tóm tắt nhanh (Quick Summary) trong 1 câu' AFTER `title`,
ADD COLUMN `problem` TEXT DEFAULT NULL COMMENT 'Phần mô tả Vấn đề - dạng Bullet points' AFTER `summary`,
ADD COLUMN `solution` TEXT DEFAULT NULL COMMENT 'Phần Cách giải quyết chi tiết' AFTER `problem`,
ADD COLUMN `red_flags` TEXT DEFAULT NULL COMMENT 'Phần Lưu ý quan trọng (Red Flags) - cảnh báo rủi ro' AFTER `solution`;

-- Di chuyển dữ liệu cũ vào cột problem để không mất nội dung (Tùy chọn)
UPDATE `knowledge_base` SET `problem` = `content` WHERE `problem` IS NULL AND `content` IS NOT NULL;

ALTER TABLE cases MODIFY COLUMN status VARCHAR(50);
-- 1. Chuyển đổi sang Chờ tiếp nhận
UPDATE cases SET status = 'cho_tiep_nhan' WHERE status IN ('moi_tiep_nhan', 'open', 'pending', '');

-- 2. Chuyển đổi sang Đang xử lý
UPDATE cases SET status = 'dang_xu_ly' WHERE status IN ('in_progress', 'cho_tham_tam', 'dang_xu_ly');

-- 3. Chuyển đổi sang Đã hoàn thành
UPDATE cases SET status = 'da_hoan_thanh' WHERE status IN ('da_giai_quyet', 'dong_ho_so', 'closed', 'da_hoan_thanh');

-- 4. Chuyển đổi sang Hủy
UPDATE cases SET status = 'huy' WHERE status IN ('cancelled', 'huy');


ALTER TABLE leave_requests ADD COLUMN handover_to INT NULL COMMENT 'ID nhân viên nhận bàn giao (Liên kết bảng employees)' AFTER reason;  
ALTER TABLE leave_requests ADD COLUMN handover_content TEXT NULL COMMENT 'Chi tiết các nội dung cần bàn giao' AFTER handover_to;  

-- ----------------------------
-- UPDATE LOG: 13/04/2026 - Mở rộng Module Nghỉ phép (Quản trị)
-- ----------------------------
ALTER TABLE `leave_requests` ADD COLUMN `is_emergency` TINYINT(1) DEFAULT 0 COMMENT 'Trạng thái nghỉ khẩn cấp: 1-Có, 0-Không' AFTER `leave_type`;

ALTER TABLE cases ADD contract_value BIGINT NULL DEFAULT NULL COMMENT 'Giá trị hợp đồng (VND) - Chỉ Hành chính / Admin xem';
ALTER TABLE cases ADD payment_progress TEXT NULL DEFAULT NULL COMMENT 'Ghi chú tiến độ thanh toán';

-- ----------------------------
-- UPDATE LOG: 21/04/2026 - Module Vụ việc: Cơ chế Bàn giao & KPI chi tiết (Handover & KPI Integration)
-- Quy tắc 4 & 5: Đồng bộ hóa cấu trúc để theo dõi KPI chính xác khi có luân chuyển nhân sự.
-- ----------------------------
ALTER TABLE `case_steps` 
ADD COLUMN `assigned_to` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Nhân viên được giao phụ trách bước này (Để tính KPI tiềm năng)' AFTER `case_id`,
ADD COLUMN `completed_by` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Nhân viên thực tế đã hoàn thành/nộp bước này (Để chốt KPI thực nhận)' AFTER `completed_at`;

-- Đồng bộ dữ liệu ban đầu cho các bước cũ để tránh trống báo cáo KPI
UPDATE `case_steps` cs
INNER JOIN `cases` c ON c.id = cs.case_id
SET cs.assigned_to = COALESCE(c.assigned_lawyer_id, c.assigned_staff_id)
WHERE cs.assigned_to IS NULL;

UPDATE `case_steps` cs
INNER JOIN `cases` c ON c.id = cs.case_id
SET cs.completed_by = COALESCE(c.assigned_lawyer_id, c.assigned_staff_id)
WHERE cs.completed_by IS NULL AND cs.status = 'completed';

-- Feature: Nghỉ phép nửa ngày
ALTER TABLE leave_requests ADD COLUMN leave_duration ENUM('full_day', 'morning_half', 'afternoon_half') DEFAULT 'full_day' COMMENT 'Thời lượng nghỉ: Cả ngày (full_day), Sáng (morning_half), Chiều (afternoon_half)' AFTER end_date;

-- UPDATE LOG: 29/04/2026 - Đồng bộ KPI từ bảng case_members (Kiến trúc mới)
-- Đảm bảo dữ liệu KPI được điền đầy đủ khi hệ thống chuyển sang dùng bảng trung gian case_members
UPDATE `case_steps` cs
INNER JOIN (
    SELECT case_id, MIN(employee_id) as emp_id 
    FROM case_members 
    WHERE role_in_case IN ('assignee', 'main') 
    GROUP BY case_id
) cm ON cs.case_id = cm.case_id
SET cs.assigned_to = cm.emp_id
WHERE cs.assigned_to IS NULL;

UPDATE `case_steps` cs
INNER JOIN (
    SELECT case_id, MIN(employee_id) as emp_id 
    FROM case_members 
    WHERE role_in_case IN ('assignee', 'main') 
    GROUP BY case_id
) cm ON cs.case_id = cm.case_id
SET cs.completed_by = cm.emp_id
WHERE cs.completed_by IS NULL AND cs.status = 'completed';

-- CÂU LỆNH NÂNG CẤP DATABASE CHO HỆ THỐNG ERP L.A.N
-- Mục tiêu: Bổ sung trạng thái 'pending' cho bước công việc
ALTER TABLE case_steps MODIFY COLUMN status VARCHAR(30) DEFAULT 'pending';
UPDATE case_steps SET status = 'pending' WHERE status = '' OR status IS NULL;

-- ----------------------------
-- UPDATE LOG: 06/05/2026 - Module Quản lý Lương (Payroll Management)
-- Quy tắc 4 & 5: Đồng bộ hóa cấu trúc bảng lương và cấu hình ngày công.
-- ----------------------------
CREATE TABLE IF NOT EXISTS `payroll_configs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `month` VARCHAR(7) NOT NULL COMMENT 'Tháng tính lương (Định dạng YYYY-MM)',
    `working_days_json` TEXT COMMENT 'Danh sách các ngày đi làm (JSON array)',
    `holidays_json` TEXT COMMENT 'Danh sách các ngày lễ (JSON array: date => lý do)',
    `total_standard_days` FLOAT DEFAULT 0 COMMENT 'Tổng ngày công chuẩn của tháng',
    `is_closed` TINYINT(1) DEFAULT 0 COMMENT 'Cờ hiệu đã chốt sổ lương (1: Đã chốt, 0: Đang mở)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời gian xóa mềm',
    UNIQUE KEY `idx_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình lịch làm việc và ngày công chuẩn hàng tháng';

CREATE TABLE IF NOT EXISTS `payrolls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL COMMENT 'ID nhân viên sở hữu bảng lương',
    `month` VARCHAR(7) NOT NULL COMMENT 'Tháng nhận lương (YYYY-MM)',
    `salary_base` DECIMAL(15,2) DEFAULT 0 COMMENT 'Mức lương cơ bản tại thời điểm chốt',
    `salary_kpi` DECIMAL(15,2) DEFAULT 0 COMMENT 'Mức thưởng KPI thi đua (nhập thủ công)',
    `salary_allowance` DECIMAL(15,2) DEFAULT 0 COMMENT 'Tổng các khoản phụ cấp cố định',
    `salary_bonus` DECIMAL(15,2) DEFAULT 0 COMMENT 'Tiền thưởng thêm ngoài KPI',
    `salary_deduction` DECIMAL(15,2) DEFAULT 0 COMMENT 'Tổng tiền phạt hoặc khấu trừ',
    `total_standard_days` FLOAT DEFAULT 0 COMMENT 'Số ngày công chuẩn của tháng',
    `actual_working_days` FLOAT DEFAULT 0 COMMENT 'Số ngày công thực tế (Chấm công + Nghỉ phép có lương)',
    `attendance_violations` INT DEFAULT 0 COMMENT 'Số lần vi phạm điểm danh (muộn/về sớm)',
    `net_salary` DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương thực lĩnh (Tổng cộng sau thuế/khấu trừ)',
    `status` ENUM('pending', 'approved', 'paid') DEFAULT 'pending' COMMENT 'Trạng thái thanh toán',
    `notes` TEXT COMMENT 'Ghi chú hoặc giải trình chi tiết về lương',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời gian xóa mềm',
    UNIQUE KEY `idx_emp_month` (`employee_id`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lương chi tiết nhân sự hàng tháng';

ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `allowance_base` DECIMAL(15,2) DEFAULT 0 COMMENT 'Mức phụ cấp cố định hàng tháng' AFTER `salary_base`;

-- Cập nhật thêm cột Khác và Ghi chú JSON cho bảng lương
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `salary_other` DECIMAL(15,2) DEFAULT 0 COMMENT 'Khoản điều chỉnh khác (+ hoặc -)' AFTER `salary_deduction`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `notes_json` TEXT COMMENT 'Danh sách ghi chú dạng JSON' AFTER `salary_other`;

-- ----------------------------
-- UPDATE LOG: 06/05/2026 - Tối ưu nhắc nhở quá hạn (Workflow Escalation Enhancement)
-- Quy tắc 4 & 5: Bổ sung cột theo dõi ngày nhắc nhở để tránh spam và hỗ trợ nhắc nhở hàng ngày.
-- ----------------------------
ALTER TABLE `case_steps` ADD COLUMN IF NOT EXISTS `last_overdue_notified_at` DATE NULL DEFAULT NULL COMMENT 'Ngày cuối cùng hệ thống gửi thông báo nhắc nhở quá hạn cho bước này' AFTER `overdue_notified`;

-- ----------------------------
-- UPDATE LOG: 09/05/2026 - Cập nhật cấu trúc bảng lương (Payroll Engine v2)
-- Quy tắc 4 & 5: Đồng bộ hóa cấu trúc chi tiết bảo hiểm, thuế và phụ cấp.
-- ----------------------------
ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `insurance_salary` DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương đóng bảo hiểm' AFTER `salary_base`;
ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `diligence_allowance` DECIMAL(15,2) DEFAULT 0 COMMENT 'Phụ cấp chuyên cần' AFTER `allowance_base`;
ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `petrol_allowance` DECIMAL(15,2) DEFAULT 0 COMMENT 'Phụ cấp xăng xe' AFTER `diligence_allowance`;
ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `dependent_count` INT DEFAULT 0 COMMENT 'Số người phụ thuộc' AFTER `petrol_allowance`;

ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `insurance_salary` DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương đóng bảo hiểm' AFTER `salary_base`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `salary_per_day` DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương 1 ngày công' AFTER `total_standard_days`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `taxable_income` DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương theo ngày công làm việc (TNCT)' AFTER `actual_working_days`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `diligence_allowance` DECIMAL(15,2) DEFAULT 0 COMMENT 'Phụ cấp chuyên cần' AFTER `salary_allowance`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `petrol_allowance` DECIMAL(15,2) DEFAULT 0 COMMENT 'Phụ cấp xăng xe' AFTER `diligence_allowance`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `si_employer` DECIMAL(15,2) DEFAULT 0 COMMENT 'BHXH vào chi phí (21.5%)' AFTER `salary_bonus`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `si_employee` DECIMAL(15,2) DEFAULT 0 COMMENT 'BHXH trừ vào lương (10.5%)' AFTER `si_employer`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `dependent_deduction` DECIMAL(15,2) DEFAULT 0 COMMENT 'Giảm trừ phụ thuộc' AFTER `si_employee`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `pit_tax` DECIMAL(15,2) DEFAULT 0 COMMENT 'Thuế TNCN' AFTER `dependent_deduction`;
ALTER TABLE `payrolls` ADD COLUMN IF NOT EXISTS `total_deductions` DECIMAL(15,2) DEFAULT 0 COMMENT 'Tổng cộng các khoản giảm trừ' AFTER `pit_tax`;

 -- ----------------------------
 -- UPDATE LOG: 11/05/2026 - Tối ưu hóa cấu trúc bảng lương (Payroll Cleanup)
 -- Quy tắc: Gộp các khoản thưởng/phát sinh và xóa bỏ các cột dư thừa không còn sử dụng.
 -- ----------------------------
 -- 1. Di chuyển dữ liệu từ Phát sinh (salary_other) sang Khác (salary_bonus) trước khi xóa
 UPDATE `payrolls` SET `salary_bonus` = `salary_bonus` + IFNULL(`salary_other`, 0);

 -- 2. Xóa bỏ các cột dư thừa
 ALTER TABLE `payrolls` DROP COLUMN IF EXISTS `salary_other`;
 ALTER TABLE `payrolls` DROP COLUMN IF EXISTS `salary_allowance`;
 ALTER TABLE `payrolls` DROP COLUMN IF EXISTS `notes`;

 -- ----------------------------
 CREATE TABLE IF NOT EXISTS `work_schedules` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `employee_id` int(11) unsigned NOT NULL COMMENT 'ID nhân sự sở hữu lịch trình',
     `assigned_by_id` int(11) unsigned DEFAULT NULL COMMENT 'ID người giao việc hoặc người được đi thay',
     `created_by` int(11) unsigned NOT NULL COMMENT 'ID nhân sự tạo bản ghi',
     `type` enum('work', 'business_trip') NOT NULL DEFAULT 'work' COMMENT 'Loại lịch trình: work (Công việc), business_trip (Công tác)',
     `title` varchar(255) NOT NULL COMMENT 'Tiêu đề ngắn gọn của lịch trình',
     `location` varchar(255) DEFAULT NULL COMMENT 'Địa điểm làm việc/công tác',
     `start_at` datetime NOT NULL COMMENT 'Thời gian bắt đầu',
     `end_at` datetime NOT NULL COMMENT 'Thời gian kết thúc',
     `status` enum('pending', 'active', 'completed', 'cancelled') DEFAULT 'active' COMMENT 'Trạng thái lịch trình',
     `created_at` datetime DEFAULT NULL,
     `updated_at` datetime DEFAULT NULL,
     `deleted_at` datetime DEFAULT NULL,
     PRIMARY KEY (`id`),
     CONSTRAINT `fk_ws_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
     CONSTRAINT `fk_ws_creator` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
     CONSTRAINT `fk_ws_assigner` FOREIGN KEY (`assigned_by_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ lịch làm việc và công tác của nhân sự';
