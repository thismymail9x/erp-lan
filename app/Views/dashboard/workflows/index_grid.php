    <div class="workflow-grid-content">
        <?php if (empty($templates)) { ?>
            <div class="premium-card p-40 text-center">
                <div class="empty-state-icon m-b-20">
                    <i class="fas fa-project-diagram fa-3x text-muted-light"></i>
                </div>
                <h3>Không tìm thấy quy trình</h3>
                <p class="text-muted-dark">Không có kết quả nào khớp với bộ lọc hiện tại.</p>
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
                                <i class="fas fa-list-ol"></i> Sửa bước
                            </a>
                            <a href="<?= base_url('workflows/duplicate/' . $t['id']) ?>" class="btn-icon-only-minimal btn-icon-duplicate" onclick="return confirm('Bạn có muốn nhân bản quy trình này thành một bản sao mới?')" title="Nhân bản quy trình">
                                <i class="far fa-copy"></i>
                            </a>
                            <a href="<?= base_url('workflows/edit/' . $t['id']) ?>" class="btn-icon-only-minimal" title="Chỉnh sửa thông tin">
                                <i class="far fa-edit"></i>
                            </a>
                            <a href="<?= base_url('workflows/delete/' . $t['id']) ?>" class="btn-icon-only-minimal text-apple-red" onclick="return confirm('Xác nhận xóa vĩnh viễn quy trình này?')" title="Xóa quy trình">
                                <i class="far fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if (isset($pager)) { ?>
                <div class="pagination-wrapper m-t-30">
                    <?= $pager->links() ?>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
