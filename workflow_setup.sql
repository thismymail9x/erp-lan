-- 1. Bảng Workflow Templates (Quy trình mẫu)
DROP TABLE IF EXISTS `workflow_templates`;
CREATE TABLE `workflow_templates` (
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

DROP TABLE IF EXISTS `workflow_template_steps`;
CREATE TABLE `workflow_template_steps` (
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

-- 3. Nâng cấp bảng cases (Thêm workflow_template_id)
DROP TABLE IF EXISTS `workflow_instances`;

SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'cases' AND COLUMN_NAME = 'workflow_template_id' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `cases` ADD COLUMN `workflow_template_id` INT(11) UNSIGNED NULL AFTER `end_date`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Nâng cấp bảng case_steps
-- Thêm template_step_id
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'case_steps' AND COLUMN_NAME = 'template_step_id' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@column_exists = 0, 'ALTER TABLE `case_steps` ADD COLUMN `template_step_id` INT(11) UNSIGNED NULL AFTER `case_id`', 'SELECT 1');
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
