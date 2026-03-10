-- File Cấu trúc SQL: Quản lý Nhân sự & Phân quyền Tài khoản
-- Chú thích: Script này cung cấp toàn bộ các lệnh định nghĩa DDL để tạo bảng,
-- thay thế cho việc sử dụng thư mục Migrations của PHP. Phù hợp cho việc cài thẳng vào MySQL.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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
