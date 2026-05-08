<div class="table-responsive">
    <table class="premium-table alternate-rows">
        <thead>
            <tr>
                <?php if (has_permission('sys.admin')) { ?>
                <th class="table-cell-center" style="width: 15px;">
                    <input type="checkbox" id="check-all" style="width: 16px; height: 16px; cursor: pointer;" title="Chọn tất cả">
                </th>
                <?php } ?>
                <th class="table-cell-center" style="width: 75px;">STT (<?= $pager->getDetails()['total'] ?>)</th>
                <th style="width: 170px">Nhân sự</th>
                <th style="width: 140px">Loại nghỉ</th>
                <th style="width: 150px">Thời gian</th>
                <th style="width: 100px">Số ngày</th>
                <th style="width: auto;">Lý do</th>
                <th style="width: 160px;">Trạng thái</th>
                <th style="width: 140px;" class="text-right">Thao tác</th>
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
            <?php if (empty($requests)) { ?>
                <tr>
                    <td colspan="12" class="text-center p-40 color-muted">
                        <i class="fas fa-calendar-times display-4 m-b-16"></i>
                        <p>Không có đơn nghỉ phép nào được tìm thấy.</p>
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach ($requests as $req) { $stt++; ?>
                    <tr>
                        <?php if (has_permission('sys.admin')) { ?>
                        <td class="table-cell-center">
                            <input type="checkbox" class="record-check" value="<?= $req['id'] ?>" style="width: 16px; height: 16px; cursor: pointer;">
                        </td>
                        <?php } ?>
                        <td class="table-cell-center text-muted text-xs"><?= $stt ?></td>
                        <td>
                            <div class="user-info-cell">
                                <span class="font-weight-700 text-sm"><?= esc($req['employee_name']) ?></span>
                                <span class="text-xs text-muted">(<?= esc($req['department_name']) ?>)</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge-leave-type">
                                <?= $leaveTypeLabels[$req['leave_type']] ?? $req['leave_type'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="date-range-cell text-sm">
                                <span class="text-muted"><?= date('d/m/y', strtotime($req['start_date'])) ?></span> 
                                <i class="fas fa-arrow-right m-x-4 text-xs opacity-50"></i>
                                <span class="text-muted"><?= date('d/m/y', strtotime($req['end_date'])) ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="font-weight-700 text-blue"><?= $req['total_days'] ?></span>
                            <?php if (($req['leave_duration'] ?? 'full_day') === 'morning_half'): ?>
                                <div class="text-xs text-muted" style="margin-top: 4px;">Nửa ngày (Sáng)</div>
                            <?php elseif (($req['leave_duration'] ?? 'full_day') === 'afternoon_half'): ?>
                                <div class="text-xs text-muted" style="margin-top: 4px;">Nửa ngày (Chiều)</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm color-muted-dark limit-text-1" title="<?= esc($req['reason']) ?>">
                            <?= esc($req['reason']) ?>
                        </td>
                        <td class="text-center">
                            <?php 
                                $statusClass = 'status-pending';
                                $icon = 'fa-clock';
                                if ($req['status'] === 'approved') { $statusClass = 'status-success'; $icon = 'fa-check-circle'; }
                                if ($req['status'] === 'rejected') { $statusClass = 'status-danger'; $icon = 'fa-times-circle'; }
                                if ($req['status'] === 'cancelled') { $statusClass = 'status-muted'; $icon = 'fa-ban'; }
                            ?>
                            <span class="badge-status-premium <?= $statusClass ?>">
                                <i class="fas <?= $icon ?> m-r-4"></i>
                                <?= $statusLabels[$req['status']] ?? $req['status'] ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="d-flex justify-content-end gap-10">
                                <?php if ($req['status'] === 'pending') { ?>
                                    <?php if (has_permission('leave.approve') || has_permission('sys.admin')) { ?>
                                        <button class="btn-action-success" onclick="handleApproval(<?= $req['id'] ?>, 'approved')" title="Phê duyệt">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn-action-danger" onclick="handleApproval(<?= $req['id'] ?>, 'rejected')" title="Từ chối">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php } elseif ($req['employee_id'] == session()->get('employee_id')) { ?>
                                        <a href="<?= base_url('leave-requests/cancel/' . $req['id']) ?>" class="btn-action-muted" onclick="return confirm('Bạn có chắc muốn hủy đơn này?')" title="Hủy đơn">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    <?php } ?>
                                <?php } ?>

                                <?php if (has_permission('sys.admin')) { ?>
                                    <a href="<?= base_url('leave-requests/edit/' . $req['id']) ?>" class="btn-action-primary" title="Sửa (Quản trị)">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= base_url('leave-requests/delete/' . $req['id']) ?>" class="btn-action-danger" onclick="return confirm('CẢNH BÁO: Bạn đang xóa đơn của nhân viên. Dữ liệu chấm công liên quan (nếu có) sẽ bị xóa theo. Tiếp tục?')" title="Xóa vĩnh viễn">
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

<div class="p-20" id="leave-pagination">
    <?= $pager->links() ?>
</div>
