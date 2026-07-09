# BỘ 11 QUY TẮC PHÁT TRIỂN L.A.N ERP
---

## 1. Comment & Code Documentation (VIỆT HÓA 100%)
- **Luật Comment Không được để lỗi font chữ tiếng việt :** Mọi hàm, class, hoặc khối logic phức tạp phải có comment tiếng Việt chi tiết.
- **Tư duy giải thích:** Không chỉ comment "Hàm này làm gì", mà BẮT BUỘC giải thích "Tại sao lại tính toán như vậy" (Mô tả thuật toán rành mạch).
- **Header:** Luôn có khối comment Header ở đầu file mô tả file này phụ trách nghiệp vụ gì.

---

## 2. Kiến trúc & Logic Nghiệp vụ (Strict MVC)
- **Tầng Service:** Controller CHỈ dùng để nhận Request và trả Response. 100% não bộ logic, câu Query phức tạp, Subqueries bắt buộc phải đưa vào lớp `Service`.
- **Cấu trúc Braces:** Tuyệt đối dùng ngoặc nhọn `{ }` cho khối lệnh. NGHIÊM CẤM dùng short tags (`endif;`, `endforeach;`). Căn lề chuẩn chỉ
---

## 3. Phân Lập Dữ Liệu & Phân Quyền (Data Isolation)
- **Need-to-know & Hierarchy:** Quản lý theo tổ đội (`manager_id`). Cấp quản lý chỉ thấy việc của mình và cấp dưới trực tiếp. Nhân viên chỉ thấy việc của mình.
- Mọi logic query mảng nhân sự/phòng ban phải bọc bằng Closure Code an toàn (vd: `whereIn('id', function($builder)...)`).

---

## 4. Quy trình Cập Nhật Database (Dual DB Update)
- **Cấm sửa lõi:** Không bao giờ sửa các truy vấn `CREATE TABLE` đã có trong file `mysql.sql`.
- **Luật Đồng bộ Kép:** Khi tạo Bảng/Cột mới → (1) Viết file Migration → (2) Phải convert ra SQL thuần (`ALTER TABLE` / `CREATE TABLE` / `INSERT`) và gắn xuống cuối ở TẬN CÙNG file `mysql.sql`.

---

## 5. Ghi chú Schema Database (DB Inline Comment)
- Bất kỳ khi nào tạo cột hay bảng mới trên hệ thống, bắt buộc phải đính kèm mệnh đề `COMMENT 'Mô tả chi tiết bằng tiếng Việt'` vào SQL để toàn team hiểu ngữ nghĩa dữ liệu mà không cần xem doc.

---

## 6. Xử lý Dữ liệu Sạch (Auto-Nullify & Soft Delete Integrity)
- **Phòng chống lỗi Khóa ngoại (FK):** Mọi module đều phải kế thừa `BaseModel`. Nếu người dùng xóa dữ liệu form, đẩy lên chuỗi rỗng (`""`), tự động chuyển thành giá trị `NULL` để lưu Database mượt mà.
- **Loại trừ dữ liệu đã xóa & Bắt buộc `deleted_at`:** 
    - Mọi bảng mới BẮT BUỘC có cột `deleted_at DATETIME DEFAULT NULL` vì hệ thống dùng `BaseModel` có sẵn Soft Delete.
    - **QUY TẮC VÀNG:** Khi viết bất kỳ câu lệnh truy vấn dữ liệu nào (Raw SQL, Subqueries, Query Builder), **BẮT BUỘC** phải thêm điều kiện `deleted_at IS NULL` để loại bỏ các bản ghi đã xóa mềm. 
    - **Lưu ý:** Không tin tưởng hoàn toàn vào cơ chế tự động của Model khi viết Subqueries hoặc Join phức tạp, vì chúng thường bypass global scope của Soft Delete. Luôn kiểm tra kỹ khi tính toán KPI, doanh thu hoặc thống kê.

---

## 7. Nguyên Tắc Bảo Mật Tối Đa (Maximum Security)
- **Chống Tampering (Chỉnh sửa ID):** Luôn phải verify "ID này có thuộc quyền sở hữu của User đang đăng nhập hay không?" trước khi Update/Delete. Không tin dữ liệu front-end.
- **Validate & Escaping:** 100% Request đầu vào phải qua thư viện Validator. Hiển thị ra View luôn bọc hàm `esc()`. Cấm nối SQL string trực tiếp.
- **Masking:** Dữ liệu nhạy cảm (SĐT, Email, CCCD) hiển thị ngoài list phải ẩn bằng `****` với user thường.

---

## 8. Thẩm Mỹ Giao Diện (Rich Aesthetics & High Density)
- **Apple-Minimal & Compact:** UI phải tinh tế, màu Pantone. Giao diện ưu tiên "Mật độ cao" (High Density) cho không gian laptop nhỏ (Giảm nhẹ Font chữ, khoảng cách spacing nhỏ, padding mỏng).
- **Text-to-Icon:** Hạn chế dùng Text dài lê thê cho các Nút/Header. Thay thế bằng Nút siêu ngắn kẹp chung với Icon FontAwesome trực quan (vd: "Tạo vụ việc mới" → "`<icon>` Thêm").
- Các cột hiển thị text dài (Như Địa chỉ, Ghi chú) bắt buộc khống chế số dòng bằng CSS (Limit text).
- Giao diện đồng nhất theo các tính năng tương đương ở module khác
---

## 9. Tài Liệu Tính Năng (Feature Markdown)
- Mỗi khi Code vận hành xong một module/tính năng mới, bắt buộc phải cung cấp một bản hướng dẫn Document (Markdown) ghi rõ: Tính năng làm gì, Quyền lợi ai được xài, Input/Output là gì vào cuối file developer_guild

---

## 10. Hệ thống Master Sync & Auto-Registry (Đồng bộ Tập trung)
- **Tự động Khai báo:** Không viết các hàm khởi tạo quyền (`permissions`) hay đăng ký module nhãn dán (`tags`) rời rạc. Mọi Controller phải khai báo Metadata chuẩn tại phần đầu Class:
    - `public static $modulePermissions`: Cho hệ thống phân quyền (RBAC).
    - `public static $taggable`: Cho hệ thống nhãn dán thông minh (Smart Tags).
- **Cỗ máy quét `/perm-fix/sync`:** Đây là "Source of Truth" duy nhất. Khi có Module mới, chỉ cần chạy URL này, hệ thống sẽ tự động:
    1. Quét toàn bộ thư mục `Controllers`.
    2. Đăng ký các quyền hạn mới vào Database (Bảng `permissions`).
    3. Cập nhật Registry các module được phép gắn nhãn (`tag_modules.json`).
- **Lợi ích:** Giảm thiểu sai sót do quên đăng ký cấu hình, giúp Module mới "Sẵn sàng chạy" ngay sau khi Code xong Core logic.
- Sử dụng các hàm lấy nhân viên, phòng ban theo base cố định, để sửa thì chỉ cần sửa 1 chỗ trong file common.php
---

## 11. Phân Trang Bắt Buộc (Mandatory Pagination)
- Mọi trang danh sách (Index/List View) trong hệ thống **BẮT BUỘC** phải có phân trang. Đây là nguyên tắc chống quá tải dữ liệu.
- **Controller:** Luôn sử dụng `$model->paginate($perPage)` thay vì `findAll()` khi hiển thị danh sách.
- **View:** Luôn hiển thị component `<?= $pager->links() ?>` ở cuối danh sách.
- **AJAX:** Nếu view dùng AJAX để tải dữ liệu (như Knowledge Feed), phải hook sự kiện click vào pagination links để load nội dung mới mà không reload trang.
- **Giá trị mặc định:** Số dòng mỗi trang: **20** (tùy module). Ngoại lệ: Dashboard thống kê, Modal Select2 infinite scroll.
- Khi xử lý thời gian khi hiển thị ra view đều hiển thị tiếng việt, thứ ngay tháng tiếng viet
- Khong viết css inline, tạo class cho nó. Ko lẫn lộn css và js vào html, tach riêng file rồi nhúng vào
---

## 12. Trải nghiệm Người dùng Mới (New Feature Visibility)
- **Hệ thống Badge "New":** Mọi module/tính năng mới khi ra mắt BẮT BUỘC phải được khai báo trong `app/Config/FeatureGuidelines.php`.
- **Thông tin khai báo:** 
    - `launch_date`: Ngày phát hành (Badge sẽ tự động hiển thị trong 14 ngày kể từ ngày này).
    - `guidance`: Phải có tiêu đề và nội dung hướng dẫn (Bullet points) để hiển thị trong Modal hướng dẫn tại trang.
- **Mục đích:** Giúp người dùng nhận diện tính năng mới và nắm bắt cách dùng nhanh chóng (Compliance Rule #12).

---

## 13. Tối ưu hóa di động (Mobile Optimization & Responsiveness)
- **Mobile-First Priority:** Mọi giao diện phát triển mới BẮT BUỘC phải hoạt động hoàn hảo trên thiết bị di động. Hệ thống ERP không chỉ dùng trên Laptop mà còn được nhân sự sử dụng khi di chuyển.
- **Fluid Layout:** Sử dụng Flexbox, Grid và các đơn vị tương đối (%, vh, rem) thay vì fixed pixel cho các container chính.
- **Media Queries:** Phải có ít nhất 2 điểm breakpoint:
    - **Tablet (max-width: 1024px):** Điều chỉnh cột, giảm padding.
    - **Mobile (max-width: 768px):** Chuyển sang layout dọc (stack), ẩn các thành phần không cần thiết (vd: `hide-mobile`), hoặc sử dụng cơ chế toggle/back-button cho các view chi tiết (như Chat, Danh sách).
- **Touch Targets:** Các nút bấm, link trên mobile phải có kích thước tối thiểu 44x44px để dễ dàng thao tác bằng ngón tay.
- **Performance:** Hạn chế load các thư viện nặng chỉ dành cho desktop khi ở view mobile.

---
- tuyệt đội không được làm lỗi font tiếng việt
*Cập nhật lần cuối: 16/05/2026 — Phiên bản đầy đủ Bộ 13 Quy tắc.*
