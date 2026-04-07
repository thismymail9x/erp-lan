<?php $tagService = new \App\Services\TagService(); ?>
<div class="table-responsive">
    <table class="premium-table">
        <thead>
        <tr>
            <th class="table-cell-center text-center" style="width: 100px;">
                <a href="<?= base_url('cases') ?>?sort=code&order=<?= ($currentSort == 'code' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>"
                   class="sort-link" title="Sắp xếp theo mã hồ sơ">
                    Mã
                    <?php if ($currentSort == 'code') { ?>
                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                    <?php } else { ?>
                        <i class="fas fa-sort sort-icon-inactive"></i>
                    <?php } ?>
                </a>
            </th>
            <th style="width: 30%">
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
            <th style="width: 15%">
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
            <th style="width: 15%" class="table-cell-center">
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
            <th style="width: 14%" class="table-cell-center">Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($cases)) { ?>
            <tr>
                <td colspan="7" class="empty-state-container">
                    <i class="fas fa-folder-open"
                       style="font-size: 3rem; display: block; margin-bottom: 15px; opacity: 0.1;"></i>
                    Chưa có vụ việc nào khớp với tìm kiếm của bạn.
                </td>
            </tr>
        <?php } else { ?>
        <?php foreach ($cases as $case) { ?>
        <tr>
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
                <a href="<?= base_url('customers/show/' . $case['customer_id']) ?>"
                   class="link-premium flex-item-center">
                    <i class="fas fa-user-circle m-r-5 text-muted-dark"></i> <?= esc($case['customer_name']) ?>
                </a>
            </td>
            <td data-label="Phụ trách">
                <?php if (!empty($case['assigned_lawyer_id'])) { ?>
                    <a href="<?= base_url('employees/edit/' . $case['assigned_lawyer_id']) ?>"
                       class="link-premium flex-item-center">
                        <i class="fas fa-user-tie m-r-5 text-muted-dark"></i> <?= esc($case['lawyer_name'] ?: 'N/A') ?>
                    </a>
                <?php } else { ?>
                    <div class="font-weight-500 text-muted-dark italic"><?= esc($case['lawyer_name'] ?: 'Chưa phân công') ?></div>
                <?php } ?>
            </td>
            <td class="table-cell-center" data-label="Trạng thái">
                <?php
                $statusClasses = [
                        'moi_tiep_nhan' => 'badge-info-minimal',
                        'dang_xu_ly' => 'badge-warning-minimal',
                        'cho_tham_tam' => 'badge-secondary-minimal',
                        'da_giai_quyet' => 'badge-success-minimal',
                        'dong_ho_so' => 'badge-success-minimal',
                        'huy' => 'badge-danger-minimal'
                ];
                ?>
                <span class="badge-log <?= $statusClasses[$case['status']] ?? 'badge-secondary-minimal' ?> text-xs">
                                    <?= $statusLabels[$case['status']] ?? $case['status'] ?>
                                </span>
            </td>
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
                <button type="button" class="btn-secondary-sm text-tag"
                        onclick="openQuickTag(<?= $case['id'] ?>, '<?= esc($case['title']) ?>')"
                        title="Gắn nhãn">
                    <i class="fas fa-tag"></i>
                </button>
                <?php if (has_permission('sys.admin')) { ?>
                    <a href="<?= base_url('cases/delete/' . $case['id']) ?>"
                       class="btn-secondary-sm text-apple-orange"
                       title="Xóa tạm thời (Trash)"
                       onclick="return confirm('Bạn có chắc chắn muốn đưa hồ sơ vào thùng rác? Bạn vẫn có thể khôi phục sau này.');">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <a href="<?= base_url('cases/purge/' . $case['id']) ?>"
                       class="btn-secondary-sm text-red-premium"
                       title="Xóa VĨNH VIỄN (Purge)"
                       onclick="return confirm('HÀNH ĐỘNG KHÔNG THỂ PHỤC HỒI: Hệ thống sẽ xóa hồ sơ và mọi rác dữ liệu đi kèm. Bạn có chắc chắn?');">
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
