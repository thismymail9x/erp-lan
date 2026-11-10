ALTER TABLE cases
    ADD COLUMN case_manager_id INT UNSIGNED DEFAULT NULL COMMENT 'Nhân sự quản lý và điều phối vụ việc, độc lập với người trực tiếp xử lý',
    ADD INDEX idx_cases_manager (case_manager_id, deleted_at);
