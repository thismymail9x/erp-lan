<?php 
$tagService = new \App\Services\TagService(); 
$currentSort = service('request')->getGet('sort') ?: 'created_at';
$currentOrder = service('request')->getGet('order') ?: 'desc';

$roleName = session()->get('role_name');
$isAdminOrManager = false;
if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
    $isAdminOrManager = true;
} else {
    if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
        $isAdminOrManager = true;
    }
}

// Quyền gửi nhanh Zalo ZNS đơn lẻ và hàng loạt qua Checkbox
$canSendZnsQuick = has_permission('sys.admin') || $roleName === \Config\AppConstants::ROLE_ADMIN || $roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || has_permission('zalo.send_individual');
$canBulkDelete = has_permission('sys.admin');
$showCheckboxes = $canBulkDelete || $canSendZnsQuick;

if (!function_exists('renderSortHeader')) {
    function renderSortHeader($column, $title, $currentSort, $currentOrder) {
        $isActive = ($currentSort === $column);
        $nextOrder = ($isActive && $currentOrder === 'asc') ? 'desc' : 'asc';
        $iconClass = 'fas fa-sort text-muted';
        if ($isActive) {
            $iconClass = ($currentOrder === 'asc') ? 'fas fa-sort-up text-apple-main' : 'fas fa-sort-down text-apple-main';
        }
        
        return '<span class="sortable-header clickable" data-sort="' . $column . '" data-order="' . $nextOrder . '" style="cursor: pointer; user-select: none; display: inline-flex; align-items: center; gap: 4px;">' 
             . esc($title) 
             . ' <i class="' . $iconClass . '" style="font-size: 11px;"></i>'
             . '</span>';
    }
}
?>
<div class="table-container">
    <table class="premium-table">
        <thead>
            <tr>
                <?php if ($showCheckboxes) { ?>
                <th class="table-cell-center" style="width: 40px;">
                    <input type="checkbox" id="check-all" style="width: 16px; height: 16px; cursor: pointer;" title="Chọn tất cả">
                </th>
                <?php } ?>
                <th class="table-cell-center" style="width: 75px;">STT (<?= $pager->getDetails()['total'] ?>)</th>
                <th><?= renderSortHeader('code', 'Mã KH', $currentSort, $currentOrder) ?></th>
                <th style="width: 15%"><?= renderSortHeader('name', 'Khách hàng', $currentSort, $currentOrder) ?></th>
                <th>Liên hệ</th>
<!--                <th>Định danh</th>-->
                <th style="max-width: 25%;">Thông tin bổ sung</th>
                <th style="min-width: 180px;"><?= renderSortHeader('care_staff_name', 'Nhân sự tư vấn', $currentSort, $currentOrder) ?></th>
                <th style="min-width: 160px;"><?= renderSortHeader('care_status', 'Trạng thái tư vấn', $currentSort, $currentOrder) ?></th>
                <th class="table-cell-center gift-status-col">Qu&#224;</th>
                <th class="table-cell-right"><?= renderSortHeader('total_cases', 'Vụ việc', $currentSort, $currentOrder) ?></th>
                <th class="table-cell-center" style="width: 110px;"><?= renderSortHeader('created_at', 'Ngày tạo', $currentSort, $currentOrder) ?></th>
                <th style="width: 15%" class="table-cell-center">Thao tác</th>
            </tr>
            <?php if ($showCheckboxes) { ?>
            <!-- Floating Bulk Actions Bar -->
            <div class="bulk-actions-bar" style="display: none; align-items: center; gap: 12px;">
                <span id="selected-count">0 mục đã chọn</span>
                
                <?php if ($canBulkDelete) { ?>
                <button type="button" class="bulk-btn-delete" onclick="bulkDelete()" title="Xóa hàng loạt">
                    <i class="fas fa-trash-alt"></i> Xóa vĩnh viễn
                </button>
                <?php } ?>

                <?php if ($canSendZnsQuick) { ?>
                <button type="button" class="bulk-btn-zns btn-premium" onclick="openBulkZnsModal()" title="Gửi Zalo ZNS hàng loạt" style="background: #0068ff; color: #fff; padding: 6px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-paper-plane"></i> Gửi Zalo ZNS
                </button>
                <?php } ?>
            </div>
            <?php } ?>
        </thead>
        <tbody>
            <?php $stt = isset($pager) ? ($pager->getCurrentPage() - 1) * $pager->getPerPage() : 0; ?>
            <?php if (empty($customers)) { ?>
                <tr>
                    <td colspan="<?= $showCheckboxes ? 12 : 11 ?>" class="empty-state-container">
                        <i class="fas fa-search-minus empty-state-icon" title="Không có dữ liệu"></i>
                        Không tìm thấy khách hàng nào phù hợp với bộ lọc.
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach ($customers as $customer) { $stt++; ?>
                <tr>
                    <?php if ($showCheckboxes) { ?>
                    <td class="table-cell-center">
                        <input type="checkbox" class="record-check" value="<?= $customer['id'] ?>" style="width: 16px; height: 16px; cursor: pointer;">
                    </td>
                    <?php } ?>
                    <td class="table-cell-center text-muted-dark text-sm"><?= $stt ?></td>
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
                                    <?= esc($ct['original_name'] ?? $ct['name']) ?>
                                    <?php if ($ct['type'] === 'private' && has_permission('sys.admin')) { ?>
                                        <span class="text-apple-red" title="Nhãn cá nhân của <?= esc($ct['owner_name']) ?>" style="font-weight: bold; margin-left: 2px;">*</span>
                                    <?php } ?>
                                </a>
                            <?php } ?>
                        </div>

                        <!-- Kênh tương tác Chat & Độ nóng Lead -->
                        <?php if (!empty($customer['zalo_channels']) || !empty($customer['messenger_channels'])) { ?>
                            <div class="m-t-6" style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
                                <?php foreach ($customer['zalo_channels'] as $zalo) { ?>
                                    <a href="<?= base_url('chat?channel=zalo&contact_id=' . esc($zalo['zalo_id'])) ?>" 
                                       class="chat-channel-badge badge-zalo" 
                                       title="Bấm để chat trực tiếp với <?= esc($customer['name']) ?> qua Zalo"
                                       style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; font-size: 10px; background-color: #0068ff12; color: #0068ff; border: 1px solid #0068ff25; text-decoration: none; font-weight: 550; transition: all 0.2s;"
                                       onmouseover="this.style.backgroundColor='#0068ff20'; this.style.borderColor='#0068ff40';"
                                       onmouseout="this.style.backgroundColor='#0068ff12'; this.style.borderColor='#0068ff25';">
                                        <i class="fas fa-comment-dots" style="font-size: 10px;"></i>
                                        <span>Zalo</span>
                                        <?php if (!empty($zalo['lead_warmth']) && $zalo['lead_warmth'] !== 'cold') { 
                                            $warmthText = ($zalo['lead_warmth'] === 'hot') ? '🔥 Nóng' : '☀️ Ấm';
                                            echo '<span style="font-size: 9px; margin-left: 2px; padding: 0px 4px; border-radius: 4px; background: rgba(0,0,0,0.05);">' . $warmthText . '</span>';
                                        } ?>
                                    </a>
                                <?php } ?>
                                <?php foreach ($customer['messenger_channels'] as $fb) { ?>
                                    <a href="<?= base_url('chat?channel=messenger&contact_id=' . esc($fb['psid'])) ?>" 
                                       class="chat-channel-badge badge-messenger" 
                                       title="Bấm để chat trực tiếp với <?= esc($customer['name']) ?> qua Messenger"
                                       style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; font-size: 10px; background-color: #7209b712; color: #7209b7; border: 1px solid #7209b725; text-decoration: none; font-weight: 550; transition: all 0.2s;"
                                       onmouseover="this.style.backgroundColor='#7209b720'; this.style.borderColor='#7209b740';"
                                       onmouseout="this.style.backgroundColor='#7209b712'; this.style.borderColor='#7209b725';">
                                        <i class="fab fa-facebook-messenger" style="font-size: 10px;"></i>
                                        <span>Messenger</span>
                                        <?php if (!empty($fb['lead_warmth']) && $fb['lead_warmth'] !== 'cold') { 
                                            $warmthText = ($fb['lead_warmth'] === 'hot') ? '🔥 Nóng' : '☀️ Ấm';
                                            echo '<span style="font-size: 9px; margin-left: 2px; padding: 0px 4px; border-radius: 4px; background: rgba(0,0,0,0.05);">' . $warmthText . '</span>';
                                        } ?>
                                    </a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </td>
                    <td data-label="Liên hệ">
                        <div class="font-weight-500 text-sm" title="Số điện thoại"><?= esc($customer['phone'] ?: 'N/A') ?></div>
                        <div class="text-xs text-muted-dark" title="Email"><?= esc($customer['email'] ?: 'N/A') ?></div>
                    </td>
<!--                    <td data-label="Định danh">-->
<!--                        <div class="text-sm" title="CCCD / MST">-->
<!--                            --><?php //= $canSeePhone ? esc($customer['identity_number'] ?: $customer['tax_code'] ?: '--') : '********' ?>
<!--                        </div>-->
<!--                    </td>-->
                    <td data-label="Thông tin bổ sung">
                        <div class="text-xs font-weight-500 text-apple-main limit-text-1" title="<?= esc($customer['address']) ?>"><?= esc($customer['address']) ?></div>
                        <div class="text-xs text-muted-dark italic m-t-4 limit-text-1" title="Ghi chú: <?= esc($customer['notes_internal'] ?: '--') ?>"><?= esc($customer['notes_internal'] ?: '--') ?></div>
                    </td>
                    <td data-label="Nhân sự tư vấn" class="care-staff-cell" data-customer-id="<?= $customer['id'] ?>" data-current-staff-id="<?= esc($customer['assigned_care_staff_id'] ?? '') ?>">
                        <div class="care-staff-display-wrapper" style="position: relative; display: flex; align-items: center; min-height: 24px; width: 100%;">
                            <span class="care-staff-display-name <?= $isAdminOrManager ? 'clickable-care-staff' : '' ?> text-sm font-weight-500" style="<?= $isAdminOrManager ? 'cursor: pointer;' : '' ?> display: inline-block; width: 100%;" title="<?= $isAdminOrManager ? (!empty($customer['care_staff_name']) ? 'Đúp click để thay đổi nhân sự tư vấn' : 'Click để chọn nhân sự tư vấn') : 'Chỉ Admin hoặc Trưởng phòng mới được quyền thay đổi' ?>">
                                <?php if (!empty($customer['care_staff_name'])) { ?>
                                    <span class="text-apple-main"><i class="fas fa-user-shield text-apple-blue m-r-5"></i><?= esc($customer['care_staff_name']) ?></span>
                                <?php } else { ?>
                                    <span class="text-muted italic text-xs"><i class="fas fa-plus-circle text-muted m-r-5"></i>Trống</span>
                                <?php } ?>
                            </span>
                            
                            <!-- Hidden select dropdown, shown only during edit -->
                            <?php if ($isAdminOrManager) { ?>
                            <select class="care-staff-select-inline form-control-premium no-select2" style="display: none; width: 100%; height: 30px; padding: 2px 6px; font-size: 13px;">
                                <option value="">-- Trống --</option>
                                <?php foreach ($employees as $emp) { ?>
                                    <option value="<?= $emp['id'] ?>" <?= ($customer['assigned_care_staff_id'] == $emp['id']) ? 'selected' : '' ?>>
                                        <?= esc($emp['full_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <?php } ?>
                        </div>
                    </td>
                    <td data-label="Trạng thái tư vấn" class="care-status-cell" data-customer-id="<?= $customer['id'] ?>" data-current-status-key="<?= esc($customer['care_status'] ?? 'chua_tu_van') ?>">
                        <div class="care-status-display-wrapper" style="position: relative; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; width: 100%;">
                            <?php 
                            $canChangeStatus = $isAdminOrManager || (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == session()->get('employee_id'));
                            $statusName = 'Chưa tư vấn';
                            $statusColor = '#8e8e93';
                            foreach ($slaSettings as $s) {
                                if ($s['status_key'] === ($customer['care_status'] ?? 'chua_tu_van')) {
                                    $statusName = $s['status_name'];
                                    $statusColor = $s['color'];
                                    break;
                                }
                            }
                            ?>
                            <span class="care-status-display-name <?= $canChangeStatus ? 'clickable-care-status' : '' ?> text-sm font-weight-600" style="<?= $canChangeStatus ? 'cursor: pointer;' : '' ?> display: inline-block; width: 100%;" title="<?= $canChangeStatus ? 'Đúp click để thay đổi trạng thái tư vấn nhanh' : 'Chỉ Ban quản lý hoặc Người phụ trách chăm sóc mới được quyền thay đổi' ?>">
                                <span class="badge-care-status" style="background-color: <?= esc($statusColor) ?>15; color: <?= esc($statusColor) ?>; padding: 3px 8px; border-radius: 12px; font-size: 11px; border: 1px solid <?= esc($statusColor) ?>25;">
                                    <?= esc($statusName) ?>
                                </span>
                            </span>
                            
                            <!-- Bộ đếm ngược thời gian (SLA Countdown) -->
                            <?php if (!empty($customer['active_sla_due_time']) && !empty($customer['assigned_care_staff_id'])): ?>
                                <?php 
                                $dueTs = strtotime($customer['active_sla_due_time']);
                                $nowTs = time();
                                $diffSeconds = $dueTs - $nowTs;
                                $isOverdue = ($diffSeconds < 0 || $customer['active_sla_status'] === 'overdue');
                                ?>
                                <?php if ($isOverdue): ?>
                                    <?php 
                                    $absDiff = abs($diffSeconds);
                                    $overdueStr = format_seconds_to_duration($absDiff);
                                    ?>
                                    <div class="text-xs font-weight-700 text-apple-red" style="font-size: 10px; margin-top: 2px;" title="Quá hạn thời gian cam kết">
                                        <i class="fas fa-exclamation-triangle"></i> Trễ <?= esc($overdueStr) ?>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $remStr = format_seconds_to_duration($diffSeconds);
                                    ?>
                                    <div class="text-xs font-weight-600 text-apple-blue" style="font-size: 10px; margin-top: 2px;" title="Thời gian còn lại của bước">
                                        <i class="fas fa-clock"></i> Còn <?= esc($remStr) ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (!empty($customer['assigned_care_staff_id'])): ?>
                                <div class="text-xs text-muted-dark italic" style="font-size: 9px; margin-top: 2px;">
                                    <i class="fas fa-infinity"></i> Vô thời hạn
                                </div>
                            <?php endif; ?>
                            
                            <!-- Hidden select dropdown, shown only during edit -->
                            <?php if ($canChangeStatus) { ?>
                            <select class="care-status-select-inline form-control-premium no-select2" style="display: none; width: 100%; height: 30px; padding: 2px 6px; font-size: 13px; margin-top: 4px;">
                                <?php foreach ($slaSettings as $s) { ?>
                                    <option value="<?= esc($s['status_key']) ?>" <?= (($customer['care_status'] ?? 'chua_tu_van') === $s['status_key']) ? 'selected' : '' ?>>
                                        <?= esc($s['status_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <?php } ?>
                        </div>
                    </td>
                    <td class="table-cell-center gift-status-cell" data-label="Qu&#224;" data-customer-id="<?= $customer['id'] ?>">
                        <?php
                            $giftReceived = !empty($customer['has_received_gift']);
                            $canChangeGiftStatus = $isAdminOrManager || (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == session()->get('employee_id'));
                        ?>
                        <input type="checkbox"
                               class="gift-status-checkbox"
                               value="1"
                               <?= $giftReceived ? 'checked' : '' ?>
                               <?= $canChangeGiftStatus ? '' : 'disabled' ?>
                               title="<?= $canChangeGiftStatus ? 'Tích nếu đã tặng quà' : 'Bạn không có quyền cập nhật trạng thái quà tặng' ?>">
                    </td>
                    <td class="table-cell-right" data-label="Vụ việc">
                        <span class="badge-info-minimal p-2-8 font-weight-600" title="Tổng số vụ việc">
                            <?= $customer['total_cases'] ?>
                        </span>
                    </td>
                    <td class="table-cell-center text-muted-dark text-sm" data-label="Ngày tạo">
                        <?= date('d/m/Y', strtotime($customer['created_at'])) ?>
                    </td>
                    <td class="table-cell-center" data-label="Thao tác">
                        <div class="actions-group">
                            <?php if ($canSendZnsQuick) { ?>
                            <button type="button" class="btn-secondary-sm text-apple-blue" 
                                    onclick="openBulkZnsModal(<?= $customer['id'] ?>)" 
                                    title="Gửi Zalo ZNS nhanh" style="color: #0068ff;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <?php } ?>
                            <a href="<?= base_url('customers/edit/' . $customer['id']) ?>" class="btn-secondary-sm text-edit" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn-secondary-sm text-tag" 
                                    onclick='openQuickTag(<?= $customer['id'] ?>, "<?= esc($customer['name']) ?>", <?= json_encode(array_map("intval", array_column($cTags, "id"))) ?>)' 
                                    title="Gắn nhãn">
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

<!-- Phân trang hệ thống -->
<div class="pagination-wrapper p-20 m-t-16">
    <?= $pager->links() ?>
</div>
