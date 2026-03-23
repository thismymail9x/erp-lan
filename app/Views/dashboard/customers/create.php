<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="customer-create-container" style="max-width: 900px; margin: 0 auto;">
    <div class="dashboard-header-wrapper" style="margin-bottom: 40px;">
        <div class="header-title-container" style="text-align: center; width: 100%;">
            <h2 class="content-title">Thêm khách hàng mới</h2>
            <p class="content-subtitle">Hệ thống sẽ tự động kiểm tra trùng lặp để đảm bảo dữ liệu tinh gọn.</p>
        </div>
        <div style="position: absolute; left: 0; top: 0;">
            <a href="<?= base_url('customers') ?>" class="btn-secondary-sm" title="Quay lại danh sách khách hàng">
                <i class="fas fa-chevron-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card" style="padding: 40px;">
        <!-- Wizard Progress Bar -->
        <div class="wizard-progress" style="display: flex; justify-content: space-between; margin-bottom: 40px; position: relative;">
            <div style="position: absolute; top: 15px; left: 0; width: 100%; height: 2px; background: #f2f2f2; z-index: 1;"></div>
            <div id="progress-line" style="position: absolute; top: 15px; left: 0; width: 0%; height: 2px; background: #0071e3; z-index: 2; transition: width 0.3s ease;"></div>
            
            <div class="step-indicator active" data-step="1" style="z-index: 3; background: #fff; padding: 0 10px;">
                <div class="step-dot" style="width: 30px; height: 30px; border-radius: 50%; border: 2px solid #0071e3; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; background: #fff; color: #0071e3; font-weight: 700;">1</div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Định danh</div>
            </div>
            <div class="step-indicator" data-step="2" style="z-index: 3; background: #fff; padding: 0 10px;">
                <div class="step-dot" style="width: 30px; height: 30px; border-radius: 50%; border: 2px solid #d2d2d7; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; background: #fff; color: #d2d2d7; font-weight: 700;">2</div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Liên lạc & Địa chỉ</div>
            </div>
            <div class="step-indicator" data-step="3" style="z-index: 3; background: #fff; padding: 0 10px;">
                <div class="step-dot" style="width: 30px; height: 30px; border-radius: 50%; border: 2px solid #d2d2d7; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; background: #fff; color: #d2d2d7; font-weight: 700;">3</div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase;">CRM & Phân loại</div>
            </div>
        </div>

        <form action="<?= base_url('customers/store') ?>" method="post" id="customerWizardForm">
            <?= csrf_field() ?>
            
            <!-- Step 1: Basic Identity -->
            <div class="wizard-step active" id="step-1">
                <div class="form-group-premium" style="margin-bottom: 25px;">
                    <label class="label-premium">Loại khách hàng <span style="color:red">*</span></label>
                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;" title="Khách hàng là cá nhân (người dùng đơn lẻ)">
                            <input type="radio" name="type" value="ca_nhan" checked style="width: 18px; height: 18px;"> Cá nhân
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;" title="Khách hàng là công ty hoặc tổ chức">
                            <input type="radio" name="type" value="doanh_nghiep" style="width: 18px; height: 18px;"> Doanh nghiệp
                        </label>
                    </div>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Họ và tên / Tên DN <span style="color:red">*</span></label>
                        <input type="text" name="name" class="form-control-premium" required placeholder="Nguyễn Văn A..." title="Nhập tên đầy đủ của khách hàng hoặc tên doanh nghiệp">
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Số điện thoại chính <span style="color:red">*</span></label>
                        <input type="text" name="phone" id="phone_check" class="form-control-premium" required placeholder="09xxxxxxx" title="Số điện thoại dùng để liên lạc chính và kiểm tra trùng lặp">
                        <div id="phone_alert" style="font-size: 11px; color: var(--apple-red); margin-top: 5px; display: none;"></div>
                    </div>
                </div>

                <div id="individual_fields" style="margin-top: 20px;">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group-premium">
                            <label class="label-premium">Ngày sinh</label>
                            <input type="date" name="date_of_birth" class="form-control-premium" title="Ngày tháng năm sinh của khách hàng">
                        </div>
                        <div class="form-group-premium">
                            <label class="label-premium">Giới tính</label>
                            <select name="gender" class="form-control-premium" title="Giới tính">
                                <option value="nam">Nam</option>
                                <option value="nu">Nữ</option>
                                <option value="khac">Khác</option>
                            </select>
                        </div>
                        <div class="form-group-premium">
                            <label class="label-premium">Số CCCD/Hộ chiếu</label>
                            <input type="text" name="identity_number" id="id_check" class="form-control-premium" placeholder="12 số hoặc số passport" title="Số định danh cá nhân để quản lý hồ sơ">
                            <div id="id_alert" style="font-size: 11px; color: var(--apple-red); margin-top: 5px; display: none;"></div>
                        </div>
                    </div>
                </div>

                <div id="corporate_fields" style="margin-top: 20px; display: none;">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group-premium">
                            <label class="label-premium">Mã số thuế</label>
                            <input type="text" name="tax_code" class="form-control-premium" placeholder="0101xxxxxx" title="Mã số thuế doanh nghiệp">
                        </div>
                        <div class="form-group-premium">
                            <label class="label-premium">Người đại diện</label>
                            <input type="text" name="company_name" class="form-control-premium" placeholder="Tên GĐ/Người được ủy quyền" title="Người chịu trách nhiệm pháp lý của DN">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Contact & Address -->
            <div class="wizard-step" id="step-2" style="display: none;">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Email chính</label>
                        <input type="email" name="email" id="email_check" class="form-control-premium" placeholder="example@gmail.com" title="Địa chỉ email liên hệ chính">
                        <div id="email_alert" style="font-size: 11px; color: var(--apple-red); margin-top: 5px; display: none;"></div>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">SĐT phụ</label>
                        <input type="text" name="phone_secondary" class="form-control-premium" title="Số điện thoại dự phòng">
                    </div>
                </div>
                <div class="form-group-premium" style="margin-top: 20px;">
                    <label class="label-premium">Địa chỉ đầy đủ</label>
                    <textarea name="address" class="form-control-premium" rows="3" placeholder="Số nhà, đường, phường, quận, tỉnh..." title="Địa chỉ chi tiết để gửi hồ sơ/giấy tờ"></textarea>
                </div>
            </div>

            <!-- Step 3: CRM Meta -->
            <div class="wizard-step" id="step-3" style="display: none;">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Nguồn khách hàng</label>
                        <select name="source" class="form-control-premium" title="Khách hàng biết đến chúng ta từ đâu?">
                            <option value="khac">Khác</option>
                            <option value="facebook">Facebook</option>
                            <option value="zalo">Zalo</option>
                            <option value="google">Google Search</option>
                            <option value="gioi_thieu">Được giới thiệu</option>
                            <option value="website">Website</option>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Tags (Phân loại, cách nhau dấu phẩy)</label>
                        <input type="text" name="tags" class="form-control-premium" placeholder="đất đai, ly hôn, khách VIP..." title="Ghi chú phân loại nhanh để lọc dữ liệu">
                    </div>
                </div>
                <div class="form-group-premium" style="margin-top: 20px;">
                    <label class="label-premium">Ghi chú nội bộ (Chỉ quản lý thấy)</label>
                    <textarea name="notes_internal" class="form-control-premium" rows="4" title="Ghi chú nhạy cảm hoặc lưu ý riêng về khách hàng"></textarea>
                </div>
            </div>

            <!-- Wizard Footer Buttons -->
            <div class="wizard-footer" style="margin-top: 40px; display: flex; justify-content: space-between; padding-top: 20px; border-top: 1px solid #f2f2f2;">
                <button type="button" id="prevBtn" class="btn-secondary" style="display: none;">Quay lại</button>
                <div style="flex: 1;"></div>
                <button type="button" id="nextBtn" class="btn-premium">Tiếp theo</button>
                <button type="submit" id="submitBtn" class="btn-premium" style="display: none;">Hoàn tất & Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Logic điều khiển Wizard (Form nhiều bước) và Kiểm tra trùng lặp thời gian thực.
 */
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;      // Bước hiện tại
    const totalSteps = 3;    // Tổng số bước của Wizard
    
    // Khởi tạo các phần tử DOM quan trọng
    const form = document.getElementById('customerWizardForm');
    const steps = document.querySelectorAll('.wizard-step');
    const indicators = document.querySelectorAll('.step-indicator');
    const progressLine = document.getElementById('progress-line'); // Thanh tiến trình
    
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const submitBtn = document.getElementById('submitBtn');

    /**
     * Chuyển đổi hiển thị trường thông tin giữa Cá nhân và Doanh nghiệp.
     * Tự động ẩn/hiện các ô nhập liệu đặc thù như CCCD hoặc Mã số thuế.
     */
    const typeRadios = document.querySelectorAll('input[name="type"]');
    typeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'doanh_nghiep') {
                document.getElementById('individual_fields').style.display = 'none';
                document.getElementById('corporate_fields').style.display = 'block';
            } else {
                document.getElementById('individual_fields').style.display = 'block';
                document.getElementById('corporate_fields').style.display = 'none';
            }
        });
    });

    /**
     * Xử lý khi nhấn nút "Tiếp theo".
     */
    nextBtn.addEventListener('click', () => {
        if(currentStep < totalSteps) {
            steps[currentStep-1].style.display = 'none'; // Ẩn bước cũ
            currentStep++;
            steps[currentStep-1].style.display = 'block'; // Hiện bước mới
            updateWizard(); // Cập nhật giao diện thanh tiến trình
        }
    });

    /**
     * Xử lý khi nhấn nút "Quay lại".
     */
    prevBtn.addEventListener('click', () => {
        if(currentStep > 1) {
            steps[currentStep-1].style.display = 'none';
            currentStep--;
            steps[currentStep-1].style.display = 'block';
            updateWizard();
        }
    });

    /**
     * Cập nhật trạng thái hiển thị của các nút bấm và thanh tiến trình.
     */
    function updateWizard() {
        // 1. Cập nhật các vòng tròn chỉ số (Indicators)
        indicators.forEach(ind => {
            const step = parseInt(ind.dataset.step);
            if(step <= currentStep) ind.classList.add('active');
            else ind.classList.remove('active');
            
            const dot = ind.querySelector('.step-dot');
            if(step < currentStep) {
                // Bước đã hoàn thành: Hiện dấu tích xanh
                dot.style.background = '#0071e3';
                dot.style.color = '#fff';
                dot.innerHTML = '<i class="fas fa-check"></i>';
            } else if(step === currentStep) {
                // Bước hiện tại: Highlight màu xanh dương
                dot.style.background = '#fff';
                dot.style.color = '#0071e3';
                dot.style.borderColor = '#0071e3';
                dot.innerHTML = step;
            } else {
                // Bước chưa tới: Hiện màu xám mờ
                dot.style.background = '#fff';
                dot.style.color = '#d2d2d7';
                dot.style.borderColor = '#d2d2d7';
                dot.innerHTML = step;
            }
        });

        // 2. Cập nhật độ dài thanh tiến trình (Progress Line)
        progressLine.style.width = ((currentStep - 1) / (totalSteps - 1) * 100) + '%';

        // 3. Điều khiển hiển thị các nút điều hướng (Prev/Next/Submit)
        prevBtn.style.display = (currentStep === 1) ? 'none' : 'block';
        if(currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
    }

    /**
     * Logic kiểm tra trùng lặp thông qua API (Asynchronous).
     * Khi người dùng nhập xong và rời khỏi ô (blur), hệ thống sẽ gọi lên máy chủ để kiểm tra.
     * 
     * @param string field Tên trường cần kiểm tra (phone, identity_number, email)
     * @param string value Giá trị người dùng đã nhập
     * @param string alertId ID của phần tử hiển thị cảnh báo
     */
    async function checkDuplicate(field, value, alertId) {
        if(!value) return;
        const alertDiv = document.getElementById(alertId);
        try {
            // Gọi API kiểm tra trùng lặp
            const response = await fetch(`<?= base_url('customers/check-duplicate') ?>?${field}=${value}`);
            const result = await response.json();
            
            if(result.exists) {
                // Nếu tồn tại: Hiển thị cảnh báo kèm link dẫn đến hồ sơ cũ để nhân viên đối soát
                const dup = result.duplicates[field];
                alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> Trùng lặp: <a href="<?= base_url('customers/show') ?>/${dup.id}" target="_blank" style="color: inherit; text-decoration: underline;">${dup.code} - ${dup.name}</a>`;
                alertDiv.style.display = 'block';
            } else {
                alertDiv.style.display = 'none';
            }
        } catch(e) {
            console.error("Lỗi khi kiểm tra trùng lặp:", e);
        }
    }

    // Gán sự kiện cho các ô nhập liệu quan trọng
    document.getElementById('phone_check').addEventListener('blur', function() {
        checkDuplicate('phone', this.value, 'phone_alert');
    });
    document.getElementById('id_check').addEventListener('blur', function() {
        checkDuplicate('identity_number', this.value, 'id_alert');
    });
    document.getElementById('email_check').addEventListener('blur', function() {
        checkDuplicate('email', this.value, 'email_alert');
    });
});
</script>

<style>
.step-indicator { text-align: center; transition: all 0.3s ease; }
.step-indicator.active .step-dot { border-color: #0071e3; }
.wizard-step { animation: fadeIn 0.4s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
<?= $this->endSection() ?>
