<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions">
    <div class="header-title-container">
        <h2 class="content-title">Danh sách nhân viên</h2>
        <p class="content-subtitle">Quản lý hồ sơ và tài khoản nhân viên trong hệ thống.</p>
    </div>
    <a href="<?= base_url('employees/create') ?>" class="btn-premium-sm">
        <i class="fas fa-plus"></i> Thêm nhân viên
    </a>
</div>

<div class="premium-card">
    <div class="table-container">
        <table class="premium-table">
            <thead>
                <tr>
                    <th>Họ và tên</th>
                    <th>Chức vụ</th>
                    <th>Ngày tham gia</th>
                    <th>Mức lương</th>
                    <th style="text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--apple-text-muted);">
                            Chưa có dữ liệu nhân viên nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= $emp['full_name'] ?></td>
                        <td><?= $emp['position'] ?></td>
                        <td><?= date('d/m/Y', strtotime($emp['join_date'])) ?></td>
                        <td><?= number_format($emp['salary_base'], 0, ',', '.') ?> VNĐ</td>
                        <td style="text-align: right;">
                            <a href="<?= base_url('employees/edit/' . $emp['id']) ?>" class="action-btn-icon" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= base_url('employees/delete/' . $emp['id']) ?>" class="action-btn-icon" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
