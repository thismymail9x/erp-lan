<?php if ($selectedContact) { ?>
    <div class="chat-header">
        <a href="<?= base_url('messenger') ?>" class="btn-back-mobile" style="display: none; margin-right: 15px; color: #64748b; font-size: 20px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="position: relative; flex-shrink: 0;">
            <img src="<?= $selectedContact['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($selectedContact['display_name']) . '&background=1877f2&color=fff' ?>"
                 class="zalo-avatar" style="width: 40px; height: 40px;" alt="Avatar">
            <span style="position: absolute; bottom: -2px; right: -2px; width: 16px; height: 16px; background: #1877f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff;">
                <i class="fab fa-facebook-messenger" style="color: #fff; font-size: 8px;"></i>
            </span>
        </div>
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="font-weight: 600; font-size: 16px; color: #0f172a;"><?= esc($selectedContact['display_name']) ?></div>
            </div>
            <div style="font-size: 12px; color: #1877f2;"><i class="fas fa-circle" style="font-size: 8px;"></i> <?= $selectedContact['mid_code'] ?></div>
        </div>

        <div style="margin-left: auto; display: flex; align-items: center; gap: 10px; margin-right: 15px;">
            <span style="font-size: 12px; color: #64748b;">Phụ trách:</span>
            <?php if ($isAdmin) { ?>
                <select class="form-control" id="staffAssignment"
                        data-contact-id="<?= $selectedContact['id'] ?>"
                        style="padding: 4px 8px; font-size: 13px; border-radius: 4px; border: 1px solid #cbd5e1;">
                    <option value="">-- Chưa gán --</option>
                    <?php foreach ($staffs as $staff) { ?>
                        <option value="<?= $staff['user_id'] ?>" <?= $selectedContact['assigned_to'] == $staff['user_id'] ? 'selected' : '' ?>>
                            <?= esc($staff['full_name'] ?: $staff['email']) ?>
                        </option>
                    <?php } ?>
                </select>
            <?php } else { ?>
                <span style="font-weight: 600; font-size: 13px; color: #1877f2;">
                    <?= esc($selectedContact['assigned_staff_name'] ?? 'Chưa có ai nhận') ?>
                </span>
            <?php } ?>
        </div>

        <div class="hide-mobile" style="display: flex; align-items: center; gap: 8px;">
            <div id="chatHeaderTags" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; max-width: 220px;">
                <?php
                    $headerTags = json_decode($selectedContact['tags'], true);
                    if (!empty($headerTags)) {
                        foreach (array_slice($headerTags, 0, 3) as $ht) {
                            echo '<span class="tag-badge" style="font-size: 11px; padding: 2px 8px;">#' . esc($ht) . '</span>';
                        }
                        if (count($headerTags) > 3) {
                            echo '<span style="font-size: 11px; color: #94a3b8;">+' . (count($headerTags) - 3) . '</span>';
                        }
                    }
                ?>
            </div>
            <button class="btn-premium" style="white-space: nowrap; background: #1877f2;" onclick="$('#tagEditModal').css('display','flex')" title="Gắn nhãn">
                <i class="fas fa-tags"></i> Nhãn
            </button>
            <button class="btn-premium" style="background: #10b981;" onclick="document.getElementById('insightPanel').classList.toggle('open')">
                <i class="fas fa-address-card"></i> Hồ sơ
            </button>
        </div>
    </div>

    <!-- Vùng tin nhắn -->
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($messages)) { ?>
            <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 13px;">
                Chưa có tin nhắn nào.
            </div>
        <?php } else { ?>
            <?php foreach ($messages as $msg) { ?>
                <?php $isReceived = ($msg['sender_type'] === 'user'); ?>
                <div class="message-bubble <?= $isReceived ? 'received' : 'sent' ?>" data-msg-id="<?= $msg['id'] ?>">
                    <div class="message-content">
                        <?= esc($msg['message_text']) ?>
                        <?php
                            if ($msg['attachments']) {
                                $attachments = json_decode($msg['attachments'], true);
                                if (!empty($attachments)) {
                                    echo '<div class="message-attachments" style="margin-top: 8px; display: flex; flex-direction: column; gap: 8px;">';
                                    foreach ($attachments as $attach) {
                                        $aType = $attach['type'] ?? '';
                                        if ($aType === 'image') {
                                            $imgUrl = $attach['payload']['url'] ?? ($attach['url'] ?? '');
                                            if ($imgUrl) {
                                                echo '<div class="attach-image"><img src="' . esc($imgUrl) . '" style="max-width: 220px; border-radius: 8px; cursor: pointer;" onclick="window.open(\'' . esc($imgUrl) . '\')"></div>';
                                            }
                                        } elseif ($aType === 'audio') {
                                            $audioUrl = $attach['payload']['url'] ?? ($attach['url'] ?? '');
                                            if ($audioUrl) {
                                                echo '<div class="attach-audio" style="margin-top: 8px;">';
                                                echo '<audio controls style="max-width: 260px; outline: none; border-radius: 30px; height: 36px; display: block;">';
                                                echo '<source src="' . esc($audioUrl) . '" type="audio/mpeg">';
                                                echo '<source src="' . esc($audioUrl) . '" type="audio/ogg">';
                                                echo '<source src="' . esc($audioUrl) . '" type="audio/wav">';
                                                echo 'Trình duyệt không hỗ trợ phát trực tiếp.';
                                                echo '</audio>';
                                                echo '<a href="' . esc($audioUrl) . '" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #1877f2; margin-top: 4px; font-weight: 500; text-decoration: none;" title="Tải về máy">';
                                                echo '<i class="fas fa-external-link-alt"></i> Tải file ghi âm</a>';
                                                echo '</div>';
                                            }
                                        } elseif (in_array($aType, ['file', 'video'])) {
                                            $icon = ($aType === 'video') ? 'fa-video' : 'fa-file-download';
                                            $name = $attach['payload']['name'] ?? $aType;
                                            echo '<div class="attach-file" style="background: rgba(24,119,242,0.08); padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">';
                                            echo '<i class="fas ' . $icon . '" style="font-size: 20px; color: #1877f2;"></i>';
                                            echo '<div style="font-size: 13px; font-weight: 500;">' . esc($name) . '</div>';
                                            echo '</div>';
                                        }
                                    }
                                    echo '</div>';
                                }
                            }
                        ?>
                    </div>
                    <div class="message-time"><?= date('H:i d/m/Y', strtotime($msg['created_at'])) ?></div>
                </div>
            <?php } ?>
        <?php } ?>
        <div style="text-align: center; margin: 20px 0;">
            <span style="background: #f1f5f9; color: #64748b; padding: 4px 12px; border-radius: 12px; font-size: 11px;">
                <i class="fas fa-robot"></i> Hệ thống đã tự động cấp mã: <?= $selectedContact['mid_code'] ?>
            </span>
        </div>
    </div>

    <!-- Input gửi tin nhắn -->
    <div class="chat-input-area">
        <div class="chat-input-wrapper">
            <div class="input-actions" style="display: flex; gap: 10px; padding: 0 10px; border-right: 1px solid #e2e8f0; margin-right: 10px;">
                <i class="fas fa-bolt" style="color: #f59e0b; font-size: 18px; cursor: pointer;" title="Trả lời nhanh" onclick="$('#quickReplyModal').fadeIn()"></i>
            </div>
            <input type="text" class="chat-input" placeholder="Nhập tin nhắn hỗ trợ khách hàng qua Messenger...">
            <button class="btn-send" style="background: linear-gradient(135deg, #1877f2, #0d5bbf);">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <!-- Insight Panel -->
    <div class="insight-panel" id="insightPanel">
        <div class="insight-header">
            <h3>Thông tin khách hàng</h3>
            <i class="fas fa-times insight-close" onclick="document.getElementById('insightPanel').classList.remove('open')"></i>
        </div>

        <div class="insight-customer-card">
            <img src="<?= $selectedContact['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($selectedContact['display_name']) . '&background=1877f2&color=fff' ?>" alt="Avatar">
            <div style="font-weight: 600; font-size: 16px;"><?= esc($selectedContact['display_name']) ?></div>
            <div style="color: #1877f2; font-size: 12px;"><i class="fab fa-facebook-messenger"></i> Facebook Messenger</div>
        </div>

        <div class="insight-info-row">
            <span class="insight-info-label">Mã MID</span>
            <span class="insight-info-value"><?= $selectedContact['mid_code'] ?></span>
        </div>
        <div class="insight-info-row">
            <span class="insight-info-label">PSID Facebook</span>
            <span class="insight-info-value" style="font-size: 11px; word-break: break-all;"><?= $selectedContact['psid'] ?></span>
        </div>
        <div class="insight-info-row">
            <span class="insight-info-label">Trạng thái CRM</span>
            <span class="insight-info-value" style="color: <?= $selectedContact['customer_id'] ? '#10b981' : '#f59e0b' ?>;">
                <?= $selectedContact['customer_id'] ? 'Đã đồng bộ CRM' : 'Khách tiềm năng' ?>
            </span>
        </div>

        <div style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div style="font-size: 13px; color: #64748b;">Nhãn hội thoại</div>
                <button class="btn-filter-secondary" style="font-size: 11px; padding: 2px 8px;" onclick="$('#tagEditModal').css('display','flex')">
                    <i class="fas fa-edit"></i> Sửa
                </button>
            </div>
            <div id="currentTags">
                <?php
                    $cTagsPanel = json_decode($selectedContact['tags'], true);
                    if (!empty($cTagsPanel)) {
                        foreach ($cTagsPanel as $pt) { echo '<span class="tag-badge">#' . esc($pt) . '</span>'; }
                    } else {
                        echo '<span class="tag-badge" style="background: #e2e8f0; color: #64748b;">Chưa có nhãn</span>';
                    }
                ?>
            </div>
        </div>
    </div>

    <!-- Tag Edit Modal -->
    <div id="tagEditModal" class="modal-backdrop" style="display: none;">
        <div class="modal-content" style="background: #fff; width: 420px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h4 style="margin: 0; font-size: 16px; color: #0f172a;"><i class="fas fa-tags" style="color: #1877f2; margin-right: 8px;"></i>Gắn nhãn khách hàng</h4>
                <i class="fas fa-times" style="cursor: pointer; color: #94a3b8; font-size: 16px;" onclick="$('#tagEditModal').hide()"></i>
            </div>
            <div style="padding: 20px;">
                <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Chọn nhãn có sẵn</div>
                <div id="tagCheckboxList" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; min-height: 36px; padding: 8px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <?php foreach ($allTags as $sysTag) { ?>
                        <label style="cursor: pointer; margin: 0;">
                            <input type="checkbox" class="tag-checkbox" value="<?= esc($sysTag['name']) ?>"
                                <?= (!empty($cTagsPanel) && in_array($sysTag['name'], $cTagsPanel)) ? 'checked' : '' ?> style="display: none;">
                            <span class="tag-option" style="padding: 4px 12px; border-radius: 20px; border: 1px solid #cbd5e1; font-size: 12px; transition: all 0.2s; display: inline-block;">
                                #<?= esc($sysTag['name']) ?>
                            </span>
                        </label>
                    <?php } ?>
                    <?php if (empty($allTags)) { ?>
                        <span class="no-tags-msg" style="color: #94a3b8; font-size: 12px; align-self: center;">Chưa có nhãn nào.</span>
                    <?php } ?>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 500; text-transform: uppercase;">Tạo nhãn mới</div>
                <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <input type="text" id="newTagInput" placeholder="Nhập tên nhãn mới..."
                        style="flex: 1; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none;"
                        onkeypress="if(event.which==13){MessengerApp.createNewTag();return false;}">
                    <button onclick="MessengerApp.createNewTag()" style="padding: 8px 14px; background: #1877f2; color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer;">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                </div>
                <button class="btn-premium" style="width: 100%; background: #1877f2;" onclick="MessengerApp.saveTags(<?= $selectedContact['id'] ?>)">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
    </div>
<?php } else { ?>
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">
        <i class="fab fa-facebook-messenger" style="font-size: 56px; margin-bottom: 16px; color: #1877f2; opacity: 0.3;"></i>
        <p style="font-size: 15px;">Chọn một khách hàng để xem hội thoại.</p>
    </div>
<?php } ?>
