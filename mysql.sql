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
                               `description` text DEFAULT NULL,
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
                         `workflow_template_id` int(11) unsigned DEFAULT NULL,
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
                              `completed_at` datetime DEFAULT NULL,
                              `status` enum('pending', 'active', 'completed', 'overdue') DEFAULT 'pending',
                              `responsible_role` varchar(50) DEFAULT NULL,
                              `kpi_reward` decimal(15,2) DEFAULT 0.00,
                              `overdue_notified` tinyint(1) DEFAULT 0,
                              `sort_order` int(11) DEFAULT 0,
                              `required_documents` text DEFAULT NULL,
                              `next_step_condition` text DEFAULT NULL,
                              `notification_template` text DEFAULT NULL,
                              `created_at` datetime DEFAULT NULL,
                              `updated_at` datetime DEFAULT NULL,
                              `deleted_at` datetime DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              CONSTRAINT `fk_cs_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 14. BẢNG `case_members` (Đội ngũ tương tác vụ việc)
-- ----------------------------
DROP TABLE IF EXISTS `case_members`;
CREATE TABLE `case_members` (
                                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                `case_id` int(11) unsigned NOT NULL,
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
                             `is_encrypted` tinyint(1) DEFAULT 0,
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
                                     `file_path` varchar(255) NOT NULL,
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
                                   `value` text DEFAULT NULL,
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
                               `user_agent` text NOT NULL,
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
                                `action` varchar(100) DEFAULT 'tiep_nhan',
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
ALTER TABLE `workflow_templates` CHANGE `created_at` `created_at` DATETIME DEFAULT NULL;

-- 2. LOẠI BỎ CỘT LOẠI VỤ VIỆC (DỊ VẬT LỖI THỜI KHÔNG CÒN SỬ DỤNG)
ALTER TABLE `workflow_templates` DROP COLUMN `case_type`;

-- 3. NÂNG CẤP ĐỘ DÀI MÃ ĐỊNH DANH (PHỤC VỤ TÍNH NĂNG NHÂN BẢN QUY TRÌNH HOÀN HẢO)
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
                                  `case_id` int(11) unsigned DEFAULT NULL COMMENT 'Khóa ngoại móc nối tới Vụ việc (Context ID). Nếu NULL là bài viết tự do',
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
-- 24. BẢNG knowledge_votes (Lịch sử bình chọn Hữu ích)
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

-- ----------------------------
-- UPDATE LOG: 13/05/2026 - Tái thiết Module Liên hệ (Contact Management)
-- Quy tắc 5: Bảng contacts lưu trữ thông tin liên lạc từ file excel danh sách.
-- ----------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
                            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                            `source` varchar(255) DEFAULT NULL COMMENT 'Nguồn / Tab',
                            `unit_name` varchar(255) NOT NULL COMMENT 'Tên đơn vị / Người phụ trách',
                            `phone` varchar(100) DEFAULT NULL COMMENT 'Số điện thoại',
                            `address` text DEFAULT NULL COMMENT 'Địa chỉ / Cơ quan',
                            `position` varchar(255) DEFAULT NULL COMMENT 'Chức vụ / Chức danh',
                            `area` text DEFAULT NULL COMMENT 'Địa bàn / Phạm vi quản lý',
                            `reorganized_unit` varchar(255) DEFAULT NULL COMMENT 'Đơn vị tổ chức lại / Sau sắp xếp',
                            `notes` text DEFAULT NULL COMMENT 'Lưu ý / Ghi chú',
                            `province` varchar(255) DEFAULT NULL COMMENT 'Tỉnh / Khu vực',
                            `is_private` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Cờ Private (1: Chỉ Admin xem SĐT và sửa, 0: Mọi người)',
                            `created_by` int(11) unsigned DEFAULT NULL COMMENT 'ID người tạo',
                            `created_at` datetime DEFAULT NULL,
                            `updated_at` datetime DEFAULT NULL,
                            `deleted_at` datetime DEFAULT NULL COMMENT 'Xóa mềm',
                            PRIMARY KEY (`id`),
                            INDEX (`unit_name`),
                            INDEX (`phone`),
                            INDEX (`province`),
                            CONSTRAINT `fk_contact_creator` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng danh bạ liên hệ thông minh';

-- ----------------------------
-- UPDATE LOG: 14/05/2026 - Tích hợp Zalo OA (Zalo OA Integration)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `zalo_followers` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `zalo_id` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Mã định danh người dùng Zalo',
    `display_name` VARCHAR(255) NOT NULL,
    `avatar_url` TEXT DEFAULT NULL,
    `phone_number` VARCHAR(20) DEFAULT NULL,
    `mid_code` VARCHAR(50) DEFAULT NULL,
    `customer_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Liên kết với bảng customers',
    `tags` TEXT DEFAULT NULL COMMENT 'Phân loại tệp khách hàng Zalo',
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_zalo_follower_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lưu trữ thông tin người quan tâm Zalo OA (Followers)';

CREATE TABLE IF NOT EXISTS `zalo_messages` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `zalo_msg_id` VARCHAR(255) DEFAULT NULL UNIQUE COMMENT 'ID tin nhắn từ Zalo',
    `follower_id` INT(11) UNSIGNED NOT NULL COMMENT 'Liên kết với bảng zalo_followers',
    `sender_type` ENUM('user', 'oa') NOT NULL DEFAULT 'user' COMMENT 'Người gửi: user (khách hàng), oa (nhân sự)',
    `message_text` TEXT NOT NULL COMMENT 'Nội dung tin nhắn',
    `attachments` TEXT DEFAULT NULL COMMENT 'Đính kèm (link ảnh, file)',
    `created_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_zalo_msg_follower` FOREIGN KEY (`follower_id`) REFERENCES `zalo_followers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lưu trữ lịch sử tin nhắn Zalo vĩnh viễn';

CREATE TABLE IF NOT EXISTS `zalo_campaigns` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `template_id` VARCHAR(100) NOT NULL COMMENT 'ID mẫu ZNS',
    `target_tags` TEXT DEFAULT NULL COMMENT 'Tags khách hàng mục tiêu',
    `status` ENUM('draft', 'running', 'completed', 'cancelled') DEFAULT 'draft',
    `sent_count` INT DEFAULT 0,
    `success_count` INT DEFAULT 0,
    `created_by` INT(11) UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_zalo_camp_creator` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chiến dịch gửi ZNS (Tiếp thị lại)';

CREATE TABLE IF NOT EXISTS `zalo_surveys` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `follower_id` INT(11) UNSIGNED NOT NULL,
    `employee_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Nhân sự được đánh giá',
    `case_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Vụ việc liên quan',
    `rating` INT DEFAULT NULL COMMENT 'Đánh giá 1-5 sao',
    `feedback` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'completed') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_zalo_survey_follower` FOREIGN KEY (`follower_id`) REFERENCES `zalo_followers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_zalo_survey_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_zalo_survey_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Khảo sát chất lượng tư vấn (Quản lý hiệu suất)';

-- ----------------------------
-- UPDATE LOG: 15/05/2026 - Nâng cấp Zalo OA: Phân quyền & Trạng thái đọc (Permissions & Read Status)
-- ----------------------------
ALTER TABLE `zalo_followers` ADD COLUMN `assigned_to` int(11) unsigned DEFAULT NULL AFTER `customer_id`;
ALTER TABLE `zalo_followers` ADD CONSTRAINT `fk_zalo_follower_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `zalo_messages` ADD COLUMN `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Chưa đọc, 1: Đã đọc' AFTER `attachments`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------
-- 25. BẢNG zalo_quick_replies (Câu trả lời nhanh)
-- Nhóm quản lý các mẫu câu hỗ trợ khách hàng nhanh chóng cho module Tư vấn khách hàng (Zalo/Messenger).
-- Tuân thủ Rule #5 (Comments) & Rule #6 (Soft Delete).
-- ----------------------------
CREATE TABLE IF NOT EXISTS `zalo_quick_replies` (
                                                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL COMMENT 'Tiêu đề mẫu gợi nhớ',
    `content` TEXT NOT NULL COMMENT 'Nội dung câu trả lời thực tế',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Thời gian tạo mẫu',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Thời gian cập nhật',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời gian xóa mềm (Soft Delete)'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hệ thống mẫu phản hồi nhanh cho tư vấn viên';

-- ----------------------------
-- UPDATE LOG: 16/05/2026 - Module Tích hợp Facebook Messenger (Omni-Channel Communication)
-- Thiết kế tương đồng với Zalo OA (zalo_followers / zalo_messages) để đồng nhất kiến trúc.
-- Rule #4: Viết bên dưới, không sửa CREATE TABLE gốc.
-- Rule #5: Gắn COMMENT tiếng Việt đầy đủ cho mỗi cột.
-- ----------------------------

CREATE TABLE IF NOT EXISTS `messenger_contacts` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `psid` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Page-Scoped ID: Định danh duy nhất của người dùng Facebook với Page này',
    `display_name` VARCHAR(255) NOT NULL DEFAULT 'Khách Facebook' COMMENT 'Tên hiển thị lấy từ Facebook Graph API',
    `avatar_url` TEXT DEFAULT NULL COMMENT 'URL ảnh đại diện người dùng',
    `phone_number` VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại (nếu người dùng cung cấp)',
    `mid_code` VARCHAR(50) DEFAULT NULL COMMENT 'Mã định danh nội bộ hệ thống ERP (VD: FB-A1B2C3)',
    `customer_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Khóa ngoại liên kết bảng customers nếu đã đồng bộ CRM',
    `assigned_to` INT(11) UNSIGNED DEFAULT NULL COMMENT 'user_id của nhân sự phụ trách hội thoại này',
    `tags` TEXT DEFAULT NULL COMMENT 'Mảng JSON các nhãn phân loại (VD: ["Tiềm năng","Khiếu nại"])',
    `locale` VARCHAR(20) DEFAULT 'vi_VN' COMMENT 'Ngôn ngữ người dùng (VD: vi_VN, en_US)',
    `timezone` TINYINT DEFAULT 7 COMMENT 'Múi giờ UTC+N của người dùng',
    `page_id` VARCHAR(100) DEFAULT NULL COMMENT 'Facebook Page ID nhận tin nhắn (hỗ trợ multi-page)',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Thời gian khởi tạo bản ghi',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Thời gian tương tác gần nhất (dùng để sort danh sách)',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời gian xóa mềm (Soft Delete)',
    INDEX `idx_psid` (`psid`),
    INDEX `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách người dùng tương tác qua Facebook Messenger (tương đương zalo_followers)';

CREATE TABLE IF NOT EXISTS `messenger_messages` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `contact_id` INT(11) UNSIGNED NOT NULL COMMENT 'Khóa ngoại tới messenger_contacts.id',
    `fb_msg_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID tin nhắn Facebook (dùng chống trùng lặp webhook)',
    `sender_type` ENUM('user','page') NOT NULL DEFAULT 'user' COMMENT 'Chiều tin nhắn: user=KH gửi vào, page=ERP gửi ra',
    `message_text` TEXT DEFAULT NULL COMMENT 'Nội dung văn bản của tin nhắn',
    `attachments` TEXT DEFAULT NULL COMMENT 'JSON các đính kèm: [{type: image|file|video, payload: {...}}]',
    `is_read` TINYINT(1) DEFAULT 0 COMMENT '0=Chưa đọc, 1=Đã đọc (Dùng để tính badge unread)',
    `mid_staff_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'user_id nhân sự gửi tin (khi sender_type=page)',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Thời gian tin nhắn',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Thời gian cập nhật bản ghi',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời gian xóa mềm (Soft Delete)',
    INDEX `idx_contact` (`contact_id`),
    UNIQUE KEY `idx_fb_msg_id` (`fb_msg_id`),
    CONSTRAINT `fk_msn_msg_contact` FOREIGN KEY (`contact_id`) REFERENCES `messenger_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử tin nhắn Facebook Messenger (tương đương zalo_messages)';

-- Seed cấu hình Messenger vào system_settings (Giá trị rỗng, admin tự nhập qua /messenger/config)
INSERT IGNORE INTO `system_settings` (`key`, `value`) VALUES
    ('messenger_page_access_token', ''),
    ('messenger_app_id', ''),
    ('messenger_app_secret', ''),
    ('messenger_verify_token', 'lan_erp_messenger_verify_2026');


-- ----------------------------
-- UPDATE LOG: 19/05/2026 - Module Khách hàng: Bổ dung nhân sự phụ trách chăm sóc tư vấn
-- Rule #4: Viết bên dưới, không sửa CREATE TABLE gốc.
-- Rule #5: Gắn COMMENT tiếng Việt đầy đủ cho mỗi cột.
-- ----------------------------
ALTER TABLE `customers` ADD COLUMN `assigned_care_staff_id` int(11) unsigned DEFAULT NULL COMMENT 'ID nhân sự phụ trách chăm sóc tư vấn (Liên kết bảng employees)' AFTER `created_by`;
ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_care_staff` FOREIGN KEY (`assigned_care_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

-- ----------------------------
-- UPDATE LOG: 21/05/2026 - Tích hợp Phân loại & Phân công Chat thông minh (Giai đoạn 2 & 3)
-- Rule #4: Viết bên dưới, không sửa CREATE TABLE gốc.
-- Rule #5: Gắn COMMENT tiếng Việt đầy đủ cho mỗi cột.
-- ----------------------------

ALTER TABLE `zalo_followers` 
    ADD COLUMN `email` VARCHAR(255) DEFAULT NULL COMMENT 'Địa chỉ email khách hàng cung cấp qua chat' AFTER `phone_number`,
    ADD COLUMN `lead_warmth` ENUM('hot', 'warm', 'cold') NOT NULL DEFAULT 'cold' COMMENT 'Độ nóng của lead: hot (Nóng), warm (Ấm), cold (Lạnh)' AFTER `tags`,
    ADD COLUMN `is_duplicate` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Cờ báo trùng lặp (1: Trùng lặp, 0: Bình thường)' AFTER `lead_warmth`,
    ADD COLUMN `duplicate_of` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID liên hệ chính trong zalo_followers bị trùng' AFTER `is_duplicate`,
    ADD COLUMN `assigned_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm phân công nhân sự gần nhất' AFTER `assigned_to`,
    ADD COLUMN `first_response_deadline` DATETIME DEFAULT NULL COMMENT 'Hạn chót để phản hồi khách hàng lần đầu (2 tiếng)' AFTER `assigned_at`,
    ADD COLUMN `first_responded_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm phản hồi thực tế lần đầu tiên' AFTER `first_response_deadline`,
    ADD COLUMN `is_overdue` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Cờ đánh dấu quá hạn phản hồi (1: Quá hạn, 0: Đúng hạn)' AFTER `first_responded_at`,
    ADD COLUMN `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời gian xóa mềm (Soft Delete)' AFTER `updated_at`,
    ADD CONSTRAINT `fk_zalo_follower_dup` FOREIGN KEY (`duplicate_of`) REFERENCES `zalo_followers` (`id`) ON DELETE SET NULL;

ALTER TABLE `messenger_contacts` 
    ADD COLUMN `email` VARCHAR(255) DEFAULT NULL COMMENT 'Địa chỉ email khách hàng cung cấp qua chat' AFTER `phone_number`,
    ADD COLUMN `lead_warmth` ENUM('hot', 'warm', 'cold') NOT NULL DEFAULT 'cold' COMMENT 'Độ nóng của lead: hot (Nóng), warm (Ấm), cold (Lạnh)' AFTER `tags`,
    ADD COLUMN `is_duplicate` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Cờ báo trùng lặp (1: Trùng lặp, 0: Bình thường)' AFTER `lead_warmth`,
    ADD COLUMN `duplicate_of` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID liên hệ chính trong messenger_contacts bị trùng' AFTER `is_duplicate`,
    ADD COLUMN `assigned_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm phân công nhân sự gần nhất' AFTER `assigned_to`,
    ADD COLUMN `first_response_deadline` DATETIME DEFAULT NULL COMMENT 'Hạn chót để phản hồi khách hàng lần đầu (2 tiếng)' AFTER `assigned_at`,
    ADD COLUMN `first_responded_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm phản hồi thực tế lần đầu tiên' AFTER `first_response_deadline`,
    ADD COLUMN `is_overdue` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Cờ đánh dấu quá hạn phản hồi (1: Quá hạn, 0: Đúng hạn)' AFTER `first_responded_at`,
    ADD CONSTRAINT `fk_messenger_contact_dup` FOREIGN KEY (`duplicate_of`) REFERENCES `messenger_contacts` (`id`) ON DELETE SET NULL;

ALTER TABLE `employees`
    ADD COLUMN `specialties` VARCHAR(255) DEFAULT NULL COMMENT 'JSON array các lĩnh vực chuyên môn (VD: ["Đất đai","Ly hôn"])' AFTER `position`,
    ADD COLUMN `max_workload` INT(11) NOT NULL DEFAULT 15 COMMENT 'Giới hạn số lead tối đa nhân sự được nhận đồng thời' AFTER `specialties`;

-- ----------------------------
-- UPDATE LOG: 2026-05-22 - Bổ sung tính năng Ongoing SLA cho Chat
-- Rule #4: Viết bên dưới, không sửa CREATE TABLE gốc.
-- Rule #5: Gắn COMMENT tiếng Việt đầy đủ cho mỗi cột.
-- ----------------------------
ALTER TABLE `zalo_followers` 
ADD COLUMN `ongoing_response_deadline` DATETIME DEFAULT NULL COMMENT 'Hạn chót để phản hồi tin nhắn mới nhất của khách trong quá trình trao đổi' AFTER `first_responded_at`,
ADD COLUMN `last_customer_msg_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm khách hàng gửi tin nhắn cuối cùng' AFTER `ongoing_response_deadline`,
ADD COLUMN `ongoing_is_overdue` TINYINT(1) DEFAULT 0 COMMENT 'Cờ đánh dấu vi phạm SLA trao đổi kế tiếp' AFTER `last_customer_msg_at`;

ALTER TABLE `messenger_contacts` 
ADD COLUMN `ongoing_response_deadline` DATETIME DEFAULT NULL COMMENT 'Hạn chót để phản hồi tin nhắn mới nhất của khách trong quá trình trao đổi' AFTER `first_responded_at`,
ADD COLUMN `last_customer_msg_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm khách hàng gửi tin nhắn cuối cùng' AFTER `ongoing_response_deadline`,
ADD COLUMN `ongoing_is_overdue` TINYINT(1) DEFAULT 0 COMMENT 'Cờ đánh dấu vi phạm SLA trao đổi kế tiếp' AFTER `last_customer_msg_at`;

INSERT INTO `system_settings` (`key`, `value`, `updated_at`) VALUES ('ongoing_sla_hours', '2', NOW()) ON DUPLICATE KEY UPDATE `value`='2', `updated_at`=NOW();

-- ----------------------------
-- UPDATE LOG: 2026-05-22 - Module Chăm Sóc Khách Hàng (CSKH) - Phase 1
-- Rule #4: Viết bên dưới, không sửa CREATE TABLE gốc.
-- Rule #5: Gắn COMMENT tiếng Việt đầy đủ cho mỗi cột.
-- ----------------------------

ALTER TABLE `customers` 
    ADD COLUMN `customer_segment` VARCHAR(50) DEFAULT NULL COMMENT 'Phân nhóm khách hàng A/B/C: vip (VIP - Nhóm A), regular (Phổ thông - Nhóm B), potential (Tiềm năng - Nhóm C)' AFTER `assigned_care_staff_id`,
    ADD COLUMN `zalo_phone` VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại Zalo riêng (nếu khác SĐT chính)' AFTER `customer_segment`,
    ADD COLUMN `occupation` VARCHAR(255) DEFAULT NULL COMMENT 'Nghề nghiệp/Lĩnh vực hoạt động' AFTER `zalo_phone`,
    ADD COLUMN `care_status` VARCHAR(50) NOT NULL DEFAULT 'new' COMMENT 'Trạng thái CSKH: new (Mới), phase1 (Giai đoạn 1), phase2 (Giai đoạn 2), phase3 (Giai đoạn 3), completed (Đã hoàn thành chăm sóc), dormant (Bỏ quên/cần kích hoạt lại)' AFTER `occupation`,
    ADD COLUMN `service_completed_date` DATE DEFAULT NULL COMMENT 'Ngày hoàn thành dịch vụ/hợp đồng gần nhất' AFTER `care_status`,
    ADD COLUMN `referral_count` INT(11) DEFAULT 0 COMMENT 'Số lần khách giới thiệu người khác' AFTER `service_completed_date`;

CREATE TABLE IF NOT EXISTS `customer_care_plans` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `customer_id` INT(11) UNSIGNED NOT NULL COMMENT 'Khóa ngoại liên kết bảng customers',
    `phase` VARCHAR(50) NOT NULL COMMENT 'Giai đoạn CSKH: phase1 (Giai đoạn 1), phase2 (Giai đoạn 2), phase3 (Giai đoạn 3)',
    `title` VARCHAR(255) NOT NULL COMMENT 'Tiêu đề kế hoạch chăm sóc',
    `description` TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết kế hoạch',
    `assigned_staff_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Nhân sự chịu trách nhiệm chăm sóc (Liên kết employees)',
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái: pending (Chờ), in_progress (Đang làm), completed (Hoàn thành), skipped (Bỏ qua)',
    `due_date` DATE DEFAULT NULL COMMENT 'Hạn chót hoàn thành kế hoạch',
    `completed_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm hoàn thành thực tế',
    `result_notes` TEXT DEFAULT NULL COMMENT 'Kết quả hoặc ghi chú thu thập được từ khách',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm tạo bản ghi',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm cập nhật bản ghi',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm xóa mềm (Soft Delete)',
    CONSTRAINT `fk_care_plan_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_care_plan_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kế hoạch chăm sóc khách hàng cũ theo từng giai đoạn';

CREATE TABLE IF NOT EXISTS `customer_care_tasks` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `care_plan_id` INT(11) UNSIGNED NOT NULL COMMENT 'Khóa ngoại liên kết customer_care_plans',
    `customer_id` INT(11) UNSIGNED NOT NULL COMMENT 'Khóa ngoại liên kết customers để query nhanh',
    `task_type` VARCHAR(50) NOT NULL COMMENT 'Loại công việc: thank_you, feedback, follow_up, gift, content, call, etc.',
    `title` VARCHAR(255) NOT NULL COMMENT 'Tiêu đề công việc CSKH',
    `description` TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết công việc',
    `channel` VARCHAR(50) DEFAULT NULL COMMENT 'Kênh tương tác: zalo, email, call, meeting, letter',
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Trạng thái hoàn thành: 0 (Chưa), 1 (Đã xong)',
    `completed_by` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Nhân sự thực hiện công việc (Liên kết employees)',
    `completed_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm hoàn thành thực tế',
    `due_date` DATE DEFAULT NULL COMMENT 'Hạn chót hoàn thành công việc',
    `sort_order` INT(11) DEFAULT 0 COMMENT 'Thứ tự sắp xếp hiển thị',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm tạo bản ghi',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm cập nhật bản ghi',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm xóa mềm (Soft Delete)',
    CONSTRAINT `fk_care_task_plan` FOREIGN KEY (`care_plan_id`) REFERENCES `customer_care_plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_care_task_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_care_task_staff` FOREIGN KEY (`completed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Checklist công việc CSKH chi tiết trong từng kế hoạch';

CREATE TABLE IF NOT EXISTS `customer_loyalty` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `customer_id` INT(11) UNSIGNED NOT NULL COMMENT 'Khóa ngoại liên kết customers',
    `loyalty_tier` VARCHAR(50) NOT NULL DEFAULT 'standard' COMMENT 'Hạng thành viên: standard (Tiêu chuẩn), silver (Bạc), gold (Vàng), vip (VIP)',
    `benefits` TEXT DEFAULT NULL COMMENT 'Quyền lợi được áp dụng (Định dạng JSON)',
    `points` INT(11) DEFAULT 0 COMMENT 'Điểm tích lũy',
    `referral_code` VARCHAR(20) DEFAULT NULL COMMENT 'Mã giới thiệu duy nhất của khách hàng',
    `total_referrals` INT(11) DEFAULT 0 COMMENT 'Tổng số lượng khách giới thiệu thành công',
    `notes` TEXT DEFAULT NULL COMMENT 'Ghi chú thêm về loyalty/VIP',
    `activated_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm kích hoạt thẻ/hạng',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm tạo bản ghi',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm cập nhật bản ghi',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm xóa mềm (Soft Delete)',
    UNIQUE KEY `idx_referral_code` (`referral_code`),
    CONSTRAINT `fk_loyalty_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chương trình khách hàng thân thiết và VIP';

-- ----------------------------
-- UPDATE LOG: 25/05/2026 - Hệ thống Trạng thái & SLA CSKH Cấu hình Động (Dynamic SLA & Status Config)
-- Quy tắc 4 & 5: Đồng bộ hóa kép cấu hình trạng thái và nhật ký theo dõi SLA cho module Khách hàng.
-- ----------------------------
CREATE TABLE IF NOT EXISTS `customer_sla_settings` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `status_key` VARCHAR(50) NOT NULL COMMENT 'Khóa định danh trạng thái',
    `status_name` VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị trạng thái',
    `sla_hours` INT(11) NOT NULL DEFAULT 0 COMMENT 'Thời gian xử lý SLA (giờ), 0 là không giới hạn',
    `color` VARCHAR(20) NOT NULL DEFAULT '#6c757d' COMMENT 'Màu sắc đại diện trạng thái (Hex hoặc CSS)',
    `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hoạt động (1: Bật, 0: Tắt)',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Ngày tạo',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Ngày cập nhật',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Ngày xóa mềm',
    UNIQUE KEY `idx_status_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình trạng thái tư vấn và thời hạn SLA động cho khách hàng';

CREATE TABLE IF NOT EXISTS `customer_sla_history` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `customer_id` INT(11) UNSIGNED NOT NULL COMMENT 'Khóa ngoại liên kết bảng customers',
    `assigned_staff_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Nhân viên phụ trách tại thời điểm này (Liên kết employees)',
    `status` VARCHAR(50) NOT NULL COMMENT 'Trạng thái tư vấn',
    `start_time` DATETIME NOT NULL COMMENT 'Thời điểm bắt đầu trạng thái',
    `end_time` DATETIME DEFAULT NULL COMMENT 'Thời điểm kết thúc trạng thái',
    `sla_duration` INT(11) NOT NULL DEFAULT 0 COMMENT 'Thời hạn SLA được áp dụng (giờ)',
    `due_time` DATETIME DEFAULT NULL COMMENT 'Thời gian hạn chót hoàn thành',
    `sla_status` VARCHAR(30) NOT NULL DEFAULT 'in_progress' COMMENT 'Trạng thái SLA: in_progress, achieved, overdue, completed_late',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Ngày tạo',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Ngày cập nhật',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Ngày xóa mềm',
    CONSTRAINT `fk_csh_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_csh_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký lịch sử tiến độ và trạng thái SLA chăm sóc khách hàng';

INSERT IGNORE INTO `customer_sla_settings` (`status_key`, `status_name`, `sla_hours`, `color`, `sort_order`, `created_at`, `updated_at`) VALUES
('chua_tu_van', 'Chưa được tư vấn', 24, '#6c757d', 1, NOW(), NOW()),
('dang_tu_van', 'Đang tư vấn', 48, '#0071e3', 2, NOW(), NOW()),
('doi_ho_so', 'Đợi khách gửi hồ sơ', 120, '#ff9500', 3, NOW(), NOW()),
('nghien_cuu_bao_phi', 'Đang nghiên cứu để báo phí', 48, '#af52de', 4, NOW(), NOW()),
('thuong_luong', 'Đang thương lượng', 72, '#5856d6', 5, NOW(), NOW()),
('chot_hop_dong', 'Đã chốt hợp đồng', 0, '#34c759', 6, NOW(), NOW()),
('tam_dung', 'Tạm dừng chăm sóc', 0, '#8e8e93', 7, NOW(), NOW()),
('khong_tiem_nang', 'Không tiềm năng / Hủy', 0, '#ff3b30', 8, NOW(), NOW());

-- ============================================================
-- ZALO ZNS (Zalo Notification Service) - Bảng dữ liệu
-- Ngày tạo: 2026-05-26
-- Mô tả: Lưu trữ mẫu tin, chiến dịch và log gửi thông báo ZNS
-- ============================================================

CREATE TABLE IF NOT EXISTS `zns_templates` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` VARCHAR(100) NOT NULL COMMENT 'ID mẫu tin từ hệ thống Zalo Business',
    `template_name` VARCHAR(255) NOT NULL COMMENT 'Tên mẫu tin hiển thị trong ERP',
    `template_content` TEXT NULL COMMENT 'Nội dung mẫu tin (preview)',
    `template_params` JSON NULL COMMENT 'Danh sách các biến trong mẫu tin (JSON array)',
    `default_mappings` JSON NULL COMMENT 'Cấu hình ánh xạ mặc định do Admin thiết lập giữa tham số ZNS và trường dữ liệu ERP',
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Trạng thái: active=đang sử dụng, inactive=tạm ngưng',
    `created_by` INT(11) UNSIGNED NULL COMMENT 'ID nhân sự tạo template',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL COMMENT 'Xóa mềm (Soft Delete)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng mẫu tin ZNS - Lưu trữ các template thông báo Zalo đã đăng ký';

CREATE TABLE IF NOT EXISTS `zns_campaigns` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Tên chiến dịch',
    `description` TEXT NULL COMMENT 'Mô tả chi tiết chiến dịch',
    `zns_template_id` INT(11) UNSIGNED NOT NULL COMMENT 'FK tới zns_templates.id',
    `template_data_mapping` JSON NULL COMMENT 'Mapping giữa biến template và trường dữ liệu KH (JSON)',
    `filter_criteria` JSON NULL COMMENT 'Bộ lọc KH mục tiêu (tag, status, segment...)',
    `customer_ids` JSON NULL COMMENT 'Danh sách ID KH được chọn thủ công',
    `status` ENUM('draft','sending','completed','failed','cancelled') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái chiến dịch',
    `total_recipients` INT(11) NOT NULL DEFAULT 0 COMMENT 'Tổng số người nhận',
    `sent_count` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tin đã gửi',
    `success_count` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tin gửi thành công',
    `fail_count` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tin gửi thất bại',
    `created_by` INT(11) UNSIGNED NULL COMMENT 'ID nhân sự tạo chiến dịch',
    `started_at` DATETIME NULL COMMENT 'Thời điểm bắt đầu gửi',
    `completed_at` DATETIME NULL COMMENT 'Thời điểm hoàn thành',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL COMMENT 'Xóa mềm (Soft Delete)',
    PRIMARY KEY (`id`),
    KEY `zns_template_id` (`zns_template_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng chiến dịch ZNS - Quản lý gửi thông báo hàng loạt tới KH';

CREATE TABLE IF NOT EXISTS `zns_logs` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` INT(11) UNSIGNED NULL COMMENT 'FK tới zns_campaigns.id (NULL nếu gửi đơn lẻ)',
    `customer_id` INT(11) UNSIGNED NULL COMMENT 'FK tới customers.id',
    `template_id` VARCHAR(100) NOT NULL COMMENT 'ID mẫu tin Zalo',
    `phone` VARCHAR(20) NOT NULL COMMENT 'SĐT người nhận (format 84xxx)',
    `template_data` JSON NULL COMMENT 'Dữ liệu đã gửi vào template (JSON)',
    `status` ENUM('pending','sent','delivered','failed') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái gửi',
    `zalo_msg_id` VARCHAR(100) NULL COMMENT 'ID tin nhắn từ Zalo trả về',
    `error_code` INT(11) NULL COMMENT 'Mã lỗi từ Zalo API',
    `error_message` TEXT NULL COMMENT 'Nội dung lỗi chi tiết',
    `sent_by` INT(11) UNSIGNED NULL COMMENT 'ID nhân sự thực hiện gửi',
    `sent_at` DATETIME NULL COMMENT 'Thời điểm gửi thực tế',
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `campaign_id` (`campaign_id`),
    KEY `customer_id` (`customer_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng log ZNS - Ghi nhận chi tiết từng tin nhắn ZNS đã gửi';

-- ============================================================
-- HƯỚNG DẪN CẬP NHẬT CƠ SỞ DỮ LIỆU THỦ CÔNG (Nếu không chạy được /run-migrations)
-- Vui lòng chạy câu lệnh dưới đây trong phpMyAdmin hoặc cổng truy vấn MySQL để thêm cột:
--
-- ALTER TABLE `zns_templates` ADD COLUMN `default_mappings` JSON NULL COMMENT 'Cấu hình ánh xạ mặc định do Admin thiết lập' AFTER `template_params`;
-- ============================================================


-- ----------------------------
-- UPDATE LOG: 01/06/2026 - Nâng cấp Nghiệp vụ Bảng lương (Hệ số lương thử việc + Chuyển hạng giữa tháng + Truy lĩnh tự động + Ngày công bù)
-- Quy tắc 4: Chỉ thêm ALTER TABLE cuối file, KHÔNG sửa CREATE TABLE gốc.
-- ----------------------------

-- Bảng EMPLOYEES: Hệ số lương và thông tin chuyển hạng giữa kỳ
ALTER TABLE `employees`
    ADD COLUMN IF NOT EXISTS `probation_rate` DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Hệ số lương hiện tại (% lương cơ bản): 85=Thử việc, 40=Thực tập, 60=Học việc, 100=Chính thức' AFTER `allowance_base`,
    ADD COLUMN IF NOT EXISTS `probation_end_date` DATE NULL DEFAULT NULL COMMENT 'Ngày kết thúc giai đoạn thử việc/thực tập. NULL = không chuyển hạng trong kỳ tính' AFTER `probation_rate`,
    ADD COLUMN IF NOT EXISTS `new_rate_after` DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Hệ số % lương áp dụng SAU ngày probation_end_date (thường là 100 khi chuyển sang chính thức)' AFTER `probation_end_date`;

-- Bảng PAYROLLS: Ngày công bù thủ công, snapshot hệ số lương và cột khoản khác/truy lĩnh
ALTER TABLE `payrolls`
    ADD COLUMN IF NOT EXISTS `manual_adjust_days` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Số ngày công cộng thêm thủ công (Admin bù delay chấm công nhân viên mới)' AFTER `actual_working_days`,
    ADD COLUMN IF NOT EXISTS `probation_rate_snapshot` DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Snapshot hệ số % lương tại thời điểm tính lương (phục vụ tra cứu lịch sử)' AFTER `manual_adjust_days`,
    ADD COLUMN IF NOT EXISTS `salary_other` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Khoản điều chỉnh khác / truy lĩnh tự động' AFTER `salary_bonus`;

-- ----------------------------
-- UPDATE LOG: 03/06/2026 - KPI tư vấn theo giá trị hợp đồng đã chốt
-- Mốc KPI: 150.000.000 VNĐ giá trị hợp đồng/tháng tương ứng thưởng 5.000.000 VNĐ.
-- ----------------------------
ALTER TABLE `cases`
    ADD COLUMN IF NOT EXISTS `consultant_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Nhân sự tư vấn đã chốt khách để tính KPI tư vấn' AFTER `assigned_staff_id`,
    ADD COLUMN IF NOT EXISTS `consultation_closed_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm ghi nhận hồ sơ được tư vấn chốt thành công' AFTER `consultant_id`;


-- ----------------------------
-- UPDATE LOG: 17/06/2026 - Đăng ký xe trong lịch trình công việc
-- Quy tắc 4: Chỉ thêm ALTER TABLE cuối file, KHÔNG sửa CREATE TABLE gốc.
-- ----------------------------
ALTER TABLE `work_schedules`
    ADD COLUMN IF NOT EXISTS `requires_vehicle` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 nếu lịch trình có đăng ký sử dụng xe công ty' AFTER `location`;
-- ----------------------------
-- UPDATE LOG: 03/07/2026 - Trang thai qua tang cho khach hang
-- Quy tac 4: Chi them ALTER TABLE cuoi file, KHONG sua CREATE TABLE goc.
-- ----------------------------
ALTER TABLE `customers`
    ADD COLUMN IF NOT EXISTS `has_received_gift` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Trang thai qua tang cua khach hang: 0 chua tang, 1 da tang' AFTER `care_status`;

-- ----------------------------
-- UPDATE LOG: 09/07/2026 - Deadline cuối ngày và ghi nhận KPI ngoại lệ cho step vụ việc
-- Quy tac 4: Chi them ALTER TABLE/UPDATE cuoi file, KHONG sua CREATE TABLE goc.
-- ----------------------------
ALTER TABLE `case_steps`
    ADD COLUMN IF NOT EXISTS `kpi_override_approved` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Quản lý ghi nhận KPI dù step hoàn thành sau hạn' AFTER `kpi_reward`,
    ADD COLUMN IF NOT EXISTS `kpi_override_reason` TEXT NULL COMMENT 'Lý do chấp thuận KPI ngoại lệ' AFTER `kpi_override_approved`,
    ADD COLUMN IF NOT EXISTS `kpi_override_by` INT(11) UNSIGNED NULL COMMENT 'Nhân sự quản lý đã chấp thuận KPI ngoại lệ' AFTER `kpi_override_reason`,
    ADD COLUMN IF NOT EXISTS `kpi_override_at` DATETIME NULL COMMENT 'Thời điểm chấp thuận KPI ngoại lệ' AFTER `kpi_override_by`;

UPDATE `case_steps`
SET `deadline` = CONCAT(DATE(`deadline`), ' 23:59:59')
WHERE `deadline` IS NOT NULL;

UPDATE `cases`
SET `deadline` = CONCAT(DATE(`deadline`), ' 23:59:59')
WHERE `deadline` IS NOT NULL;

-- --------------------------------------------------------
-- Update 20/07/2026: Bang tep vat ly cua tai lieu DMS nhieu file
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_files` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh cua tep vat ly trong tai lieu DMS',
  `document_id` INT(11) UNSIGNED NOT NULL COMMENT 'Tai lieu cha trong bang documents',
  `original_name` VARCHAR(255) NOT NULL COMMENT 'Ten tep goc nguoi dung tai len',
  `file_path` VARCHAR(255) NOT NULL COMMENT 'Duong dan tep trong WRITEPATH uploads/dms',
  `file_type` VARCHAR(20) NULL COMMENT 'Phan mo rong tep',
  `mime_type` VARCHAR(150) NULL COMMENT 'MIME type cua tep',
  `size` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'Dung luong tep tinh bang byte',
  `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Thu tu hien thi tep trong mot tai lieu',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem tep',
  PRIMARY KEY (`id`),
  KEY `idx_document_files_document_id` (`document_id`),
  CONSTRAINT `fk_document_files_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Danh sach tep vat ly thuoc mot tai lieu DMS';

-- --------------------------------------------------------
-- Update 23/07/2026: Chi phi xu ly vu viec va lien ket lich cong tac voi vu viec
-- --------------------------------------------------------
ALTER TABLE `work_schedules`
  ADD COLUMN IF NOT EXISTS `case_id` INT(11) UNSIGNED NULL COMMENT 'Vu viec lien quan den lich cong tac, chi hien thi cho nguoi co quyen' AFTER `assigned_by_id`;

CREATE TABLE IF NOT EXISTS `case_expenses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh cua phieu chi phi xu ly vu viec',
  `case_id` INT(11) UNSIGNED NOT NULL COMMENT 'Vu viec phat sinh chi phi xu ly',
  `work_schedule_id` INT(11) UNSIGNED NULL COMMENT 'Lich cong tac lien quan neu chi phi duoc nhap tu lich',
  `employee_id` INT(11) UNSIGNED NOT NULL COMMENT 'Nhan su truc tiep phat sinh chi phi',
  `created_by` INT(11) UNSIGNED NOT NULL COMMENT 'Nhan su tao phieu chi phi',
  `expense_date` DATE NOT NULL COMMENT 'Ngay phat sinh chi phi',
  `category` VARCHAR(40) NOT NULL DEFAULT 'other' COMMENT 'Loai chi phi: travel, fuel, taxi, meal, lodging, fee, other',
  `amount` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'So tien de nghi thanh toan bang VND',
  `actual_start_at` DATETIME NULL COMMENT 'Thoi diem bat dau xu ly thuc te',
  `actual_end_at` DATETIME NULL COMMENT 'Thoi diem ket thuc xu ly thuc te',
  `actual_hours` DECIMAL(6,2) NOT NULL DEFAULT 0 COMMENT 'Tong so gio nhan su di xu ly vu viec',
  `note` TEXT NULL COMMENT 'Ghi chu nghiep vu hoac giai trinh khoan chi',
  `status` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT 'Trang thai duyet chi phi',
  `approved_amount` BIGINT(20) NULL COMMENT 'So tien ke toan duyet thuc thanh toan',
  `approval_note` TEXT NULL COMMENT 'Ghi chu duyet hoac ly do tu choi',
  `approved_by` INT(11) UNSIGNED NULL COMMENT 'Nhan su ke toan/quan ly duyet phieu',
  `approved_at` DATETIME NULL COMMENT 'Thoi diem duyet chi phi',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem',
  PRIMARY KEY (`id`),
  KEY `idx_case_expenses_case_status` (`case_id`, `status`),
  KEY `idx_case_expenses_employee_date` (`employee_id`, `expense_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chi phi va thoi gian nhan su xu ly tung vu viec';

CREATE TABLE IF NOT EXISTS `case_expense_attachments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh cua chung tu chi phi',
  `expense_id` INT(11) UNSIGNED NOT NULL COMMENT 'Phieu chi phi so huu chung tu',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Ten tep chung tu goc',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'Duong dan luu chung tu trong writable/uploads',
  `file_type` VARCHAR(80) NULL COMMENT 'MIME type cua tep chung tu',
  `uploaded_by` INT(11) UNSIGNED NOT NULL COMMENT 'Nhan su tai chung tu len',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem',
  PRIMARY KEY (`id`),
  KEY `idx_case_expense_attachments_expense` (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chung tu dinh kem phieu chi phi xu ly vu viec';

-- --------------------------------------------------------
-- Update 24/07/2026: Chi phi van hanh noi bo cho ke toan va admin
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `office_expenses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh cua khoan chi phi van hanh',
  `expense_date` DATE NOT NULL COMMENT 'Ngay phat sinh chi phi van hanh',
  `category` VARCHAR(80) NOT NULL COMMENT 'Loai chi phi van hanh: electricity, water, internet, rent, stationery, maintenance, software, tax_fee, salary_misc, other',
  `vendor` VARCHAR(255) NULL COMMENT 'Nha cung cap hoac don vi nhan thanh toan',
  `amount` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'So tien chi phi van hanh bang VND',
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash' COMMENT 'Phuong thuc thanh toan: cash, transfer, card hoac other',
  `note` TEXT NULL COMMENT 'Ghi chu ky thanh toan, ma hoa don hoac ly do phat sinh',
  `receipt_file_name` VARCHAR(255) NULL COMMENT 'Ten file chung tu goc do ke toan tai len',
  `receipt_file_path` VARCHAR(500) NULL COMMENT 'Duong dan luu chung tu trong writable/uploads',
  `receipt_file_type` VARCHAR(80) NULL COMMENT 'MIME type cua file chung tu',
  `created_by` INT(11) UNSIGNED NOT NULL COMMENT 'Nhan su ke toan hoac admin tao khoan chi',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem ban ghi',
  PRIMARY KEY (`id`),
  KEY `idx_office_expenses_date_category` (`expense_date`, `category`),
  KEY `idx_office_expenses_created_by` (`created_by`),
  CONSTRAINT `fk_office_expenses_created_by` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chi phi van hanh noi bo khong gan truc tiep voi vu viec';

-- --------------------------------------------------------
-- Update 24/07/2026: Chuan hoa so gio chi phi vu viec khong am
-- --------------------------------------------------------
UPDATE `case_expenses`
SET `actual_hours` = ABS(`actual_hours`)
WHERE `actual_hours` < 0;

-- --------------------------------------------------------
-- Update 24/07/2026: So du phep nam cho vai tro Truong phong hoac Nhan vien chinh thuc
-- --------------------------------------------------------
ALTER TABLE `employees`
    ADD COLUMN IF NOT EXISTS `annual_leave_start_date` DATE NULL COMMENT 'Ngay bat dau tinh phep nam cho vai tro Truong phong hoac Nhan vien chinh thuc' AFTER `join_date`;

-- --------------------------------------------------------
-- Update 25/07/2026: Dang ky quyen cho module chi phi xu ly va chi phi van hanh
-- --------------------------------------------------------
INSERT INTO `permissions` (`name`, `module_group`, `description`, `created_at`, `updated_at`) VALUES
('case_expense.submit', 'Chi phí xử lý vụ việc', 'Tạo phiếu chi phí cho vụ việc mình được phân công hoặc tham gia', NOW(), NOW()),
('case_expense.view_own', 'Chi phí xử lý vụ việc', 'Xem chi phí vụ việc của cá nhân', NOW(), NOW()),
('case_expense.view_team', 'Chi phí xử lý vụ việc', 'Xem chi phí của nhân sự cấp dưới trực tiếp', NOW(), NOW()),
('case_expense.view_all', 'Chi phí xử lý vụ việc', 'Xem toàn bộ chi phí xử lý vụ việc', NOW(), NOW()),
('case_expense.approve', 'Chi phí xử lý vụ việc', 'Duyệt hoặc từ chối chi phí xử lý vụ việc', NOW(), NOW()),
('office_expense.view', 'Chi phí vận hành', 'Xem thống kê và danh sách chi phí vận hành', NOW(), NOW()),
('office_expense.manage', 'Chi phí vận hành', 'Nhập và xóa chi phí vận hành', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `module_group` = VALUES(`module_group`),
  `description` = VALUES(`description`),
  `updated_at` = NOW();

INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT role_id, p.id
FROM (
    SELECT 1 role_id UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
) r
JOIN `permissions` p ON p.name IN ('case_expense.submit', 'case_expense.view_own');

INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT role_id, p.id
FROM (
    SELECT 1 role_id UNION ALL SELECT 2 UNION ALL SELECT 3
) r
JOIN `permissions` p ON p.name = 'case_expense.view_team';

INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT role_id, p.id
FROM (
    SELECT 1 role_id UNION ALL SELECT 2
) r
JOIN `permissions` p ON p.name IN ('case_expense.view_all', 'case_expense.approve', 'office_expense.view', 'office_expense.manage');

-- --------------------------------------------------------
-- Update 11/08/2026: CRM quan he khach hang va co hoi phat trien dich vu
-- --------------------------------------------------------
ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `relationship_level` VARCHAR(50) NOT NULL DEFAULT 'lead' COMMENT 'Cap do quan he: lead, active, loyal, strategic' AFTER `care_status`,
  ADD COLUMN IF NOT EXISTS `relationship_score` INT(11) NOT NULL DEFAULT 0 COMMENT 'Diem quan he khach hang tu 0 den 100' AFTER `relationship_level`,
  ADD COLUMN IF NOT EXISTS `relationship_status` VARCHAR(30) NOT NULL DEFAULT 'healthy' COMMENT 'Trang thai quan he: healthy, watch, risk, critical' AFTER `relationship_score`,
  ADD COLUMN IF NOT EXISTS `health_score` INT(11) NOT NULL DEFAULT 0 COMMENT 'Diem suc khoe khach hang tu 0 den 100' AFTER `relationship_status`,
  ADD COLUMN IF NOT EXISTS `next_interaction_date` DATE NULL COMMENT 'Ngay du kien tuong tac ke tiep voi khach hang' AFTER `last_contact_date`,
  ADD COLUMN IF NOT EXISTS `relationship_manager_id` INT(11) UNSIGNED NULL COMMENT 'Nhan su quan ly quan he khach hang' AFTER `assigned_care_staff_id`,
  ADD COLUMN IF NOT EXISTS `referred_by_customer_id` INT(11) UNSIGNED NULL COMMENT 'Khach hang da gioi thieu khach hang nay' AFTER `referred_by`,
  ADD COLUMN IF NOT EXISTS `referral_score` TINYINT(3) NOT NULL DEFAULT 0 COMMENT 'Diem tiem nang gioi thieu tu 0 den 100' AFTER `referral_count`,
  ADD COLUMN IF NOT EXISTS `interests` TEXT NULL COMMENT 'Moi quan tam, so thich, nhu cau thuong gap cua khach hang' AFTER `occupation`,
  ADD COLUMN IF NOT EXISTS `identified_issues` TEXT NULL COMMENT 'Van de phap ly hoac nhu cau da duoc nhan dien' AFTER `interests`;

ALTER TABLE `customer_interactions`
  ADD COLUMN IF NOT EXISTS `interaction_result` VARCHAR(50) NULL COMMENT 'Ket qua tuong tac: positive, neutral, negative, no_response' AFTER `summary`,
  ADD COLUMN IF NOT EXISTS `importance_level` VARCHAR(20) NOT NULL DEFAULT 'normal' COMMENT 'Muc do quan trong: low, normal, high, urgent' AFTER `interaction_result`,
  ADD COLUMN IF NOT EXISTS `requires_follow_up` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Danh dau tuong tac can theo doi lai' AFTER `importance_level`;

CREATE TABLE IF NOT EXISTS `customer_opportunities` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh co hoi phat trien dich vu',
  `customer_id` INT(11) UNSIGNED NOT NULL COMMENT 'Khach hang so huu co hoi',
  `issue_title` VARCHAR(255) NOT NULL COMMENT 'Ten van de hoac nhu cau khach hang',
  `issue_description` TEXT NULL COMMENT 'Mo ta chi tiet van de da phat hien',
  `service_suggestion` TEXT NULL COMMENT 'Dich vu hoac giai phap de xuat',
  `estimated_value` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Gia tri doanh thu du kien cua co hoi',
  `probability` TINYINT(3) NOT NULL DEFAULT 0 COMMENT 'Xac suat chuyen doi phan tram',
  `assigned_staff_id` INT(11) UNSIGNED NULL COMMENT 'Nhan su phu trach theo doi co hoi',
  `discovered_at` DATE NULL COMMENT 'Ngay phat hien co hoi',
  `follow_up_date` DATE NULL COMMENT 'Ngay can theo doi co hoi',
  `stage` VARCHAR(50) NOT NULL DEFAULT 'detected' COMMENT 'Giai doan co hoi: detected, consulting, quoted, won, lost',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active' COMMENT 'Trang thai co hoi: active, won, lost, paused',
  `source_type` VARCHAR(50) NOT NULL DEFAULT 'manual' COMMENT 'Nguon phat hien co hoi: manual, interaction, referral, case',
  `created_by` INT(11) UNSIGNED NULL COMMENT 'Nhan su tao co hoi',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat ban ghi',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem',
  PRIMARY KEY (`id`),
  KEY `idx_customer_opportunities_customer_status` (`customer_id`, `status`),
  KEY `idx_customer_opportunities_follow_up` (`follow_up_date`),
  CONSTRAINT `fk_customer_opportunities_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_customer_opportunities_assigned_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Co hoi phat trien dich vu tu cham soc quan he khach hang';

-- --------------------------------------------------------
-- Update 13/08/2026: Quan ly quy vi pham noi bo
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `violation_funds` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh khoan quy vi pham',
  `employee_id` INT(11) UNSIGNED NOT NULL COMMENT 'Nhan su bi ghi nhan vi pham noi bo',
  `violation_date` DATE NOT NULL COMMENT 'Ngay xay ra hanh vi vi pham',
  `due_month` CHAR(7) NOT NULL COMMENT 'Thang hanh chinh can theo doi thu quy, dinh dang YYYY-MM',
  `category` VARCHAR(80) NOT NULL COMMENT 'Nhom vi pham: cham cong, bao cao, bao mat, noi quy, nghi phep hoac nhom khac',
  `behavior` VARCHAR(500) NOT NULL COMMENT 'Hanh vi vi pham cu the theo quy dinh hoac noi dung nhap thu cong',
  `rank_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT 'Cap bac ap dung tai thoi diem vi pham: 1, 2 hoac 3',
  `base_amount` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Muc san theo bang quy dinh truoc khi dieu chinh tai pham hoac nhap tay',
  `amount` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'So tien thuc te can thu vao quy vi pham noi bo bang VND',
  `recurrence_count` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'So lan tai pham cung loi trong thang tai thoi diem ghi nhan',
  `status` VARCHAR(30) NOT NULL DEFAULT 'notified' COMMENT 'Trang thai thu quy: notified, collected hoac waived',
  `collection_method` VARCHAR(50) NOT NULL DEFAULT 'cash' COMMENT 'Hinh thuc thu: tien mat, chuyen khoan, can tru bang luong hoac hinh thuc khac',
  `explanation` TEXT NULL COMMENT 'Giai trinh hoac boi canh xem xet truoc khi ghi nhan vi pham',
  `hr_note` TEXT NULL COMMENT 'Ghi chu cua nhan su/admin khi lap khoan vi pham',
  `admin_note` TEXT NULL COMMENT 'Ghi chu hanh chinh khi thu, mien hoac xu ly khoan vi pham',
  `notified_at` DATETIME NULL COMMENT 'Thoi diem he thong thong bao cho nguoi vi pham va hanh chinh',
  `collected_at` DATETIME NULL COMMENT 'Thoi diem hanh chinh xac nhan da thu khoan vi pham',
  `created_by` INT(11) UNSIGNED NOT NULL COMMENT 'Nhan su tao ban ghi vi pham',
  `updated_by` INT(11) UNSIGNED NULL COMMENT 'Nhan su cap nhat trang thai hoac ghi chu gan nhat',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem ban ghi',
  PRIMARY KEY (`id`),
  KEY `idx_violation_funds_month_status` (`due_month`, `status`),
  KEY `idx_violation_funds_employee_date` (`employee_id`, `violation_date`),
  KEY `idx_violation_funds_created_by` (`created_by`),
  CONSTRAINT `fk_violation_funds_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_violation_funds_created_by` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_violation_funds_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Quan ly cac khoan dong quy vi pham noi bo';

INSERT INTO `permissions` (`name`, `module_group`, `description`, `created_at`, `updated_at`) VALUES
('violation_fund.view', 'Quỹ vi phạm nội bộ', 'Xem toàn bộ báo cáo quỹ vi phạm nội bộ', NOW(), NOW()),
('violation_fund.view_own', 'Quỹ vi phạm nội bộ', 'Xem các khoản vi phạm của bản thân', NOW(), NOW()),
('violation_fund.manage', 'Quỹ vi phạm nội bộ', 'Ghi nhận và xóa khoản vi phạm', NOW(), NOW()),
('violation_fund.collect', 'Quỹ vi phạm nội bộ', 'Cập nhật trạng thái hành chính đã thu', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `module_group` = VALUES(`module_group`),
  `description` = VALUES(`description`),
  `updated_at` = NOW();

INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT role_id, p.id
FROM (
    SELECT 1 role_id UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
) r
JOIN `permissions` p ON p.name = 'violation_fund.view_own';

INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT role_id, p.id
FROM (
    SELECT 1 role_id UNION ALL SELECT 2
) r
JOIN `permissions` p ON p.name IN ('violation_fund.view', 'violation_fund.manage', 'violation_fund.collect');

-- --------------------------------------------------------
-- Update 17/08/2026: Doi tac va hoa hong theo tien do thanh toan vu viec
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partners` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh ho so doi tac',
  `user_id` INT(11) UNSIGNED NULL COMMENT 'Tai khoan users dung de doi tac dang nhap nhu user binh thuong',
  `name` VARCHAR(255) NOT NULL COMMENT 'Ten ca nhan hoac cong ty doi tac',
  `partner_type` VARCHAR(50) NOT NULL DEFAULT 'individual' COMMENT 'Loai doi tac: individual hoac company',
  `phone` VARCHAR(50) NULL COMMENT 'So dien thoai lien he doi tac',
  `email` VARCHAR(255) NULL COMMENT 'Email lien he doi tac',
  `tax_code` VARCHAR(100) NULL COMMENT 'Ma so thue hoac thong tin dinh danh thanh toan',
  `bank_name` VARCHAR(255) NULL COMMENT 'Ngan hang nhan thanh toan',
  `bank_account` VARCHAR(100) NULL COMMENT 'So tai khoan nhan thanh toan',
  `bank_owner` VARCHAR(255) NULL COMMENT 'Chu tai khoan nhan thanh toan',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active' COMMENT 'Trang thai hop tac: active, paused, ended',
  `notes` TEXT NULL COMMENT 'Ghi chu noi bo ve doi tac',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem',
  PRIMARY KEY (`id`),
  KEY `idx_partners_user` (`user_id`),
  KEY `idx_partners_status` (`status`),
  CONSTRAINT `fk_partners_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Ho so doi tac cong ty lien ket voi tai khoan users de dang nhap';

CREATE TABLE IF NOT EXISTS `case_partners` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh cau hinh hop tac doi tac theo vu viec',
  `case_id` INT(11) UNSIGNED NOT NULL COMMENT 'Vu viec duoc gan doi tac',
  `partner_id` INT(11) UNSIGNED NOT NULL COMMENT 'Doi tac tham gia hoac gioi thieu vu viec',
  `role_label` VARCHAR(100) NOT NULL DEFAULT 'referrer' COMMENT 'Vai tro doi tac: referrer, consultant, closer, operator, expert, other',
  `calculation_base` VARCHAR(30) NOT NULL DEFAULT 'paid' COMMENT 'Co so tinh hoa hong: contract hoac paid',
  `percentage` DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT 'Phan tram doi tac duoc huong',
  `fixed_amount` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'So tien co dinh doi tac duoc huong them theo vu viec',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active' COMMENT 'Trang thai cau hinh hop tac: active, paused, ended',
  `notes` TEXT NULL COMMENT 'Ghi chu dieu kien hop tac rieng cua vu viec',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem',
  PRIMARY KEY (`id`),
  KEY `idx_case_partners_case_partner` (`case_id`, `partner_id`),
  KEY `idx_case_partners_status` (`status`),
  CONSTRAINT `fk_case_partners_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_case_partners_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cau hinh ty le va so tien doi tac duoc huong theo tung vu viec';

CREATE TABLE IF NOT EXISTS `partner_commission_entries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khoa chinh dong hoa hong doi tac phat sinh',
  `case_partner_id` INT(11) UNSIGNED NOT NULL COMMENT 'Cau hinh hop tac sinh ra dong hoa hong',
  `partner_id` INT(11) UNSIGNED NOT NULL COMMENT 'Doi tac duoc huong hoa hong',
  `case_id` INT(11) UNSIGNED NOT NULL COMMENT 'Vu viec phat sinh hoa hong',
  `payment_index` INT(11) NOT NULL DEFAULT 0 COMMENT 'Thu tu dot thanh toan trong cases.payment_progress',
  `payment_title` VARCHAR(255) NULL COMMENT 'Ten dot thanh toan cua khach hang',
  `payment_date` DATE NULL COMMENT 'Ngay ghi nhan khach da thanh toan dot nay',
  `calculation_base` VARCHAR(30) NOT NULL DEFAULT 'paid' COMMENT 'Co so tinh tai thoi diem phat sinh: contract hoac paid',
  `base_amount` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'Gia tri goc dung de tham chieu cong thuc tinh',
  `percentage` DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT 'Phan tram ap dung tai thoi diem phat sinh',
  `fixed_amount` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'Phan tien co dinh duoc phan bo vao dot thanh toan nay',
  `commission_amount` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'So tien hoa hong doi tac duoc nhan trong dot nay',
  `status` VARCHAR(30) NOT NULL DEFAULT 'accrued' COMMENT 'Trang thai chi tra: accrued, requested, approved, paid, held',
  `request_note` TEXT NULL COMMENT 'Ghi chu yeu cau thanh toan cua doi tac',
  `admin_note` TEXT NULL COMMENT 'Ghi chu duyet chi hoac thanh toan cua noi bo',
  `requested_at` DATETIME NULL COMMENT 'Thoi diem doi tac gui yeu cau thanh toan',
  `approved_at` DATETIME NULL COMMENT 'Thoi diem noi bo duyet chi',
  `paid_at` DATETIME NULL COMMENT 'Thoi diem xac nhan da thanh toan cho doi tac',
  `created_at` DATETIME NULL COMMENT 'Thoi diem tao ban ghi',
  `updated_at` DATETIME NULL COMMENT 'Thoi diem cap nhat gan nhat',
  `deleted_at` DATETIME NULL COMMENT 'Thoi diem xoa mem',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_partner_commission_case_payment` (`case_partner_id`, `payment_index`),
  KEY `idx_partner_commission_partner_status` (`partner_id`, `status`),
  KEY `idx_partner_commission_case_status` (`case_id`, `status`),
  CONSTRAINT `fk_partner_commission_case_partner` FOREIGN KEY (`case_partner_id`) REFERENCES `case_partners` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_partner_commission_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_partner_commission_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cac khoan hoa hong doi tac tu phat sinh khi khach thanh toan vu viec';

INSERT INTO `permissions` (`name`, `module_group`, `description`, `created_at`, `updated_at`) VALUES
('partner.portal', 'Doi tac', 'Doi tac xem doanh thu duoc nhan va gui yeu cau thanh toan', NOW(), NOW()),
('partner.manage', 'Doi tac', 'Quan ly ho so doi tac va cau hinh hop tac theo vu viec', NOW(), NOW()),
('partner.payout', 'Doi tac', 'Duyet va cap nhat trang thai chi tra hoa hong doi tac', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `module_group` = VALUES(`module_group`),
  `description` = VALUES(`description`),
  `updated_at` = NOW();

INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`)
SELECT role_id, p.id
FROM (
    SELECT 1 role_id UNION ALL SELECT 2
) r
JOIN `permissions` p ON p.name IN ('partner.manage', 'partner.payout');

-- Update 18/08/2026: Gan doi tac gioi thieu vao khach hang
ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `referred_partner_id` INT(11) UNSIGNED NULL COMMENT 'Doi tac gioi thieu khach hang' AFTER `referred_by`;

-- ----------------------------
-- UPDATE LOG: 24/08/2026 - Cấu hình trạng thái Giám sát CSKH
-- ----------------------------
ALTER TABLE `customers`
    ADD COLUMN IF NOT EXISTS `monitoring_status` TEXT NULL COMMENT 'JSON array trạng thái giám sát chất lượng tư vấn CSKH' AFTER `care_status`;

ALTER TABLE `customers`
    MODIFY COLUMN `monitoring_status` TEXT NULL COMMENT 'JSON array trạng thái giám sát chất lượng tư vấn CSKH';

UPDATE `customers`
SET `monitoring_status` = CONCAT('[', JSON_QUOTE(`monitoring_status`), ']')
WHERE `monitoring_status` IS NOT NULL
  AND `monitoring_status` != ''
  AND JSON_VALID(`monitoring_status`) = 0;

CREATE TABLE IF NOT EXISTS `customer_monitoring_status_settings` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính',
    `status_key` VARCHAR(80) NOT NULL COMMENT 'Khóa định danh trạng thái giám sát',
    `status_name` VARCHAR(150) NOT NULL COMMENT 'Tên hiển thị trạng thái giám sát',
    `color` VARCHAR(20) NOT NULL DEFAULT '#6c757d' COMMENT 'Màu sắc đại diện trạng thái',
    `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hoạt động',
    `created_at` DATETIME DEFAULT NULL COMMENT 'Ngày tạo',
    `updated_at` DATETIME DEFAULT NULL COMMENT 'Ngày cập nhật',
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'Ngày xóa mềm',
    UNIQUE KEY `idx_monitoring_status_key` (`status_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình trạng thái giám sát CSKH cho khách hàng';

INSERT IGNORE INTO `customer_monitoring_status_settings` (`status_key`, `status_name`, `color`, `sort_order`, `created_at`, `updated_at`) VALUES
('good', 'Good', '#34c759', 1, NOW(), NOW()),
('miss_tin_03_phut_khi_tao_nhom', 'Miss tin trong 03 phút khi tạo nhóm', '#ff3b30', 2, NOW(), NOW()),
('miss_tuong_tac_trong_qua_trinh_tv', 'Miss tương tác trong quá trình TV', '#ff9500', 3, NOW(), NOW()),
('chua_gui_bao_phi', 'Chưa gửi báo phí', '#af52de', 4, NOW(), NOW()),
('chua_co_anh_cham_soc_cuoi_cung', 'Chưa có ảnh chăm sóc cuối cùng', '#5856d6', 5, NOW(), NOW()),
('tu_van_chua_than_thiet_nhiet_tinh', 'Tư vấn chưa thân thiện, nhiệt tình', '#ff2d55', 6, NOW(), NOW()),
('khach_goi_phan_nan', 'Khách gọi phàn nàn', '#d70015', 7, NOW(), NOW());
