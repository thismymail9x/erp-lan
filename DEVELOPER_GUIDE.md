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

## 11. Phân Trang Bắt Buộc (Mandatory Pagination)
Mọi trang danh sách (Index/List View) trong hệ thống **BẮT BUỘC** phải có phân trang. Đây là nguyên tắc chống quá tải dữ liệu.

### **11.1. Quy tắc triển khai:**
- **Controller**: Luôn sử dụng `$model->paginate($perPage)` thay vì `findAll()` khi hiển thị danh sách.
- **View**: Luôn hiển thị component `<?= $pager->links() ?>` ở cuối danh sách.
- **AJAX**: Nếu view dùng AJAX để tải dữ liệu (như Knowledge Feed), phải hook sự kiện click vào pagination links để load nội dung mới mà không reload trang.

### **11.2. Giá trị mặc định:**
- Số dòng mỗi trang: **10-15** (tùy module).
- Ngoại lệ: Dashboard (thống kê tổng), Modal chọn nhân sự (Select2 infinite scroll).

---

## 12. Quản lý Nghỉ phép & Phối hợp Nhân sự (Leave & Handover Rule)
Module Nghỉ phép được thiết kế để đảm bảo tính liên tục của vận hành công ty thông qua các rào cản báo trước "hợp pháp".

### **12.1. Quy tắc báo trước (Rule #1 - Notice Period):**
Hệ thống tự động tính toán tổng số ngày nghỉ và áp dụng bộ kiểm soát báo trước (Tính từ thời điểm làm đơn đến ngày bắt đầu nghỉ):
- **Nghỉ 1 ngày**: Báo trước ≥ 1 ngày.
- **Nghỉ 2-4 ngày**: Báo trước ≥ 3 ngày.
- **Nghỉ ≥ 5 ngày**: Báo trước ≥ 7 ngày.
- **Nghỉ đột xuất (Emergency)**: Cờ ghi đè (`is_emergency`) cho phép bỏ qua các ràng buộc báo trước nhưng yêu cầu giải trình lý do khẩn cấp và thường bị kiểm soát chặt chẽ hơn khi phê duyệt.

### **12.2. Cơ chế Bàn giao (Handover):**
- **Người nhận bàn giao (Optional)**: Nhân viên có thể chọn một đồng nghiệp để bàn giao công việc. Hệ thống sẽ tự động gửi thông báo (`NotificationService`) cho người nhận bàn giao kèm nội dung bàn giao chi tiết.
- **Nội dung bàn giao**: Văn bản mô tả các đầu việc cần hỗ trợ trong kỳ nghỉ. Trường này không bắt buộc nhưng khuyến khích nhập nếu nghỉ dài ngày.

### **12.3. Quy chuẩn Thẩm mỹ (Rule #8 - Premium Aesthetic):**
- Giao diện sử dụng dải màu Apple, icon trực quan thay thế cho text (Sign-out/Sign-in icons).
- Hiển thị badge tổng số ngày nghỉ dạng minimal (`badge-success-minimal`).
- Padding và Spacing được tối ưu cho mật độ hiển thị cao (Compact Design).


---

## 13. Cơ chế Bàn giao & KPI chi tiết (Handover & KPI Attribution Rule)
Module Vụ việc được nâng cấp để giải quyết bài toán luân chuyển nhân sự giữa chừng mà không làm sai lệch bảng lương thưởng KPI.

### **13.1. Phân bộ KPI theo Bước (Step-level Attribution):**
- **Nguyên tắc**: KPI thuộc về người sở hữu bước công việc đó tại thời điểm thực hiện.
- **Dữ liệu**:
    - `case_steps.assigned_to`: Người chịu trách nhiệm thực hiện chính (Người sở hữu KPI tiềm năng).
    - `case_steps.completed_by`: Người thực tế đã nộp bài (Người sở hữu KPI thực nhận).
- **Quy tắc Người Phụ Trách (Primary Person Rule)**: Bất kể ai bấm nút "Nộp bài/Trình duyệt" (như Người hỗ trợ, TTS), hệ thống luôn ưu tiên ghi nhận KPI cho ID nằm trong cột `assigned_to` của bước đó.

### **13.2. Cơ chế Bàn giao tự động (Auto-Handover Integration):**
- Khi vụ việc thay đổi nhân sự phụ trách (`assigned_lawyer_id` hoặc `assigned_staff_id` thay đổi):
    - Hệ thống tự động quét tất cả các bước **chưa hoàn thành** (Pending) và đổi `assigned_to` sang cho người mới.
    - Các bước **đã hoàn thành** (Completed) được giữ nguyên người cũ để bảo toàn lịch sử thu nhập.

### **13.3. Logic Báo cáo (KPI Reporting Strategy):**
- Hệ thống báo cáo (`KpiService`) được thiết kế tách rời khỏi trạng thái vụ việc:
    - **Earned KPI**: Truy vấn trực tiếp từ bảng `case_steps` dựa trên cột `completed_by`. Đảm bảo nhân sự rời khỏi vụ việc vẫn xem được các khoản thưởng mình đã nỗ lực làm xong.
    - **Potential KPI**: Truy vấn dựa trên cột `assigned_to` của các bước chưa xong.

---

*Cập nhật lần cuối: 21/04/2026 - Bổ sung Quy tắc Số 13: Cơ chế Bàn giao & KPI chi tiết.*

## T�nh nang Ngh? ph�p N?a ng�y (Half-day Leave)
- **M� t?**: Cho ph�p nh�n vi�n t?o don ngh? ph�p v?i kho?ng th?i gian ch? m?t n?a ng�y (S�ng/Chi?u).
- **�?i tu?ng**: T?t c? nh�n s? c� quy?n leave.manage.
- **Quy tr�nh**: C?p nh?t Database (leave_duration trong b?ng leave_requests), thay d?i UI t? kh�a Ng�y k?t th�c tr�ng v?i Ng�y b?t d?u, v� Service t? d?ng n?i suy t?ng s? ng�y l�  .5.

---

## 12. Ph�n h? Truy?n th�ng MKT (MKT Hub)
- **M?c d�ch:** X�y d?ng quy tr�nh kh�p k�n gi?a nh�n vi�n hi?n tru?ng (thu th?p tu li?u, ?nh th?c t?) v� b? ph?n MKT (Ki?m duy?t, t?i uu SEO, dang b�i MXH). Gi�p c�ng ty lu�n c� ngu?n content d?i d�o, ch�n th?c.
- **Quy?n h?n:**
  - Nh�n vi�n c� quy?n mkt.hub ho?c m?c d?nh du?c c?p quy?n g?i tu li?u.
  - B? ph?n MKT ho?c Qu?n l� du?c c?p quy?n mkt.manage c� th? xem to�n b? tu li?u, duy?t ?nh, v� th?c hi?n x�a d?n d?p h? th?ng.
- **T�nh nang tr?ng t�m:**
  - **Auto-Nullify & Soft Delete:** �p d?ng cho b?ng mkt_materials (Tu li?u) d? qu?n l� l?ch s? an to�n.
  - **SEO Naming:** T? d?ng b?t t�n g?c c?a file ho?c nh�n vi�n t? g�n t�n chu?n SEO (vd: 	u-van-ly-hon.jpg) ngay t? l�c upload, ph?c v? d?y m?nh t?i uu SEO Facebook/Google.
  - **Clear Data:** Co ch? c?ng x�a c�c b?n nh�p r�c/t? ch?i d? ch?ng d?y b? nh? ? c?ng.
  - **Data Isolation:** Nh�n vi�n ch? xem du?c ?nh m�nh t? up, MKT xem du?c ?nh c?a to�n b? nh�n vi�n.

---

## MODULE BẢO MẬT: XÁC THỰC 2 LỚP (2FA) - Cập nhật 06/05/2026

### 1. Mục tiêu
Bảo vệ các dữ liệu nhạy cảm (Lương, Ngân hàng, CCCD) khỏi việc xem trộm ngay cả khi nhân viên đã đăng nhập. Chỉ những người có mã OTP từ ứng dụng điện thoại mới có thể mở khóa dữ liệu này.

### 2. Thành phần kỹ thuật
- **Service:** SecurityService - Xử lý tạo mã Secret và kiểm tra OTP.
- **Controller:** SecurityController - API endpoints cho frontend.
- **Cấu trúc Database:** Bảng users bổ sung cột two_factor_secret.
- **Thư viện sử dụng:** sonata-project/google-authenticator.

### 3. Cách thức hoạt động
1. **Khởi tạo:** Khi người dùng bấm "Mở khóa", hệ thống kiểm tra nếu chưa có Secret Key sẽ tạo mới và trả về QR Code.
2. **Xác thực:** Người dùng nhập mã 6 số. Nếu đúng, hệ thống lưu sensitive_data_verified = true vào Session.
3. **Hiển thị:** View kiểm tra Session để quyết định có gỡ bỏ lớp mờ (blur) và cho phép chỉnh sửa hay không.

### 4. Quy tắc phát triển liên quan
- Luôn sử dụng SecurityService cho mọi logic liên quan đến bảo mật đa nhân tố.
- Không lưu mã OTP vào database, chỉ lưu Secret Key.
- Khi thêm dữ liệu nhạy cảm mới, hãy bọc chúng bằng class CSS .sensitive-masked và kiểm tra quyền qua Session.

---
