<style>
    /* CSS để giao diện gọn gàng, dễ dùng */
    #attendance-container {
        max-width: 500px;
        margin: 20px auto;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    #video-preview, #photo-canvas {
        max-width: 100%;
        height: 200px;
        border-radius: 5px;
    }

    #captured-photo {
        max-width: 100%;
        height: 200px;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-group .btn {
        flex: 1;
    }

    #status-message {
        margin-top: 15px;
        font-weight: bold;
        min-height: 24px;
    }
</style>

<div id="attendance-container">
    <h5>Chấm công ngày: <?php echo date('d/m/Y'); ?></h5>
    <hr>

    <!-- Khu vực hiển thị camera và ảnh đã chụp -->
    <video id="video-preview" autoplay playsinline style="display:none;"></video>
    <canvas id="photo-canvas" style="display:none;"></canvas>
    <div id="photo-display" class="text-center mb-3" style="display:none;">
        <p>Ảnh đã chụp:</p>
        <img id="captured-photo" src="" alt="Ảnh chấm công">
    </div>

    <!-- Ghi chú -->
    <div class="form-group">
        <label for="note">Ghi chú (nếu cần):</label>
        <textarea id="note" class="form-control" rows="3" placeholder="Ví dụ: Đi gặp khách hàng A tại..."></textarea>
    </div>

    <!-- Các nút hành động -->
    <div class="btn-group">
        <button id="btn-start-camera" class="btn btn-info">1. Mở Camera & Vị trí</button>
        <button id="btn-take-photo" class="btn btn-warning" disabled>2. Chụp ảnh</button>
    </div>
    <div class="btn-group">
        <!-- THAY ĐỔI 1: Sử dụng một nút duy nhất cho hành động chính -->
        <button id="btn-submit-attendance" class="btn btn-secondary" disabled>3. Chấm công</button>
    </div>

    <!-- Thông báo trạng thái -->
    <div id="status-message" class="alert alert-info">Đang tải trạng thái...</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- DOM Elements ---
        const video = document.getElementById('video-preview');
        const canvas = document.getElementById('photo-canvas');
        const capturedPhotoImg = document.getElementById('captured-photo');
        const photoDisplay = document.getElementById('photo-display');
        const noteText = document.getElementById('note');
        const statusMessage = document.getElementById('status-message');
        const btnStartCamera = document.getElementById('btn-start-camera');
        const btnTakePhoto = document.getElementById('btn-take-photo');
        const btnSubmitAttendance = document.getElementById('btn-submit-attendance');

        // --- State variables ---
        let capturedBlob = null;
        let stream = null;
        let currentLocation = null;
        let isSubmitting = false;
        let watchId = null;

        /**
         * Lấy trạng thái chấm công từ server và cập nhật giao diện
         */
        async function getInitialStatus() {
            statusMessage.textContent = 'Đang kiểm tra trạng thái...';
            statusMessage.className = 'alert alert-info';
            btnSubmitAttendance.disabled = true;

            try {
                const response = await fetch('/attendances/status');
                const data = await response.json();

                if (data.code !== 0) {
                    statusMessage.textContent = `Lỗi: ${data.error || 'Không thể lấy trạng thái.'}`;
                    statusMessage.className = 'alert alert-danger';
                    return;
                }
                updateUIForStatus(data);
            } catch (error) {
                statusMessage.textContent = 'Lỗi kết nối. Không thể kiểm tra trạng thái.';
                statusMessage.className = 'alert alert-danger';
                console.error('Fetch status error:', error);
            }
        }

        /**
         * Cập nhật giao diện (nút, text) dựa trên trạng thái được trả về
         */
        function updateUIForStatus(data) {
            resetCaptureState(); // Reset trạng thái chụp ảnh mỗi khi cập nhật
            switch (data.status) {
                case 'NOT_CHECKED_IN':
                    statusMessage.textContent = 'Sẵn sàng điểm danh ĐẾN.';
                    statusMessage.className = 'alert alert-info';
                    btnSubmitAttendance.textContent = '3. Điểm danh ĐẾN';
                    btnSubmitAttendance.className = 'btn btn-success';
                    btnStartCamera.disabled = false;
                    break;
                case 'CHECKED_IN':
                    statusMessage.textContent = `Đã điểm danh đến lúc ${data.check_in_time}. Sẵn sàng điểm danh VỀ.`;
                    statusMessage.className = 'alert alert-warning';
                    btnSubmitAttendance.textContent = '3. Điểm danh VỀ';
                    btnSubmitAttendance.className = 'btn btn-danger';
                    btnStartCamera.disabled = false;
                    break;
                case 'CHECKED_OUT':
                    statusMessage.textContent = 'Bạn đã hoàn thành điểm danh hôm nay.';
                    statusMessage.className = 'alert alert-success';
                    btnSubmitAttendance.textContent = 'Đã hoàn thành';
                    btnSubmitAttendance.className = 'btn btn-secondary';
                    btnSubmitAttendance.disabled = true;
                    btnStartCamera.disabled = true;
                    btnTakePhoto.disabled = true;
                    break;
            }
        }

        /**
         * Xử lý Camera và Vị trí
         */
        btnStartCamera.addEventListener('click', async () => {
            statusMessage.textContent = 'Đang yêu cầu quyền truy cập Camera và Vị trí...';
            btnStartCamera.disabled = true;
            try {
                const locationPromise = new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {enableHighAccuracy: true});
                });
                const streamPromise = navigator.mediaDevices.getUserMedia({video: {facingMode: 'user'}, audio: false});

                [currentLocation, stream] = await Promise.all([locationPromise, streamPromise]);

                video.srcObject = stream;
                video.style.display = 'block';
                btnTakePhoto.disabled = false;
                statusMessage.textContent = 'Camera và vị trí đã sẵn sàng. Hãy chụp ảnh.';
            } catch (err) {
                statusMessage.textContent = 'Lỗi: Không thể truy cập Camera hoặc Vị trí. Vui lòng cấp quyền.';
                statusMessage.className = 'alert alert-danger';
                btnStartCamera.disabled = false;
            }
        });


        /**
         * Xử lý chụp ảnh
         */
        btnTakePhoto.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            stream.getTracks().forEach(track => track.stop());
            video.style.display = 'none';

            canvas.toBlob(blob => {
                capturedBlob = blob;
                capturedPhotoImg.src = URL.createObjectURL(capturedBlob);
                photoDisplay.style.display = 'block';
                btnTakePhoto.disabled = true;
                btnSubmitAttendance.disabled = false; // Kích hoạt nút chấm công
                statusMessage.textContent = 'Đã chụp ảnh. Bạn có thể chấm công ngay.';
            }, 'image/jpeg', 0.9);
        });

        /**
         * Gửi dữ liệu chấm công lên server
         */
        btnSubmitAttendance.addEventListener('click', async function () {
            if (isSubmitting || !capturedBlob || !currentLocation) {
                statusMessage.textContent = 'Vui lòng chụp ảnh và cho phép vị trí trước.';
                return;
            }

            isSubmitting = true;
            this.disabled = true;
            this.textContent = 'Đang gửi dữ liệu...';

            const formData = new FormData();
            formData.append('latitude', currentLocation.coords.latitude);
            formData.append('longitude', currentLocation.coords.longitude);
            formData.append('note', noteText.value);
            formData.append('photo', capturedBlob, 'attendance.jpg');

            try {
                const response = await fetch('/attendances/submit', {method: 'POST', body: formData});
                const result = await response.json();

                if (result.code === 0) {
                    statusMessage.textContent = `${result.message} Vui lòng chờ...`;
                    statusMessage.className = 'alert alert-success';

                    // GIẢI PHÁP CHỐNG NHẤN NHẦM:
                    // Đợi 2 giây để người dùng đọc thông báo, sau đó tự động làm mới trạng thái.
                    setTimeout(() => {
                        getInitialStatus();
                    }, 2000);
                } else if (result.code == 2) {
                    // lỗi vị trí thì dừng 5s cho nhân viên đọc
                    statusMessage.textContent = `${result.message} .`;
                    statusMessage.className = 'alert alert-success';
                    setTimeout(() => {
                        getInitialStatus();
                    }, 5000);
                } else {
                    statusMessage.textContent = `Lỗi: ${result.error}`;
                    statusMessage.className = 'alert alert-danger';
                    this.disabled = false; // Cho phép thử lại nếu lỗi
                    isSubmitting = false;
                }
            } catch (error) {
                // statusMessage.textContent = 'Lỗi mạng khi gửi dữ liệu.';
                statusMessage.textContent = error;
                statusMessage.className = 'alert alert-danger';
                this.disabled = false; // Cho phép thử lại nếu lỗi
                isSubmitting = false;
            }
        });



        /**
         * Đặt lại trạng thái chụp ảnh về ban đầu
         */
        function resetCaptureState() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            video.style.display = 'none';
            photoDisplay.style.display = 'none';
            capturedPhotoImg.src = '';
            capturedBlob = null;
            btnTakePhoto.disabled = true;
            btnSubmitAttendance.disabled = true;
        }

        // --- Khởi chạy ---
        getInitialStatus();
    });
</script>
