<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="user-list-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Tài khoản</h2>
            <p class="content-subtitle hide-mobile">Danh sách truy cập hệ thống.</p>
        </div>
        <div class="header-controls">
            <?php if(session()->get('role_name') == \Config\AppConstants::ROLE_ADMIN): ?>
            <a href="<?= base_url('users/create') ?>" class="btn-premium">
                <i class="fas fa-plus"></i> <span class="hide-mobile">Tạo tài khoản mới</span><span class="show-mobile-only">Tạo</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="premium-card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="check-all" style="width: 18px; height: 18px; cursor: pointer;">
                        </th>
                        <th>Tài khoản / Email</th>
                        <th class="hide-mobile">Liên kết nhân sự</th>
                        <th class="hide-mobile">
                            <a href="<?= base_url('users') ?>?sort=role&order=<?= ($currentSort == 'role' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Vai trò 
                                <?php if($currentSort == 'role'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('users') ?>?sort=status&order=<?= ($currentSort == 'status' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Trạng thái 
                                <?php if($currentSort == 'status'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 60px; color: var(--apple-text-muted);">
                                Chưa có tài khoản nào được đăng ký.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="record-check" value="<?= $user['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                            </td>
                            <td>
                                <div class="user-email"><?= esc($user['email']) ?></div>
                                <div class="show-mobile-only user-meta-mobile">
                                    <?= esc($user['role_title']) ?> • <?= esc($user['full_name'] ?? 'Chưa liên kết') ?>
                                </div>
                            </td>
                            <td class="hide-mobile"><?= esc($user['full_name'] ?? 'Chưa liên kết') ?></td>
                            <td class="hide-mobile">
                                <span class="badge badge-light">
                                    <?= esc($user['role_title']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($user['active_status']): ?>
                                    <span class="status-active"><i class="fas fa-check-circle"></i> HD</span>
                                <?php else: ?>
                                    <span class="status-locked"><i class="fas fa-lock"></i> Khóa</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="action-btn-icon" title="Chỉnh sửa">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <?php if(session()->get('role_name') == \Config\AppConstants::ROLE_ADMIN && $user['id'] != session()->get('user_id')): ?>
                                <a href="<?= base_url('users/delete/' . $user['id']) ?>" class="action-btn-icon text-red" title="Xóa" onclick="return confirm('Xác nhận xóa tài khoản này?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pager): ?>
        <div class="pagination-container" style="padding: 20px; border-top: 1px solid #eee; display: flex; justify-content: center;">
            <?= $pager->links() ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Bulk Actions Bar -->
<div class="bulk-actions-bar" id="bulk-bar" style="display: none; position: fixed; bottom: 0; right: 0; background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(10px); padding: 15px 30px; box-shadow: 0 -4px 20px rgba(0,0,0,0.15); z-index: 1000; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1);">
    <span id="selected-count" style="font-weight: 600; font-size: 15px; color: white;">0 thư mục đã chọn</span>
    <div style="display: flex; gap: 12px; align-items: center;">
        <button onclick="applyBulkDelete()" class="btn-premium-sm" style="background: #ff3b30; color: white; border: none; padding: 10px 24px; font-weight: 600; border-radius: 12px; height: 44px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-trash-alt"></i> Xóa các mục đã chọn
        </button>
    </div>
</div>

<script>
// Bulk Actions Logic
const checkAll = document.getElementById('check-all');
const recordChecks = document.querySelectorAll('.record-check');
const bulkBar = document.getElementById('bulk-bar');
const selectedCount = document.getElementById('selected-count');

function updateBulkBar() {
    const checked = document.querySelectorAll('.record-check:checked');
    if (checked.length > 0) {
        bulkBar.style.display = 'flex';
        selectedCount.innerText = checked.length + ' mục đã chọn';
    } else {
        bulkBar.style.display = 'none';
    }
}

if (checkAll) {
    checkAll.addEventListener('change', function() {
        recordChecks.forEach(cb => cb.checked = checkAll.checked);
        updateBulkBar();
    });
}

recordChecks.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

async function applyBulkDelete() {
    const ids = Array.from(document.querySelectorAll('.record-check:checked')).map(cb => cb.value);
    
    if (!confirm('Hệ thống sẽ xóa vĩnh viễn ' + ids.length + ' tài khoản được chọn. Dữ liệu không thể khôi phục. Tiếp tục?')) return;

    try {
        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));

        const response = await fetch('<?= base_url('users/bulk-delete') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const res = await response.json();
        if (res.code === 0) {
            location.reload();
        } else {
            alert('Lỗi: ' + res.error);
        }
    } catch (err) {
        alert('Lỗi kết nối máy chủ');
    }
}
</script>
<?= $this->endSection() ?>
