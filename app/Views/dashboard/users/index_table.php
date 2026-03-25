
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th class="table-cell-center logs-col-40px">
                            <input type="checkbox" id="check-all" class="checkbox-premium" title="Chọn tất cả các mục">
                        </th>
                        <th>
                            <a href="<?= base_url('users') ?>?sort=email&order=<?= ($currentSort == 'email' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo email">
                                Tài khoản / Email
                                <?php if($currentSort == 'email') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile">
                            <a href="<?= base_url('users') ?>?sort=name&order=<?= ($currentSort == 'name' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo tên nhân sự">
                                Liên kết nhân sự
                                <?php if($currentSort == 'name') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile">
                            <a href="<?= base_url('users') ?>?sort=role&order=<?= ($currentSort == 'role' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo vai trò người dùng">
                                Vai trò 
                                <?php if($currentSort == 'role') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('users') ?>?sort=status&order=<?= ($currentSort == 'status' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo trạng thái hoạt động">
                                Trạng thái 
                                <?php if($currentSort == 'status') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="table-cell-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)) { ?>
                        <tr>
                            <td colspan="6" class="empty-state-container">
                                Chưa có tài khoản nào được đăng ký.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($users as $user) { ?>
                        <tr>
                            <td class="table-cell-center">
                                <input type="checkbox" class="record-check checkbox-premium" value="<?= $user['id'] ?>" title="Chọn tài khoản này">
                            </td>
                            <td>
                                <div class="user-email font-weight-500" title="Email định danh của tài khoản">
                                    <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="text-decoration-none" title="Chỉnh sửa">
                                    <?= esc($user['email']) ?>
                                    </a>
                                </div>
                                <div class="show-mobile-only m-t-4 text-xs opacity-06">
                                    <?= esc($user['role_title']) ?> • <a href="<?= base_url('employees/edit/' . $user['emp_id']) ?>" class="text-decoration-none" title="Xem nhân viên">
                                        <?= esc($user['full_name'] ?? 'Chưa liên kết') ?> </a>
                                </div>
                            </td>
                            <td class="hide-mobile"><a href="<?= base_url('employees/edit/' . $user['emp_id']) ?>" class="text-decoration-none" title="Xem nhân viên"><?= esc($user['full_name'] ?? 'Chưa liên kết') ?></a></td>
                            <td class="hide-mobile">
                                <span class="badge-secondary-minimal text-xs" title="Phân quyền hệ thống">
                                    <?= esc($user['role_title']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($user['active_status']) { ?>
                                    <span class="status-active" title="Tài khoản đang hoạt động bình thường"><i class="fas fa-check-circle"></i> HD</span>
                                <?php } else { ?>
                                    <span class="status-locked" title="Tài khoản đã bị khóa truy cập"><i class="fas fa-lock"></i> Khóa</span>
                                <?php } ?>
                            </td>
                            <td class="table-cell-right no-wrap">
                                <?php if(has_permission('user.manage')) { ?>
                                <a href="javascript:void(0)" onclick="openPermissionModal(<?= $user['id'] ?>)" class="action-btn-icon text-apple-blue" title="Phân Quyền Cá Nhân">
                                    <i class="fas fa-key"></i>
                                </a>
                                <?php } ?>
                                <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="action-btn-icon" title="Chỉnh sửa">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <?php if(has_permission('sys.admin') && $user['id'] != session()->get('user_id')) { ?>
                                <a href="<?= base_url('impersonate/' . $user['id']) ?>" class="action-btn-icon text-blue" title="Đăng nhập hộ" onclick="return confirm('Bạn có chắc chắn muốn đăng nhập vào tài khoản này?')">
                                    <i class="fas fa-user-secret"></i>
                                </a>
                                <?php } ?>
                                <?php if(has_permission('user.manage') && $user['id'] != session()->get('user_id')) { ?>
                                <a href="<?= base_url('users/delete/' . $user['id']) ?>" class="action-btn-icon text-red" title="Xóa" onclick="return confirm('Xác nhận xóa tài khoản này?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if ($pager) { ?>
        <div class="pagination-wrapper">
            <?= $pager->links() ?>
        </div>
        <?php } ?>
