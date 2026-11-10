-- Thêm quyền vào danh sách phân quyền; không tự gán cho user hoặc vai trò.
SET NAMES utf8mb4;
INSERT INTO permissions (name, module_group, description, created_at, updated_at)
SELECT 'case.accounting_own', 'Vụ việc pháp lý',
       'Hành chính - Kế toán: Xem và cập nhật vụ việc do mình tạo', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'case.accounting_own');
