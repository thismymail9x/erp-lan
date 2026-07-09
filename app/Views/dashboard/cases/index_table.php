<?php $tagService = new \App\Services\TagService(); ?>
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
            <th class="table-cell-center text-center" style="width: 130px;">
                <a href="<?= base_url('cases') ?>?sort=code&order=<?= ($currentSort == 'code' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Sắp xếp theo mã hồ sơ">
                    Mã HĐ
                    <?php if ($currentSort == 'code') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <th style="width: 18%">
                <a href="<?= base_url('cases') ?>?sort=title&order=<?= ($currentSort == 'title' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Vụ việc">
                    Vụ việc
                    <?php if ($currentSort == 'title') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <th style="width: 15%">
                <a href="<?= base_url('cases') ?>?sort=customer&order=<?= ($currentSort == 'customer' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Khách hàng">
                    Khách hàng
                    <?php if ($currentSort == 'customer') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <th style="width: 12%">
                <a href="<?= base_url('cases') ?>?sort=lawyer&order=<?= ($currentSort == 'lawyer' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Người phụ trách">
                    Phụ trách
                    <?php if ($currentSort == 'lawyer') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <th style="width: 8%">
                Người duyệt
            </th>
            <th style="width: 12%" class="table-cell-center">
                <a href="<?= base_url('cases') ?>?sort=status&order=<?= ($currentSort == 'status' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Trạng thái">
                    Trạng thái
                    <?php if ($currentSort == 'status') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <th class="table-cell-center" style="width: 120px;">
                <a href="<?= base_url('cases') ?>?sort=kpi&order=<?= ($currentSort == 'kpi' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Sắp xếp theo số tiền thưởng đã đạt">
                    KPI (Đạt/Còn)
                    <?php if ($currentSort == 'kpi') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <?php 
            $isHanhChinhOrAdmin = (session()->get('role_name') === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
            if ($isHanhChinhOrAdmin) { ?>
            <th class="table-cell-center" style="width: 120px;">Giá trị HĐ</th>
            <?php } ?>
            <th class="table-cell-center">
                <a href="<?= base_url('cases') ?>?sort=deadline&order=<?= ($currentSort == 'deadline' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Hạn chót">
                    Hạn
                    <?php if ($currentSort == 'deadline') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <th style="width: 12%" class="table-cell-center">Thao tác</th>
        </tr>
        <?php if (has_permission('sys.admin')) { ?>
        <!-- Floating Bulk Actions Bar -->
        <div class="bulk-actions-bar">
            <span id="selected-count">0 mục đã chọn</span>
            <button type="button" class="bulk-btn-delete js-case-bulk-delete" title="Xóa hàng loạt">
                <i class="fas fa-trash-alt"></i> Xóa vĩnh viễn
            </button>
        </div>
        <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php $stt = isset($pager) ? ($pager->getCurrentPage() - 1) * $pager->getPerPage() : 0; ?>
        <?php if (empty($cases)) { ?>
            <tr>
                <td colspan="10" class="empty-state-container">
                    <i class="fas fa-folder-open"
                       style="font-size: 3rem; display: block; margin-bottom: 15px; opacity: 0.1;"></i>
                    Chưa có vụ việc nào khớp với tìm kiếm của bạn.
                </td>
            </tr>
        <?php } else { ?>
        <?php foreach ($cases as $case) { $stt++; ?>
        <tr>
            <?php if (has_permission('sys.admin')) { ?>
            <td class="table-cell-center">
                <input type="checkbox" class="record-check case-record-check" value="<?= $case['id'] ?>">
            </td>
            <?php } ?>
            <td class="table-cell-center text-muted-dark text-sm"><?= $stt ?></td>
            <td class="table-cell-center" data-label="Mã số">
                                <span class="badge-secondary-minimal text-monospace font-weight-600 text-sm">
                                    <?= esc($case['code']) ?>
                                </span>
            </td>
            <td data-label="Tên vụ việc">
                <div class="font-weight-600 text-apple-main limit-text-1">
                    <a href="<?= base_url('cases/show/' . $case['id']) ?>"
                       class="text-decoration-none"><?= esc($case['title']) ?></a>
                </div>
                <div class="tags-container-minimal m-t-4 flex-wrap" id="tags-row-<?= $case['id'] ?>">
                    <?php
                    $cTags = $tagService->getTagsByEntity($case['id'], 'cases');
                    foreach ($cTags as $ct) {
                        ?>
                        <a href="<?= base_url('tags/show/' . $ct['id']) ?>" class="tag-badge-premium"
                           style="background-color: <?= esc($ct['color']) ?>15; color: <?= esc($ct['color']) ?>; border: 1px solid <?= esc($ct['color']) ?>30; text-decoration: none;">
                            <i class="fas fa-tag m-r-4" style="font-size: 8px;"></i> <?= esc($ct['name']) ?>
                        </a>
                    <?php } ?>
                </div>
                <?php if ($case['current_step_name']) { ?>
                    <div class="text-xs m-t-4">
                        <i class="fas fa-arrow-right m-r-8" style="font-size: 9px; opacity: 0.5;"></i>
                        <span class="text-apple-blue font-weight-500">Đang ở: <?= esc($case['current_step_name']) ?></span>
                    </div>
                <?php } ?>
            </td>
            <td data-label="Khách hàng">
                <?php if ($case['customer_id']) { ?>
                    <a href="<?= base_url('customers/show/' . $case['customer_id']) ?>" class="link-premium font-weight-600 text-muted-dark text-sm">
                         <?= esc($case['customer_name'] ?: 'N/A') ?>
                    </a>
                <?php } else { ?>
                    <div class="font-weight-600 text-sm"><?= esc($case['customer_name'] ?: 'N/A') ?></div>
                <?php } ?>
            </td>
            <td data-label="Phụ trách">
                <?php if (!empty($case['assignees_data'])) { ?>
                    <div class="flex-column gap-5">
                        <?php foreach ($case['assignees_data'] as $idx => $assignee) { ?>
                            <a href="<?= base_url('employees/edit/' . $assignee['id']) ?>"
                               class="link-premium text-sm font-weight-600">
                                <?= esc($assignee['name']) ?><?= ($idx < count($case['assignees_data']) - 1) ? ',' : '' ?>
                            </a>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="font-weight-500 text-muted-dark text-sm"><?= esc($case['lawyer_name'] ?: 'Chưa phân công') ?></div>
                <?php } ?>
            </td>
            <td data-label="Người duyệt">
                <?php if (!empty($case['approvers_data'])) { ?>
                    <div class="flex-column gap-5">
                        <?php foreach ($case['approvers_data'] as $idx => $approver) { ?>
                            <a href="<?= base_url('employees/edit/' . $approver['id']) ?>"
                               class="text-sm font-weight-600 text-muted-dark hover-blue" style="text-decoration: none;">
                                <?= esc($approver['name']) ?><?= ($idx < count($case['approvers_data']) - 1) ? ',' : '' ?>
                            </a>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="font-weight-500 text-muted-dark text-sm">--</div>
                <?php } ?>
            </td>
            <td class="table-cell-center" data-label="Trạng thái">
                <?php
                $statusClasses = [
                        'cho_tiep_nhan' => 'badge-info-minimal',
                        'dang_xu_ly'    => 'badge-warning-minimal',
                        'tam_dung'      => 'badge-secondary-minimal',
                        'da_hoan_thanh' => 'badge-success-minimal',
                        'huy'           => 'badge-danger-minimal'
                ];
                $currentClass = $statusClasses[$case['status']] ?? 'badge-secondary-minimal';
                ?>
                <span class="badge-log <?= $currentClass ?> text-xs">
                    <?= $statusLabels[$case['status']] ?? $case['status'] ?>
                </span>
            </td>
            <td class="table-cell-center" data-label="KPI Thưởng">
                <div class="text-sm font-weight-500">
                    <span class="text-apple-green" title="Đã đạt"><?= number_format($case['kpi_earned'] ?? 0) ?></span>
                    <span class="text-muted mx-1">/</span>
                    <span class="text-apple-orange" title="Tiềm năng"><?= number_format($case['kpi_remaining'] ?? 0) ?></span>
                </div>
            </td>
            <?php if ($isHanhChinhOrAdmin) { ?>
            <td class="table-cell-center" data-label="Giá trị HĐ">
                <div class="text-sm font-weight-600 text-apple-blue" title="<?= esc($case['payment_progress'] ?? '') ?>">
                    <?= !empty($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') : '--' ?>
                </div>
            </td>
            <?php } ?>
            <td class="table-cell-center" data-label="Hạn chót">
                <?php
                $displayDeadline = $case['step_deadline'] ?: $case['deadline'];
                if ($displayDeadline) {
                    ?>
                    <div class="font-weight-600 <?= (strtotime($displayDeadline) < time()) ? 'text-apple-red' : 'text-apple-main' ?>">
                        <?= date('d/m/Y', strtotime($displayDeadline)) ?>
                    </div>
                <?php } else { ?>
                    <span class="text-muted-dark">--</span>
                <?php } ?>
            </td>
            <td class="table-cell-center" data-label="Thao tác">
                <a href="<?= base_url('cases/edit/' . $case['id']) ?>" class="btn-secondary-sm text-edit" title="Sửa">
                    <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn-secondary-sm text-tag js-open-quick-tag"
                        data-case-id="<?= (int)$case['id'] ?>"
                        data-case-name="<?= esc($case['title']) ?>"
                        title="Gắn nhãn">
                    <i class="fas fa-tag"></i>
                </button>
                <?php if (has_permission('sys.admin')) { ?>
                    <a href="<?= base_url('cases/delete/' . $case['id']) ?>"
                       class="btn-secondary-sm text-apple-orange js-confirm-link"
                       title="Xóa tạm thời (Trash)"
                       data-confirm-message="Bạn có chắc chắn muốn đưa hồ sơ vào thùng rác? Bạn vẫn có thể khôi phục sau này.">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <a href="<?= base_url('cases/purge/' . $case['id'] . '?force=1') ?>"
                       class="btn-secondary-sm text-red-premium js-confirm-link"
                       title="Xóa VĨNH VIỄN (Force Purge)"
                       data-confirm-message="HÀNH ĐỘNG KHÔNG THỂ PHỤC HỒI: Hệ thống sẽ xóa hồ sơ và mọi rác dữ liệu đi kèm (Bao gồm lịch sử nghiệm thu). Bạn có chắc chắn?">
                        <i class="fas fa-eraser"></i>
                    </a>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    <?php } ?>
    </tbody>
</table>
</div>

<?php if (isset($pager)) { ?>
    <div class="pager-wrapper m-t-16">
        <div class="pagination-wrapper">
            <?= $pager->links() ?>
        </div>
    </div>
<?php } ?>
