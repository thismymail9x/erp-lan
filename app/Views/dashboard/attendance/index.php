<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="attendance-page-container">
    <div class="dashboard-header-actions">
        <h2 class="content-title">Điểm danh thông minh</h2>
        <p class="content-subtitle">Ghi nhận thời gian làm việc bằng hình ảnh và vị trí GPS.</p>
    </div>

    <div class="attendance-main-card">
        <!-- Clock Display -->
        <div class="clock-display-group">
            <div id="current-time" class="clock-display-time" title="Thời gian hiện tại theo hệ thống">00:00:00</div>
            <div class="clock-display-date" title="Ngày tháng hiện tại"><?= \CodeIgniter\I18n\Time::now('Asia/Ho_Chi_Minh')->toLocalizedString('cccc, dd/MM/yyyy', 'vi') ?></div>
        </div>

        <!-- Status Banner -->
        <div id="attendance-status" class="status-indicator-banner" title="Trạng thái điểm danh trong ngày của bạn">
            Đang tải dữ liệu...
        </div>

        <!-- Token Logic UI -->
        <div id="office-pc-status" style="display: none; margin-bottom: 20px;">
            <div class="lan-status-box lan-status-success" style="background: rgba(52, 199, 89, 0.1); border: 1px solid #34c759;">
                <i class="fas fa-desktop lan-box-icon" style="color: #34c759;"></i>
                <h3 class="lan-box-title" style="color: #1a7f37;">Điểm danh tại Văn Phòng</h3>
                <button id="btn-token-submit" class="btn btn-blue-apple">
                    <i class="fas fa-check-circle"></i> Xác nhận Điểm Danh
                </button>
            </div>
        </div>

        <?php if ($isMobile) { ?>
            <!-- Camera Area (Only for Mobile) -->
            <div class="capture-viewport" title="Khung nhìn camera để nhận diện khuôn mặt">
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
                        <h3 class="lan-box-title" style="color: #9f1239;">Lỗi kết nối Mạng Văn Phòng</h3>
                        <p class="lan-box-desc" style="color: #be123c;">Chỉ được phép điểm danh bằng thiết bị PC/Laptop tại mạng nội bộ văn phòng.<br>Nếu bạn đang đi công tác, vui lòng dùng <strong>Điện thoại</strong> để điểm danh.</p>
                    </div>
                <?php } ?>
            </div>

            <div class="attendance-note-form" style="margin: 20px auto;">
                <label for="note">Ghi chú bổ sung</label>
                <textarea id="note" rows="3" placeholder="Đi muộn do kẹt xe, làm bù giờ..." title="Ghi chú nội dung làm việc hoặc lý do bất thường"></textarea>
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
    (function () {
        function updateClock() {
            const clock = document.getElementById('current-time');
            if (!clock) return;
            const now = new Date();
            const h = now.getHours();
            const m = now.getMinutes();
            const s = now.getSeconds();
            clock.textContent = (h < 10 ? '0' + h : h) + ':' +
                (m < 10 ? '0' + m : m) + ':' +
                (s < 10 ? '0' + s : s);
        }
        setInterval(updateClock, 1000);
        document.addEventListener('DOMContentLoaded', updateClock);
    })();

    document.addEventListener('DOMContentLoaded', function () {
        const statusBanner = document.getElementById('attendance-status');
        const video = document.getElementById('video-preview');
        const canvas = document.getElementById('photo-canvas');
        const btnInit = document.getElementById('btn-init');
        const btnSnap = document.getElementById('btn-snap');
        const btnSubmit = document.getElementById('btn-submit');
        const btnLanSubmit = document.getElementById('btn-lan-submit');
        const btnTokenSubmit = document.getElementById('btn-token-submit');
        const capturedContainer = document.getElementById('captured-container');
        const capturedPhoto = document.getElementById('captured-photo');
        const placeholder = document.getElementById('photo-placeholder');
        const note = document.getElementById('note');
        
        const officeToken = localStorage.getItem('office_security_token');

        // Check for Token Authorization
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

        async function checkStatus() {
            try {
                const res = await fetch('<?= base_url('attendance/status') ?>');
                const result = await res.json();
                if (result.code === 0) {
                    updateUI(result.data);
                }
            } catch (e) { console.error(e); }
        }

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

        // Logic Mobile
        <?php if ($isMobile) { ?>
            if (btnInit) {
                btnInit.onclick = async () => {
                    btnInit.disabled = true;
                    statusBanner.textContent = 'Đang khởi động Camera & Định vị...';
                    navigator.geolocation.getCurrentPosition(pos => {
                        geoLocation = pos;
                        navigator.mediaDevices.getUserMedia({video: {facingMode: 'user'}, audio: false})
                        .then(s => {
                            stream = s; video.srcObject = s; video.style.display = 'block';
                            placeholder.style.display = 'none'; btnSnap.disabled = false;
                            statusBanner.textContent = 'Sẵn sàng! Vui lòng chụp ảnh khuôn mặt.';
                        }).catch(e => {
                            statusBanner.className = 'status-indicator-banner status-banner-error';
                            statusBanner.textContent = 'Lỗi Camera: ' + e.message;
                            btnInit.disabled = false;
                        });
                    }, err => {
                        statusBanner.className = 'status-indicator-banner status-banner-error';
                        statusBanner.textContent = 'Lỗi GPS: Vui lòng bật định vị và thử lại.';
                        btnInit.disabled = false;
                    });
                };
            }
            btnSnap.onclick = () => {
                canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                canvas.toBlob(blob => {
                    photoBlob = blob; capturedPhoto.src = URL.createObjectURL(blob);
                    capturedContainer.style.display = 'block'; video.style.display = 'none';
                    btnSnap.disabled = true; btnSubmit.disabled = false;
                    if (stream) stream.getTracks().forEach(t => t.stop());
                }, 'image/jpeg', 0.8);
            };
            btnSubmit.onclick = () => {
                const fd = new FormData();
                fd.append('latitude', geoLocation.coords.latitude);
                fd.append('longitude', geoLocation.coords.longitude);
                fd.append('note', note.value);
                fd.append('photo', photoBlob, 'att.jpg');
                submitData(fd, btnSubmit);
            };
        <?php } ?>

        // Logic LAN
        if (btnLanSubmit) {
            btnLanSubmit.onclick = () => {
                const fd = new FormData();
                fd.append('note', note.value);
                submitData(fd, btnLanSubmit);
            };
        }

        // Logic Token
        if (btnTokenSubmit) {
            btnTokenSubmit.onclick = () => {
                const fd = new FormData();
                fd.append('note', note.value);
                fd.append('officeToken', officeToken);
                submitData(fd, btnTokenSubmit);
            };
        }

        // Admin Authorize PC
        const btnAuth = document.getElementById('btn-authorize-pc');
        if (btnAuth) {
            btnAuth.onclick = async () => {
                if (!confirm('Xác thực máy tính này là máy văn phòng dùng để chấm công?')) return;
                const res = await fetch('<?= base_url('attendance/get-office-token') ?>');
                const result = await res.json();
                if (result.code === 0) {
                    localStorage.setItem('office_security_token', result.token);
                    document.getElementById('auth-success-msg').style.display = 'block';
                    btnAuth.style.display = 'none';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Lỗi: ' + result.error);
                }
            };
        }

        async function submitData(formData, btnElem) {
            if (isSubmitting) return;
            isSubmitting = true;
            const originalHTML = btnElem.innerHTML;
            btnElem.disabled = true;
            btnElem.textContent = 'Đang xử lý...';

            try {
                const res = await fetch('<?= base_url('attendance/submit') ?>', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.code === 0) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.error);
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
