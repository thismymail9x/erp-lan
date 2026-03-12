<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="attendance-wrapper" style="max-width: 800px; margin: 0 auto;">
    <div class="dashboard-header-actions" style="margin-bottom: 32px;">
        <h2 class="content-title">Điểm danh thông minh</h2>
        <p class="content-subtitle">Ghi nhận thời gian làm việc bằng hình ảnh và vị trí GPS.</p>
    </div>

    <div class="premium-card" style="padding: 40px; text-align: center; background: #fff;">
        <!-- Clock Display -->
        <div class="clock-display" style="margin-bottom: 30px;">
            <div id="current-time"
                 style="font-size: 64px; font-weight: 700; color: var(--apple-blue); font-feature-settings: 'tnum';">
                00:00:00
            </div>
            <div style="font-size: 18px; color: var(--apple-text-muted);"><?= \CodeIgniter\I18n\Time::now('Asia/Ho_Chi_Minh')->toLocalizedString('cccc, dd/MM/yyyy', 'vi') ?></div>
        </div>

        <!-- Status Banner -->
        <div id="attendance-status" class="status-banner"
             style="margin-bottom: 30px; padding: 15px; border-radius: 16px; font-weight: 600;">
            Đang tải dữ liệu...
        </div>

        <?php if ($isMobile) { ?>
            <!-- Camera Area (Only for Mobile) -->
            <div class="capture-container"
                 style="position: relative; margin-bottom: 30px; border-radius: 20px; overflow: hidden; background: #f5f5f7; min-height: 400px; display: flex; align-items: center; justify-content: center;">
                <video id="video-preview" autoplay playsinline
                       style="width: 100%; height: auto; display: none;"></video>
                <canvas id="photo-canvas" style="display: none;"></canvas>

                <div id="photo-placeholder" style="color: var(--apple-text-muted);">
                    <i class="fas fa-camera"
                       style="font-size: 48px; display: block; margin-bottom: 10px; opacity: 0.2;"></i>
                    Nhấn "Bắt đầu" để mở camera
                </div>

                <div id="captured-container" style="display: none; width: 100%;">
                    <img id="captured-photo" src="" style="width: 100%; display: block;">
                    <div style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.5); color: #fff; padding: 4px 10px; border-radius: 8px; font-size: 12px;">
                        Đã chụp
                    </div>
                </div>
            </div>

            <div class="attendance-actions"
                 style="display: flex; flex-direction: column; gap: 20px; align-items: center;">
                <div class="form-group" style="width: 100%; max-width: 400px; text-align: left;">
                    <label>Ghi chú bổ sung</label>
                    <textarea id="note" rows="3" placeholder="Đi muộn do kẹt xe, làm bù giờ..."></textarea>
                </div>

                <div class="btn-step-group" style="display: flex; gap: 15px;">
                    <button id="btn-init" class="btn btn-secondary-apple" style="padding: 12px 24px;">
                        <i class="fas fa-power-off"></i> Bắt đầu
                    </button>
                    <button id="btn-snap" class="btn btn-secondary-apple" disabled style="padding: 12px 24px;">
                        <i class="fas fa-camera"></i> Chụp ảnh
                    </button>
                    <button id="btn-submit" class="btn btn-blue-apple" disabled style="padding: 12px 32px;">
                        <i class="fas fa-check-circle"></i> Xác nhận
                    </button>
                </div>
            </div>
        <?php } else { ?>
            <?php if ($isLan) { ?>
                <!-- LAN Attendance Area (PC in Office) -->
                <div class="lan-checkin-wrapper"
                     style="padding: 40px; background: #f0fdf4; border-radius: 16px; border: 2px solid #bbf7d0; margin-bottom: 30px;">
                    <i class="fas fa-wifi"
                       style="font-size: 48px; color: #22c55e; margin-bottom: 20px; display:block;"></i>
                    <h3 style="color: #166534; margin-bottom: 10px; font-size: 20px;">Đã kết nối Mạng Nội Bộ</h3>
                    <p style="color: #15803d; margin-bottom: 20px;">Hệ thống nhận diện bạn đang ở văn phòng. Bạn có thể
                        điểm danh.</p>
                    <button id="btn-lan-submit" class="btn btn-blue-apple" style="padding: 15px 40px; font-size: 16px;">
                        <i class="fas fa-check-circle"></i> Xác nhận Điểm Danh
                    </button>
                </div>
                <div class="form-group" style="width: 100%; max-width: 400px; text-align: left; margin: 0 auto 20px;">
                    <label>Ghi chú bổ sung</label>
                    <textarea id="note" rows="3" placeholder="Đi muộn do kẹt xe, làm bù giờ..."></textarea>
                </div>
            <?php } else { ?>
                <!-- PC Out of Office (Cannot Attend) -->
                <div class="lan-checkin-wrapper"
                     style="padding: 40px; background: #fff1f2; border-radius: 16px; border: 2px solid #fecdd3; margin-bottom: 30px;">
                    <i class="fas fa-exclamation-triangle"
                       style="font-size: 48px; color: #e11d48; margin-bottom: 20px; display:block;"></i>
                    <h3 style="color: #9f1239; margin-bottom: 10px; font-size: 20px;">Lỗi kết nối Mạng Văn Phòng</h3>
                    <p style="color: #be123c; margin-bottom: 20px;">Chỉ được phép điểm danh bằng thiết bị PC/Laptop tại
                        mạng nội bộ văn phòng.<br>Nếu bạn đang đi công tác hoặc thị trường ngoài văn phòng, vui lòng sử
                        dụng <strong>Điện thoại di động</strong> để điểm danh.</p>
                </div>
            <?php } ?>
        <?php } ?>

    </div>

    <div style="margin-top: 20px; text-align: center;">
        <a href="<?= base_url('attendance/list?view=monthly') ?>"
           style="color: var(--apple-blue); text-decoration: none; font-weight: 500;">
            <i class="fas fa-history"></i> Xem lịch sử điểm danh tháng này
        </a>
    </div>
</div>

<style>
    .btn-secondary-apple {
        background: #f5f5f7;
        color: #1d1d1f;
        border: none;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.2s;
    }

    .btn-secondary-apple:hover:not(:disabled) {
        background: #e8e8ed;
    }

    .btn-blue-apple {
        background: var(--apple-blue);
        color: #fff;
        border: none;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.2s;
    }

    .btn-blue-apple:hover:not(:disabled) {
        opacity: 0.9;
        transform: scale(0.98);
    }

    .btn-success-apple {
        background: #28a745;
        color: #fff;
        border: none;
        font-weight: 600;
        border-radius: 12px;
    }

    .status-banner.not-in {
        background: #e1f5fe;
        color: #01579b;
    }

    .status-banner.in {
        background: #fff3e0;
        color: #e65100;
    }

    .status-banner.done {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-banner.error {
        background: #ffebee;
        color: #c62828;
    }

    .capture-container {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .capture-container:hover {
        background: #ececef !important;
        transform: scale(1.01);
    }
</style>

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
        const capturedContainer = document.getElementById('captured-container');
        const capturedPhoto = document.getElementById('captured-photo');
        const placeholder = document.getElementById('photo-placeholder');
        const note = document.getElementById('note');

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
            } catch (e) {
                console.error(e);
            }
        }

        function updateUI(data) {
            let actionBtn = document.getElementById('btn-submit') || document.getElementById('btn-lan-submit');
            if (!actionBtn) return;

            if (data.status === 'NOT_CHECKED_IN') {
                statusBanner.className = 'status-banner not-in';
                statusBanner.textContent = 'Bạn chưa điểm danh hôm nay. Hãy bắt đầu Check-in.';
                actionBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Xác nhận CHECK-IN';
            } else if (data.status === 'CHECKED_IN') {
                statusBanner.className = 'status-banner in';
                statusBanner.textContent = 'Đã vào lúc ' + data.check_in_time + '. Sẵn sàng CHECK-OUT.';
                actionBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Xác nhận CHECK-OUT';
                actionBtn.classList.replace('btn-blue-apple', 'btn-secondary-apple');
                actionBtn.style.color = 'var(--apple-red)';
            } else if (data.status === 'CHECKED_OUT') {
                statusBanner.className = 'status-banner done';
                statusBanner.textContent = 'Đã hoàn thành công việc hôm nay. Nghỉ ngơi nhé!';
                actionBtn.disabled = true;
                actionBtn.textContent = 'HOÀN TẤT';
                if (btnInit) btnInit.disabled = true;
            }
        }

        <?php if ($isMobile) { ?>
        if (btnInit) {
            btnInit.onclick = async () => {
                btnInit.disabled = true;
                statusBanner.textContent = 'Đang khởi động Camera & Định vị...';
                let cameraReady = false;
                let gpsReady = false;

                const locPromise = new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 5000
                    });
                }).then(pos => {
                    geoLocation = pos;
                    gpsReady = true;
                    if (cameraReady) statusBanner.textContent = 'Sẵn sàng! Vui lòng chụp ảnh khuôn mặt.';
                    return pos;
                }).catch(err => {
                    console.warn('GPS delay/error:', err);
                    throw err;
                });

                const camPromise = navigator.mediaDevices.getUserMedia({
                    video: {facingMode: 'user'},
                    audio: false
                }).then(s => {
                    stream = s;
                    video.srcObject = s;
                    video.style.display = 'block';
                    placeholder.style.display = 'none';
                    btnSnap.disabled = false;
                    cameraReady = true;
                    if (!gpsReady) statusBanner.textContent = 'Camera đã sẵn sàng. Đang xác định vị trí...';
                    return s;
                }).catch(err => {
                    console.error('Camera error:', err);
                    throw err;
                });

                try {
                    await Promise.all([locPromise, camPromise]);
                    statusBanner.textContent = 'Đã sẵn sàng. Vui lòng chụp ảnh khuôn mặt.';
                } catch (e) {
                    statusBanner.className = 'status-banner error';
                    statusBanner.textContent = 'Lỗi: Không thể truy cập Camera hoặc GPS. Vui lòng cấp quyền.';
                    btnInit.disabled = false;
                }
            };

            // Gán sự kiện click cho khung hiển thị camera
            const captureContainer = document.querySelector('.capture-container');
            if (captureContainer) {
                captureContainer.onclick = () => {
                    if (!btnInit.disabled) {
                        btnInit.click();
                    }
                };
            }
        }

            btnSnap.onclick = () => {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                canvas.toBlob(blob => {
                    photoBlob = blob;
                    capturedPhoto.src = URL.createObjectURL(blob);
                    capturedContainer.style.display = 'block';
                    video.style.display = 'none';
                    btnSnap.disabled = true;
                    btnSubmit.disabled = false;
                    if (stream) stream.getTracks().forEach(t => t.stop());
                    statusBanner.textContent = 'Đã chụp ảnh. Bạn có thể xác nhận điểm danh.';
                }, 'image/jpeg', 0.8);
            };

            btnSubmit.onclick = async () => {
                if (isSubmitting) return;
                isSubmitting = true;
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Đang xử lý...';
                const formData = new FormData();
                formData.append('latitude', geoLocation.coords.latitude);
                formData.append('longitude', geoLocation.coords.longitude);
                formData.append('note', note.value);
                formData.append('photo', photoBlob, 'att.jpg');
                submitData(formData, btnSubmit);
            };
        }
        <?php } else { ?>
        const btnLanSubmit = document.getElementById('btn-lan-submit');
        if (btnLanSubmit) {
            btnLanSubmit.onclick = async () => {
                if (isSubmitting) return;
                isSubmitting = true;
                btnLanSubmit.disabled = true;
                btnLanSubmit.textContent = 'Đang xử lý...';
                const formData = new FormData();
                formData.append('note', note.value);
                submitData(formData, btnLanSubmit);
            };
        }
        <?php } ?>

        async function submitData(formData, btnElem) {
            const originalText = btnElem.innerHTML;
            try {
                const res = await fetch('<?= base_url('attendance/submit') ?>', {
                    method: 'POST',
                    body: formData
                });
                const text = await res.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (jsonErr) {
                    console.error('Server response was not JSON:', text);
                    throw new Error('Hệ thống trả về phản hồi không hợp lệ.');
                }

                if (result.code === 0) {
                    statusBanner.className = 'status-banner done';
                    statusBanner.textContent = result.message;
                    btnElem.innerHTML = '<i class="fas fa-check"></i> ' + result.message;
                    btnElem.classList.replace('btn-blue-apple', 'btn-success-apple');
                    alert(result.message);
                    location.reload();
                } else {
                    statusBanner.className = 'status-banner error';
                    statusBanner.textContent = result.error || 'Đã có lỗi xảy ra.';
                    if (btnElem) {
                        btnElem.disabled = false;
                        btnElem.innerHTML = '<i class="fas fa-redo"></i> Thử lại';
                    }
                    isSubmitting = false;
                }
            } catch (e) {
                console.error('Submit Error:', e);
                statusBanner.className = 'status-banner error';
                statusBanner.textContent = e.message || 'Lỗi kết nối máy chủ.';
                if (btnElem) {
                    btnElem.disabled = false;
                    btnElem.innerHTML = originalText;
                }
                isSubmitting = false;
            }
        }

        checkStatus();
    });
</script>
<?= $this->endSection() ?>
