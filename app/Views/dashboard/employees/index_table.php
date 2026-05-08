
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <?php if (has_permission('sys.admin')) { ?>
                        <th class="table-cell-center" style="width: 40px;">
                            <input type="checkbox" id="check-all" style="width: 16px; height: 16px; cursor: pointer;" title="Chọn tất cả">
                        </th>
                        <?php } ?>
                        <th class="table-cell-center" style="width: 75px;">STT (<?= $pager->getDetails()['total'] ?>)</th>
                        <th>Nhân viên</th>
                        <th class="hide-mobile">NS</th>
                        <th class="hide-mobile">CCCD</th>
                        <th>Chức vụ</th>
                        <th>Bộ phận</th>
                        <th class="table-cell-right">#</th>
                    </tr>
                    <?php if (has_permission('sys.admin')) { ?>
                    <!-- Floating Bulk Actions Bar -->
                    <div class="bulk-actions-bar">
                        <span id="selected-count">0 mục đã chọn</span>
                        <button type="button" class="bulk-btn-delete" onclick="bulkDelete()" title="Xóa hàng loạt">
                            <i class="fas fa-trash-alt"></i> Xóa vĩnh viễn
                        </button>
                    </div>
                    <?php } ?>
                </thead>
                <tbody>
                    <?php $stt = isset($pager) ? ($pager->getCurrentPage() - 1) * $pager->getPerPage() : 0; ?>
                    <?php if (empty($employees)) { ?>
                        <tr>
                            <td colspan="10" class="empty-state-container">
                                Chưa có hồ sơ nhân viên nào.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($employees as $emp) { $stt++; ?>
                        <tr>
                            <?php if (has_permission('sys.admin')) { ?>
                            <td class="table-cell-center">
                                <input type="checkbox" class="record-check" value="<?= $emp['id'] ?>" style="width: 16px; height: 16px; cursor: pointer;">
                            </td>
                            <?php } ?>
                            <td class="table-cell-center text-muted-dark text-sm"><?= $stt ?></td>
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
                                <?php if (session()->get('role_name') == \Config\AppConstants::ROLE_ADMIN) { ?>
                                <a href="<?= base_url('employees/delete/' . $emp['id']) ?>" class="action-btn-icon text-red" title="Xóa" onclick="return confirm('Xác nhận xóa hồ sơ nhân viên này?')">
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
