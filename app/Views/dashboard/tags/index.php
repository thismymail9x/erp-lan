<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<style>
    .tag-color-circle {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        border: 1px solid rgba(0,0,0,0.1);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="tags-page-container">
    <div class="dashboard-header-wrapper m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Nhãn dán</h2>
            <p class="content-subtitle">Hệ thống phân loại hồ sơ.</p>
        </div>
        <div class="header-controls">
            <button class="btn-premium" onclick="document.getElementById('createTagModal').style.display='flex'">
                <i class="fas fa-plus"></i> Thêm nhãn
            </button>
        </div>
    </div>

    <!-- Table: Tags List -->
    <div class="premium-card premium-card-full">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 250px;">Nhãn</th>
                        <th style="width: 150px;">Loại</th>
                        <th style="width: 150px;">Phạm vi</th>
                        <th class="table-cell-right" style="width: 150px;">Đã gắn</th>
                        <th class="table-cell-center" style="width: 150px;">#</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tags)) { ?>
                        <tr>
                            <td colspan="5" class="empty-state-container p-40">
                                <i class="fas fa-tags empty-state-icon"></i>
                                Chưa có nhãn dán nào được thiết lập.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($tags as $tag) { 
                            $count = count(model('App\Services\TagService')->getTaggedEntities($tag['id']));
                        ?>
                            <tr>
                                <td>
                                    <div class="flex-row align-center">
                                        <span class="tag-color-circle" style="background-color: <?= esc($tag['color']) ?>"></span>
                                        <span class="tag-badge-premium" style="background-color: <?= esc($tag['color']) ?>15; color: <?= esc($tag['color']) ?>; border: 1px solid <?= esc($tag['color']) ?>30;">
                                            <?= esc($tag['name']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($tag['type'] == 'global') { ?>
                                        <span class="badge-log" style="background: rgba(0, 122, 255, 0.1); color: #007aff;">
                                            <i class="fas fa-globe m-r-4"></i> Chung
                                        </span>
                                    <?php } else { ?>
                                        <span class="badge-log" style="background: rgba(142, 142, 147, 0.1); color: #8e8e93;">
                                            <i class="fas fa-lock m-r-4"></i> Cá nhân
                                        </span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <span class="text-xs text-muted-dark font-weight-500">
                                        <?= esc(ucfirst($tag['module_scope'] == 'all' ? 'Toàn hệ thống' : $tag['module_scope'])) ?>
                                    </span>
                                </td>
                                <td class="table-cell-right">
                                    <span class="badge-info-minimal p-2-8 font-weight-600"><?= $count ?></span>
                                </td>
                                <td class="table-cell-center">
                                    <div class="actions-group">
                                        <a href="<?= base_url('tags/show/' . $tag['id']) ?>" class="btn-secondary-sm" title="Xem danh sách chi tiết các mục được gắn nhãn">
                                            <i class="fas fa-list"></i>
                                        </a>
                                        <?php 
                                            $canEdit = (has_permission('sys.admin') || $tag['owner_id'] == session()->get('employee_id'));
                                            if ($canEdit) { 
                                        ?>
                                            <button class="btn-secondary-sm" onclick="openEditTag(<?= htmlspecialchars(json_encode($tag)) ?>)" title="Chỉnh sửa cấu hình nhãn">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="<?= base_url('tags/delete/' . $tag['id']) ?>" class="btn-secondary-sm text-apple-red" onclick="return confirm('Xóa nhãn dán này?')" title="Xóa bỏ hoàn toàn nhãn">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals & Scripts giữ nguyên logic (Chỉ đổi Style thành Apple/Premium nếu cần) -->
<!-- Create Modal -->
<div id="createTagModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">
    <div class="premium-card p-24" style="width:420px;">
        <div class="flex-row justify-between align-center m-b-20">
            <h3 class="section-header-title">Thiết lập nhãn mới</h3>
            <span class="close-btn-minimal" onclick="document.getElementById('createTagModal').style.display='none'">&times;</span>
        </div>
        <form action="<?= base_url('tags/store') ?>" method="POST" class="flex-column gap-15">
            <?= csrf_field() ?>
            <div class="form-group-premium">
                <label class="label-premium">Tên gợi nhớ của nhãn</label>
                <input type="text" name="name" class="form-control-premium" required placeholder="Gấp, Quan trọng, Hình sự...">
            </div>
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group-premium">
                    <label class="label-premium">Màu sắc thị giác</label>
                    <input type="color" name="color" value="#007aff" class="form-control-premium" style="height: 42px; padding: 2px;">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Loại cấu hình</label>
                    <select name="type" class="form-control-premium">
                        <?php if ($isPowerUser) { ?>
                            <option value="global">Dùng chung</option>
                        <?php } ?>
                        <option value="private" selected>Dùng riêng</option>
                    </select>
                </div>
            </div>
            <div class="form-group-premium">
                <label class="label-premium">Module được phép áp dụng</label>
                <select name="module_scope" class="form-control-premium">
                    <option value="all">Tất cả Module</option>
                    <?php if (!empty($taggableModules)) : ?>
                        <?php foreach ($taggableModules as $mod) : ?>
                            <option value="<?= esc($mod['type']) ?>"><?= esc($mod['label']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-actions-row m-t-15" style="justify-content: flex-end; gap: 10px; border-top: 1px solid #f2f2f2; padding-top: 20px;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('createTagModal').style.display='none'">Hủy bỏ</button>
                <button type="submit" class="btn-premium">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal (Tương tự) -->
<div id="editTagModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">
    <div class="premium-card p-24" style="width:420px;">
        <div class="flex-row justify-between align-center m-b-20">
            <h3 class="section-header-title">Cập nhật cấu hình nhãn</h3>
            <span class="close-btn-minimal" onclick="document.getElementById('editTagModal').style.display='none'">&times;</span>
        </div>
        <form id="editTagForm" action="" method="POST" class="flex-column gap-15">
            <?= csrf_field() ?>
            <div class="form-group-premium">
                <label class="label-premium">Tên nhãn</label>
                <input type="text" name="name" id="edit_name" class="form-control-premium" required>
            </div>
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group-premium">
                    <label class="label-premium">Màu sắc</label>
                    <input type="color" name="color" id="edit_color" class="form-control-premium" style="height: 42px; padding: 2px;">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Loại</label>
                    <select name="type" id="edit_type" class="form-control-premium">
                        <option value="global">Dùng chung</option>
                        <option value="private">Dùng riêng</option>
                    </select>
                </div>
            </div>
            <div class="form-group-premium">
                <label class="label-premium">Module áp dụng</label>
                <select name="module_scope" id="edit_module_scope" class="form-control-premium">
                    <option value="all">Tất cả</option>
                    <?php if (!empty($taggableModules)) : ?>
                        <?php foreach ($taggableModules as $mod) : ?>
                            <option value="<?= esc($mod['type']) ?>"><?= esc($mod['label']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-actions-row m-t-15" style="justify-content: flex-end; gap: 10px; border-top: 1px solid #f2f2f2; padding-top: 20px;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('editTagModal').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditTag(tag) {
        document.getElementById('editTagForm').action = '<?= base_url('tags/update') ?>/' + tag.id;
        document.getElementById('edit_name').value = tag.name;
        document.getElementById('edit_color').value = tag.color;
        document.getElementById('edit_type').value = tag.type;
        document.getElementById('edit_module_scope').value = tag.module_scope;
        document.getElementById('editTagModal').style.display = 'flex';
    }
</script>
<?= $this->endSection() ?>
