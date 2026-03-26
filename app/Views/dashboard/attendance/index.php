<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/attendance.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="attendance-page-container">
    <div class="dashboard-header-actions">
        <h2 class="content-title">Điểm danh</h2>
    </div>

    <div class="attendance-main-card">
        <!-- Clock Display -->
        <div class="clock-display-group">
            <div id="current-time" class="clock-display-time" title="Thời gian hiện tại theo hệ thống">00:00:00</div>
            <div class="clock-display-date" title="Ngày tháng hiện tại">
                <?php
                $time = \CodeIgniter\I18n\Time::now('Asia/Ho_Chi_Minh');
                $dayEng = $time->format('l');
                $daysTranslate = [
                    'Monday'    => 'Thứ Hai',
                    'Tuesday'   => 'Thứ Ba',
                    'Wednesday' => 'Thứ Tư',
                    'Thursday'  => 'Thứ Năm',
                    'Friday'    => 'Thứ Sáu',
                    'Saturday'  => 'Thứ Bảy',
                    'Sunday'    => 'Chủ Nhật',
                ];
                echo ($daysTranslate[$dayEng] ?? $dayEng) . ', ' . $time->format('d/m/Y');
                ?>
            </div>
        </div>

        <!-- Status Banner -->
        <div id="attendance-status" class="status-indicator-banner" title="Trạng thái điểm danh trong ngày của bạn">
            Đang tải dữ liệu...
        </div>

        <!-- Token Logic UI -->
        <div id="office-pc-status" style="display: none; margin-bottom: 20px;">
            <div class="lan-status-box lan-status-success" style="background: rgba(52, 199, 89, 0.1); border: 1px solid #34c759;">
                <i class="fas fa-desktop lan-box-icon" style="color: #34c759;"></i>
                <h3 class="lan-box-title" style="color: #1a7f37;">Điểm danh bằng máy tính</h3>
                <button id="btn-token-submit" class="btn btn-blue-apple">
                    <i class="fas fa-check-circle"></i> Xác nhận Điểm Danh
                </button>
            </div>
        </div>

        <?php if ($isMobile) { ?>
            <!-- Camera Area (Only for Mobile) -->
            <div id="camera-area" class="capture-viewport" title="Khung nhìn camera để nhận diện khuôn mặt" style="display: none;">
                <video id="video-preview" class="capture-video" autoplay playsinline></video>
                <canvas id="photo-canvas" style="display: none;"></canvas>

                <div id="photo-placeholder" class="capture-placeholder">
                    <i class="fas fa-camera"></i>
                    Nhấn "Bắt đầu" để mở camera
                </div>

                <div id="captured-container" class="capture-result-preview" style="display: none;">
                    <img id="captured-photo" class="capture-img" src="">
                    <div class="capture-badge" title="Ảnh đã được chụp thành công">Đã chụp</div>
                </div>
            </div>

            <div class="attendance-form-layout">
                <div class="attendance-note-form">
                    <label for="note">Ghi chú bổ sung</label>
                    <textarea id="note" rows="3" placeholder="Làm bù giờ, đi công tác tại..." title="Nhập lý do nếu đi muộn, về sớm hoặc có lưu ý đặc biệt"></textarea>
                </div>

                <div class="btn-step-group">
                    <button id="btn-init" class="btn btn-secondary-apple" title="Kích hoạt Camera và GPS định vị">
                        <i class="fas fa-power-off"></i> Bắt đầu
                    </button>
                    <button id="btn-snap" class="btn btn-secondary-apple" disabled title="Chụp ảnh khuôn mặt để điểm danh">
                        <i class="fas fa-camera"></i> Chụp ảnh
                    </button>
                    <button id="btn-submit" class="btn btn-blue-apple" disabled title="Gửi dữ liệu điểm danh về hệ thống">
                        <i class="fas fa-check-circle"></i> Xác nhận
                    </button>
                </div>
            </div>
        <?php } else { ?>
            <div id="pc-attendance-area">
                <?php if ($isLan) { ?>
                    <!-- LAN Attendance Area (PC in Office) -->
                    <div class="lan-status-box lan-status-success" title="Kết nối mạng nội bộ hợp lệ">
                        <i class="fas fa-wifi lan-box-icon"></i>
                        <h3 class="lan-box-title">Đã kết nối Mạng Nội Bộ</h3>
                        <p class="lan-box-desc">Hệ thống nhận diện bạn đang ở văn phòng. Bạn có thể điểm danh.</p>
                        <button id="btn-lan-submit" class="btn btn-blue-apple" title="Gửi xác nhận điểm danh từ PC văn phòng">
                            <i class="fas fa-check-circle"></i> Xác nhận Điểm Danh
                        </button>
                    </div>
                <?php } else { ?>
                    <!-- PC Out of Office -->
                    <div id="out-of-office-box" class="lan-status-box lan-status-error">
                        <i class="fas fa-exclamation-triangle lan-box-icon" style="color: #e11d48;"></i>
                        <h3 class="lan-box-title" style="color: #9f1239;">Lỗi kết nối</h3>
                        <p class="lan-box-desc" style="color: #be123c;">Chỉ được phép điểm danh bằng thiết bị PC/Laptop tại văn phòng.<br>Nếu bạn đang đi công tác, vui lòng dùng <strong>Điện thoại</strong> để điểm danh.</p>
                    </div>
                <?php } ?>
            </div>

            <div class="attendance-note-form" style="margin: 20px 0;">
                <label for="note">Ghi chú bổ sung</label>
                <textarea id="note" rows="3" placeholder="Lên CATP, đi tòa A..." title="Ghi chú nội dung làm việc hoặc lý do bất thường"></textarea>
            </div>
        <?php } ?>

        <!-- Admin Authorization Section -->
        <?php if ($role === \Config\AppConstants::ROLE_ADMIN && !$isMobile) { ?>
            <div id="admin-auth-section" style="margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 20px; text-align: center;">
                <p class="text-sm text-muted">Dành cho Quản trị viên:</p>
                <button id="btn-authorize-pc" class="btn-secondary-sm">
                    <i class="fas fa-shield-alt"></i> Ủy quyền Máy tính này
                </button>
                <p id="auth-success-msg" style="display: none; color: #34c759; margin-top: 10px; font-size: 0.85rem;">
                    <i class="fas fa-check-circle"></i> Máy tính này đã được ủy quyền điểm danh vĩnh viễn.
                </p>
            </div>
        <?php } ?>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <a href="<?= base_url('attendance/list?view=monthly') ?>"
           style="color: var(--apple-blue); text-decoration: none; font-weight: 500;" title="Xem bảng công chi tiết của bạn trong tháng">
            <i class="fas fa-history"></i> Xem lịch sử điểm danh tháng này
        </a>
    </div>
</div>

<script>
    /**
     * L.A.N ERP - Hệ thống Chấm công Đa phương thức (Mobile / LAN / Office Token)
     * Đảm bảo tính nhất thực và vị trí của nhân viên khi Check-in/Check-out.
     */

    // 1. Quản lý Đồng hồ Thời gian thực (Real-time Clock)
    (function () {
        const timeElement = document.getElementById('current-time'); // Cập nhật ID để khớp với HTML
        const dateElement = document.querySelector('.clock-display-date'); // Lấy phần tử ngày tháng
        const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];

        function updateClock() {
            if (!timeElement) return;
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            // Cập nhật ngày tháng chỉ một lần hoặc khi cần thiết, vì PHP đã render
            // Để tránh ghi đè lên nội dung PHP, chỉ cập nhật nếu không có nội dung PHP
            if (dateElement && dateElement.textContent.trim() === '') {
                dateElement.textContent = days[now.getDay()] + ', ' + now.toLocaleDateString('vi-VN');
            }
        }
        setInterval(updateClock, 1000);
        document.addEventListener('DOMContentLoaded', updateClock);
    })();

    document.addEventListener('DOMContentLoaded', function () {
        // Khởi tạo các biến DOM và trạng thái
        const statusBanner = document.getElementById('attendance-status');
        const video = document.getElementById('video-preview');
        const canvas = document.getElementById('photo-canvas');
        const btnInit = document.getElementById('btn-init');
        const btnSnap = document.getElementById('btn-snap');
        const btnSubmit = document.getElementById('btn-submit');
        const btnLanSubmit = document.getElementById('btn-lan-submit');
        const btnTokenSubmit = document.getElementById('btn-token-submit');
        const capturedContainer = document.getElementById('captured-container');
        const cameraArea = document.getElementById('camera-area');
        const capturedPhoto = document.getElementById('captured-photo');
        const placeholder = document.getElementById('photo-placeholder');
        const note = document.getElementById('note');
        
        // Lấy token thiết bị văn phòng từ LocalStorage (nếu có)
        const officeToken = localStorage.getItem('office_security_token');

        // Logic hiển thị nút bấm tương ứng với quyền/thiết bị
        if (officeToken && !'<?= $isMobile ?>') {
            const tokenBox = document.getElementById('office-pc-status');
            const pcArea = document.getElementById('pc-attendance-area');
            if (tokenBox) tokenBox.style.display = 'block';
            if (pcArea) pcArea.style.display = 'none';
        }

        let stream = null;
        let geoLocation = null;
        let photoBlob = null;
        let isSubmitting = false;

        /**
         * Kiểm tra trạng thái chấm công hiện tại của User từ Server.
         */
        async function checkStatus() {
            try {
                const res = await fetch('<?= base_url('attendance/status') ?>');
                const result = await res.json();
                if (result.code === 0) {
                    updateUI(result.data);
                }
            } catch (e) { console.error('Lỗi check status:', e); }
        }

        /**
         * Cập nhật giao diện (Banner, Nút bấm) dựa trên trạng thái (Chưa vào / Đã vào / Đã xong).
         * @param {object} data - Dữ liệu trạng thái từ server.
         */
        function updateUI(data) {
            const actionBtns = [btnSubmit, btnLanSubmit, btnTokenSubmit].filter(b => b);
            
            if (data.status === 'NOT_CHECKED_IN') {
                statusBanner.className = 'status-indicator-banner status-banner-not-in';
                statusBanner.textContent = 'Bạn chưa điểm danh hôm nay. Hãy bắt đầu Check-in.';
                actionBtns.forEach(b => b.innerHTML = '<i class="fas fa-sign-in-alt"></i> Xác nhận CHECK-IN');
            } else if (data.status === 'CHECKED_IN') {
                statusBanner.className = 'status-indicator-banner status-banner-in';
                statusBanner.textContent = 'Đã vào lúc ' + data.check_in_time + '. Sẵn sàng CHECK-OUT.';
                actionBtns.forEach(b => {
                    b.innerHTML = '<i class="fas fa-sign-out-alt"></i> Xác nhận CHECK-OUT';
                    b.classList.replace('btn-blue-apple', 'btn-secondary-apple');
                    b.style.color = 'var(--apple-red)';
                });
            } else if (data.status === 'CHECKED_OUT') {
                statusBanner.className = 'status-indicator-banner status-banner-done';
                statusBanner.textContent = 'Đã hoàn thành công việc hôm nay. Nghỉ ngơi nhé!';
                actionBtns.forEach(b => {
                    b.disabled = true;
                    b.textContent = 'HOÀN TẤT';
                });
                if (btnInit) btnInit.disabled = true;
            }
        }

        // --- NHÓM LOGIC DÀNH CHO MOBILE (SỬ DỤNG CAMERA & GPS) ---
        <?php if ($isMobile) { ?>
            /**
             * Khởi động quy trình chấm công Mobile (Camera + GPS).
             */
            async function startAttendance() {
                if (btnInit.disabled) return;
                btnInit.disabled = true;
                statusBanner.textContent = 'Đang khởi động Camera & Định vị...';
                
                // Lấy tọa độ GPS người dùng
                navigator.geolocation.getCurrentPosition(pos => {
                    geoLocation = pos;
                    // Yêu cầu quyền Camera
                    navigator.mediaDevices.getUserMedia({video: {facingMode: 'user'}, audio: false})
                    .then(s => {
                        stream = s; 
                        video.srcObject = s; 
                        video.style.display = 'block';
                        if (cameraArea) cameraArea.style.display = 'flex';
                        placeholder.style.display = 'none'; 
                        btnSnap.disabled = false;
                        statusBanner.textContent = 'Sẵn sàng! Vui lòng chụp ảnh khuôn mặt.';
                    }).catch(e => {
                        statusBanner.className = 'status-indicator-banner status-banner-error';
                        statusBanner.textContent = 'Lỗi Camera: Không thể truy cập máy ảnh.';
                        btnInit.disabled = false;
                        if (cameraArea) cameraArea.style.display = 'none';
                    });
                }, err => {
                    statusBanner.className = 'status-indicator-banner status-banner-error';
                    statusBanner.textContent = 'Lỗi GPS: Vui lòng bật định vị và cấp quyền cho trình duyệt.';
                    btnInit.disabled = false;
                    if (cameraArea) cameraArea.style.display = 'none';
                });
            }

            if (btnInit) {
                btnInit.onclick = startAttendance;
                
                // Tự động kích hoạt khi vào trang nếu chưa hoàn thành công việc
                setTimeout(() => {
                    if (!btnInit.disabled) startAttendance();
                }, 500);
            }

            /**
             * Chụp ảnh từ luồng Video và chuyển đổi sang Blob.
             */
            btnSnap.onclick = () => {
                canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                canvas.toBlob(blob => {
                    photoBlob = blob; 
                    capturedPhoto.src = URL.createObjectURL(blob);
                    capturedContainer.style.display = 'block'; 
                    video.style.display = 'none';
                    btnSnap.disabled = true; 
                    btnSubmit.disabled = false;
                    // Dừng luồng camera sau khi đã chụp để tiết kiệm pin
                    if (stream) stream.getTracks().forEach(t => t.stop());
                }, 'image/jpeg', 0.8);
            };

            /**
             * Gửi dữ liệu chấm công Mobile lên server.
             */
            btnSubmit.onclick = () => {
                const fd = new FormData();
                fd.append('latitude', geoLocation.coords.latitude);
                fd.append('longitude', geoLocation.coords.longitude);
                fd.append('note', note.value);
                fd.append('photo', photoBlob, 'att.jpg');
                submitData(fd, btnSubmit);
            };
        <?php } ?>

        // --- NHÓM LOGIC DÀNH CHO MẠNG NỘI BỘ (LAN) ---
        if (btnLanSubmit) {
            btnLanSubmit.onclick = () => {
                const fd = new FormData();
                fd.append('note', note.value);
                submitData(fd, btnLanSubmit);
            };
        }

        // --- NHÓM LOGIC DÀNH CHO THIẾT BỊ ĐÃ XÁC THỰC (OFFICE TOKEN) ---
        if (btnTokenSubmit) {
            btnTokenSubmit.onclick = () => {
                const fd = new FormData();
                fd.append('note', note.value);
                fd.append('officeToken', officeToken);
                submitData(fd, btnTokenSubmit);
            };
        }

        // --- LOGIC XÁC THỰC MÁY TÍNH VĂN PHÒNG (DÀNH CHO ADMIN) ---
        const btnAuth = document.getElementById('btn-authorize-pc');
        if (btnAuth) {
            btnAuth.onclick = async () => {
                if (!confirm('Xác thực máy tính này là máy văn phòng dùng để chấm công?')) return;
                const res = await fetch('<?= base_url('attendance/get-office-token') ?>');
                const result = await res.json();
                if (result.code === 0) {
                    // Lưu định danh máy tính vào LocalStorage của trình duyệt
                    localStorage.setItem('office_security_token', result.token);
                    document.getElementById('auth-success-msg').style.display = 'block';
                    btnAuth.style.display = 'none';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Lỗi xác thực: ' + result.error);
                }
            };
        }

        /**
         * Hàm trung tâm gửi dữ liệu lên Server.
         * Quản lý trạng thái loading và phản hồi từ API.
         * @param {FormData} formData - Dữ liệu cần gửi.
         * @param {HTMLElement} btnElem - Nút bấm kích hoạt để hiển thị loading.
         */
        async function submitData(formData, btnElem) {
            if (isSubmitting) return;
            isSubmitting = true;
            const originalHTML = btnElem.innerHTML;
            btnElem.disabled = true;
            btnElem.textContent = 'Đang xử lý...';

            try {
                const res = await fetch('<?= base_url('attendance/submit') ?>', { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.code === 0) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Không thành công: ' + result.error);
                    btnElem.disabled = false;
                    btnElem.innerHTML = originalHTML;
                    isSubmitting = false;
                }
            } catch (e) {
                alert('Lỗi kết nối máy chủ.');
                btnElem.disabled = false;
                btnElem.innerHTML = originalHTML;
                isSubmitting = false;
            }
        }

        checkStatus();
    });
</script>
<?= $this->endSection() ?>
