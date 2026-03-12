<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="employee-list-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nhân sự</h2>
            <p class="content-subtitle hide-mobile">Quản lý hồ sơ nhân viên.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('employees/create') ?>" class="btn-premium">
                <i class="fas fa-plus"></i> <span class="hide-mobile">Thêm nhân viên</span><span class="show-mobile-only">Thêm</span>
            </a>
        </div>
    </div>

    <div class="premium-card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>
                            <a href="<?= base_url('employees') ?>?sort=name&order=<?= ($currentSort == 'name' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Họ và tên 
                                <?php if($currentSort == 'name') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('employees') ?>?sort=position&order=<?= ($currentSort == 'position' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Chức vụ 
                                <?php if($currentSort == 'position') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile">
                            <a href="<?= base_url('employees') ?>?sort=join_date&order=<?= ($currentSort == 'join_date' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Ngày tham gia 
                                <?php if($currentSort == 'join_date') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile">
                            <a href="<?= base_url('employees') ?>?sort=salary&order=<?= ($currentSort == 'salary' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Mức lương 
                                <?php if($currentSort == 'salary') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)) { ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 60px; color: var(--apple-text-muted);">
                                Chưa có dữ liệu nhân viên nào.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($employees as $emp) { ?>
                        <tr>
                            <td>
                                <div class="emp-name"><?= esc($emp['full_name']) ?></div>
                                <div class="show-mobile-only emp-meta-mobile">
                                    <?= esc($emp['position']) ?>
                                </div>
                            </td>
                            <td class="emp-position-col"><?= esc($emp['position']) ?></td>
                            <td class="hide-mobile emp-date"><span class="text-muted"><?= date('d/m/Y', strtotime($emp['join_date'])) ?></span></td>
                            <td class="hide-mobile emp-salary"><?= number_format($emp['salary_base'], 0, ',', '.') ?> <span class="currency">VNĐ</span></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= base_url('employees/edit/' . $emp['id']) ?>" class="action-btn-icon" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= base_url('employees/delete/' . $emp['id']) ?>" class="action-btn-icon text-red" title="Xóa" onclick="return confirm('Xác nhận xóa nhân viên này?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
