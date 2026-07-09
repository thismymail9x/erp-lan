<?php
/**
 * _sidebar.php — Partial sidebar danh sách hội thoại (AJAX-replaceable)
 *
 * Hiển thị danh sách liên hệ đa kênh (Zalo + Messenger) với:
 * - Ô tìm kiếm tên/SĐT
 * - Bộ lọc nhãn và nhân sự (admin)
 * - Avatar + channel badge (Z xanh cho Zalo, FB icon cho Messenger)
 * - Preview tin nhắn cuối, thời gian, badge chưa đọc
 * - Staff badge, tag badges
 *
 * Biến nhận từ controller:
 * $contacts, $filter, $tags, $staffList, $isAdmin,
 * $selectedContactId, $selectedChannel
 */
?>
<div class="chat-sidebar-header">
    <form action="<?= base_url('chat') ?>" method="GET" id="filterForm">
        <?php
        /**
         * Hidden input giữ lại channel filter hiện tại khi submit form
         * để không mất bộ lọc kênh khi tìm kiếm hoặc thay đổi nhãn/nhân viên
         */
        ?>
        <?php if (!empty($selectedChannel)) { ?>
            <input type="hidden" name="channel" value="<?= esc($selectedChannel) ?>">
        <?php } ?>

        <div class="chat-search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="chat-search-bar"
                   placeholder="Tìm tên, SĐT..."
                   value="<?= esc($filter['search'] ?? '') ?>">
        </div>

        <div class="chat-filter-row">
            <select name="filter_tag" class="chat-filter-select">
                <option value="">-- Nhãn --</option>
                <?php foreach ($allTags as $tag) { ?>
                    <option value="<?= esc($tag['name']) ?>"
                        <?= (($filter['tag'] ?? '') == $tag['name']) ? 'selected' : '' ?>>
                        #<?= esc($tag['name']) ?>
                    </option>
                <?php } ?>
            </select>

            <?php if ($isAdmin) { ?>
                <select name="filter_staff" class="chat-filter-select">
                    <option value="">-- Nhân viên --</option>
                    <?php foreach ($staffs as $s) { ?>
                        <option value="<?= esc($s['user_id']) ?>"
                            <?= (($filter['staff'] ?? '') == $s['user_id']) ? 'selected' : '' ?>>
                            <?= esc($s['full_name'] ?: $s['email']) ?>
                        </option>
                    <?php } ?>
                </select>
            <?php } ?>
        </div>

        <?php if ($isAdmin) { ?>
            <div class="chat-filter-row">
                <select name="filter_creator" class="chat-filter-select">
                    <option value="">-- Người tạo --</option>
                    <?php foreach (($creators ?? []) as $creator) { ?>
                        <option value="<?= esc($creator['id']) ?>"
                            <?= (($filter['creator'] ?? '') == $creator['id']) ? 'selected' : '' ?>>
                            <?= esc($creator['full_name'] ?: ($creator['personal_email'] ?? ('NV #' . $creator['id']))) ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="chat-bulk-row">
                <label>
                    <input type="checkbox" id="chatSelectAll">
                    <span>Chọn tất cả</span>
                    <strong id="chatSelectedCount">(0)</strong>
                </label>
                <button type="button" id="chatBulkDeleteBtn" class="chat-bulk-delete-btn" disabled>
                    <i class="fas fa-trash"></i> Xóa all
                </button>
            </div>
        <?php } ?>
    </form>
</div>

<div class="chat-conversation-list">
    <?php if (empty($contacts)) { ?>
        <div class="chat-empty-list">
            <i class="fas fa-inbox"></i>
            Chưa có khách hàng tương tác.
        </div>
    <?php } else { ?>
        <?php foreach ($contacts as $contact) { ?>
            <?php
                /**
                 * Xác định thông tin kênh và URL cho mỗi liên hệ
                 * platform_id: zalo_id hoặc psid tùy kênh
                 */
                $contactChannel = $contact['channel'] ?? 'zalo';
                $platformId = $contact['platform_id'] ?? '';
                
                $qs = [
                    'channel' => $channel ?? 'all',
                    'selected_channel' => $contactChannel,
                    'contact_id' => $platformId
                ];
                if (!empty($filter['search'])) $qs['search'] = $filter['search'];
                if (!empty($filter['tag'])) $qs['filter_tag'] = $filter['tag'];
                if (!empty($filter['staff'])) $qs['filter_staff'] = $filter['staff'];
                if (!empty($filter['creator'])) $qs['filter_creator'] = $filter['creator'];
                
                $contactUrl = base_url('chat?' . http_build_query($qs));
                $isActive   = ($selectedContactId == $platformId && ($selectedChannel == $contactChannel || empty($selectedChannel)));
            ?>
            <a href="<?= $contactUrl ?>"
               class="conversation-link"
                data-channel="<?= esc($contactChannel) ?>"
               data-contact-id="<?= esc($platformId) ?>">
                <div class="conversation-item <?= $isActive ? 'active' : '' ?>">
                    <?php if ($isAdmin) { ?>
                        <div class="chat-select-cell">
                            <input type="checkbox"
                                   class="chat-contact-checkbox"
                                   data-channel="<?= esc($contactChannel) ?>"
                                   data-contact-id="<?= esc($platformId) ?>"
                                   aria-label="Chọn hội thoại <?= esc($contact['display_name']) ?>">
                        </div>
                    <?php } ?>
                    <?php
                    /**
                     * Avatar với channel badge overlay:
                     * - Zalo: hình tròn xanh dương với chữ "Z"
                     * - Messenger: hình tròn xanh dương đậm với icon messenger
                     */
                    ?>
                    <div class="avatar-wrapper">
                        <img src="<?= $contact['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($contact['display_name']) . '&background=random' ?>"
                             class="chat-avatar" alt="Avatar">
                        <?php if ($contactChannel === 'zalo') { ?>
                            <span class="channel-badge channel-badge-zalo">
                                <span class="badge-letter">Z</span>
                            </span>
                        <?php } else { ?>
                            <span class="channel-badge channel-badge-messenger">
                                <i class="fab fa-facebook-messenger"></i>
                            </span>
                        <?php } ?>
                    </div>

                    <div class="conversation-info">
                        <div class="conversation-name">
                            <div class="conv-name-text-wrapper">
                                <span class="conv-name-text"><?= esc($contact['display_name']) ?></span>
                                <?php
                                    $warmth = $contact['lead_warmth'] ?? 'cold';
                                    if ($warmth === 'hot') {
                                        echo '<i class="fa fa-fire text-danger" title="Lead Nóng 🔥" style="color: #e74c3c !important; font-size: 12px; margin-left: 2px;"></i>';
                                    } elseif ($warmth === 'warm') {
                                        echo '<i class="fa fa-sun text-warning" title="Lead Ấm ☀️" style="color: #f39c12 !important; font-size: 12px; margin-left: 2px;"></i>';
                                    } else {
                                        echo '<i class="fa fa-snowflake text-info" title="Lead Lạnh ❄️" style="color: #3498db !important; font-size: 12px; margin-left: 2px;"></i>';
                                    }

                                    if (($contact['is_duplicate'] ?? 0) == 1) {
                                        echo '<span class="badge-duplicate" title="Trùng lặp số điện thoại hoặc email!"><i class="fas fa-copy"></i> Trùng</span>';
                                    }

                                    if (($contact['is_overdue'] ?? 0) == 1 && empty($contact['first_responded_at'])) {
                                        echo '<span class="badge-overdue pulse-alert" title="Quá hạn phản hồi đầu tiên 2h!"><i class="fas fa-exclamation-triangle"></i></span>';
                                    } elseif (($contact['ongoing_is_overdue'] ?? 0) == 1) {
                                        echo '<span class="badge-overdue pulse-alert ongoing-overdue-badge" title="Quá hạn phản hồi khách đang trao đổi!"><i class="fas fa-exclamation-triangle"></i></span>';
                                    }
                                ?>
                            </div>
                            <div class="conversation-right">
                                <span class="conversation-time"><?= esc($contact['updated_at'] ?? '') ?></span>
                                <?php if (($contact['unread_count'] ?? 0) > 0) { ?>
                                    <span class="unread-badge"><?= (int)$contact['unread_count'] ?></span>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="conversation-preview"><?= esc($contact['last_message'] ?? '') ?></div>
                        <div class="conversation-meta">
                            <?php
                                /**
                                 * Hiển thị nhân sự phụ trách
                                 * Nếu chưa gán → hiện cảnh báo đỏ
                                 */
                                $staffName = $contact['assigned_name'] ?? '';
                                if ($staffName) {
                                    echo '<span class="conv-staff-badge"><i class="fas fa-user-tie"></i> ' . esc($staffName) . '</span>';
                                } else {
                                    echo '<span class="conv-staff-badge conv-staff-unassigned"><i class="fas fa-exclamation-circle"></i> Chưa nhận</span>';
                                }

                                /**
                                 * Hiển thị tags (tối đa 2 nhãn + số nhãn còn lại)
                                 */
                                $cTags = is_string($contact['tags'] ?? null)
                                    ? json_decode($contact['tags'], true)
                                    : ($contact['tags'] ?? []);
                                if (!empty($cTags)) {
                                    foreach (array_slice($cTags, 0, 2) as $ct) {
                                        echo '<span class="conv-tag-badge">#' . esc($ct) . '</span>';
                                    }
                                    if (count($cTags) > 2) {
                                        echo '<span class="extra-tag-count">+' . (count($cTags) - 2) . '</span>';
                                    }
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php } ?>
    <?php } ?>
</div>
