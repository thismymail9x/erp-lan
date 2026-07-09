<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper m-b-24">
    <div class="header-title-container">
        <h2 class="content-title">Quản lý Nghỉ phép</h2>
        <p class="content-subtitle">Theo dõi, phê duyệt và đồng bộ dữ liệu nghỉ phép vào hệ thống chấm công.</p>
    </div>
    <div class="header-controls">
        <?php if (has_permission('leave.manage')) { ?>
            <a href="<?= base_url('leave-requests/create') ?>" class="btn-premium">
                <i class="fas fa-plus"></i>&nbsp; Tạo đơn
            </a>
        <?php } ?>
    </div>
</div>

<!-- Search/Filter Bar (Apple Style Row) -->
<form action="<?= base_url('leave-requests') ?>" method="GET" class="search-filter-bar m-b-24 filter-bar" id="leave-filter-form">
    <div class="search-input-group">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Tìm theo lý do, ..." value="<?= esc(request()->getGet('search')) ?>">
    </div>
    
    <select name="status" class="filter-select">
        <option value="">Tất cả trạng thái</option>
        <?php foreach ($statusLabels as $key => $label) { ?>
            <option value="<?= $key ?>" <?= $filters['status'] == $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php } ?>
    </select>

    <div class="search-input-group" id="month-group">
        <i class="fas fa-calendar-alt"></i>
        <input type="month" name="month" value="<?= esc($filters['month'] ?? '') ?>" class="form-control-premium">
    </div>

    <?php if (has_permission('leave.approve') || has_permission('sys.admin')) { ?>
        <select name="department_id" class="filter-select">
            <option value="">Tất cả phòng ban</option>
            <?php foreach ($departments as $dept) { ?>
                <option value="<?= $dept['id'] ?>" <?= $filters['department_id'] == $dept['id'] ? 'selected' : '' ?>><?= $dept['name'] ?></option>
            <?php } ?>
        </select>
    <?php } ?>
    
    <a href="<?= base_url('leave-requests') ?>" class="btn-filter-secondary">Xóa bộ lọc</a>
</form>

<!-- Danh mục Đơn nghỉ phép -->
<div class="premium-card premium-card-full overflow-hidden" id="leave-table-container">
    <?= view('dashboard/leave_requests/index_table') ?>
</div>

<!-- Modal Chi tiết & Phê duyệt -->
<div id="leaveModal" class="premium-modal" style="display: none;">
    <div class="modal-content-premium-800">
        <div class="modal-header">
            <h3>Chi tiết Đơn nghỉ phép <span id="modalId"></span></h3>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body p-24">
            <div class="row m-b-24">
                <div class="col-md-6 border-right">
                    <p class="color-muted m-b-8">Thông tin nhân sự</p>
                    <h4 id="modalName" class="m-b-4"></h4>
                    <p id="modalPos" class="color-secondary"></p>
                </div>
                <div class="col-md-6 p-l-24">
                    <p class="color-muted m-b-8">Chi tiết thời gian</p>
                    <div class="d-flex align-items-center">
                        <span class="tag-premium tag-primary" id="modalDays"></span>
                        <span class="m-x-16 color-muted">từ</span>
                        <span id="modalRange" class="font-weight-bold text-lg"></span>
                    </div>
                </div>
            </div>
            <div class="p-16 bg-light rounded m-b-24">
                <p class="color-muted m-b-8">Lý do xin nghỉ:</p>
                <p id="modalReason" class="text-lg"></p>
            </div>
            
            <div id="approvalSection" style="display: none;">
                <hr class="m-b-24">
                <div class="form-group-premium">
                    <label>Phản hồi / Ghi chú phê duyệt</label>
                    <textarea id="approvalNote" class="form-control-premium" rows="3" placeholder="Nhập lý do phê duyệt hoặc từ chối..."></textarea>
                </div>
                <div class="d-flex justify-content-end gap-16 m-t-16">
                    <button class="btn-danger" id="btnReject">Từ chối đơn</button>
                    <button class="btn-premium" id="btnApprove">Phê duyệt ngay</button>
                </div>
            </div>

            <div id="approvedInfo" style="display: none;" class="p-16 border-success-left">
                <p class="color-muted m-b-8">Thông tin phê duyệt:</p>
                <p class="m-b-4">Người duyệt: <strong id="modalApprover"></strong></p>
                <p class="m-b-4">Ngày duyệt: <span id="modalAppDate"></span></p>
                <p>Nội dung: <em id="modalAppNote"></em></p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/leave_requests.js') ?>"></script>
<?= $this->endSection() ?>
