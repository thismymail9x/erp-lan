# TÀI LIỆU PHÁT TRUYỂN CHI TIẾT (TECHNICAL DEVELOPER GUIDE)

Tài liệu này hướng dẫn cách hệ thống vận hành và các bước cụ thể để mở rộng tính năng mới cho cả **Người quản lý dữ liệu** và **Lập trình viên**.

---

## 1. Hệ thống Phân quyền (RBAC+ Overrides)
Hệ thống kết hợp giữa quyền mặc định của Nhóm và quyền riêng biệt của từng người.

### **Cách thêm một Quyền mới (Ví dụ: Quyền "Xóa Vụ Việc"):**
1.  **Dữ liệu (Data Developer):** 
    - Mở file `mysql.sql`.
    - Thêm dòng: `INSERT INTO permissions (name, module_group, description) VALUES ('case.delete', 'Vụ việc', 'Cho phép xóa hồ sơ vụ việc');`.
    - Gán cho Admin: `INSERT INTO roles_permissions (role_id, permission_id) VALUES (1, ID_CUA_QUYEN_MOI);`.
2.  **Lập trình (Coder):**
    - Kiểm tra tại Controller để chặn hành động: `if (!has_permission('case.delete')) throw new Exception("Không đủ thẩm quyền");`.
    - Kiểm tra tại View để ẩn/hiện nút: `<?php if(has_permission('case.delete')) { ?> <button>Xóa</button> <?php } ?>`.

---

## 2. Module Quy trình (Workflow Template System)
Hệ thống chạy hoàn toàn dựa trên các "Bản mẫu" để tự động hóa Timeline.

### **Cách thêm một Quy trình nghiệp vụ mới (Ví dụ: Quy trình "Công chứng"):**
1.  **Dữ liệu (Data Developer):** 
    - Vào giao diện **Quản lý Quy trình mẫu** -> Tạo mới "Quy trình Công chứng".
    - Thiết lập các **Bước** (VD: Bước 1: Tiếp nhận - 1 ngày, Bước 2: Soạn thảo - 2 ngày, Bước 3: Ký tên - 1 ngày).
2.  **Lập trình (Coder):**
    - Thông thường không cần sửa code nếu logic chỉ là tuần tự. 
    - Nếu bước đó cần xử lý đặc biệt (ví dụ: Tự động gửi email khi tới bước Ký tên), hãy mở `WorkflowService->completeStep` và thêm logic dựa vào `template_step_id`.

---

## 3. Module Chấm công (Smart Attendance)
Kết hợp xác thực IP LAN, GPS, Camera và **Browser Token**.

### **Cách giải quyết lỗi IP Động (Dynamic IP):**
Hệ thống sử dụng cơ chế **"Máy tính được ủy quyền"**:
1.  **Cơ chế**: Admin đăng nhập trên máy văn phòng -> Nhấn "Ủy quyền máy tính này" -> Token được lưu vào `localStorage`.
2.  **Xác thực**: Khi chấm công, nếu phát hiện `office_security_token` trong trình duyệt, hệ thống sẽ bỏ qua kiểm tra IP/GPS/Ảnh.
3.  **Dữ liệu (Data Developer)**: Cấu hình token nằm ở bảng `system_settings` với key `office_security_token`.
4.  **Lập trình (Coder)**:
    - Logic kiểm tra token nằm trong `AttendanceService->isInternalAccess`.
    - Giao diện Admin để cấp quyền nằm cuối file `attendance/index.php`.

---

## 4. Quản lý Vụ việc & Khách hàng
Trái tim của hệ thống ERP, liên kết chặt chẽ giữa CRM và Xử lý nghiệp vụ.

### **Cách thêm một trường thông tin mới (Ví dụ: Thêm "Số tiền tạm ứng" cho Vụ việc):**
1.  **Dữ liệu (Data Developer):**
    - Thêm cột vào bảng `cases` trong `mysql.sql`: `ALTER TABLE cases ADD COLUMN deposit_amount DECIMAL(15,2) DEFAULT 0;`.
2.  **Lập trình (Coder):**
    - Cập nhật `$allowedFields` trong `CaseModel.php` để cho phép lưu dữ liệu.
    - Thêm ô nhập liệu vào View `cases/create.php` và `cases/edit.php`.

---

## 5. Tư duy Logic Lõi (Core Principles)
1.  **Logic để ở Service**: Tuyệt đối không viết logic tính toán tại Controller. Hãy tạo Service mới nếu module phức tạp.
2.  **Hằng số là bắt buộc**: Mọi tham số (Role name, Dept ID, Status) phải nằm trong `AppConstants`.
3.  **Cú pháp View**: Luôn dùng `{ }` (VD: `foreach($a as $b) { ... }`) để đồng nhất dự án.
4.  **SQL Tập trung**: Mọi câu lệnh thay đổi cấu trúc DB phải được ghi vào cuối file `mysql.sql`.

---
*Cập nhật: 25/03/2026 - Chi tiết hóa theo yêu cầu người dùng.*
