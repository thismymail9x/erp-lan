<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions">
    <div class="header-title-container">
        <h2 class="content-title">Danh sách tài khoản</h2>
        <p class="content-subtitle">Quản lý và phân quyền người dùng trong hệ thống.</p>
    </div>
    <?php if(session()->get('role_name') == \Config\AppConstants::ROLE_ADMIN): ?>
    <a href="<?= base_url('users/create') ?>" class="btn-premium-sm">
        <i class="fas fa-plus"></i> Tạo tài khoản
    </a>
    <?php endif; ?>
</div>

<div class="premium-card">
    <div class="table-container">
        <table class="premium-table">
            <thead>
                <tr>
                    <th>Email đăng nhập</th>
                    <th>Nhân viên sở hữu</th>
                    <th>Thuộc phòng ban</th>
                    <th>Quyền (Vai trò)</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--apple-text-muted);">
                            Không có dữ liệu tài khoản.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td style="font-weight: 500; color: var(--apple-blue);"><?= esc($user['email']) ?></td>
                        <td><?= esc($user['full_name'] ?? 'Chưa liên kết') ?></td>
                        <td><?= esc($user['department_name'] ?? 'Chưa phân') ?></td>
                        <td>
                            <span class="badge" style="background-color: #f2f2f7; color: #1d1d1f; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 500;">
                                <?= esc($user['role_title']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($user['active_status']): ?>
                                <span style="color: #34c759; font-weight: 500;"><i class="fas fa-check-circle"></i> Đang hoạt động</span>
                            <?php else: ?>
                                <span style="color: #ff3b30; font-weight: 500;"><i class="fas fa-lock"></i> Đã khóa</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="action-btn-icon" title="Chỉnh quyền/Sửa">
                                <i class="fas fa-user-edit"></i>
                            </a>
                            <?php if(session()->get('role_name') == \Config\AppConstants::ROLE_ADMIN && $user['id'] != session()->get('user_id')): ?>
                            <a href="<?= base_url('users/delete/' . $user['id']) ?>" class="action-btn-icon" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này? Hành động này không thể hoàn tác.')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
