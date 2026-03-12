-- File Cấu trúc SQL: Quản lý Nhân sự & Phân quyền Tài khoản
-- Chú thích: Script này cung cấp toàn bộ các lệnh định nghĩa DDL để tạo bảng,
-- thay thế cho việc sử dụng thư mục Migrations của PHP. Phù hợp cho việc cài thẳng vào MySQL.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;



    -- Law Firm ERP Database Schema
-- Generated: 2026-03-10
-- Chú thích: Toàn bộ các bảng và trường đã được bổ sung comment tiếng Việt.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for roles (Vai trò người dùng)
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
                         `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                         `name` varchar(100) NOT NULL COMMENT 'Tên vai trò (Admin, Lawyer, Staff...)',
                         `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết về vai trò',
                         `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo',
                         `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                         `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ các vai trò trong hệ thống';

-- ----------------------------
-- Table structure for departments (Phòng ban/Bộ phận)
-- ----------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
                               `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                               `name` varchar(100) NOT NULL COMMENT 'Tên phòng ban (Marketing, Pháp lý...)',
                               `description` text DEFAULT NULL COMMENT 'Mô tả nhiệm vụ phòng ban',
                               `created_at` datetime DEFAULT NULL,
                               `updated_at` datetime DEFAULT NULL,
                               `deleted_at` datetime DEFAULT NULL,
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý các phòng ban trong công ty';

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
-- Table structure for users (Tài khoản đăng nhập)
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
                         `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                         `role_id` int(11) unsigned NOT NULL COMMENT 'ID vai trò (khóa ngoại)',
                         `email` varchar(255) NOT NULL COMMENT 'Email đăng nhập',
                         `password` varchar(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa',
                         `active_status` tinyint(1) DEFAULT 1 COMMENT 'Trạng thái hoạt động (1: Bật, 0: Khóa)',
                         `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo tài khoản',
                         `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                         `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `email` (`email`),
                         KEY `role_id` (`role_id`),
                         CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng tài khoản người dùng hệ thống';

-- ----------------------------
-- Table structure for employees (Thông tin nhân viên)
-- ----------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
                             `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                             `user_id` int(11) unsigned DEFAULT NULL COMMENT 'ID tài khoản liên kết (nếu có)',
                             `full_name` varchar(255) NOT NULL COMMENT 'Họ và tên đầy đủ',
                             `dob` date DEFAULT NULL COMMENT 'Ngày sinh',
                             `identity_card` varchar(50) DEFAULT NULL COMMENT 'Số CMND/CCCD',
                             `address` text DEFAULT NULL COMMENT 'Địa chỉ thường trú',
                             `join_date` date DEFAULT NULL COMMENT 'Ngày bắt đầu làm việc',
                             `salary_base` decimal(15,2) DEFAULT '0.00' COMMENT 'Mức lương cơ bản',
                             `position` varchar(100) DEFAULT NULL COMMENT 'Chức danh công việc',
                             `department_id` int(11) unsigned DEFAULT NULL COMMENT 'ID phòng ban (khóa ngoại)',
                             `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo hồ sơ',
                             `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                             `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                             PRIMARY KEY (`id`),
                             KEY `user_id` (`user_id`),
                             KEY `department_id` (`department_id`),
                             CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                             CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng hồ sơ chi tiết nhân viên';

-- ----------------------------
-- Table structure for customers (Khách hàng)
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
                             `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                             `name` varchar(255) NOT NULL COMMENT 'Tên khách hàng hoặc tên công ty',
                             `type` enum('individual','corporate') DEFAULT 'individual' COMMENT 'Loại khách hàng (Cá nhân/Doanh nghiệp)',
                             `tax_code` varchar(50) DEFAULT NULL COMMENT 'Mã số thuế',
                             `representative` varchar(255) DEFAULT NULL COMMENT 'Người đại diện pháp luật',
                             `phone` varchar(20) DEFAULT NULL COMMENT 'Số điện thoại liên lạc',
                             `email` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ email',
                             `address` text DEFAULT NULL COMMENT 'Địa chỉ liên hệ',
                             `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo',
                             `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                             `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý thông tin khách hàng';

-- ----------------------------
-- Table structure for cases (Vụ việc pháp lý)
-- ----------------------------
DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases` (
                         `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                         `customer_id` int(11) unsigned NOT NULL COMMENT 'ID khách hàng liên quan',
                         `title` varchar(255) NOT NULL COMMENT 'Tên vụ việc',
                         `internal_code` varchar(50) NOT NULL COMMENT 'Mã hồ sơ nội bộ',
                         `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết vụ việc',
                         `status` enum('open','in_progress', 'pending', 'closed', 'cancelled') DEFAULT 'open' COMMENT 'Trạng thái (Mới, Đang xử lý, Tạm dừng, Đóng, Hủy)',
                         `priority` enum('low','medium', 'high', 'critical') DEFAULT 'medium' COMMENT 'Mức độ ưu tiên',
                         `assigned_lawyer_id` int(11) unsigned DEFAULT NULL COMMENT 'ID luật sư phụ trách chính',
                         `start_date` date DEFAULT NULL COMMENT 'Ngày bắt đầu thụ lý',
                         `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc dự kiến/thực tế',
                         `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo hồ sơ',
                         `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                         `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `internal_code` (`internal_code`),
                         KEY `customer_id` (`customer_id`),
                         KEY `assigned_lawyer_id` (`assigned_lawyer_id`),
                         CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                         CONSTRAINT `cases_ibfk_2` FOREIGN KEY (`assigned_lawyer_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý các hồ sơ vụ việc';

-- ----------------------------
-- Table structure for contracts (Hợp đồng)
-- ----------------------------
DROP TABLE IF EXISTS `contracts`;
CREATE TABLE `contracts` (
                             `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                             `case_id` int(11) unsigned NOT NULL COMMENT 'ID vụ việc liên kết',
                             `title` varchar(255) NOT NULL COMMENT 'Tiêu đề hợp đồng',
                             `sign_date` date DEFAULT NULL COMMENT 'Ngày ký kết',
                             `expiry_date` date DEFAULT NULL COMMENT 'Ngày hết hạn',
                             `total_value` decimal(15,2) DEFAULT '0.00' COMMENT 'Giá trị hợp đồng',
                             `file_path` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn lưu file đính kèm',
                             `status` enum('draft','pending', 'signed', 'expired', 'cancelled') DEFAULT 'draft' COMMENT 'Trạng thái hợp đồng',
                             `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo',
                             `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                             `deleted_at` datetime DEFAULT NULL COMMENT 'Ngày xóa mềm',
                             PRIMARY KEY (`id`),
                             KEY `case_id` (`case_id`),
                             CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý hợp đồng pháp lý';

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
-- Table structure for accounting (Kế toán/Giao dịch)
-- ----------------------------
DROP TABLE IF EXISTS `accounting`;
CREATE TABLE `accounting` (
                              `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                              `case_id` int(11) unsigned DEFAULT NULL COMMENT 'ID vụ việc liên quan (nếu có)',
                              `transaction_type` enum('income','expense') DEFAULT 'income' COMMENT 'Loại giao dịch (Thu/Chi)',
                              `category` varchar(100) NOT NULL COMMENT 'Hạng mục thu chi',
                              `amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Số tiền giao dịch',
                              `payment_method` varchar(50) DEFAULT NULL COMMENT 'Phương thức thanh toán',
                              `note` text DEFAULT NULL COMMENT 'Ghi chú giao dịch',
                              `date` date NOT NULL COMMENT 'Ngày thực hiện giao dịch',
                              `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo phiếu',
                              `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                              PRIMARY KEY (`id`),
                              KEY `case_id` (`case_id`),
                              CONSTRAINT `accounting_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý thu chi, kế toán';

-- ----------------------------
-- Table structure for trainings (Đào tạo nội bộ)
-- ----------------------------
DROP TABLE IF EXISTS `trainings`;
CREATE TABLE `trainings` (
                             `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                             `title` varchar(255) NOT NULL COMMENT 'Tên khóa đào tạo',
                             `content` text DEFAULT NULL COMMENT 'Nội dung chi tiết đào tạo',
                             `type` varchar(100) NOT NULL COMMENT 'Loại hình (Video, Offline, Tài liệu)',
                             `duration` int(11) NOT NULL COMMENT 'Thời lượng (phút)',
                             `trainer_name` varchar(255) DEFAULT NULL COMMENT 'Tên giảng viên/người hướng dẫn',
                             `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo',
                             `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng danh sách các khóa đào tạo nội bộ';

-- ----------------------------
-- Table structure for employee_trainings (Tiến độ đào tạo nhân viên)
-- ----------------------------
DROP TABLE IF EXISTS `employee_trainings`;
CREATE TABLE `employee_trainings` (
                                      `employee_id` int(11) unsigned NOT NULL COMMENT 'ID nhân viên tham gia',
                                      `training_id` int(11) unsigned NOT NULL COMMENT 'ID khóa đào tạo',
                                      `status` enum('enrolled','in_progress', 'completed', 'failed') DEFAULT 'enrolled' COMMENT 'Trạng thái học tập',
                                      `completion_date` date DEFAULT NULL COMMENT 'Ngày hoàn thành',
                                      `score` int(11) DEFAULT NULL COMMENT 'Điểm số đạt được',
                                      PRIMARY KEY (`employee_id`,`training_id`),
                                      KEY `training_id` (`training_id`),
                                      CONSTRAINT `employee_trainings_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                      CONSTRAINT `employee_trainings_ibfk_2` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng theo dõi tiến độ đào tạo của từng nhân viên';

-- ----------------------------
-- Table structure for leave_requests (Đơn xin nghỉ phép)
-- ----------------------------
DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
                                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                                  `employee_id` int(11) unsigned NOT NULL COMMENT 'ID nhân viên xin nghỉ',
                                  `leave_type` enum('annual','sick', 'personal', 'unpaid') DEFAULT 'annual' COMMENT 'Loại nghỉ (Phép năm, Ốm, Việc riêng, Nghỉ không lương)',
                                  `start_date` date NOT NULL COMMENT 'Ngày bắt đầu nghỉ',
                                  `end_date` date NOT NULL COMMENT 'Ngày kết thúc nghỉ',
                                  `reason` text DEFAULT NULL COMMENT 'Lý do xin nghỉ',
                                  `status` enum('pending','approved', 'rejected') DEFAULT 'pending' COMMENT 'Trạng thái duyệt đơn',
                                  `approved_by` int(11) unsigned DEFAULT NULL COMMENT 'ID người phê duyệt',
                                  `created_at` datetime DEFAULT NULL COMMENT 'Ngày làm đơn',
                                  `updated_at` datetime DEFAULT NULL COMMENT 'Ngày cập nhật',
                                  PRIMARY KEY (`id`),
                                  KEY `employee_id` (`employee_id`),
                                  CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý nghỉ phép của nhân viên';

-- ----------------------------
-- Table structure for performance_reviews (Đánh giá nhân viên)
-- ----------------------------
DROP TABLE IF EXISTS `performance_reviews`;
CREATE TABLE `performance_reviews` (
                                       `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                                       `employee_id` int(11) unsigned NOT NULL COMMENT 'ID nhân viên được đánh giá',
                                       `reviewer_id` int(11) unsigned NOT NULL COMMENT 'ID người thực hiện đánh giá',
                                       `review_period` varchar(50) DEFAULT NULL COMMENT 'Kỳ đánh giá (vd: Quý 1-2024)',
                                       `criteria_scores` json DEFAULT NULL COMMENT 'Chi tiết điểm số theo tiêu chí (định dạng JSON)',
                                       `final_score` int(11) DEFAULT NULL COMMENT 'Tổng điểm cuối cùng',
                                       `comments` text DEFAULT NULL COMMENT 'Nhận xét chi tiết',
                                       `review_date` date NOT NULL COMMENT 'Ngày đánh giá',
                                       `created_at` datetime DEFAULT NULL COMMENT 'Ngày tạo bản đánh giá',
                                       PRIMARY KEY (`id`),
                                       KEY `employee_id` (`employee_id`),
                                       KEY `reviewer_id` (`reviewer_id`),
                                       CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                       CONSTRAINT `performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ kết quả đánh giá nhân sự';

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
-- Table structure for daily_reports (Báo cáo hàng ngày)
-- ----------------------------
DROP TABLE IF EXISTS `daily_reports`;
CREATE TABLE `daily_reports` (
                                 `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                                 `employee_id` int(11) unsigned NOT NULL COMMENT 'ID nhân viên báo cáo',
                                 `report_date` date NOT NULL COMMENT 'Ngày báo cáo',
                                 `content` text NOT NULL COMMENT 'Nội dung công việc đã làm',
                                 `obstacles` text DEFAULT NULL COMMENT 'Khó khăn/Vướng mắc',
                                 `tomorrow_plan` text DEFAULT NULL COMMENT 'Kế hoạch ngày mai',
                                 `created_at` datetime DEFAULT NULL COMMENT 'Ngày gửi báo cáo',
                                 PRIMARY KEY (`id`),
                                 KEY `employee_id` (`employee_id`),
                                 CONSTRAINT `daily_reports_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lưu trữ báo cáo công việc hàng ngày';

-- ----------------------------
-- Table structure for internal_messages (Tin nhắn nội bộ)
-- ----------------------------
DROP TABLE IF EXISTS `internal_messages`;
CREATE TABLE `internal_messages` (
                                     `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
                                     `sender_id` int(11) unsigned NOT NULL COMMENT 'ID người gửi',
                                     `receiver_id` int(11) unsigned NOT NULL COMMENT 'ID người nhận',
                                     `subject` varchar(255) NOT NULL COMMENT 'Tiêu đề tin nhắn',
                                     `body` text NOT NULL COMMENT 'Nội dung tin nhắn',
                                     `is_read` tinyint(4) DEFAULT 0 COMMENT 'Trạng thái đã đọc (1: Rồi, 0: Chưa)',
                                     `sent_at` datetime DEFAULT NULL COMMENT 'Thời gian gửi',
                                     PRIMARY KEY (`id`),
                                     KEY `sender_id` (`sender_id`),
                                     KEY `receiver_id` (`receiver_id`),
                                     CONSTRAINT `internal_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                     CONSTRAINT `internal_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng trao đổi thông tin nội bộ hệ thống';

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

-- Admin account (Email: admin@lawfirm.erp | Mật khẩu: admin123)
INSERT INTO `users` (`role_id`, `email`, `password`, `active_status`, `created_at`) VALUES
    (1, 'admin@lawfirm.erp', '$2y$12$Oergst.CYv4Fr/bUMsUFJuO/fuvvDjcw0ZWSWO7kH55x.XWyPgMhS', 1, NOW());

INSERT INTO `employees` (`user_id`, `full_name`, `position`, `salary_base`, `created_at`) VALUES
    (1, 'Quản trị viên hệ thống', 'IT Manager', 0.00, NOW());

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

-- ==========================================================
-- DỮ LIỆU ĐỔ MẪU DÀNH CHO BẢN ĐÓNG GÓI CHIỀU Ý KHÁCH HÀNG
-- ==========================================================

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Admin', 'Tuyệt đối hệ thống. Có toàn quyền quyết định sự tồn tại của hệ thống.', NOW()),
(2, 'Mod', 'Ban giám đốc, được phép xem các tài khoản và thay thế ủy quyền người dưới tay.', NOW()),
(3, 'Trưởng phòng', 'Quản trị phòng ban, xem xét và cấp/quét quyền thuộc hạ.', NOW()),
(4, 'Nhân viên chính thức', 'Người thực thi nghiệp vụ thông thường.', NOW()),
(5, 'Thực tập sinh', 'Quyền thấp nhất, đa số bị hạn chế.', NOW());

INSERT INTO `departments` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Marketing', 'Quản lý thương hiệu và quảng cáo.', NOW()),
(2, 'Sale', 'Đội ngũ tìm kiếm khách và hợp đồng.', NOW()),
(3, 'Pháp lý', 'Chuyên xử lý luật sư vụ kiện.', NOW()),
(4, 'Hành chính', 'Thủ tục văn thư, HR.', NOW());

-- Khởi tạo tài khoản CEO Tối cao
INSERT INTO `users` (`id`, `role_id`, `email`, `password`, `active_status`, `created_at`) VALUES
(1, 1, 'admin@lawfirm.erp', '$2y$12$Oergst.CYv4Fr/bUMsUFJuO/fuvvDjcw0ZWSWO7kH55x.XWyPgMhS', 1, NOW());

-- Tạo hồ sơ của CEO Tối cao (Trường hợp Admin)
INSERT INTO `employees` (`id`, `user_id`, `full_name`, `position`, `department_id`, `created_at`) VALUES
(1, 1, 'LawFirm Admin Tổng', 'CEO - System Admin', 4, NOW());

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
