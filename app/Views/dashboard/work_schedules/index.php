<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<link rel="stylesheet" href="<?= base_url('css/work_schedules.css') ?>">

<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="calendar-container" data-current-employee-id="<?= esc($current_employee_id) ?>">
    <aside class="calendar-sidebar">
        <button class="btn-create-ws" id="btnOpenCreate">
            <i class="fas fa-plus"></i> Tạo lịch mới
        </button>

        <div class="filter-section">
            <div class="sidebar-section-title">Bộ lọc nhân sự</div>
            <div class="filter-group m-b-12">
                <select id="filterDept">
                    <option value="">Tất cả phòng ban</option>
                    <?php foreach ($departments as $dept) : ?>
                        <option value="<?= $dept['id'] ?>"><?= esc($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select id="filterEmployee" class="select2-basic">
                    <option value="">Tất cả nhân viên</option>
                    <?php foreach ($employees as $emp) : ?>
                        <option value="<?= $emp['id'] ?>" data-dept="<?= $emp['department_id'] ?>"><?= esc($emp['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="legend-section">
            <div class="sidebar-section-title">Loại lịch trình</div>
            <div class="type-legend">
                <div class="legend-item active" data-type="work">
                    <div class="legend-color" style="background: #3498db;"></div>
                    <span>Tại văn phòng</span>
                </div>
                <div class="legend-item active" data-type="business_trip">
                    <div class="legend-color" style="background: #e74c3c;"></div>
                    <span>Đi công tác</span>
                </div>
                <div class="legend-item active" data-type="vehicle_hint">
                    <div class="legend-color" style="background: #2563eb;"></div>
                    <span>Đăng ký xe</span>
                </div>
                <div class="legend-item active" data-type="leave">
                    <div class="legend-color" style="background: #10b981;"></div>
                    <span>Nghỉ phép</span>
                </div>
            </div>
        </div>

        <div class="info-box premium-card-lite" style="background: #f0f7ff; border: 1px solid #d0e7ff; padding: 15px; border-radius: 12px; font-size: 0.8rem; color: #0056b3;">
            <i class="fas fa-info-circle"></i> <b>Tip:</b> Bạn có thể nhấn trực tiếp vào lịch để tạo nhanh sự kiện mới. Mọi người sẽ nhận được thông báo khi bạn tạo lịch công tác.
        </div>
    </aside>

    <main class="calendar-main">
        <div id="calendar"></div>
    </main>
</div>

<!-- Modal Tạo/Sửa Lịch Trình -->
<div class="modal-overlay" id="wsModal">
    <div class="modal-content-custom">
        <div class="modal-header">
            <h3 id="modalTitle">Tạo lịch trình mới</h3>
            <button class="close-modal" id="btnCloseModal"><i class="fas fa-times"></i></button>
        </div>
        <form id="wsForm">
            <div class="modal-body-scroll">
            <input type="hidden" name="id" id="wsId">
            
            <div class="form-group">
                <label>Tiêu đề / Mục đích</label>
                <input type="text" name="title" id="wsTitle" class="form-control-custom" placeholder="Ví dụ: Họp với khách hàng A, Công tác Hà Nội..." required>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label>Loại lịch trình</label>
                    <select name="type" id="wsType" class="form-control-custom">
                        <option value="business_trip">Đi công tác</option>
                        <option value="work">Tại văn phòng</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Địa điểm</label>
                    <input type="text" name="location" id="wsLocation" class="form-control-custom" placeholder="Địa chỉ hoặc tên văn phòng">
                </div>
            </div>

            <label class="ws-vehicle-option" for="wsRequiresVehicle">
                <input type="checkbox" name="requires_vehicle" id="wsRequiresVehicle" value="1">
                <span>
                    Đăng ký xe
                    <small>Lịch trình sẽ được đánh dấu riêng trên overview để hành chính/nhân sự nhận biết nhu cầu sử dụng xe.</small>
                </span>
            </label>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ngày bắt đầu</label>
                    <input type="date" id="wsStartDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Giờ</label>
                    <input type="time" id="wsStartTime" class="form-control-custom" value="08:00" required>
                </div>
            </div>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" id="wsEndDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Giờ</label>
                    <input type="time" id="wsEndTime" class="form-control-custom" value="17:00" required>
                </div>
            </div>

            <!-- Hidden inputs to sync with original start_at and end_at -->
            <input type="hidden" name="start_at" id="wsStartAt">
            <input type="hidden" name="end_at" id="wsEndAt">

            <div class="ws-assignment-panel">
                <div class="form-group">
                    <label>Nhân sự thực hiện</label>
                    <select name="employee_id" id="wsEmployeeId" class="form-control-custom select2-basic">
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $current_employee_id ? 'selected' : '' ?>><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label><i class="fas fa-user-tag"></i> Vụ việc cá nhân / Nhận phân công từ</label>
                    <select name="assigned_by_id" id="wsAssignedById" class="form-control-custom select2-basic">
                        <option value="">-- Vụ việc cá nhân --</option>
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>"><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" id="btnDeleteWs" style="display: none; background: #fee2e2; color: #dc2626;">Xóa</button>
                <button type="button" class="btn-secondary-custom" id="btnCancelModal">Hủy</button>
                <button type="submit" class="btn-primary-custom" id="btnSaveWs">Lưu lịch trình</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<script src="<?= base_url('js/work_schedules.js') ?>"></script>
<?= $this->endSection() ?>
