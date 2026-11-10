-- Chọn database ERP hiện có trước khi chạy.
-- Tương đương hai migration ngày 27/10/2026; có thể chạy lại.
-- Không tính lại lương, không thay đổi vai trò hoặc ghi đè lịch sử.
SET NAMES utf8mb4;

SET @erp_stage_sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'payrolls'
          AND COLUMN_NAME = 'position_snapshot'
    ),
    'SELECT ''payrolls.position_snapshot already exists'' AS migration_status',
    'ALTER TABLE `payrolls` ADD COLUMN `position_snapshot` VARCHAR(100) NULL'
);
PREPARE erp_stage_stmt FROM @erp_stage_sql;
EXECUTE erp_stage_stmt;
DEALLOCATE PREPARE erp_stage_stmt;

SET @erp_stage_sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'employees'
          AND COLUMN_NAME = 'initial_position'
    ),
    'SELECT ''employees.initial_position already exists'' AS migration_status',
    'ALTER TABLE `employees` ADD COLUMN `initial_position` VARCHAR(100) NULL'
);
PREPARE erp_stage_stmt FROM @erp_stage_sql;
EXECUTE erp_stage_stmt;
DEALLOCATE PREPARE erp_stage_stmt;

-- Giữ NULL cho dữ liệu cũ để ứng dụng đối chiếu lịch sử vai trò.
-- Không backfill vai trò ban đầu bằng vai trò hiện tại một cách hàng loạt.
