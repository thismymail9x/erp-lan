<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-700 text-dark mb-1">Checklist Công Việc Hôm Nay</h1>
            <p class="text-muted font-size-0.9">Danh sách các công việc CSKH được chỉ định cho bạn cần hoàn thành trong ngày hôm nay.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('customer-care') ?>" class="btn btn-secondary d-flex align-items-center gap-2">
                <i class="fas fa-chevron-left"></i> <span>Quay lại Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Checklist Body -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-4">
            <h5 class="card-title font-weight-700 mb-4">Checklist Công Việc Cần Thực Hiện</h5>

            <?php if (empty($checklist)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <p class="m-0 font-weight-600">Hoàn hảo! Không có công việc CSKH nào cần xử lý hôm nay.</p>
                </div>
            <?php else: ?>
                <ul class="task-checklist">
                    <?php foreach ($checklist as $t): ?>
                        <li class="task-item">
                            <div class="task-checkbox-wrapper">
                                <input type="checkbox" class="task-checkbox" data-id="<?= $t['id'] ?>">
                            </div>
                            <div class="task-details">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="task-title-text"><?= esc($t['title']) ?></div>
                                        <div class="task-desc-text"><?= esc($t['description']) ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-size-0.8 font-weight-600 text-dark"><?= esc($t['customer_name']) ?></div>
                                        <div class="font-size-0.7 text-muted"><?= esc($t['customer_code']) ?></div>
                                    </div>
                                </div>
                                <div class="task-meta-row">
                                    <span class="task-channel <?= esc($t['channel']) ?>"><?= esc($t['channel']) ?></span>
                                    <span class="task-meta-item <?= strtotime($t['due_date']) < time() ? 'overdue' : '' ?>">
                                        <i class="fas fa-calendar-alt"></i> Hạn chót: <?= date('d/m/Y', strtotime($t['due_date'])) ?>
                                    </span>
                                    <span class="task-meta-item">
                                        <i class="fas fa-user-tag"></i> Nhóm: <strong class="text-capitalize"><?= esc($t['customer_segment']) ?></strong>
                                    </span>
                                    <span class="task-meta-item ml-auto">
                                        <a href="<?= base_url('customer-care/care-plan/' . $t['customer_id']) ?>" class="btn btn-xs btn-link p-0 text-primary">
                                            Xem chi tiết quy trình <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- POPUP MODAL NHẬP NOTE HOÀN THÀNH CSKH -->
<div class="modal-task-notes" id="taskNoteModal">
    <div class="modal-task-notes-content">
        <h6 class="font-weight-700 mb-2"><i class="fas fa-clipboard-check text-success"></i> Ghi Nhận Nhật Ký Chăm Sóc</h6>
        <p class="font-size-0.78 text-muted mb-3">Nhập tóm tắt phản hồi từ khách hàng hoặc ghi ghi chú kết quả chăm sóc để các nhân viên sau tiện theo dõi.</p>
        
        <div class="form-group mb-4">
            <textarea id="taskNotesInput" class="form-control font-size-0.85" rows="4" placeholder="Ví dụ: Khách hàng phản hồi rất tốt về thái độ phục vụ của Luật sư, có tiềm năng tái ký hợp đồng mới..." style="border-radius: 8px;"></textarea>
        </div>
        
        <div class="d-flex gap-2 justify-content-end">
            <button id="btnCancelTaskNote" class="btn btn-sm btn-light px-3 rounded-pill">Hủy</button>
            <button id="btnSkipTaskNote" class="btn btn-sm btn-outline-secondary px-3 rounded-pill">Bỏ qua ghi chú</button>
            <button id="btnConfirmTaskNote" class="btn btn-sm btn-success px-4 rounded-pill">Xác nhận xong</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customer_care.js') ?>"></script>
<?= $this->endSection() ?>
