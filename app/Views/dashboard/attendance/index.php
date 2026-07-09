<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/attendance.css') ?>?v=2026062701">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="attendance-page-container"
     data-is-mobile="<?= $isMobile ? '1' : '0' ?>"
     data-is-admin="<?= $role === \Config\AppConstants::ROLE_ADMIN ? '1' : '0' ?>"
     data-url-status="<?= base_url('attendance/status') ?>"
     data-url-submit="<?= base_url('attendance/submit') ?>"
     data-url-office-token="<?= base_url('attendance/get-office-token') ?>">
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
        <?php if ($role !== \Config\AppConstants::ROLE_ADMIN) { ?>
            <div id="attendance-status" class="status-indicator-banner" title="Trạng thái điểm danh trong ngày của bạn">
                Đang tải dữ liệu...
            </div>
        <?php } else { ?>
            <div class="status-indicator-banner status-banner-done attendance-admin-banner">
                <i class="fas fa-user-shield"></i> Tài khoản Quản trị viên không cần chấm công.
            </div>
        <?php } ?>

        <!-- Token Logic UI -->
        <div id="office-pc-status" class="att-office-status <?= ($isAuthorized && !$isMobile) ? '' : 'is-hidden' ?>">
            <div class="lan-status-box lan-status-success att-office-authorized">
                <i class="fas fa-desktop lan-box-icon"></i>
                <h3 class="lan-box-title">Điểm danh bằng máy tính (Đã Ủy Quyền)</h3>
                <button id="btn-token-submit" class="btn btn-blue-apple">
                    <i class="fas fa-check-circle"></i> Xác nhận
                </button>
            </div>
        </div>

        <?php if ($role !== \Config\AppConstants::ROLE_ADMIN) { ?>
            <?php if ($isMobile) { ?>
                <!-- Camera Area (Only for Mobile) -->
                <div id="camera-area" class="capture-viewport is-hidden" title="Khung nhìn camera để nhận diện khuôn mặt">
                    <video id="video-preview" class="capture-video" autoplay playsinline></video>
                    <canvas id="photo-canvas" class="is-hidden"></canvas>

                    <div id="photo-placeholder" class="capture-placeholder">
                        <i class="fas fa-camera"></i>
                        Nhấn "Bắt đầu" để mở camera
                    </div>

                    <div id="captured-container" class="capture-result-preview is-hidden">
                        <img id="captured-photo" class="capture-img" src="">
                        <div class="capture-badge" title="Ảnh đã được chụp thành công">Đã chụp</div>
                    </div>
                </div>

                <div class="attendance-form-layout">
                    <div class="attendance-note-form">
                        <label for="note">Lưu ý</label>
                        <textarea id="note" rows="3"  title="Nhập lý do nếu đi muộn, về sớm hoặc có lưu ý đặc biệt"></textarea>
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
                    <?php if ($isLan || $isAuthorized) { ?>
                        <!-- LAN Attendance Area (PC in Office / Authorized) -->
                        <div class="lan-status-box lan-status-success" title="Kết nối mạng nội bộ hoặc máy tính đã ủy quyền hợp lệ">
                            <i class="fas <?= $isAuthorized ? 'fa-shield-alt' : 'fa-wifi' ?> lan-box-icon"></i>
                            <h3 class="lan-box-title"><?= $isAuthorized ? 'Máy tính đã được Ủy Quyền' : 'Đã kết nối Mạng Nội Bộ' ?></h3>
                            <p class="lan-box-desc">Hệ thống nhận diện thiết bị này an toàn cho việc điểm danh.</p>
                            <button id="btn-lan-submit" class="btn btn-blue-apple" title="Gửi xác nhận điểm danh từ thiết bị văn phòng">
                                <i class="fas fa-check-circle"></i> Xác nhận
                            </button>
                        </div>
                    <?php } else { ?>
                        <!-- PC Out of Office -->
                        <div id="out-of-office-box" class="lan-status-box lan-status-error">
                            <i class="fas fa-exclamation-triangle lan-box-icon att-error-icon"></i>
                            <h3 class="lan-box-title att-error-title">Lỗi kết nối</h3>
                            <p class="lan-box-desc att-error-desc">Chỉ được phép điểm danh bằng thiết bị PC/Laptop tại văn phòng.<br>Nếu bạn đang đi công tác, vui lòng dùng <strong>Điện thoại</strong> để điểm danh.</p>
                        </div>
                    <?php } ?>
                </div>

                <div class="attendance-note-form attendance-note-form-spaced">
                    <label for="note">Ghi chú bổ sung</label>
                    <textarea id="note" rows="3" title="Ghi chú nội dung làm việc hoặc lý do bất thường"></textarea>
                </div>
            <?php } ?>
        <?php } ?>

        <!-- Admin Authorization Section -->
        <?php if ($role === \Config\AppConstants::ROLE_ADMIN && !$isMobile) { ?>
            <div id="admin-auth-section" class="attendance-admin-auth">
                <p class="text-sm text-muted">Dành cho Quản trị viên:</p>
                <button id="btn-authorize-pc" class="btn-secondary-sm">
                    <i class="fas fa-shield-alt"></i> Ủy quyền Máy tính này
                </button>
                <p id="auth-success-msg" class="attendance-auth-success is-hidden">
                    <i class="fas fa-check-circle"></i> Máy tính này đã được ủy quyền điểm danh vĩnh viễn.
                </p>
            </div>
        <?php } ?>
    </div>

    <div class="attendance-history-link-row">
        <a href="<?= base_url('attendance/list?view=monthly') ?>"
           class="attendance-history-link" title="Xem bảng công chi tiết của bạn trong tháng">
            <i class="fas fa-clock"></i> Lịch sử tháng
        </a>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/attendance_checkin.js') ?>?v=2026062701"></script>
<?= $this->endSection() ?>
