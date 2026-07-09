<div class="zalo-sidebar-header">
    <form action="<?= base_url('zalo') ?>" method="GET" id="filterForm">
        <div style="position: relative; margin-bottom: 10px;">
            <input type="text" name="search" class="zalo-search-bar" placeholder="Tìm tên, SĐT..." value="<?= esc($filter['search']) ?>" style="padding-left: 35px;">
            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
        </div>
        
        <div class="zalo-filter-row">
            <select name="filter_tag" class="zalo-filter-select filter-select">
                <option value="">-- Tất cả Nhãn --</option>
                <?php foreach ($allTags as $tag) { ?>
                    <option value="<?= esc($tag['name']) ?>" <?= $filter['tag'] == $tag['name'] ? 'selected' : '' ?>>#<?= esc($tag['name']) ?></option>
                <?php } ?>
            </select>

            <?php if ($isAdmin) { ?>
                <select name="filter_staff" class="zalo-filter-select filter-select">
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
    <?php if (empty($followers)) { ?>
        <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 13px;">
            Chưa có khách hàng tương tác.
        </div>
    <?php } else { ?>
        <?php foreach ($followers as $follower) { ?>
            <a href="<?= base_url('zalo?mid=' . $follower['zalo_id']) ?>" class="conversation-link" data-mid="<?= $follower['zalo_id'] ?>" style="text-decoration: none; color: inherit;">
                <div class="conversation-item <?= ($selectedZaloId == $follower['zalo_id']) ? 'active' : '' ?>">
                    <img src="<?= $follower['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($follower['display_name']).'&background=random' ?>" class="zalo-avatar" alt="Avatar">
                    <div class="conversation-info">
                        <div class="conversation-name">
                            <span class="conv-name-text"><?= esc($follower['display_name']) ?></span>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 3px; flex-shrink: 0;">
                                <span class="conversation-time"><?= $follower['last_time'] ?></span>
                                <?php if ($follower['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?= $follower['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="conversation-preview"><?= esc($follower['last_message']) ?></div>
                        <div class="conversation-meta">
                            <?php 
                                // Hiển thị nhân sự phụ trách
                                $staffName = $follower['assigned_staff_name'] ?? '';
                                if ($staffName) {
                                    echo '<span class="conv-staff-badge"><i class="fas fa-user-tie"></i> ' . esc($staffName) . '</span>';
                                } else {
                                    echo '<span class="conv-staff-badge" style="background: #fff1f2; color: #ef4444; border: 1px solid #fecaca; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Chưa có ai nhận</span>';
                                }
                                
                                // Hiển thị tags (tối đa 2)
                                $fTags = json_decode($follower['tags'], true);
                                if (!empty($fTags)) {
                                    foreach (array_slice($fTags, 0, 2) as $ft) {
                                        echo '<span class="conv-tag-badge">#' . esc($ft) . '</span>';
                                    }
                                    if (count($fTags) > 2) echo '<span style="font-size: 10px; color: #94a3b8;">+' . (count($fTags) - 2) . '</span>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php } ?>
    <?php } ?>
</div>
