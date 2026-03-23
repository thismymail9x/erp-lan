
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>
                            <a href="<?= base_url('employees') ?>?sort=name&order=<?= ($currentSort == 'name' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo họ tên">
                                Họ tên
                                <?php if($currentSort == 'name'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="hide-mobile">Ngày sinh</th>
                        <th class="hide-mobile">Số CCCD</th>
                        <th>
                            <a href="<?= base_url('employees') ?>?sort=position&order=<?= ($currentSort == 'position' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo chức vụ">
                                Chức vụ
                                <?php if($currentSort == 'position'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('employees') ?>?sort=dept&order=<?= ($currentSort == 'dept' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo bộ phận">
                                Bộ phận
                                <?php if($currentSort == 'dept'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="table-cell-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="6" class="empty-state-container">
                                Chưa có hồ sơ nhân viên nào.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <div class="font-weight-500"><a href="<?= base_url('employees/edit/' . $emp['id']) ?>" class="text-decoration-none" title="Chỉnh sửa"><?= esc($emp['full_name']) ?></a></div>
                                <div class="show-mobile-only m-t-4 text-xs opacity-06">
                                    <?= esc($emp['position']) ?> • <?= esc($emp['department_name']) ?>
                                </div>
                            </td>
                            <td class="hide-mobile"><?= date('d/m/Y', strtotime($emp['dob'])) ?></td>
                            <td class="hide-mobile"><?= esc($emp['identity_card']) ?></td>
                            <td><?= esc($emp['position']) ?></td>
                            <td>
                                <span class="badge-secondary-minimal text-xs">
                                    <?= esc($emp['department_name']) ?>
                                </span>
                            </td>
                            <td class="table-cell-right">
                                <a href="<?= base_url('employees/edit/' . $emp['id']) ?>" class="action-btn-icon" title="Chỉnh sửa">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <?php if(session()->get('role_name') == \Config\AppConstants::ROLE_ADMIN): ?>
                                <a href="<?= base_url('employees/delete/' . $emp['id']) ?>" class="action-btn-icon text-red" title="Xóa" onclick="return confirm('Xác nhận xóa hồ sơ nhân viên này?')">
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
        <div class="pagination-wrapper">
            <?= $pager->links() ?>
        </div>
        <?php endif; ?>
