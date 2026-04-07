<?php $tagService = new \App\Services\TagService(); ?>
<div class="table-container">
    <table class="premium-table">
        <thead>
            <tr>
                <th>Mã KH</th>
                <th style="width: 25%">Khách hàng</th>
                <th>Liên hệ</th>
                <th>Định danh</th>
                <th style="max-width: 200px;">Thông tin bổ sung</th>
                <th class="table-cell-right">Vụ việc</th>
                <th style="width: 15%" class="table-cell-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)) { ?>
                <tr>
                    <td colspan="7" class="empty-state-container">
                        <i class="fas fa-search-minus empty-state-icon" title="Không có dữ liệu"></i>
                        Không tìm thấy khách hàng nào phù hợp với bộ lọc.
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach ($customers as $customer) { 
                    $canSeePhone = has_permission('sys.admin') || ($customer['created_by'] == session()->get('employee_id'));
                    $maskedPhone = $canSeePhone ? $customer['phone'] : substr($customer['phone'], 0, 4) . '****' . substr($customer['phone'], -3);
                ?>
                <tr>
                    <td data-label="Mã KH">
                        <span class="badge-secondary-minimal text-monospace font-weight-600" title="Mã định danh duy nhất"><?= esc($customer['code']) ?></span>
                    </td>
                    <td data-label="Khách hàng">
                        <a href="<?= base_url('customers/show/' . $customer['id']) ?>" class="font-weight-600 text-apple-main text-decoration-none hover-underline">
                            <?= esc($customer['name']) ?>
                        </a>
                        <div class="text-xs text-muted-dark" title="Loại khách hàng">
                            <?= ($customer['type'] == 'ca_nhan') ? 'Cá nhân' : 'Doanh nghiệp' ?>
                            <?php if ($customer['is_blacklist']) { ?>
                                <span class="text-apple-red m-l-5" title="Blacklist"><i class="fas fa-exclamation-triangle"></i> Blacklist</span>
                            <?php } ?>
                        </div>
                        <div class="m-t-4 flex-row flex-wrap gap-4" id="tags-row-<?= $customer['id'] ?>">
                            <?php 
                                $cTags = $tagService->getTagsByEntity($customer['id'], 'customers');
                                foreach ($cTags as $ct) {
                            ?>
                                <a href="<?= base_url('tags/show/' . $ct['id']) ?>" class="tag-badge-premium" style="background-color: <?= esc($ct['color'] ?? '#eee') ?>15; color: <?= esc($ct['color'] ?? '#444') ?>; border: 1px solid <?= esc($ct['color'] ?? '#eee') ?>30; font-size: 9px; padding: 1px 6px; text-decoration: none;">
                                    <?= esc($ct['name']) ?>
                                </a>
                            <?php } ?>
                        </div>
                    </td>
                    <td data-label="Liên hệ">
                        <div class="font-weight-500 text-sm" title="Số điện thoại"><?= esc($maskedPhone ?: 'N/A') ?></div>
                        <div class="text-xs text-muted-dark" title="Email"><?= esc($canSeePhone ? $customer['email'] : '***@***.***') ?></div>
                    </td>
                    <td data-label="Định danh">
                        <div class="text-sm" title="CCCD / MST">
                            <?= $canSeePhone ? esc($customer['identity_number'] ?: $customer['tax_code'] ?: '--') : '********' ?>
                        </div>
                    </td>
                    <td data-label="Thông tin bổ sung">
                        <div class="text-xs font-weight-500 text-apple-main limit-text-1" title="<?= esc($customer['address']) ?>"><?= esc($customer['address']) ?></div>
                        <div class="text-xs text-muted-dark italic m-t-4 limit-text-1" title="<?= esc($customer['notes_internal'] ?: '--') ?>"><?= esc($customer['notes_internal'] ?: '--') ?></div>
                    </td>
                    <td class="table-cell-right" data-label="Vụ việc">
                        <span class="badge-info-minimal p-2-8 font-weight-600" title="Tổng số vụ việc">
                            <?= $customer['total_cases'] ?>
                        </span>
                    </td>
                    <td class="table-cell-center" data-label="Thao tác">
                        <div class="actions-group">
                            <a href="<?= base_url('customers/edit/' . $customer['id']) ?>" class="btn-secondary-sm text-edit" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn-secondary-sm text-tag" onclick="openQuickTag(<?= $customer['id'] ?>, '<?= esc($customer['name']) ?>')" title="Gắn nhãn">
                                <i class="fas fa-tag"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>
