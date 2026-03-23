
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th class="table-cell-center" style="width: 140px;">
                            <a href="<?= base_url('cases') ?>?sort=code&order=<?= ($currentSort == 'code' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo mã hồ sơ">
                                Mã số
                                <?php if($currentSort == 'code'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('cases') ?>?sort=title&order=<?= ($currentSort == 'title' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo tên vụ việc">
                                Tên vụ việc
                                <?php if($currentSort == 'title'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('cases') ?>?sort=customer&order=<?= ($currentSort == 'customer' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo tên khách hàng">
                                Khách hàng
                                <?php if($currentSort == 'customer'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('cases') ?>?sort=type&order=<?= ($currentSort == 'type' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo loại hình">
                                Loại hình
                                <?php if($currentSort == 'type'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= base_url('cases') ?>?sort=lawyer&order=<?= ($currentSort == 'lawyer' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo luật sư phụ trách">
                                Phụ trách
                                <?php if($currentSort == 'lawyer'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="table-cell-center">
                            <a href="<?= base_url('cases') ?>?sort=status&order=<?= ($currentSort == 'status' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo trạng thái">
                                Trạng thái
                                <?php if($currentSort == 'status'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="table-cell-center">
                            <a href="<?= base_url('cases') ?>?sort=deadline&order=<?= ($currentSort == 'deadline' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" title="Sắp xếp theo hạn chót">
                                Hạn chót
                                <?php if($currentSort == 'deadline'): ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort sort-icon-inactive"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="table-cell-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr>
                            <td colspan="8" class="empty-state-container">
                                <i class="fas fa-folder-open" style="font-size: 3rem; display: block; margin-bottom: 15px; opacity: 0.1;"></i>
                                Chưa có vụ việc nào khớp với tìm kiếm của bạn.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td class="table-cell-center" data-label="Mã số">
                                <span class="badge-secondary-minimal text-monospace font-weight-600 text-sm">
                                    <?= esc($case['code']) ?>
                                </span>
                            </td>
                            <td data-label="Tên vụ việc">
                                <div class="font-weight-600 text-apple-main limit-text-1">
                                    <a href="<?= base_url('cases/show/' . $case['id']) ?>" class="text-decoration-none"><?= esc($case['title']) ?></a>
                                </div>
                                <?php if($case['current_step_name']): ?>
                                    <div class="text-xs m-t-4">
                                        <i class="fas fa-arrow-right m-r-8" style="font-size: 9px; opacity: 0.5;"></i>
                                        <span class="text-apple-blue font-weight-500">Đang ở: <?= esc($case['current_step_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Khách hàng">
                                <div class="font-weight-500"><?= esc($case['customer_name']) ?></div>
                            </td>
                            <td data-label="Loại hình">
                                <?php
                                    $types = [
                                        'to_tung_dan_su' => 'Tố tụng Dân sự',
                                        'thu_tuc_hanh_chinh' => 'Thủ tục Hành chính',
                                        'xoa_an_tich' => 'Xóa án tích',
                                        'ly_hon_thuan_tinh' => 'Ly hôn',
                                        'tu_van' => 'Tư vấn pháp luật',
                                        'khac' => 'Khác'
                                    ];
                                    echo $types[$case['type']] ?? $case['type'];
                                ?>
                            </td>
                            <td data-label="Phụ trách">
                                <div class="font-weight-500"><?= esc($case['lawyer_name'] ?: 'Chưa phân công') ?></div>
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
                                    if($displayDeadline): 
                                ?>
                                    <div class="font-weight-600 <?= (strtotime($displayDeadline) < time()) ? 'text-apple-red' : 'text-apple-main' ?>">
                                        <?= date('d/m/Y', strtotime($displayDeadline)) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted-dark">--</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-cell-center" data-label="Thao tác">
                                <a href="<?= base_url('cases/show/' . $case['id']) ?>" class="btn-secondary-sm">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
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
