<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="workflow-index-container">
    <div class="dashboard-header-wrapper m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý Quy trình mẫu</h2>
            <p class="content-subtitle">Thiết lập các giai đoạn chuẩn cho từng quy trình nghiệp vụ.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('workflows/create') ?>" class="btn-premium">
                <i class="fas fa-plus"></i> Tạo quy trình mới
            </a>
        </div>
    </div>

    <div class="workflow-grid">
        <?php if (empty($templates)) { ?>
            <div class="premium-card p-40 text-center">
                <div class="empty-state-icon m-b-20">
                    <i class="fas fa-project-diagram fa-3x text-muted-light"></i>
                </div>
                <h3>Chưa có quy trình nào</h3>
                <p class="text-muted-dark">Hãy tạo quy trình đầu tiên để chuẩn hóa cách làm việc của văn phòng.</p>
                <a href="<?= base_url('workflows/create') ?>" class="btn-secondary-sm m-t-15">Bắt đầu ngay</a>
            </div>
        <?php } else { ?>
            <div class="grid-layout-premium">
                <?php foreach ($templates as $t) { ?>
                    <div class="workflow-card premium-card">
                        <div class="card-header-flex">
                            <div class="status-dot-container align-center flex-row">
                                <div class="status-dot <?= $t['is_active'] ? 'bg-apple-green' : 'bg-apple-gray' ?>" title="<?= $t['is_active'] ? 'Đang hoạt động' : 'Tạm ngưng' ?>"></div>
                                <span class="m-l-8 text-xs text-muted-dark"><?= $t['is_active'] ? 'Hoạt động' : 'Tạm ngưng' ?></span>
                            </div>
                        </div>
                        <h3 class="workflow-title"><?= esc($t['name']) ?></h3>
                        <div class="workflow-meta">
                            <div class="meta-item">
                                <i class="far fa-clock"></i>
                                <span>~<?= $t['total_estimated_days'] ?> ngày làm việc</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-code-branch"></i>
                                <span>Mã: <?= esc($t['code']) ?></span>
                            </div>
                        </div>
                        <div class="card-actions-premium m-t-20">
                            <a href="<?= base_url('workflows/steps/' . $t['id']) ?>" class="btn-secondary-sm">
                                <i class="fas fa-list-ol"></i> Thiết lập bước
                            </a>
                            <a href="<?= base_url('workflows/edit/' . $t['id']) ?>" class="btn-icon-only-minimal" title="Chỉnh sửa thông tin">
                                <i class="far fa-edit"></i>
                            </a>
                            <a href="<?= base_url('workflows/delete/' . $t['id']) ?>" class="btn-icon-only-minimal text-apple-red" onclick="return confirm('Xóa quy trình này?')" title="Xóa quy trình">
                                <i class="far fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.grid-layout-premium {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}
.workflow-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    padding: 24px;
    display: flex;
    flex-direction: column;
}
.workflow-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
}
.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.workflow-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 10px 0;
    color: var(--apple-main);
}
.workflow-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    color: var(--text-muted-dark);
    font-size: 0.9rem;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.bg-apple-green { background-color: #34c759; }
.bg-apple-gray { background-color: #8e8e93; }

.btn-icon-only-minimal {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(0,0,0,0.03);
    color: var(--apple-main);
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}
.btn-icon-only-minimal:hover {
    background: rgba(0,0,0,0.08);
}
.flex-row { display: flex; }
.align-center { align-items: center; }
.m-l-8 { margin-left: 8px; }
</style>
<?= $this->endSection() ?>
