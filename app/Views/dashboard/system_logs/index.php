<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
    <div class="header-title-container">
        <h2 class="content-title" style="margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em;">Nhật ký hệ thống</h2>
        <p class="content-subtitle" style="margin: 4px 0 0; color: var(--apple-text-muted);">Theo dõi dấu vết các thao tác của người dùng trên toàn hệ thống.</p>
    </div>
    
    <div class="filter-actions" style="display: flex; gap: 12px;">
        <form action="<?= base_url('system-logs') ?>" method="GET" style="display: flex; gap: 10px; align-items: center; background: #fff; padding: 6px 12px; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-calendar-alt" style="color: var(--apple-text-muted); font-size: 0.9rem;"></i>
                <input type="date" name="date" value="<?= $filters['date'] ?? '' ?>" style="border: none; font-size: 0.9rem; outline: none; background: transparent;">
            </div>
            <div style="height: 20px; width: 1px; background: var(--border-color);"></div>
            <select name="user_id" style="border: none; font-size: 0.9rem; outline: none; background: transparent; cursor: pointer; max-width: 150px;">
                <option value="">Tất cả User</option>
                <?php foreach($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= esc($u['email']) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="height: 20px; width: 1px; background: var(--border-color);"></div>
            <select name="action" style="border: none; font-size: 0.9rem; outline: none; background: transparent; cursor: pointer;">
                <option value="">Tất cả thao tác</option>
                <option value="LOGIN" <?= ($filters['action'] ?? '') == 'LOGIN' ? 'selected' : '' ?>>Đăng nhập</option>
                <option value="CREATE" <?= ($filters['action'] ?? '') == 'CREATE' ? 'selected' : '' ?>>Tạo mới</option>
                <option value="UPDATE" <?= ($filters['action'] ?? '') == 'UPDATE' ? 'selected' : '' ?>>Cập nhật</option>
                <option value="DELETE" <?= ($filters['action'] ?? '') == 'DELETE' ? 'selected' : '' ?>>Xóa</option>
            </select>
            <button type="submit" style="background: var(--apple-blue); color: #fff; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                <i class="fas fa-filter"></i> Lọc
            </button>
            <?php if(!empty($filters['date']) || !empty($filters['action'])): ?>
                <a href="<?= base_url('system-logs') ?>" style="color: var(--apple-red); font-size: 0.85rem; text-decoration: none; padding: 0 5px;" title="Xóa lọc">
                    <i class="fas fa-times-circle"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="premium-card">
    <div class="table-container">
        <table class="premium-table">
            <thead>
                <tr>
                    <th style="width: 180px;">Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Thao tác</th>
                    <th>Module</th>
                    <th>Chi tiết</th>
                    <th>Địa chỉ IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 60px; color: var(--apple-text-muted);">
                            <i class="fas fa-history" style="font-size: 2.5rem; display: block; margin-bottom: 15px; opacity: 0.2;"></i>
                            Không có dữ liệu nhật ký nào được tìm thấy.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-size: 0.85rem; color: var(--apple-text-muted);">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600; color: var(--apple-blue);"><?= esc($log['user_email'] ?? 'Hệ thống') ?></span>
                                <span style="font-size: 0.75rem; color: var(--apple-text-muted);">ID: #<?= $log['user_id'] ?></span>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $badgeClass = '';
                                switch($log['action']) {
                                    case 'CREATE': $badgeClass = 'badge-success'; break;
                                    case 'UPDATE': $badgeClass = 'badge-warning'; break;
                                    case 'DELETE': $badgeClass = 'badge-danger'; break;
                                    case 'LOGIN': $badgeClass = 'badge-info'; break;
                                    default: $badgeClass = 'badge-secondary';
                                }
                            ?>
                            <span class="badge <?= $badgeClass ?>" style="padding: 4px 10px; font-size: 0.75rem; font-weight: 700;">
                                <?= $log['action'] ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 500;"><?= esc($log['module']) ?></span>
                            <span style="color: var(--apple-text-muted);"> (ID: <?= $log['entity_id'] ?: '--' ?>)</span>
                        </td>
                        <td>
                            <div style="font-size: 0.85rem; max-width: 400px; color: var(--apple-text-muted);">
                                <?php 
                                    $details = json_decode($log['details'], true);
                                    if (is_array($details)):
                                        if (isset($details['before']) || isset($details['after'])):
                                            // Hiển thị thay đổi theo cặp
                                            $allKeys = array_unique(array_merge(array_keys($details['before'] ?? []), array_keys($details['after'] ?? [])));
                                            foreach ($allKeys as $key):
                                                if (in_array($key, ['password'])) continue; // Không hiện MK
                                                $oldVal = $details['before'][$key] ?? '<span style="color:var(--apple-blue)">Mới</span>';
                                                $newVal = $details['after'][$key] ?? '<span style="color:var(--apple-red)">Xóa</span>';
                                                echo "<div style='margin-bottom:2px;'><strong>" . esc($key) . "</strong>: " . $oldVal . " <i class='fas fa-arrow-right' style='font-size:10px;'></i> " . $newVal . "</div>";
                                            endforeach;
                                        elseif (isset($details['deleted_record'])):
                                            echo "<div style='color:var(--apple-red);'><strong>Hồ sơ bị xóa</strong>: " . esc($details['deleted_record']['full_name'] ?? $details['deleted_record']['email'] ?? '...') . "</div>";
                                        else:
                                            echo esc($log['details']);
                                        endif;
                                    else:
                                        echo esc($log['details'] ?: '---');
                                    endif;
                                ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                                <i class="fas fa-network-wired" style="opacity: 0.4;"></i>
                                <?= $log['ip_address'] ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager): ?>
    <div class="pagination-container" style="margin-top: 30px; display: flex; justify-content: center;">
        <?= $pager->links() ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .badge-success { background: #e3f9e5; color: #1f7a24; border: 1px solid #ccf0d1; }
    .badge-warning { background: #fff4e5; color: #b45d00; border: 1px solid #ffe8cc; }
    .badge-danger { background: #ffebeb; color: #cf2222; border: 1px solid #ffd1d1; }
    .badge-info { background: #e1f5fe; color: #01579b; border: 1px solid #b3e5fc; }
    .badge-secondary { background: #f5f5f7; color: #1d1d1f; border: 1px solid #d2d2d7; }
</style>

<?= $this->endSection() ?>
