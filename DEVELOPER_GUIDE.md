# TÀI LIỆU PHÁT TRIỂN CHI TIẾT (TECHNICAL DEVELOPER GUIDE)

Tài liệu này hướng dẫn cách hệ thống vận hành và các bước cụ thể để mở rộng tính năng mới cho cả **Người quản lý dữ liệu** và **Lập trình viên**.

---

## 1. Hệ thống Phân quyền & Phân lập dữ liệu (Data Isolation)
Hệ thống ERP tuân thủ nghiêm ngặt nguyên tắc **"Biết vừa đủ" (Need-to-know basis)** và **"Quản lý theo Tổ đội" (Team-based Management)**.

### **1.1. Logic phân lập Khách hàng & Vụ việc (Hierarchy-Centric):**
- **Admin**: Toàn quyền xem mọi dữ liệu của công ty.
- **Trưởng phòng (Manager 1, 2, 3...)**: 
    - **Cơ chế**: Dựa trên cột `manager_id` trong bảng `employees`.
    - **Vùng dữ liệu**: Chỉ thấy dữ liệu (Vụ việc, Khách hàng, Thống kê) của chính mình và của các nhân viên có `manager_id` trỏ về mình. 
    - **Ngoại lệ Pháp lý**: Trưởng phòng Pháp lý được quyền xem thêm các vụ việc "vô chủ" (IS NULL) để thực hiện điều phối.
- **Nhân viên**: Chỉ thấy dữ liệu mà mình đang trực tiếp phụ trách hoặc tham gia hỗ trợ.

### **1.2. Kỹ thuật lập trình Phân quyền:**
- Luôn sử dụng cú pháp **Closure (`whereIn('id', function($builder)...)`)** để đảm bảo Subquery an toàn.
- Khi tính toán Stats (Dashboard), luôn truyền `managerId` vào Service để lọc đúng phạm vi đội ngũ.

---

## 2. Quy trình Nâng cấp Database (Migration Policy)
- **TUYỆT ĐỐI KHÔNG** sửa trực tiếp vào các khối `CREATE TABLE` đã có trong `mysql.sql` nếu hệ thống đang chạy.
- Mọi thay đổi cấu trúc hoặc dữ liệu mới phải được viết dưới dạng lệnh `ALTER TABLE` hoặc `INSERT` và đặt vào phần **UPDATE LOG** ở cuối file `mysql.sql`.

---

## 3. Cơ chế Chuẩn hóa Dữ liệu (Global Data Sanitization)
Hệ thống sử dụng cơ chế **Auto-Nullify** tại lớp `BaseModel`. 
- Mọi Model kế thừa từ `BaseModel` sẽ tự động chuyển đổi các chuỗi rỗng (`""` hoặc `" "`) từ Form sang giá trị **`NULL`**.
- **Mục tiêu**: Ngăn chặn triệt để lỗi xung đột Khóa ngoại (Foreign Key Constraint) khi người dùng để trống các ô chọn liên kết (vd: ID Sếp, ID Khách hàng...).

---

## 4. Phân loại Vai trò (Roles & Permissions)
Hệ thống hỗ trợ các vai trò mặc định:
1. `Admin`: Toàn quyền.
2. `Mod`: Điều hành cấp cao.
3. `Trưởng phòng`: Quản lý tổ đội và vụ việc thuộc bộ phận.
4. `Nhân viên chính thức`: Nghiệp vụ chuyên môn.
5. `Thực tập sinh`, `Thử việc`, `Học việc`: Quyền hạn cơ bản, báo cáo cho sếp trực tiếp.

---

## 5. Module Quy trình (Workflow Template System)
Hệ thống vận hành dựa trên các "Bản mẫu" (Templates) để tự động hóa Timeline.
- `workflow_templates`: Định nghĩa tên quy trình.
- `workflow_template_steps`: Các bước con và thời hạn (`duration_days`).
- `case_steps`: Thực thể thực tế của quy trình trong từng vụ việc.

---

## 6. Logic Lõi & Tư duy phát triển (Core Principles)
1.  **Logic tại Service**: Controller chỉ điều hướng. Mọi truy vấn phức tạp phải nằm trong Service.
2.  **SQL Hợp nhất**: Mọi thay đổi Database phải được cập nhật vào file `mysql.sql` duy nhất.
3.  **Hierarchy over Department**: Ưu tiên phân quyền theo cấp quản lý trực tiếp (`manager_id`) hơn là theo ID phòng ban (`department_id`) để đảm bảo tính riêng tư giữa các tổ đội.

---

## 7. Hệ thống Nhãn dán thông minh (L.A.N Smart Tag)
Hệ thống nhãn dán giúp phân loại và quản trị dữ liệu đa chiều, thay thế cho cách tạo thư mục truyền thống cồng kềnh.

### **7.1. Cấu trúc Đa hình (Polymorphic Architecture):**
- **Bảng `tags`**: Lưu trữ định nghĩa nhãn (Tên, Màu sắc, Loại).
- **Bảng `entity_tags`**: Cầu nối đa hình. Cho phép gắn 1 nhãn vào nhiều thực thể (`cases`, `customers`, `documents`).
- **Phân cấp Thẻ (Tag Scoping)**:
    - **Global**: Thẻ chung toàn hệ thống (Của Admin/Management).
    - **Private**: Thẻ cá nhân (Chỉ nhân viên sở hữu thẻ mới nhìn thấy và sử dụng cho hồ sơ của họ).

### **7.2. Tiêu chuẩn Thẩm mỹ (UI/UX Standards):**
- Luôn sử dụng lớp CSS `.tag-badge-premium` với màu Pantone/Pastel để hiển thị.
- Các hạt Tag (`Pills`) phải bo tròn 20px và có hiệu ứng hover mượt mà.

---

## 8. Tính năng Sửa Lõi: Đổi Quy Trình Thiết Lập Cho Vụ Việc
Trong quá trình khởi tạo hồ sơ vụ việc pháp lý, nhân sự có thể thao tác chọn nhầm Template (Quy trình mẫu). Tính năng này cho phép "Sửa sai" ngay từ đầu.
- **Quyền truy cập:** CHỈ CÓ Quản trị viên hệ thống (Admin) HOẶC Trưởng phòng ban Pháp lý mới được phép sửa đổi.
- **Rào cản an ninh:** Trưởng các phòng ban khác không được phép sửa vòng đời pháp lý của khách hàng.
- **Xác thực trạng thái (Strict State Checking):** NẾU Số lượng bước đã hoàn thành (completed) > 0 -> Từ chối thay đổi để bảo toàn KPI người trước.
- **Giao dịch (Transaction Wipes):** 
    1. Xóa thô bạo (Wipe-out) tất cả các dòng lưu trong `case_steps` hiện tại.
    2. Cấu hình ID mẫu mới và gọi thuật toán `initializeFlowForCase` để tạo bản lưu vết tương lai.
- **Tích hợp:** Nhúng ngầm vào màn hình Sửa vụ việc (`edit.php`), hệ thống tự phát hiện ID thay đổi để thực thi Reset ảo mà không làm phình UI.

---

## 9. Cẩm Nang Tri Thức (Knowledge Base / Wiki Nội Bộ)
Module Knowledge Management sinh ra nhằm bảo vệ chất xám công ty và làm tài liệu training nội bộ.
- **Kiến trúc dữ liệu:**
    - `knowledge_base`: Chứa nội dung, khóa ngoại móc lỏng với Vụ việc (Case Study).
    - `knowledge_votes`: Bảng vệ tinh chặn hành vi (1 user vote tự phát quá nhiều lần để thao túng ranking).
- **Phân quyền (RBAC):**
    - Quyền Đọc: Toàn bộ thành viên (Không áp dụng Data Isolation).
    - Quyền Chỉnh sửa/Xóa bài: Giới hạn nghiêm khắc cho **Tác giả bài đăng** OẶCH **Quản trị viên (Admin)**.
- **Bảo mật:** `KnowledgeController` chặn tuyệt đối việc nhét/tiêm giá trị đếm View và Vote thông qua POST Form Request. Mọi update View và Vote được chốt hạ bằng Route/Transaction gián tiếp.
- **Tích hợp chéo:** Nhúng thẳng nút "Rút kinh nghiệm" vào Chi tiết vụ việc (Chuyển tiếp `case_id` làm Contextual ID). Vận dụng chéo `TagService` để gắn nhãn phân loại (vd: `Hình sự`, `Tư vấn Doanh nghiệp`). 

---

## 10. Hệ thống Master Sync & Auto-Registry (Quy chuẩn Đồng bộ Tập trung)
Đây là quy chuẩn bắt buộc cho mọi Module mới khi gia nhập hệ thống L.A.N ERP để đảm bảo tính nhất quán và tự động hóa cao nhất.

### **10.1. Tự động Khai báo & Nhận diện (Auto-Discovery):**
- **Nguyên tắc**: Không viết các hàm khởi tạo quyền (`permissions`) hay đăng ký module nhãn dán (`tags`) rời rạc.
- **Thực thi**: Mọi Controller phải khai báo Metadata chuẩn tại phần đầu Class:
    - `public static $modulePermissions`: Cho hệ thống phân quyền (RBAC).
    - `public static $taggable`: Cho hệ thống nhãn dán thông minh (Smart Tags).

### **10.2. Cỗ máy quét Master Sync (`/perm-fix/sync`):**
- Đây là "Source of Truth" duy nhất để cập nhật các bảng Master Registry.
- Khi có Module mới, chỉ cần chạy URL này, hệ thống sẽ tự động:
    1. Quét toàn bộ thư mục `Controllers`.
    2. Đăng ký các quyền hạn mới vào Database (Bảng `permissions`).
    3. Cập nhật Registry các module được phép gắn nhãn (`tag_modules.json`).
- **Lợi ích**: Giảm thiểu sai sót do quên đăng ký cấu hình, giúp Module mới "Sẵn sàng chạy" ngay sau khi Code xong Core logic.

---

*Cập nhật lần cuối: 07/04/2026 - Biên soạn Quy chuẩn Số 10: Master Sync & Registry.*
