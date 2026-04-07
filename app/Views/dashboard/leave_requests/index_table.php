<div class="table-responsive">
    <table class="premium-table alternate-rows">
        <thead>
            <tr>
                <th style="width: 60px;">Mã</th>
                <th>Nhân sự</th>
                <th>Loại nghỉ</th>
                <th>Thời gian</th>
                <th>Số ngày</th>
                <th>Lý do</th>
                <th style="width: 140px;">Trạng thái</th>
                <th class="text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)) { ?>
                <tr>
                    <td colspan="8" class="text-center p-40 color-muted">
                        <i class="fas fa-calendar-times display-4 m-b-16"></i>
                        <p>Không có đơn nghỉ phép nào được tìm thấy.</p>
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach ($requests as $req) { ?>
                    <tr>
                        <td class="font-weight-bold">#<?= $req['id'] ?></td>
                        <td>
                            <div class="user-info-cell">
                                <span class="user-main-info"><?= esc($req['employee_name']) ?></span>
                                <span class="user-sub-info"><?= esc($req['position']) ?> (<?= esc($req['department_name']) ?>)</span>
                            </div>
                        </td>
                        <td>
                            <span class="tag-premium tag-info"><?= esc($req['leave_type']) ?></span>
                        </td>
                        <td>
                            <div class="date-range-cell">
                                <i class="far fa-clock color-muted"></i>&nbsp;
                                <?= date('d/m/Y', strtotime($req['start_date'])) ?> 
                                <i class="fas fa-long-arrow-alt-right m-x-8 color-muted"></i>
                                <?= date('d/m/Y', strtotime($req['end_date'])) ?>
                            </div>
                        </td>
                        <td class="text-center font-weight-bold color-primary"><?= $req['total_days'] ?></td>
                        <td class="text-truncate" style="max-width: 200px;" title="<?= esc($req['reason']) ?>">
                            <?= esc($req['reason']) ?>
                        </td>
                        <td>
                            <?php 
                                $statusClass = 'tag-pending';
                                if ($req['status'] === 'approved') $statusClass = 'tag-success';
                                if ($req['status'] === 'rejected') $statusClass = 'tag-danger';
                                if ($req['status'] === 'cancelled') $statusClass = 'tag-muted';
                            ?>
                            <span class="tag-premium <?= $statusClass ?>">
                                <?= $statusLabels[$req['status']] ?>
                            </span>
                        </td>
                        <td class="text-right d-flex justify-content-end gap-8">
                            <?php if ($req['status'] === 'pending') { ?>
                                <?php if (has_permission('leave.approve') || has_permission('sys.admin')) { ?>
                                    <button class="btn-success-sm" onclick="handleApproval(<?= $req['id'] ?>, 'approved')" title="Phê duyệt">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn-danger-sm" onclick="handleApproval(<?= $req['id'] ?>, 'rejected')" title="Từ chối">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php } elseif ($req['employee_id'] == session()->get('employee_id')) { ?>
                                    <a href="<?= base_url('leave-requests/cancel/' . $req['id']) ?>" class="btn-ghost-sm" onclick="return confirm('Bạn có chắc muốn hủy đơn này?')">
                                        Hủy đơn
                                    </a>
                                <?php } ?>
                            <?php } ?>
                            <button class="btn-ghost-sm" onclick="viewDetails(<?= htmlspecialchars(json_encode($req)) ?>)" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
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
