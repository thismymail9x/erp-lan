<div class="zalo-sidebar-header">
    <form action="<?= base_url('messenger') ?>" method="GET" id="messengerFilterForm">
        <div style="position: relative; margin-bottom: 10px;">
            <input type="text" name="search" class="zalo-search-bar" placeholder="Tìm tên khách hàng..." value="<?= esc($filter['search']) ?>" style="padding-left: 35px;">
            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
        </div>

        <div class="zalo-filter-row">
            <select name="filter_tag" class="zalo-filter-select filter-select-msn">
                <option value="">-- Tất cả Nhãn --</option>
                <?php foreach ($allTags as $tag) { ?>
                    <option value="<?= esc($tag['name']) ?>" <?= $filter['tag'] == $tag['name'] ? 'selected' : '' ?>>#<?= esc($tag['name']) ?></option>
                <?php } ?>
            </select>

            <?php if ($isAdmin) { ?>
                <select name="filter_staff" class="zalo-filter-select filter-select-msn">
                    <option value="">-- Tất cả NV --</option>
                    <?php foreach ($staffs as $s) { ?>
                        <option value="<?= $s['user_id'] ?>" <?= $filter['staff'] == $s['user_id'] ? 'selected' : '' ?>><?= esc($s['full_name'] ?: $s['email']) ?></option>
                    <?php } ?>
                </select>
            <?php } ?>
        </div>
    </form>
</div>

<div class="zalo-conversation-list">
    <?php if (empty($contacts)) { ?>
        <div style="text-align: center; padding: 30px 20px; color: #94a3b8; font-size: 13px;">
            <i class="fab fa-facebook-messenger" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 12px;"></i>
            Chưa có khách hàng tương tác.
        </div>
    <?php } else { ?>
        <?php foreach ($contacts as $contact) { ?>
            <a href="<?= base_url('messenger?psid=' . $contact['psid']) ?>" class="conversation-link msn-conv-link" data-psid="<?= $contact['psid'] ?>" style="text-decoration: none; color: inherit;">
                <div class="conversation-item <?= ($selectedPsid == $contact['psid']) ? 'active' : '' ?>">
                    <div style="position: relative; flex-shrink: 0;">
                        <img src="<?= $contact['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($contact['display_name']) . '&background=1877f2&color=fff' ?>"
                             class="zalo-avatar" alt="Avatar">
                        <!-- Badge kênh Facebook -->
                        <span style="position: absolute; bottom: -2px; right: -2px; width: 14px; height: 14px; background: #1877f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff;">
                            <i class="fab fa-facebook-messenger" style="color: #fff; font-size: 7px;"></i>
                        </span>
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">
                            <span class="conv-name-text"><?= esc($contact['display_name']) ?></span>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 3px; flex-shrink: 0;">
                                <span class="conversation-time"><?= $contact['last_time'] ?></span>
                                <?php if ($contact['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?= $contact['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="conversation-preview"><?= esc($contact['last_message']) ?></div>
                        <div class="conversation-meta">
                            <?php
                                $staffName = $contact['assigned_staff_name'] ?? '';
                                if ($staffName) {
                                    echo '<span class="conv-staff-badge"><i class="fas fa-user-tie"></i> ' . esc($staffName) . '</span>';
                                } else {
                                    echo '<span class="conv-staff-badge" style="background: #fff1f2; color: #ef4444; border: 1px solid #fecaca; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Chưa có ai nhận</span>';
                                }
                                $cTags = json_decode($contact['tags'], true);
                                if (!empty($cTags)) {
                                    foreach (array_slice($cTags, 0, 2) as $ct) {
                                        echo '<span class="conv-tag-badge">#' . esc($ct) . '</span>';
                                    }
                                    if (count($cTags) > 2) echo '<span style="font-size: 10px; color: #94a3b8;">+' . (count($cTags) - 2) . '</span>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php } ?>
    <?php } ?>
</div>
