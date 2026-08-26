# PROMPT PHÁT TRIỂN MODULE CRM CHĂM SÓC & PHÁT TRIỂN QUAN HỆ KHÁCH HÀNG

## 1. Mục tiêu module

Xây dựng một module CRM trong hệ thống quản trị nội bộ của Luật Ánh Ngọc để quản lý toàn bộ quá trình chăm sóc và phát triển quan hệ với khách hàng.

Module không chỉ lưu thông tin khách hàng mà phải giúp nhân sự biết:

- Khách hàng này là ai.
- Đang sử dụng dịch vụ gì.
- Mối quan hệ hiện tại ở mức nào.
- Lần gần nhất công ty tương tác với khách là khi nào.
- Khách đang quan tâm vấn đề gì.
- Có cơ hội nào để hỗ trợ thêm.
- Khi nào cần chủ động liên hệ lại.
- Nên tạo giá trị gì cho khách.
- Có khả năng bán thêm dịch vụ hay không.
- Có khả năng giới thiệu khách hàng khác hay không.
- Nhân sự nào đang chịu trách nhiệm duy trì quan hệ.

Mục tiêu cuối cùng:

**Không để khách hàng cũ bị bỏ quên và biến khách hàng thành mối quan hệ lâu dài.**

---

# 2. Cấu trúc tổng thể

Module gồm 7 nhóm chức năng chính:

1. Hồ sơ quan hệ khách hàng.
2. Phân tầng khách hàng.
3. Nhật ký tương tác.
4. Quản lý điểm chạm.
5. Cơ hội tạo giá trị.
6. Cơ hội dịch vụ và referral.
7. Dashboard và KPI chăm sóc khách hàng.

---

# 3. HỒ SƠ QUAN HỆ KHÁCH HÀNG

Trong hồ sơ khách hàng hiện tại, bổ sung một tab mới:

## Tab: "Quan hệ khách hàng"

Hiển thị các thông tin sau.

### A. Thông tin cơ bản

- Tên khách hàng.
- Loại khách hàng:
  - Cá nhân.
  - Doanh nghiệp.
- Người đại diện.
- Số điện thoại.
- Email.
- Zalo.
- Facebook.
- Địa chỉ.
- Nghề nghiệp.
- Chức vụ.
- Công ty đang làm việc.
- Ngành nghề kinh doanh.

---

## B. Người phụ trách

Cho phép chọn:

- Nhân viên chăm sóc chính.
- Luật sư phụ trách.
- Người quản lý quan hệ.
- Người giới thiệu khách hàng.

Một khách hàng có thể có nhiều người liên quan nhưng phải có một người chịu trách nhiệm chính.

---

# 4. PHÂN TẦNG KHÁCH HÀNG

Mỗi khách hàng được gắn một cấp độ quan hệ.

## Cấp 1 — Lead

Khách mới để lại thông tin nhưng chưa sử dụng dịch vụ.

## Cấp 2 — Prospect

Đã được tư vấn hoặc đang cân nhắc sử dụng dịch vụ.

## Cấp 3 — Client

Đang sử dụng dịch vụ.

## Cấp 4 — Existing Client

Đã hoàn thành ít nhất một dịch vụ.

## Cấp 5 — Loyal Client

Đã sử dụng từ 2 dịch vụ trở lên hoặc có quan hệ tốt với công ty.

## Cấp 6 — Strategic Client

Khách hàng có giá trị cao, doanh nghiệp lớn hoặc có khả năng hợp tác lâu dài.

## Cấp 7 — Referral Partner

Khách thường xuyên giới thiệu khách hàng hoặc kết nối cơ hội kinh doanh.

Cho phép admin tùy chỉnh tên cấp độ.

---

# 5. ĐIỂM SỐ QUAN HỆ KHÁCH HÀNG

Tạo trường:

## Relationship Score

Thang điểm từ:

**0 – 100**

Điểm được hệ thống tính tự động.

Ví dụ:

+5 điểm: khách phản hồi tin nhắn.

+10 điểm: khách sử dụng dịch vụ.

+10 điểm: khách sử dụng thêm dịch vụ lần 2.

+15 điểm: khách giới thiệu khách hàng mới.

+20 điểm: khách giới thiệu khách ký hợp đồng.

+5 điểm: tham gia sự kiện.

+5 điểm: chủ động tương tác với công ty.

Trừ điểm:

-5: 60 ngày không có tương tác.

-10: 120 ngày không có tương tác.

-20: khách phản ánh không hài lòng.

Điểm này dùng để xác định chất lượng quan hệ.

---

# 6. NHẬT KÝ TƯƠNG TÁC

Trong hồ sơ khách hàng phải có timeline.

Ví dụ:

10/08/2026  
Nhân viên A gọi điện hỏi thăm tiến độ kinh doanh.

02/08/2026  
Luật sư B gửi tài liệu "Checklist hợp đồng đại lý".

15/07/2026  
Khách giới thiệu khách hàng Nguyễn Văn C.

Mỗi tương tác lưu:

- Ngày.
- Giờ.
- Người thực hiện.
- Hình thức.
- Nội dung.
- Kết quả.
- Mức độ quan trọng.
- Có cần follow-up hay không.
- Ngày follow-up tiếp theo.

---

# 7. LOẠI TƯƠNG TÁC

Dropdown lựa chọn:

- Gọi điện.
- Zalo.
- Messenger.
- Email.
- Gặp trực tiếp.
- Cafe.
- Sự kiện.
- Tư vấn pháp lý.
- Gửi tài liệu.
- Gửi cảnh báo pháp lý.
- Giới thiệu đối tác.
- Chúc sinh nhật.
- Chúc lễ/tết.
- Hỏi thăm.
- Hỗ trợ vấn đề nhỏ.
- Khác.

---

# 8. HỆ THỐNG ĐIỂM CHẠM

Mục tiêu:

Không để khách hàng quá lâu không được tương tác.

Mỗi khách hàng có trường:

## Last Interaction

Ngày tương tác gần nhất.

## Next Interaction

Ngày dự kiến tương tác tiếp theo.

Hệ thống tự tính:

**Days Since Last Interaction**

Ví dụ:

Lần cuối tương tác: 01/06/2026.

Hôm nay: 01/08/2026.

Hiển thị:

"61 ngày chưa tương tác".

---

# 9. CẢNH BÁO KHÁCH HÀNG BỊ BỎ QUÊN

Quy tắc mặc định:

30 ngày:
Màu xanh.

31–60 ngày:
Màu vàng.

61–90 ngày:
Màu cam.

Trên 90 ngày:
Màu đỏ.

Hiển thị cảnh báo trên dashboard:

"Bạn có 17 khách hàng trên 60 ngày chưa tương tác."

Click vào để xem danh sách.

---

# 10. LỊCH CHĂM SÓC 7 / 30 / 60 / 90 / 180 NGÀY

Cho phép hệ thống tự tạo task chăm sóc.

Ví dụ khi hoàn thành vụ việc:

Sau 7 ngày:
Hỏi khách tình hình sau khi hoàn thành dịch vụ.

Sau 30 ngày:
Gửi một nội dung có giá trị.

Sau 60 ngày:
Kiểm tra xem có vấn đề mới phát sinh không.

Sau 90 ngày:
Tạo điểm chạm quan hệ.

Sau 180 ngày:
Review tổng thể mối quan hệ.

Admin có thể thay đổi timeline.

---

# 11. "CƠ HỘI TẠO GIÁ TRỊ"

Đây là một chức năng riêng.

Trong hồ sơ khách hàng có nút:

**+ Tạo cơ hội giá trị**

Các loại giá trị:

- Cảnh báo rủi ro.
- Gửi tài liệu.
- Review nhanh.
- Nhắc deadline.
- Giới thiệu đối tác.
- Giới thiệu khách hàng.
- Chia sẻ cơ hội kinh doanh.
- Mời sự kiện.
- Kiểm tra hợp đồng.
- Checklist.
- Mẫu văn bản.
- Tư vấn ngắn.
- Khác.

Ví dụ:

Khách A đang mở đại lý.

Nhân sự tạo:

Loại:
Checklist.

Nội dung:
"Gửi checklist 12 điều cần kiểm tra trước khi ký hợp đồng đại lý."

Deadline:
15/08/2026.

Người thực hiện:
Nhân viên B.

---

# 12. TRƯỜNG "KHÁCH HÀNG ĐANG QUAN TÂM"

Cho phép lưu nhiều tag.

Ví dụ:

- Công nợ.
- Hợp đồng.
- Lao động.
- BHXH.
- Thuế.
- Đất đai.
- Sổ đỏ.
- Tranh chấp.
- Sở hữu trí tuệ.
- Đầu tư.
- Thành lập công ty.
- Nhượng quyền.
- M&A.
- Bất động sản.

Nhân sự có thể thêm tag mới.

---

# 13. "VẤN ĐỀ ĐÃ PHÁT HIỆN"

Đây là phần quan trọng.

Cho phép ghi nhận những vấn đề khách hàng chưa xử lý.

Ví dụ:

Khách hàng:
Công ty ABC.

Vấn đề:

"Hợp đồng bán hàng chưa có điều khoản phạt chậm thanh toán."

Mức độ:

Cao.

Khả năng phát sinh dịch vụ:

Có.

Dịch vụ liên quan:

Rà soát hợp đồng.

Trạng thái:

- Chưa đề cập.
- Đã đề cập.
- Khách quan tâm.
- Đang tư vấn.
- Đã ký dịch vụ.
- Không có nhu cầu.

---

# 14. CƠ HỘI DỊCH VỤ

Tạo tab:

## Opportunity

Thông tin:

- Khách hàng.
- Vấn đề.
- Dịch vụ phù hợp.
- Giá trị dự kiến.
- Xác suất ký.
- Người phụ trách.
- Ngày phát hiện.
- Ngày follow-up.
- Giai đoạn.

Pipeline:

1. Phát hiện.
2. Đang tìm hiểu.
3. Đã tư vấn.
4. Đề xuất giải pháp.
5. Báo giá.
6. Đang cân nhắc.
7. Đã ký.
8. Không ký.

---

# 15. GỢI Ý CROSS-SELL

Hệ thống có thể gợi ý dịch vụ dựa trên dịch vụ khách đã sử dụng.

Ví dụ:

Nếu khách sử dụng:

**Đòi nợ**

Gợi ý:

- Rà soát hợp đồng.
- Quy trình quản lý công nợ.
- Tư vấn thường xuyên doanh nghiệp.
- Kiện tranh chấp hợp đồng.

Nếu khách sử dụng:

**Đăng ký nhãn hiệu**

Gợi ý:

- Bản quyền.
- Kiểu dáng.
- Hợp đồng nhượng quyền.
- Hợp đồng đại lý.

Nếu khách sử dụng:

**Thành lập công ty**

Gợi ý:

- Hợp đồng lao động.
- Nội quy lao động.
- Nhãn hiệu.
- Tư vấn pháp lý thường xuyên.

---

# 16. REFERRAL — KHÁCH GIỚI THIỆU KHÁCH

Mỗi khách hàng có tab:

## Giới thiệu

Hiển thị:

- Số khách đã giới thiệu.
- Số khách ký hợp đồng.
- Tổng doanh thu referral.
- Lần giới thiệu gần nhất.

Khi tạo khách mới, bắt buộc có trường:

## Nguồn khách hàng

Nếu chọn:

"Khách hàng giới thiệu"

thì chọn:

"Người giới thiệu".

Hệ thống tự cộng referral cho người giới thiệu.

---

# 17. ĐÁNH GIÁ KHẢ NĂNG GIỚI THIỆU

Tạo:

## Referral Score

Ví dụ:

0:
Không có khả năng.

1:
Thấp.

2:
Trung bình.

3:
Cao.

4:
Rất cao.

5:
Đối tác chiến lược.

Nhân sự có thể cập nhật thủ công.

---

# 18. SINH NHẬT VÀ SỰ KIỆN QUAN TRỌNG

Lưu:

- Sinh nhật.
- Ngày thành lập doanh nghiệp.
- Ngày ký hợp đồng.
- Ngày hoàn thành vụ việc.
- Ngày kỷ niệm hợp tác.

Dashboard hiển thị:

"Sinh nhật khách hàng tuần này."

"5 doanh nghiệp có ngày thành lập trong tháng."

---

# 19. TASK CHĂM SÓC KHÁCH HÀNG

Mỗi task gồm:

- Khách hàng.
- Người phụ trách.
- Nội dung.
- Deadline.
- Mức độ ưu tiên.
- Loại điểm chạm.
- Mục tiêu.
- Trạng thái.

Trạng thái:

- Chưa làm.
- Đang làm.
- Đã hoàn thành.
- Hoãn.
- Hủy.

---

# 20. TẠO TASK TỰ ĐỘNG

Ví dụ:

Khách hoàn thành dịch vụ.

Hệ thống tự sinh:

Task 1:
Sau 7 ngày.

"Hỏi thăm tình hình khách sau khi hoàn thành dịch vụ."

Task 2:
Sau 30 ngày.

"Tạo một điểm chạm giá trị cho khách."

Task 3:
Sau 90 ngày.

"Kiểm tra nhu cầu pháp lý mới."

---

# 21. DASHBOARD QUAN HỆ KHÁCH HÀNG

Dashboard gồm:

## Tổng quan

- Tổng khách hàng.
- Khách đang sử dụng dịch vụ.
- Khách cũ.
- Khách chiến lược.
- Referral Partner.

## Cảnh báo

- Khách >30 ngày chưa tương tác.
- Khách >60 ngày.
- Khách >90 ngày.
- Task chăm sóc quá hạn.

## Quan hệ

- Relationship Score trung bình.
- Khách quan hệ tốt nhất.
- Khách có nguy cơ mất kết nối.

## Cơ hội

- Số cơ hội dịch vụ.
- Giá trị pipeline.
- Số referral.
- Doanh thu từ referral.

---

# 22. DASHBOARD CÁ NHÂN NHÂN VIÊN

Mỗi nhân viên thấy:

## Hôm nay cần chăm sóc

Ví dụ:

8 khách cần liên hệ.

## Khách bị quá hạn

5 khách trên 60 ngày.

## Task

12 task.

## Opportunity

4 cơ hội cần follow-up.

---

# 23. KPI CHĂM SÓC KHÁCH HÀNG

Theo tháng.

Ví dụ KPI:

### Điểm chạm

30 điểm chạm / tháng.

### Điểm chạm có giá trị

10 / tháng.

### Referral

3 referral / tháng.

### Opportunity

5 cơ hội dịch vụ mới.

### Khách quay lại

2 khách.

### Tỷ lệ hoàn thành task

>90%.

Không nên chỉ KPI số lượng tin nhắn.

Phải KPI dựa trên:

**chất lượng quan hệ + giá trị tạo ra + kết quả kinh doanh.**

---

# 24. PHÂN QUYỀN

Admin:

Toàn quyền.

Manager:

Xem tất cả khách của team.

Employee:

Chỉ xem khách mình phụ trách.

Luật sư:

Xem khách/vụ việc được phân công.

Có thể cấu hình quyền:

- Xem.
- Sửa.
- Xóa.
- Export.
- Xem doanh thu.
- Xem ghi chú nhạy cảm.

---

# 25. NHẬT KÝ THAY ĐỔI

Lưu toàn bộ lịch sử:

Ai sửa thông tin.

Sửa lúc nào.

Thông tin cũ.

Thông tin mới.

Không cho nhân viên xóa lịch sử.

---

# 26. BỘ LỌC KHÁCH HÀNG

Cho phép lọc:

- Theo nhân viên.
- Theo luật sư.
- Theo dịch vụ.
- Theo Relationship Score.
- Theo cấp khách hàng.
- Theo số ngày chưa tương tác.
- Theo doanh thu.
- Theo referral.
- Theo ngành nghề.
- Theo tag quan tâm.
- Theo vấn đề pháp lý.

---

# 27. DANH SÁCH "KHÁCH NÊN CHĂM SÓC HÔM NAY"

Hệ thống tự tạo danh sách mỗi ngày.

Thuật toán ưu tiên:

1. Khách chiến lược.
2. Khách quá lâu chưa tương tác.
3. Khách đang có cơ hội dịch vụ.
4. Khách có deadline.
5. Khách có sự kiện đặc biệt.
6. Khách có Referral Score cao.

Mỗi khách hiển thị:

Tên khách.

Lý do nên liên hệ.

Ví dụ:

**Nguyễn Văn A**

"Lần cuối liên hệ 73 ngày trước."

"Đã từng sử dụng dịch vụ đòi nợ."

"Gợi ý: gửi checklist quản lý công nợ."

---

# 28. GỢI Ý NỘI DUNG CHĂM SÓC

Trong từng khách hàng có nút:

## "Gợi ý tạo giá trị"

Hệ thống dựa trên:

- Dịch vụ đã sử dụng.
- Ngành nghề.
- Vấn đề.
- Tag quan tâm.
- Lịch sử trao đổi.

Đưa ra 3–5 gợi ý.

Ví dụ:

Khách:
Doanh nghiệp xây dựng.

Gợi ý:

1. Gửi checklist công nợ nhà thầu.

2. Kiểm tra điều khoản thanh toán hợp đồng.

3. Cảnh báo rủi ro hợp đồng khoán.

---

# 29. CƠ CHẾ "KHÔNG BỎ QUÊN KHÁCH"

Business Rule:

Nếu khách >60 ngày chưa tương tác:

Sinh task cho nhân viên phụ trách.

Nếu task quá hạn 7 ngày:

Thông báo manager.

Nếu quá hạn 14 ngày:

Hiển thị trên dashboard quản lý.

Nếu >90 ngày:

Đánh dấu:

**At Risk Relationship**

---

# 30. TRẠNG THÁI QUAN HỆ

Cho phép hệ thống tự xác định:

## Healthy

Quan hệ tốt.

## Cooling

Đang giảm tương tác.

## At Risk

Nguy cơ mất kết nối.

## Lost

Đã mất quan hệ.

## Re-engaged

Đã kết nối trở lại.

---

# 31. CUSTOMER HEALTH SCORE

Có thể tính:

Health Score =

30% Interaction

+

25% Relationship Score

+

20% Satisfaction

+

15% Referral

+

10% Opportunity

Thang điểm:

80–100:
Excellent.

60–79:
Healthy.

40–59:
Cooling.

20–39:
At Risk.

0–19:
Lost.

---

# 32. MÀN HÌNH HỒ SƠ KHÁCH HÀNG

Trang khách hàng nên chia thành các tab:

### Tổng quan

### Vụ việc

### Dịch vụ

### Quan hệ

### Tương tác

### Cơ hội

### Referral

### Task

### Tài liệu

### Lịch sử

---

# 33. WIDGET QUAN TRỌNG NHẤT

Ngay đầu hồ sơ khách hàng hiển thị:

**Relationship Health**

Ví dụ:

Khách hàng:
Công ty ABC.

Relationship Score:
82/100.

Trạng thái:
Healthy.

Lần tương tác:
12 ngày trước.

Người phụ trách:
Nguyễn Văn A.

Cơ hội:
02.

Referral:
03.

Next Action:

"Gửi checklist kiểm soát công nợ trước 15/08."

---

# 34. NGUYÊN TẮC UX

Hệ thống phải giúp nhân viên trả lời được trong 10 giây:

1. Tôi cần chăm khách nào hôm nay?

2. Vì sao phải chăm khách này?

3. Nên nói gì hoặc làm gì?

4. Khách đang quan tâm vấn đề gì?

5. Sau khi chăm xong tôi cần ghi gì?

Không thiết kế CRM theo kiểu chỉ lưu dữ liệu.

CRM phải biến dữ liệu thành:

**NEXT ACTION.**

---

# 35. LOGIC QUAN TRỌNG NHẤT

Triết lý hệ thống:

**Data → Insight → Action → Relationship → Revenue**

Ví dụ:

Data:

Khách từng sử dụng dịch vụ đòi nợ.

Insight:

Doanh nghiệp có rủi ro quản lý công nợ.

Action:

Gửi checklist quản lý công nợ.

Relationship:

Khách thấy Luật Ánh Ngọc tiếp tục quan tâm.

Opportunity:

Rà soát hợp đồng.

Revenue:

Khách ký dịch vụ tư vấn pháp lý thường xuyên.

---

# 36. MVP GIAI ĐOẠN 1

Không cần code toàn bộ ngay.

Ưu tiên phát triển:

1. Relationship Profile.
2. Interaction Timeline.
3. Last Interaction.
4. Next Interaction.
5. Task chăm sóc.
6. Cảnh báo 30/60/90 ngày.
7. Opportunity.
8. Referral.
9. Dashboard chăm sóc.
10. Gợi ý Next Action.

Sau khi vận hành ổn định mới phát triển:

- Relationship Score.
- Customer Health Score.
- AI recommendation.
- Automation nâng cao.
- Phân tích hành vi khách hàng.