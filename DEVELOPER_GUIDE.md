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

## Tính năng Nghỉ phép Nửa ngày (Half-day Leave)
- **Mô tả**: Cho phép nhân viên tạo đơn nghỉ phép với khoảng thời gian chỉ một nửa ngày (Sáng/Chiều).
- **Đối tượng**: Tất cả nhân sự có quyền leave.manage.
- **Quy trình**: Cập nhật Database (leave_duration trong bảng leave_requests), thay đổi UI tự khóa Ngày kết thúc trùng với Ngày bắt đầu, và Service tự động nội suy tổng số ngày là 0.5.

---

## 12. Phân hệ Truyền thông MKT (MKT Hub)
- **Mục đích:** Xây dựng quy trình khép kín giữa nhân viên hiện trường (thu thập tư liệu, ảnh thực tế) và bộ phận MKT (Kiểm duyệt, tối ưu SEO, đăng bài MXH). Giúp công ty luôn có nguồn content dồi dào, chân thực.
- **Quyền hạn:**
  - Nhân viên có quyền mkt.hub hoặc mặc định được cấp quyền gửi tư liệu.
  - Bộ phận MKT hoặc Quản lý được cấp quyền mkt.manage có thể xem toàn bộ tư liệu, duyệt ảnh, và thực hiện xóa dọn dẹp hệ thống.
- **Tính năng trọng tâm:**
  - **Auto-Nullify & Soft Delete:** Áp dụng cho bảng mkt_materials (Tư liệu) để quản lý lịch sử an toàn.
  - **SEO Naming:** Tự động bắt tên gốc của file hoặc nhân viên tự gán tên chuẩn SEO (vd: tu-van-ly-hon.jpg) ngay từ lúc upload, phục vụ đẩy mạnh tối ưu SEO Facebook/Google.
  - **Clear Data:** Cơ chế cứng xóa các bản nháp rác/từ chối để chống đầy bộ nhớ ổ cứng.
  - **Data Isolation:** Nhân viên chỉ xem được ảnh mình tự up, MKT xem được ảnh của toàn bộ nhân viên.

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

## 10. Module Lịch làm việc & Công tác (Work Schedule)
Module này được thiết kế để nhân sự toàn công ty có thể thông báo và theo dõi lịch trình của nhau, tăng tính phối hợp và minh bạch.

### **10.1. Đặc điểm nổi bật:**
- **Giao diện Lịch (Calendar View)**: Sử dụng FullCalendar 6 với phong cách Apple-Minimal, hỗ trợ xem chi tiết nhanh qua Tooltip (Tippy.js).
- **Phân loại màu sắc**: Công việc tại văn phòng (Màu xanh - #3498db) và Lịch công tác (Màu đỏ - #e74c3c).
- **Thông báo tự động**: Khi một lịch trình mới được tạo, hệ thống sẽ tự động gửi thông báo đến toàn thể nhân viên thông qua NotificationService.

### **10.2. Quy tắc Phân quyền (RBAC):**
- **Xem lịch trình (work_schedule.view)**: Toàn bộ nhân viên chính thức đều có quyền xem lịch trình của đồng nghiệp để phối hợp công việc.
- **Quản lý lịch trình (work_schedule.manage)**: 
    - Nhân viên được quyền tạo/sửa/xóa lịch trình của chính mình.
    - Admin và Trưởng phòng có quyền quản lý lịch trình của cấp dưới.
    - Hệ thống thực hiện kiểm tra quyền sở hữu nghiêm ngặt tại Service layer (Rule #7).

### **10.3. Tích hợp Hệ thống (Compliance Rule #10):**
- **Master Sync**: Controller WorkScheduleController khai báo modulePermissions để tự động đăng ký vào hệ thống phân quyền khi chạy /perm-fix/sync.
- **Audit Logs**: Mọi thao tác thêm/sửa/xóa đều được ghi lại trong system_logs thông qua SystemLogService.
---

## 11. Module Tích hợp Zalo OA (Zalo OA Integration)
Module Zalo OA giúp quản trị tập trung và khai thác hệ sinh thái Zalo để chăm sóc khách hàng, tiếp thị lại, và đánh giá chất lượng nhân sự tư vấn. Đây là giải pháp chống mất khách hàng "Nhân sự cầm khách đi" hiệu quả nhất.

### **11.1. Các tính năng cốt lõi (5 Core Features):**
1. **Quản lý tập trung & Chống mất khách:** Đồng bộ toàn bộ hội thoại từ Zalo OA về ERP thông qua Webhook. Lưu trữ lịch sử vĩnh viễn trong bảng `zalo_messages`.
2. **Tự động hóa quy trình (Automation):** Hệ thống cấp mã MID ngay khi có tin nhắn đầu tiên. Gắn Tag phân loại dựa trên nội dung chat (vd: `#DatDai`, `#HinhSu`).
3. **Tiếp thị lại (Remarketing & ZNS):** Cho phép tạo chiến dịch gửi tin nhắn hàng loạt (Zalo Notification Service) dựa trên tệp thẻ (Tags).
4. **Quản lý hiệu suất tư vấn:** Theo dõi thống kê thời gian phản hồi (Response Time) và đánh giá sao (5-Star Survey) của nhân viên.
5. **Cá nhân hóa trải nghiệm:** Tự động Pop-up thông tin hồ sơ CRM khi khách hàng cũ tương tác thông qua `insight-panel`.

### **11.2. Cấu trúc Database (Zalo Scheme):**
- `zalo_followers`: Bảng lưu trữ thông tin và mã định danh (MID) của khách hàng.
- `zalo_messages`: Lưu lịch sử tin nhắn vĩnh viễn (Kể cả khi nhân sự xóa tin).
- `zalo_campaigns`: Quản trị chiến dịch gửi ZNS (Remarketing).
- `zalo_surveys`: Thống kê kết quả đánh giá chất lượng tư vấn sau mỗi phiên chat.

### **11.3. Phân quyền (RBAC):**
- **zalo.view:** Được phép xem Dashboard Zalo và Hội thoại.
- **zalo.chat:** Được phép trả lời tin nhắn trực tiếp từ ERP.
- **zalo.campaign:** Quyền cho bộ phận Marketing để setup chiến dịch Remarketing.
- **zalo.config:** Dành riêng cho Admin/Giám đốc để cấu hình API Key và Webhook.
(Tất cả được khai báo tự động qua `$modulePermissions` trong `ZaloController` và đồng bộ qua `/perm-fix/sync`).

---

## 12. Module Phân bổ Nhân sự Chăm sóc & Tư vấn Khách hàng (Care Staff Assignment) - Cập nhật 19/05/2026

### **12.1. Mục tiêu & Nghiệp vụ:**
Độc lập vai trò chăm sóc/tư vấn khách hàng với nhân viên tạo hồ sơ (`created_by`) và nhân viên thụ lý vụ việc (`assigned_lawyer_id` / `assigned_staff_id`). Giúp doanh nghiệp quản trị rõ ràng quy trình CRM: một người tạo, một người chăm sóc định kỳ, và các luật sư chuyên môn thụ lý hồ sơ.

### **12.2. Thành phần kỹ thuật:**
- **Cột Database bổ sung:** `customers.assigned_care_staff_id` liên kết khóa ngoại tới `employees.id` (ON DELETE SET NULL).
- **Phân lập Dữ liệu & Phân quyền (Data Isolation):**
  - Cấp quản lý/Trưởng phòng có quyền xem và quản lý tất cả khách hàng do thành viên trong phòng ban của mình tạo hoặc phụ trách chăm sóc.
  - Nhân viên thông thường (`ROLE_NHAN_VIEN`) có quyền truy cập (xem hồ sơ 360 độ và chỉnh sửa) những khách hàng mà họ là người tạo trực tiếp (`created_by`) HOẶC là nhân sự chăm sóc tư vấn được chỉ định (`assigned_care_staff_id`).
- **Giao diện & UI/UX:**
  - Tích hợp ô chọn "Nhân sự phụ trách chăm sóc tư vấn" tại Step 3 (CRM & Phân loại) của Wizard tạo mới khách hàng (`create.php`) và form chỉnh sửa (`edit.php`).
  - Hiển thị trực quan thông tin Nhân sự chăm sóc bằng Badge màu Apple-Blue tại trang chi tiết hồ sơ (`show.php`) và hiển thị dưới dạng icon `fa-user-shield` nhỏ gọn tại bảng danh sách khách hàng (`index_table.php`).
- **Kiến trúc MVC:**
  - Logic phân lập dữ liệu truy xuất và thống kê danh sách được phân tách rõ ràng trong `CustomerService` và `CustomerController`, đảm bảo tuân thủ nghiêm ngặt nguyên tắc MVC (Controller không chứa query logic trực tiếp).

### **12.3. Cải tiến Inline AJAX Edit & Bộ lọc thời gian (Nâng cấp ngày 19/05/2026):**
- **Cập nhật Nhân sự trực tiếp (AJAX Inline Editing):**
  - Cột "Nhân sự tư vấn" được phân tách độc lập trong bảng danh sách khách hàng.
  - Khi đúp click (double click) vào tên nhân sự hiện tại, hoặc click đơn (single click) vào chữ "Trống" (nếu chưa gán), trường thông tin sẽ tự động chuyển thành một thẻ `<select>` dropdown.
  - Sau khi người dùng chọn nhân viên mới, một yêu cầu POST AJAX sẽ được gửi tới API `/customers/update-care-staff/(:num)`.
  - Trên API thành công, hệ thống tự động cập nhật hiển thị, đồng bộ ID, và kích hoạt hiệu ứng micro-animation (Highlight màu xanh lá cây dịu nhẹ trong 1 giây) để thông báo trực quan cho người dùng.
- **Thống kê số vụ việc Chính xác (Dynamic Case Query Count):**
  - Khắc phục sự không chính xác do lệch cache đồng bộ: thay thế truy vấn dữ liệu từ cột cache `customers.total_cases` bằng một Subquery động, chính xác 100% thời gian thực:
    ```sql
    (SELECT COUNT(*) FROM cases WHERE cases.customer_id = customers.id AND cases.deleted_at IS NULL) as total_cases
    ```
- **Cột Ngày tạo & Bộ lọc Nâng cao (Month/Year Advanced Filtering):**
  - Thêm cột "Ngày tạo" hiển thị rõ ràng định dạng `dd/mm/yyyy` ngay sau cột "Vụ việc".
  - Bổ sung bộ lọc "Tháng" và "Năm" tại thanh tìm kiếm nâng cao. Dữ liệu được lọc động thông qua các hàm SQL `MONTH(customers.created_at)` và `YEAR(customers.created_at)` trong `CustomerController::index()`.

---

## 13. Phân Phệ Chăm Sóc Khách Hàng Cũ (CSKH) & Loyalty Program - Cập nhật 22/05/2026

### **13.1. Nghiệp vụ & Phạm vi (Phase 1):**
- **Mục tiêu**: Tự động hóa quy trình chăm sóc khách hàng cũ ngay sau khi họ hoàn tất dịch vụ hoặc hồ sơ pháp lý, nhằm tối đa hóa cơ hội tái ký và giới thiệu khách hàng mới.
- **Phân loại Khách hàng A/B/C**:
  - **Nhóm A (VIP)**: Khách hàng lớn, mang lại doanh thu cao hoặc có khả năng kết nối mạnh. CSKH cá nhân hóa mạnh mẽ bởi Giám đốc/Luật sư cấp cao.
  - **Nhóm B (Phổ thông)**: Khách hàng sử dụng dịch vụ tiêu chuẩn. CSKH bán tự động qua kênh Hotline/Zalo định kỳ.
  - **Nhóm C (Tiềm năng nguội)**: Đã tư vấn nhưng chưa ký, nuôi dưỡng định kỳ bằng bản tin pháp lý để remarketing.
- **Quy trình CSKH 3 Giai đoạn**:
  - **Giai đoạn 1 (Phase 1)**: Chăm sóc ngay sau dịch vụ (Ngày 1 - 7). Gửi lời cảm ơn, khảo sát độ hài lòng (Feedback) và rà soát phân nhóm.
  - **Giai đoạn 2 (Phase 2)**: Nuôi dưỡng & Hỗ trợ giá trị (Ngày 7 - 30). Hỏi thăm tình hình vận hành thực tế, gửi văn bản luật hữu ích, tặng voucher.
  - **Giai đoạn 3 (Phase 3)**: Kết nối & Remarketing dài hạn (Trên 30 ngày). Gửi bản tin tháng, gọi điện định kỳ 60 ngày, giới thiệu dịch vụ mới.

### **13.2. Thành phần kỹ thuật & Cơ sở dữ liệu:**
- **Trường dữ liệu mở rộng (`customers`):**
  - `customer_segment` (`enum('vip', 'regular', 'potential')`): Phân loại A/B/C tự động hoặc thủ công.
  - `care_status` (`enum('new', 'phase1', 'phase2', 'phase3', 'completed', 'dormant')`): Trạng thái chu kỳ CSKH.
  - `service_completed_date` (`date`): Ngày hoàn thành vụ việc/dịch vụ làm mốc kích hoạt tự động.
- **Bảng `customer_care_plans`:** Quản lý vòng đời chăm sóc, lưu nhân sự phụ trách và trạng thái hoàn thành.
- **Bảng `customer_care_tasks` (Checklists):** Lưu vết các đầu việc chi tiết trong từng giai đoạn (Zalo, Gọi điện, Email, Gửi quà) kèm hạn chót và ghi chú phản hồi.
- **Bảng `customer_loyalty`:** Quản lý chương trình khách hàng thân thiết: điểm tích lũy (`points`), phân hạng thẻ VIP (`loyalty_tier`), đặc quyền (`benefits`), và mã giới thiệu (`referral_code`).

### **13.3. Thuật toán Điểm thưởng & Phân hạng VIP (Loyalty Logic):**
- **Cơ chế Tích Điểm:**
  - Cộng **10 điểm** khi hoàn thành mỗi task chăm sóc khách hàng tương tác thành công.
  - Cộng **100 điểm** khi khách cũ giới thiệu thành công một khách hàng mới sử dụng dịch vụ.
- **Quy tắc Nâng hạng tự động (`CustomerCareService::calculateLoyaltyTier`):**
  - **VIP**: Doanh thu tích lũy ≥ 100tr HOẶC điểm tích lũy ≥ 1000.
  - **Gold (Vàng)**: Doanh thu tích lũy ≥ 50tr HOẶC điểm tích lũy ≥ 500.
  - **Silver (Bạc)**: Doanh thu tích lũy ≥ 20tr HOẶC điểm tích lũy ≥ 200.
  - **Standard (Tiêu chuẩn)**: Dưới 20tr và dưới 200 điểm.
- **Mã giới thiệu (Referral Code):** Được sinh ngẫu nhiên duy nhất dạng `REF + ID khách hàng + 4 ký tự ngẫu nhiên`.

### **13.4. Phân Quyền & Tích hợp (RBAC Integration):**
- **Quyền hạn chi tiết:**
  - `care.view`: Xem tổng quan Dashboard CSKH, KPI báo cáo và danh sách phân nhóm A/B/C.
  - `care.manage`: Khởi tạo kế hoạch CSKH, tick hoàn thành checklist, thêm công việc và chỉnh sửa phân nhóm thủ công.
  - `care.view_all`: Bypass phân lập dữ liệu để quản lý CSKH trên toàn hệ thống (dành cho Ban Giám Đốc/Admin).
- **Tự động đồng bộ (Auto-Sync):** Kế thừa hoàn toàn Compliance Rule #10. Tất cả quyền được định nghĩa trong `CustomerCareController::$modulePermissions` và tự động đồng bộ vào Database Master khi truy cập `/perm-fix/sync` (hoặc qua script CLI `sync_permissions.php`).

---

## 14. Hệ thống Tiến độ & SLA Chăm sóc Khách hàng (SLA & Care Progress System) - Cập nhật 25/05/2026

### **14.1. Tổng quan & Thiết kế Cấu hình Động:**
Hệ thống quản lý thời hạn xử lý (SLA) chăm sóc khách hàng được thiết kế theo mô hình **Quy trình cấu hình động**, cho phép tự do thiết lập tên các bước, số giờ thực hiện giới hạn (SLA Hours), màu sắc hiển thị và thứ tự sắp xếp thay vì lập trình cứng trong mã nguồn.

### **14.2. Cấu trúc Database:**
- **`customer_sla_settings`**: Lưu trữ danh mục các trạng thái tư vấn và cài đặt SLA.
  - `status_key` (VARCHAR): Khóa định danh hệ thống (nhận diện logic không dấu/không cách).
  - `status_name` (VARCHAR): Tên trạng thái hiển thị trên View.
  - `sla_hours` (INT): Số giờ giới hạn SLA cho bước (Nhập `0` nếu không giới hạn).
  - `color` (VARCHAR): Mã màu Hex hiển thị đại diện (vd: `#ff3b30`, `#34c759`).
  - `sort_order` (INT): Thứ tự hiển thị trong quy trình.
- **`customer_sla_history`**: Lưu nhật ký vòng đời tiến độ thực tế của khách hàng.
  - `customer_id` (INT): Khóa ngoại liên kết khách hàng.
  - `assigned_staff_id` (INT): Nhân viên chịu trách nhiệm tại thời điểm thực thi.
  - `status` (VARCHAR): Khóa trạng thái tư vấn.
  - `start_time` & `end_time` (DATETIME): Mốc bắt đầu và kết thúc bước thực tế.
  - `due_time` (DATETIME): Thời hạn chót (`start_time + sla_hours`).
  - `sla_status` (`in_progress`, `achieved`, `completed_late`, `overdue`): Kết quả đo lường SLA.

### **14.3. Nguyên lý Xử lý Lõi (Core Service Logic - `CustomerSlaService`):**
- **Chuyển Trạng Thái (`transitionStatus`)**:
  - Đóng tiến trình SLA cũ (nếu có): So sánh `now` với `due_time` để gắn nhãn `achieved` (Đúng hạn) hoặc `completed_late` (Xong trễ).
  - Khởi tạo tiến trình SLA mới: Tra cứu số giờ quy định từ bảng cấu hình, tính toán `due_time = start_time + sla_hours` nếu có nhân sự được phân công.
  - Đồng bộ cập nhật cột `care_status` trong bảng `customers`.
- **Cơ chế Cảnh báo đỏ tự động (Cron Job Integration)**:
  - Hàm `checkAndTriggerOverdueSlas()` chạy định kỳ qua Cron quét tất cả bản ghi SLA đang xử lý quá hạn chót (`due_time < now` và `sla_status = 'in_progress'`).
  - Cập nhật trạng thái thành `overdue` (Bỏ lỡ/Quá hạn).
  - Gửi thông báo tự động (bắn thông báo hệ thống qua `NotificationService`) trực tiếp cho **Nhân viên phụ trách** và **Trưởng phòng trực tiếp** để đôn đốc.

### **14.4. Tiêu chuẩn Thẩm mỹ UI/UX (Aesthetics & Apple Style):**
- **Khối Dashboard SLA**: Hiển thị badge màu sắc động tương ứng, bộ đếm ngược thời gian còn lại trực quan (`Còn lại X giờ` hoặc `⚠️ TRỄ HẠN X ngày Y giờ` nhấp nháy đỏ tươi sử dụng CSS `@keyframes pulse`).
- **AJAX Transition Dropdown**: Cho phép chuyển trạng thái một-click bằng dropdown chọn nhanh qua AJAX mà không cần reload trang.
- **Timeline Lịch sử**: Vẽ trục thời gian Apple-Style thể hiện chi tiết: lộ trình tư vấn, thời gian thực tế xử lý, nhân sự đảm nhiệm và badge kết quả đạt/trễ.

---

## 15. Nâng cấp Nghiệp vụ Bảng lương (Pro-rata & Probation Payroll) - Cập nhật 01/06/2026

### **15.1. Tổng quan & Mục tiêu:**
Giải quyết 4 vấn đề thực tế trong chu kỳ tính lương:
1. **Nhân viên thử việc/thực tập/học việc** không hưởng 100% lương cơ bản.
2. **Chuyển hạng giữa tháng** (ví dụ: hết thử việc ngày 15/06 → các ngày còn lại áp dụng mức lương mới).
3. **Nhân viên mới vào giữa tháng** → tháng sau tự động tính truy lĩnh.
4. **Delay chấm công 1 ngày** khi cấp quyền app → Admin bù thủ công.

### **15.2. Quyền truy cập:**
- **Thiết lập hệ số lương:** Admin, Mod, phòng Hành chính (form Hồ sơ nhân viên).
- **Thêm ngày công bù:** Admin, người có quyền `payroll.manage`.
- **Xem hệ số lương:** Nhân viên xem badge hệ số trên bảng lương cá nhân.

### **15.3. Cơ sở dữ liệu mở rộng:**
**Bảng `employees` (3 cột mới):**
- `probation_rate` DECIMAL(5,2): Hệ số lương hiện tại (% lương CB). Mặc định 100.
- `probation_end_date` DATE: Ngày kết thúc giai đoạn. NULL = không chuyển hạng trong kỳ.
- `new_rate_after` DECIMAL(5,2): Hệ số % áp dụng SAU ngày kết thúc. Mặc định 100.

**Bảng `payrolls` (2 cột mới):**
- `manual_adjust_days` DECIMAL(5,2): Ngày công bù thủ công. Mặc định 0.
- `probation_rate_snapshot` DECIMAL(5,2): Snapshot hệ số tại thời điểm tính lương (lịch sử).

### **15.4. Hàm nghiệp vụ chính (`PayrollService.php`):**

**`calcTaxableIncome()`** — Tính TNCT có hỗ trợ chuyển hạng giữa tháng:
- Nếu `probation_end_date` không rơi vào tháng đang tính → tính toàn tháng một hệ số.
- Nếu có chuyển hạng → đếm ngày công trước/sau ngày chuyển, tính từng phần độc lập.
- Công thức: `TNCT = (SalaryBase × Rate% / StandardDays) × ActualDays`

**`detectAndCalcRetroPayroll()`** — Tự động phát hiện và tính truy lĩnh tháng trước:
- Kích hoạt khi: `join_date` thuộc `prevMonth` VÀ chưa có phiếu lương `prevMonth`.
- Idempotent: Marker `[Truy lĩnh tự động]` trong `notes_json` ngăn tính trùng khi bấm tính lại.

### **15.5. SOP Vận hành:**
1. NV mới/thử việc → Hồ sơ → Thiết lập `probation_rate` (dùng nút preset).
2. Biết ngày hết thử việc → Điền `probation_end_date` + `new_rate_after`.
3. **Sau khi tháng chuyển hạng qua** → Admin thủ công: cập nhật `probation_rate = new_rate_after`, xóa `probation_end_date`.
4. NV mới bị delay chấm công → Bảng lương → Nhập "Ngày bù = 1".
5. Bấm **"Tính toán lương"** → Truy lĩnh tháng trước tự động phát hiện và cộng vào cột Khác.

### **15.6. Hằng số cấu hình (`AppConstants::PROBATION_RATE_DEFAULT`):**
```
Thử việc:         85%
Thực tập sinh:    40%
Học việc:         60%
Chính thức:      100%
```
Sửa tỷ lệ tại `app/Config/AppConstants.php` nếu chính sách công ty thay đổi.

---

## 16. KPI tư vấn theo giá trị hợp đồng - Cập nhật 03/06/2026

### **16.1. Mục tiêu nghiệp vụ**
- Ghi nhận KPI cho nhân viên tư vấn dựa trên hồ sơ vụ việc khách hàng mà nhân viên đó chốt được.
- KPI tính theo tổng giá trị hợp đồng đã chốt trong tháng, không tính theo số lượng hồ sơ.
- Mốc chuẩn: 150.000.000 VNĐ giá trị hợp đồng/tháng tương ứng thưởng 5.000.000 VNĐ.
- Vượt hoặc thiếu mốc được tăng/giảm tuyến tính theo tỷ lệ.

### **16.2. Công thức**
```
KPI thưởng = (Tổng contract_value trong tháng / 150.000.000) * 5.000.000
Tiến độ = Tổng contract_value trong tháng / 150.000.000 * 100
```

### **16.3. Nguồn dữ liệu**
- `cases.consultant_id`: Nhân sự tư vấn đã chốt khách.
- `cases.consultation_closed_at`: Thời điểm ghi nhận chốt để xác định tháng KPI.
- `cases.contract_value`: Giá trị hợp đồng dùng để tính KPI.
- Loại trừ hồ sơ `deleted_at IS NOT NULL`, hồ sơ trạng thái `huy`, và hồ sơ chưa có giá trị hợp đồng.

### **16.4. Phân quyền**
- `kpi.consulting`: Được xem báo cáo KPI tư vấn và ghi nhận người tư vấn chốt trên hồ sơ vụ việc.
- `kpi.view_all`: Xem KPI toàn hệ thống.
- `kpi.view_team`: Xem KPI đội ngũ theo `manager_id`.
- Quyền được khai báo trong `KpiController::$modulePermissions` và đồng bộ qua `/perm-fix/sync` hoặc `sync_permissions.php`.

### **16.5. Giao diện**
- Dashboard chính hiển thị tiến trình KPI tư vấn tháng hiện tại cho người có quyền.
- Trang `/kpi` có bộ lọc theo tháng, phòng ban và tên nhân viên.
- Form tạo/sửa hồ sơ vụ việc có vùng “KPI tư vấn” chỉ hiển thị với admin hoặc người có quyền `kpi.consulting`.

### **16.6. Cập nhật quy tắc thống kê KPI tư vấn - 03/06/2026**
- Số hồ sơ chốt được đếm theo `consultant_id` và `consultation_closed_at`, không phụ thuộc hồ sơ đã nhập tiền hay chưa.
- Giá trị KPI ưu tiên lấy tổng tất cả dòng trong `cases.payment_progress[].amount`, không phân biệt `is_paid` đã thu hay chưa thu.
- Nếu hồ sơ chưa có `payment_progress` hoặc tổng các đợt bằng 0, hệ thống fallback về `cases.contract_value`.
- Ngày ghi nhận chốt chỉ nhập cấp ngày (`YYYY-MM-DD`); hệ thống lưu về `00:00:00` để phục vụ lọc tháng.

---

## 17. Đăng ký xe trong lịch trình công việc - Cập nhật 17/06/2026

### 17.1. Mục tiêu nghiệp vụ
- Cho phép nhân sự đánh dấu nhu cầu sử dụng xe công ty khi tạo hoặc sửa lịch trình công việc.
- Giữ nguyên loại lịch trình hiện có (`work`, `business_trip`) và tách nhu cầu xe thành cờ riêng để không làm sai nghĩa nghiệp vụ.
- Trên overview lịch, lịch có đăng ký xe được nhận diện nhanh bằng màu xanh, icon xe và tooltip “Có đăng ký xe”.

### 17.2. Quyền sử dụng
- Người có quyền `work_schedule.manage` được tạo/cập nhật cờ đăng ký xe trên lịch trình thuộc phạm vi được phép.
- Người có quyền `work_schedule.view` xem được trạng thái đăng ký xe trên lịch tổng quan.
- Quyền sửa/xóa vẫn đi theo logic sở hữu lịch trình hiện tại: admin, chủ lịch, người tạo hoặc trưởng phòng đúng phạm vi.

### 17.3. Input/Output
- Input form: checkbox `requires_vehicle` trong modal tạo/sửa lịch trình.
- Backend chuẩn hóa checkbox thành `0/1` tại Controller và Service để tránh lỗi khi checkbox không được gửi lên.
- API `/work-schedules/events` trả thêm `extendedProps.requires_vehicle` và class `ws-event-vehicle` cho FullCalendar.

### 17.4. Cơ sở dữ liệu
- Bảng `work_schedules` thêm cột `requires_vehicle TINYINT(1) NOT NULL DEFAULT 0`.
- Migration: `2026-06-17-090000_AddVehicleRegistrationToWorkSchedules.php`.
- SQL thủ công đã được append cuối `mysql.sql` theo quy tắc Dual DB Update.

---

## 18. Trang thai qua tang trong ho so khach hang - Cap nhat 03/07/2026

### 18.1. Muc tieu nghiep vu
- Luu trang thai khach hang da duoc tang qua hay chua ngay tren ho so CRM.
- Ho tro nhan su cham soc nhin nhanh khach nao da nhan qua de tranh tang trung hoac bo sot.
- Cho phep cap nhat nhanh tren danh sach khach hang ma khong can mo form sua chi tiet.

### 18.2. Quyen su dung
- Admin, nguoi co quyen `customer.manage` hoac `customer.edit_all` duoc cap nhat trang thai qua tang.
- Truong phong va nhan su dang phu trach cham soc khach hang duoc cap nhat trong pham vi ho so duoc phep.
- Endpoint van kiem tra quyen theo tung `customer_id`, khong tin du lieu ID tu front-end.

### 18.3. Input/Output
- Input form tao/sua khach hang: select `has_received_gift` voi gia tri `0` la chua tang, `1` la da tang.
- Tac vu nhanh tren danh sach: nut badge trong cot "Qua tang" goi AJAX toi `/customers/update-gift-status/{id}`.
- Output API tra JSON gom `status`, `message`, va `data.has_received_gift` de front-end cap nhat badge tai cho.

### 18.4. Co so du lieu
- Bang `customers` them cot `has_received_gift TINYINT(1) NOT NULL DEFAULT 0`.
- Migration: `2026-07-03-090000_AddGiftStatusToCustomers.php`.
- SQL thu cong da duoc append cuoi `mysql.sql` theo quy tac Dual DB Update.

---

## 19. Deadline cuối ngày và ghi nhận KPI ngoại lệ cho step vụ việc - Cập nhật 09/07/2026

### 19.1. Mục tiêu nghiệp vụ
- Deadline của mỗi bước vụ việc được hiểu là hết ngày hạn định, tức `23:59:59`, để nhân sự có trọn ngày làm việc trước khi bị tính quá hạn.
- Quản lý hoặc người duyệt có thể ghi nhận KPI cho một step đã hoàn thành trễ nếu nhân sự giải trình hợp lý và chất lượng công việc đạt yêu cầu.
- Hệ thống vẫn giữ nguyên `deadline` và `completed_at` thực tế để phục vụ kiểm toán, chỉ thêm cờ ngoại lệ cho báo cáo KPI.

### 19.2. Quyền sử dụng
- Người có quyền `sys.admin`, `case.edit_all`, `case.approve`, hoặc là người duyệt của vụ việc được ghi nhận KPI ngoại lệ.
- Controller vẫn kiểm tra quyền theo từng `step_id` thông qua hồ sơ vụ việc, không tin dữ liệu ID từ giao diện.

### 19.3. Input/Output
- Input thao tác: nút ghi nhận KPI trên lộ trình step đã hoàn thành trễ hạn, gửi tới `POST /cases/approve-step-kpi/{stepId}`.
- Backend lưu `kpi_override_approved = 1`, lý do ghi nhận, người ghi nhận và thời điểm ghi nhận.
- Output: Báo cáo KPI, danh sách vụ việc và tổng hợp lương tính KPI đạt nếu step hoàn thành đúng hạn hoặc được ghi nhận ngoại lệ.

### 19.4. Cơ sở dữ liệu
- Bảng `case_steps` thêm các cột:
  - `kpi_override_approved TINYINT(1) NOT NULL DEFAULT 0`: Cờ quản lý chấp thuận KPI ngoại lệ.
  - `kpi_override_reason TEXT NULL`: Lý do chấp thuận.
  - `kpi_override_by INT(11) UNSIGNED NULL`: Nhân sự quản lý/người duyệt ghi nhận.
  - `kpi_override_at DATETIME NULL`: Thời điểm ghi nhận.
- Migration: `2026-07-09-170000_AddKpiOverrideAndEndOfDayDeadlines.php`.
- SQL thủ công đã được append cuối `mysql.sql` theo quy tắc Dual DB Update.
