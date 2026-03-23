-- SQL Seeding for Case History and Comments
-- Dữ liệu mẫu cho quá trình timeline và bình luận nội bộ
-- Phù hợp với dữ liệu trong example.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Lịch sử vụ việc (case_history) - 10 bản ghi
INSERT INTO `case_history` (`case_id`, `user_id`, `action`, `old_value`, `new_value`, `note`, `created_at`) VALUES
(1, 2, 'tiep_nhan', 'System', 'moi_tiep_nhan', 'Đã tiếp nhận yêu cầu từ khách hàng Hương qua Facebook.', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(1, 2, 'cap_nhat_trang_thai', 'moi_tiep_nhan', 'dang_xu_ly', 'Hoàn thành thẩm định hồ sơ đất đai Ba Vì.', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 3, 'tiep_nhan', 'System', 'dang_xu_ly', 'Bắt đầu nghiên cứu hợp đồng thương mại cho công ty ABC.', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(4, 3, 'giao_viec', 'System', 'dang_xu_ly', 'Giao cho Luật sư Mai nộp hồ sơ khiếu kiện hành chính.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 4, 'tiep_nhan', 'System', 'moi_tiep_nhan', 'Tiếp nhận bản di chúc gốc từ khách hàng.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(9, 3, 'upload_ho_so', 'System', 'dang_xu_ly', 'Đã upload bản scan đơn khiếu nại bồi thường.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(10, 2, 'cap_nhat_trang_thai', 'System', 'cho_tham_tam', 'Chuyển trạng thái chờ thẩm định điều kiện niêm yết.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 4, 'giao_viec', 'dang_xu_ly', 'dang_xu_ly', 'Phân công thư ký chuẩn bị bản đồ địa chính.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 3, 'cap_nhat_trang_thai', 'dang_xu_ly', 'dang_xu_ly', 'Đã nhận phản hồi từ cơ quan thuế.', NOW()),
(7, 3, 'tiep_nhan', 'System', 'moi_tiep_nhan', 'Tiếp nhận hồ sơ tranh chấp quyền nuôi con.', NOW());

-- 2. Bình luận nội bộ (case_comments) - 10 bản ghi
INSERT INTO `case_comments` (`case_id`, `user_id`, `content`, `is_internal`, `created_at`) VALUES
(1, 2, 'Hồ sơ này cần lưu ý phần ranh giới phía Tây, có dấu hiệu lấn chiếm.', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1, 3, 'Tôi đã kiểm tra lại sổ đỏ cũ, thông số hoàn toàn khớp.', 1, DATE_SUB(NOW(), INTERVAL 11 DAY)),
(2, 6, 'Khách hàng yêu cầu đẩy nhanh tiến độ thẩm định hợp đồng.', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(4, 3, 'Cơ quan thuế yêu cầu bổ sung chứng từ quyết toán năm 2023.', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(6, 4, 'Di chúc có chữ ký của người làm chứng thứ 2 hơi mờ, cần giám định.', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(9, 6, 'Mức bồi thường đề xuất đang thấp hơn giá thị trường 20%.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 5, 'Em đã hoàn thành thống kê các điều kiện tài chính cơ bản.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 2, 'Đã đặt lịch hẹn với cán bộ địa chính vào sáng thứ 2 tới.', 1, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(5, 3, 'Khách hàng đang lưỡng lự về việc phân chia tài sản chung.', 1, NOW()),
(7, 3, 'Cần thu thập thêm bằng chứng về điều kiện kinh tế của người cha.', 1, NOW());

SET FOREIGN_KEY_CHECKS = 1;
