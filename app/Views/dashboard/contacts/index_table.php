<?php 
    $isAdmin = has_permission('contact.admin'); 
?>
<div class="table-responsive">
    <table class="table-premium">
        <thead>
            <tr>
                <th class="table-col-checkbox">
                    <input type="checkbox" id="check-all-contacts">
                </th>
                <th style="width: 9%">Danh mục</th>
                <th>Tên đơn vị / Phụ trách</th>
                <th>Chức vụ</th>
                <th>Số điện thoại</th>
                <th style="width: 15%">Địa chỉ</th>
                <th style="width: 20%">Địa bàn / Khu vực</th>
                <th style="width: 10%">Ghi chú</th>
                <th style="width: 6%">Tỉnh thành</th>
                <?php if ($isAdmin){ ?>
                <th>Trạng thái</th>
                <?php } ?>
                <th class="table-col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contacts)) { ?>
            <tr>
                <td colspan="<?= $isAdmin ? 11 : 10 ?>" class="table-empty-container">
                    <i class="fas fa-address-book table-empty-icon"></i>
                    Không tìm thấy kết quả nào phù hợp.
                </td>
            </tr>
            <?php } else { ?>
                <?php foreach ($contacts as $contact) { 
                    $sourceTrimmed = trim($contact['source'] ?? '');
                    $sourceIndex = array_search($sourceTrimmed, array_map('trim', $sources));
                    if ($sourceIndex === false) $sourceIndex = 'default';
                ?>
                <tr class="contact-row-item">
                    <td>
                        <input type="checkbox" class="contact-checkbox" value="<?= $contact['id'] ?>">
                    </td>
                    <td>
                        <span class="badge-source-type badge-source-color-<?= $sourceIndex ?>">
                            <?= esc($contact['source'] ?: 'Chung') ?>
                        </span>
                    </td>
                    <td>
                        <div class="contact-name-cell"><?= esc($contact['unit_name']) ?></div>
                    </td>
                    <td class="text-sm"><?= esc($contact['position'] ?? '---') ?></td>
                    <td>
                        <?php if (strpos($contact['phone'] ?? '', '****') !== false) { ?>
                            <span class="contact-phone-masked" title="Dữ liệu riêng tư">
                                <i class="fas fa-lock text-xs"></i> <?= esc($contact['phone']) ?>
                            </span>
                        <?php } else { ?>
                            <span class="text-sm font-medium"><?= esc($contact['phone']) ?></span>
                        <?php } ?>
                    </td>
                    <td class="text-xs"><?= esc($contact['address'] ?? '---') ?></td>
                    <td class="text-xs text-muted"><?= esc($contact['area'] ?? '---') ?></td>
                    <td class="text-xs italic"><?= esc($contact['notes'] ?? '---') ?></td>
                    <td><span class="contact-province-badge"><?= esc($contact['province'] ?? '---') ?></span></td>
                    <?php if ($isAdmin) { ?>
                    <td>
                        <?php if ($contact['is_private']) { ?>
                            <span class="badge-private"><i class="fas fa-shield-alt"></i> Private</span>
                        <?php } else { ?>
                            <span class="badge-public"><i class="fas fa-globe"></i> Public</span>
                        <?php } ?>
                    </td>
                    <?php } ?>
                    <td class="text-right">
                        <div class="actions-group justify-end">
                            <?php if ($contact['_can_edit'] && has_permission('contact.edit')) { ?>
                            <button class="btn-secondary-sm text-edit" onclick='openContactModal(<?= $contact["id"] ?>, <?= json_encode($contact) ?>)' title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php } ?>
                            
                            <?php if (has_permission('contact.delete')) { ?>
                            <button class="btn-secondary-sm text-danger" onclick="deleteContact(<?= $contact['id'] ?>)" title="Xóa">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination-wrapper m-t-20">
    <?= $pager->links() ?>
</div>
