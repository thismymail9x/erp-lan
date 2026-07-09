<?php if (!empty($selectedContact)) { ?>
    <?php
    /**
     * _chat_area.php — Giao diện khung chat hợp nhất (AJAX-replaceable)
     *
     * Bao gồm:
     * - Chat Header: thông tin liên hệ đa kênh, trạng thái phân công nhân sự, nhãn hội thoại
     * - Khung tin nhắn: hiển thị lịch sử hội thoại, các tệp đính kèm (ảnh, sticker, video, file, audio)
     * - Khung nhập liệu: gửi tin nhắn văn bản, nút trả lời nhanh, nút đính kèm tệp (chỉ Zalo)
     * - Hồ sơ khách hàng (Insight Panel)
     * - Modal chỉnh sửa nhãn hội thoại (Tag Edit Modal)
     */
    ?>
    <div class="chat-header">
        <a href="<?= base_url('chat') ?>" class="btn-back-mobile" style="display: none; margin-right: 15px; color: #64748b; font-size: 20px;">
            <i class="fas fa-arrow-left"></i>
        </a>

        <?php
        /**
         * Avatar khách hàng kèm channel badge (Zalo / Messenger)
         */
        ?>
        <div class="avatar-wrapper" style="width: 40px; height: 40px; position: relative;">
            <img src="<?= $selectedContact['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($selectedContact['display_name']) . '&background=random' ?>"
                 class="chat-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" alt="Avatar">
            <?php if ($selectedContact['channel'] === 'zalo') { ?>
                <span class="channel-badge channel-badge-zalo" style="position: absolute; bottom: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; color: #fff; background: #0068ff; border: 2px solid #fff;">
                    <span class="badge-letter">Z</span>
                </span>
            <?php } else { ?>
                <span class="channel-badge channel-badge-messenger" style="position: absolute; bottom: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; color: #fff; background: #1877f2; border: 2px solid #fff;">
                    <i class="fab fa-facebook-messenger" style="font-size: 8px;"></i>
                </span>
            <?php } ?>
        </div>

        <div style="flex: 1; margin-left: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="font-weight: 600; font-size: 16px; color: #0f172a;"><?= esc($selectedContact['display_name']) ?></div>
                <?php if ($selectedContact['channel'] === 'zalo') { ?>
                    <i class="fas fa-sync-alt btn-sync-profile" style="font-size: 12px; color: #94a3b8; cursor: pointer;" title="Cập nhật thông tin từ Zalo" data-contact-id="<?= esc($selectedContact['zalo_id']) ?>"></i>
                <?php } ?>
            </div>
            <div style="font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <?php if ($selectedContact['channel'] === 'zalo') { ?>
                    <span style="color: #10b981; font-weight: 500;"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle; margin-right: 2px;"></i> Zalo OA</span>
                <?php } else { ?>
                    <span style="color: #1877f2; font-weight: 500;"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle; margin-right: 2px;"></i> Messenger</span>
                <?php } ?>
                <span>• MID: <?= esc($selectedContact['mid_code']) ?></span>
            </div>
        </div>
        
        <?php
        /**
         * Dropdown phân công nhân sự phụ trách chăm sóc hội thoại
         */
        ?>
        <div class="hide-mobile" style="display: flex; align-items: center; gap: 10px; margin-right: 15px;">
            <span style="font-size: 12px; color: #64748b;">Phụ trách:</span>
            <?php if ($isAdmin) { ?>
                <select class="form-control" id="staffAssignment" data-contact-id="<?= esc($selectedContact['id']) ?>" style="padding: 4px 8px; font-size: 13px; border-radius: 4px; border: 1px solid #cbd5e1; outline: none; background: #fff;">
                    <option value="">-- Chưa gán --</option>
                    <?php foreach ($staffs as $staff) { ?>
                        <option value="<?= esc($staff['user_id']) ?>" <?= ($selectedContact['assigned_to'] == $staff['user_id']) ? 'selected' : '' ?>>
                            <?= esc($staff['full_name'] ?: $staff['email']) ?>
                        </option>
                    <?php } ?>
                </select>
            <?php } else { ?>
                <span style="font-weight: 600; font-size: 13px; color: #0ea5e9;">
                    <?= esc($selectedContact['assigned_staff_name'] ?? 'Chưa nhận') ?>
                </span>
            <?php } ?>
        </div>

        <?php
        /**
         * Các nút chức năng (Đồng bộ lịch sử, Ghi cuộc gọi, Nhãn, Hồ sơ)
         */
        ?>
        <div class="hide-mobile" style="display: flex; align-items: center; gap: 8px;">
            <div id="chatHeaderTags" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; max-width: 180px;">
                <?php 
                    $headerTags = json_decode($selectedContact['tags'], true);
                    if (!empty($headerTags)) {
                        foreach (array_slice($headerTags, 0, 2) as $ht) {
                            echo '<span class="tag-badge" style="font-size: 10px; padding: 2px 6px;">#' . esc($ht) . '</span>';
                        }
                        if (count($headerTags) > 2) {
                            echo '<span style="font-size: 10px; color: #94a3b8;">+' . (count($headerTags) - 2) . '</span>';
                        }
                    }
                ?>
            </div>

            <?php if ($selectedContact['channel'] === 'zalo') { ?>
                <button class="btn-premium btn-sync-history" style="background: #3b82f6; white-space: nowrap;" data-contact-id="<?= esc($selectedContact['zalo_id']) ?>" title="Đồng bộ tin nhắn từ Zalo">
                    <i class="fas fa-sync-alt"></i> Đồng bộ
                </button>
                <button class="btn-premium btn-open-callmodal" style="background: linear-gradient(135deg, #10b981, #059669); white-space: nowrap; box-shadow: 0 4px 12px rgba(16,185,129,0.3);" title="Gọi điện cho khách hàng">
                    <i class="fas fa-phone-alt"></i> Gọi điện
                </button>
            <?php } elseif ($selectedContact['channel'] === 'messenger' && !empty($selectedContact['phone_number'])) { ?>
                <button class="btn-premium btn-open-callmodal" style="background: linear-gradient(135deg, #10b981, #059669); white-space: nowrap; box-shadow: 0 4px 12px rgba(16,185,129,0.3);" title="Gọi điện cho khách hàng">
                    <i class="fas fa-phone-alt"></i> Gọi điện
                </button>
            <?php } ?>

            <button class="btn-premium btn-open-tags" style="white-space: nowrap;" title="Gắn nhãn">
                <i class="fas fa-tags"></i> Nhãn
            </button>
            <button class="btn-premium btn-toggle-insight" style="background: #6366f1; white-space: nowrap;" title="Hồ sơ chi tiết">
                <i class="fas fa-address-card"></i> Hồ sơ
            </button>
        </div>
    </div>

    <!-- Mobile Actions Toolbar -->
    <div class="chat-mobile-actions-bar" style="display: none; overflow-x: auto; white-space: nowrap; padding: 8px 12px; border-bottom: 1px solid #cbd5e1; background: #fff; gap: 8px; align-items: center; -webkit-overflow-scrolling: touch;">
        <!-- Phụ trách dropdown / staff selection on mobile -->
        <div style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px; flex-shrink: 0;">
            <span style="font-size: 11px; color: #64748b; font-weight: 600;">Phụ trách:</span>
            <?php if ($isAdmin) { ?>
                <select class="form-control staff-assignment-select" id="staffAssignmentMobile" data-contact-id="<?= esc($selectedContact['id']) ?>" style="padding: 2px 6px; font-size: 12px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none; background: #fff; max-width: 120px;">
                    <option value="">-- Chưa gán --</option>
                    <?php foreach ($staffs as $staff) { ?>
                        <option value="<?= esc($staff['user_id']) ?>" <?= ($selectedContact['assigned_to'] == $staff['user_id']) ? 'selected' : '' ?>>
                            <?= esc($staff['full_name'] ?: $staff['email']) ?>
                        </option>
                    <?php } ?>
                </select>
            <?php } else { ?>
                <span style="font-weight: 700; font-size: 12px; color: #0ea5e9; white-space: nowrap;">
                    <?= esc($selectedContact['assigned_staff_name'] ?? 'Chưa nhận') ?>
                </span>
            <?php } ?>
        </div>

        <!-- Sync Button -->
        <?php if ($selectedContact['channel'] === 'zalo') { ?>
            <button class="btn-premium btn-sync-history" style="background: #3b82f6; white-space: nowrap; font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 600;" data-contact-id="<?= esc($selectedContact['zalo_id']) ?>">
                <i class="fas fa-sync-alt"></i> Đồng bộ
            </button>
            <button class="btn-premium btn-open-callmodal" style="background: linear-gradient(135deg, #10b981, #059669); white-space: nowrap; box-shadow: 0 2px 6px rgba(16,185,129,0.2); font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
                <i class="fas fa-phone-alt"></i> Gọi điện
            </button>
        <?php } elseif ($selectedContact['channel'] === 'messenger' && !empty($selectedContact['phone_number'])) { ?>
            <button class="btn-premium btn-open-callmodal" style="background: linear-gradient(135deg, #10b981, #059669); white-space: nowrap; box-shadow: 0 2px 6px rgba(16,185,129,0.2); font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
                <i class="fas fa-phone-alt"></i> Gọi điện
            </button>
        <?php } ?>

        <button class="btn-premium btn-open-tags" style="white-space: nowrap; font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
            <i class="fas fa-tags"></i> Nhãn
        </button>
        <button class="btn-premium btn-toggle-insight" style="background: #6366f1; white-space: nowrap; font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
            <i class="fas fa-address-card"></i> Hồ sơ
        </button>
    </div>

    <?php
    /**
     * Khung hiển thị các bong bóng tin nhắn (Message Bubbles)
     */
    ?>
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($messages)) { ?>
            <div style="text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 13px;">
                <i class="fas fa-inbox" style="font-size: 32px; color: #e2e8f0; display: block; margin-bottom: 10px;"></i>
                Chưa có tin nhắn nào trong hội thoại này.
            </div>
        <?php } else { ?>
            <?php foreach ($messages as $msg) { ?>
                <?php 
                    $isReceived = ($msg['sender_type'] === 'user'); 
                    $attachments = !empty($msg['attachments']) ? json_decode($msg['attachments'], true) : [];
                    
                    // Kiểm tra xem có phải call log system message không
                    $isCallLog = ($msg['sender_type'] === 'system' 
                        && is_array($attachments) 
                        && isset($attachments['type']) 
                        && $attachments['type'] === 'call_log');
                ?>

                <?php if ($isCallLog) { ?>
                    <?php
                        $callResult  = $attachments['result'] ?? 'answered';
                        $resultLabel = $attachments['result_label'] ?? 'Cuộc gọi';
                        $durationTxt = $attachments['duration_text'] ?? '';
                        $staffName   = $attachments['staff_name'] ?? '';
                        $callNotes   = $attachments['notes'] ?? '';
                        
                        $cssMap = ['no_answer' => 'call-no-answer', 'callback' => 'call-callback', 'rejected' => 'call-rejected'];
                        $iconMap = ['answered' => 'fa-phone', 'no_answer' => 'fa-phone-slash', 'callback' => 'fa-redo', 'rejected' => 'fa-phone-slash'];
                        
                        $resultCss   = isset($cssMap[$callResult]) ? $cssMap[$callResult] : '';
                        $resultIcon  = isset($iconMap[$callResult]) ? $iconMap[$callResult] : 'fa-phone';

                        $details = [];
                        if ($durationTxt) $details[] = '⏱ ' . esc($durationTxt);
                        if ($staffName)   $details[] = '👤 ' . esc($staffName);
                        if ($callNotes)   $details[] = '📝 ' . esc($callNotes);
                    ?>
                    <div class="message-bubble system-message" data-msg-id="<?= $msg['id'] ?>" style="display:flex;justify-content:center;padding:8px 16px;">
                        <div class="call-bubble <?= $resultCss ?>">
                            <div class="call-bubble-icon"><i class="fas <?= $resultIcon ?>"></i></div>
                            <div class="call-bubble-content">
                                <div class="call-bubble-title">📞 <?= esc($resultLabel) ?></div>
                                <?php if (!empty($details)) { ?>
                                    <div class="call-bubble-detail"><?= implode(' • ', $details) ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                <div class="message-bubble <?= $isReceived ? 'received' : 'sent' ?>" data-msg-id="<?= $msg['id'] ?>">

                    <?php if (!$isReceived && !empty($msg['staff_name'])) { ?>
                        <div class="message-staff-name" style="font-size: 10px; color: rgba(0,0,0,0.45); margin-bottom: 2px; padding: 0 4px;">
                            <?= esc($msg['staff_name']) ?>
                        </div>
                    <?php } ?>
                    
                    <div class="message-content">
                        <?= esc($msg['message_text']) ?>
                        
                    <?php
                        // Chỉ render attachments nếu là indexed array (list of items), không phải JSON object
                        $hasAttachments = !empty($attachments) && is_array($attachments) && isset($attachments[0]);
                    ?>
                    <?php if ($hasAttachments) { ?>
                        <div class="message-attachments" style="margin-top: 8px; display: flex; flex-direction: column; gap: 8px;">
                            <?php foreach ($attachments as $attach) { ?>
                                <?php if (!is_array($attach) || !isset($attach['type'])) { continue; } ?>
                                <?php if ($attach['type'] === 'image') { ?>

                                        <?php 
                                            $imageUrl = $attach['payload']['url'] ?? ($attach['url'] ?? '');
                                            if (!$imageUrl && !empty($attach['payload']['local_file'])) {
                                                $imageUrl = base_url('zalo/view-temp/' . $attach['payload']['local_file']);
                                            }
                                        ?>
                                        <?php if ($imageUrl) { ?>
                                            <div class="attach-image">
                                                <img src="<?= esc($imageUrl) ?>" class="js-open-attachment" data-url="<?= esc($imageUrl) ?>" style="max-width: 220px; border-radius: 8px; cursor: pointer; transition: transform 0.2s;" alt="Attachment">
                                            </div>
                                        <?php } ?>
                                    <?php } elseif ($attach['type'] === 'sticker') { ?>
                                        <?php $stickerUrl = $attach['payload']['url'] ?? ''; ?>
                                        <?php if ($stickerUrl) { ?>
                                            <div class="attach-sticker">
                                                <img src="<?= esc($stickerUrl) ?>" style="width: 100px; display: block;" alt="Sticker">
                                            </div>
                                        <?php } ?>
                                    <?php } elseif ($attach['type'] === 'audio') { ?>
                                        <?php 
                                            $audioUrl = $attach['payload']['url'] ?? ($attach['url'] ?? '');
                                            if (!$audioUrl && $selectedContact['channel'] === 'zalo' && !empty($attach['payload']['token'])) {
                                                $audioUrl = base_url('zalo/download-attachment?msg_id='.$msg['id'].'&token='.urlencode($attach['payload']['token']).'&name=voice.mp3');
                                            }
                                        ?>
                                        <?php if ($audioUrl) { ?>
                                            <div class="attach-audio" style="margin-top: 8px;">
                                                <audio controls style="max-width: 260px; outline: none; border-radius: 30px; height: 36px; display: block;">
                                                    <source src="<?= esc($audioUrl) ?>" type="audio/mpeg">
                                                    <source src="<?= esc($audioUrl) ?>" type="audio/ogg">
                                                    <source src="<?= esc($audioUrl) ?>" type="audio/wav">
                                                    Trình duyệt không hỗ trợ phát trực tiếp.
                                                </audio>
                                                <?php if ($selectedContact['channel'] === 'zalo' && !empty($attach['payload']['token'])) { ?>
                                                    <a href="<?= base_url('zalo/download-attachment?msg_id='.$msg['id'].'&token='.urlencode($attach['payload']['token']).'&name=voice.mp3') ?>" 
                                                       style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #3b82f6; margin-top: 4px; font-weight: 500; text-decoration: none;" title="Tải về máy">
                                                        <i class="fas fa-cloud-download-alt"></i> Tải file ghi âm
                                                    </a>
                                                <?php } elseif (!empty($attach['url'])) { ?>
                                                    <a href="<?= esc($attach['url']) ?>" target="_blank"
                                                       style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #3b82f6; margin-top: 4px; font-weight: 500; text-decoration: none;" title="Tải về máy">
                                                        <i class="fas fa-external-link-alt"></i> Tải file ghi âm
                                                    </a>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    <?php } elseif (in_array($attach['type'], ['file', 'video'])) { ?>
                                        <?php 
                                            $name = $attach['payload']['name'] ?? (($attach['type'] === 'video') ? 'Video' : 'File');
                                            $size = $attach['payload']['size'] ?? 0;
                                            $sizeStr = $size > 1048576 ? round($size/1048576, 2) . ' MB' : round($size/1024, 2) . ' KB';
                                            $iconClass = ($attach['type'] === 'video') ? 'fa-video' : 'fa-file-download';
                                        ?>
                                        <div class="attach-file" style="background: rgba(0,0,0,0.06); padding: 8px 12px; border-radius: 8px; display: flex; align-items: center; gap: 10px; min-width: 180px; max-width: 280px; color: inherit;">
                                            <i class="fas <?= $iconClass ?>" style="font-size: 20px; color: #3b82f6; flex-shrink: 0;"></i>
                                            <div style="flex: 1; overflow: hidden; text-align: left;">
                                                <div style="font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: inherit;">
                                                    <?= esc($name) ?>
                                                </div>
                                                <?php if ($size > 0) { ?>
                                                    <div style="font-size: 11px; opacity: 0.7; color: inherit;"><?= $sizeStr ?></div>
                                                <?php } ?>
                                            </div>
                                            <?php if ($selectedContact['channel'] === 'zalo' && !empty($attach['payload']['token'])) { ?>
                                                <a href="<?= base_url('zalo/download-attachment?msg_id='.$msg['id'].'&token='.urlencode($attach['payload']['token']).'&name='.urlencode($name).'&size='.$size) ?>" class="btn-download" style="color: #3b82f6; font-size: 16px; padding: 4px;" title="Tải xuống tệp">
                                                     <i class="fas fa-cloud-download-alt"></i>
                                                </a>
                                            <?php } elseif (!empty($attach['url'])) { ?>
                                                <a href="<?= esc($attach['url']) ?>" target="_blank" class="btn-download" style="color: #3b82f6; font-size: 16px; padding: 4px;" title="Xem liên kết">
                                                     <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                             </div>
                         <?php } ?>
                     </div>
                     <div class="message-time" style="font-size: 10px; color: #94a3b8; margin-top: 4px; padding: 0 4px;"><?= date('H:i d/m/Y', strtotime($msg['created_at'])) ?></div>
                 </div>
                 <?php } /* end if $isCallLog else */ ?>
             <?php } ?>
         <?php } ?>
         
         <?php if ($selectedContact['created_at']) { ?>
             <div style="text-align: center; margin: 20px 0;">
                 <span style="background: #f1f5f9; color: #64748b; padding: 4px 12px; border-radius: 12px; font-size: 11px; display: inline-block;">
                     <i class="fas fa-robot"></i> Hệ thống đã tự động cấp mã MID: <?= esc($selectedContact['mid_code']) ?>
                 </span>
             </div>
         <?php } ?>
     </div>

     <?php
     /**
      * Khung nhập liệu tin nhắn hỗ trợ khách hàng
      */
     ?>
     <div class="chat-input-area">
         <div class="chat-input-wrapper">
             <div class="input-actions" style="display: flex; gap: 12px; padding: 0 10px; border-right: 1px solid #e2e8f0; margin-right: 10px; align-items: center;">
                 <i class="fas fa-bolt btn-quick-reply" style="color: #f59e0b; font-size: 18px; cursor: pointer;" title="Trả lời nhanh"></i>
                 <?php if ($selectedContact['channel'] === 'zalo') { ?>
                     <label style="margin: 0; cursor: pointer; display: inline-flex; align-items: center; margin-right: 12px;">
                         <i class="fas fa-image" style="color: #3b82f6; font-size: 18px;" title="Gửi ảnh (Zalo)"></i>
                         <input type="file" id="imageUpload" accept="image/*" style="display: none;">
                     </label>
                     <label style="margin: 0; cursor: pointer; display: inline-flex; align-items: center;">
                         <i class="fas fa-paperclip" style="color: #94a3b8; font-size: 18px;" title="Đính kèm tài liệu (Zalo)"></i>
                         <input type="file" id="mediaUpload" style="display: none;">
                     </label>
                 <?php } ?>
             </div>
             <input type="text" class="chat-input" placeholder="Nhập tin nhắn hỗ trợ khách hàng..." style="flex: 1; border: none; outline: none; font-size: 14px; padding: 8px 0; background: transparent;">
             <button class="btn-send" style="background: none; border: none; color: #3b82f6; font-size: 18px; cursor: pointer; padding: 0 8px; display: inline-flex; align-items: center;" title="Gửi"><i class="fas fa-paper-plane"></i></button>
         </div>
     </div>

     <?php
     /**
      * Slide-out Insight Panel (Hồ sơ khách hàng chi tiết)
      */
     ?>
     <div class="insight-panel" id="insightPanel">
         <div class="insight-header" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
             <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a;"><i class="fas fa-id-card-alt" style="color: #6366f1; margin-right: 8px;"></i>Hồ sơ khách hàng</h3>
             <i class="fas fa-times insight-close" style="cursor: pointer; color: #94a3b8; font-size: 18px; padding: 4px;" title="Đóng"></i>
         </div>
         
         <div class="insight-customer-card" style="padding: 20px; text-align: center; border-bottom: 1px solid #f1f5f9;">
             <img src="<?= $selectedContact['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($selectedContact['display_name']).'&background=random' ?>" alt="Avatar" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid #e0e7ff; margin-bottom: 12px;">
             <div style="font-weight: 600; font-size: 16px; color: #0f172a;"><?= esc($selectedContact['display_name']) ?></div>
             <div style="color: #64748b; font-size: 13px; margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                 <span>
                     <i class="fas fa-phone-alt" style="font-size: 11px;"></i> 
                     <?= !empty($selectedContact['phone_number']) ? esc($selectedContact['phone_number']) : 'Chưa cập nhật SĐT' ?>
                 </span>
                 <?php if ($selectedContact['channel'] === 'zalo' || !empty($selectedContact['phone_number'])) { ?>
                     <button class="btn-open-callmodal" style="background: linear-gradient(135deg,#10b981,#059669); color:#fff; border:none; border-radius:20px; padding:4px 12px; font-size:11px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px; box-shadow:0 2px 8px rgba(16,185,129,0.3); transition:all 0.2s;" title="Gọi điện cho khách hàng">
                         <i class="fas fa-phone-alt"></i> Gọi
                     </button>
                 <?php } ?>
             </div>
         </div>

         
         <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px; border-bottom: 1px solid #f1f5f9;">
             <!-- Kênh tương tác -->
             <div class="insight-info-row" style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                 <span class="insight-info-label" style="color: #64748b; font-weight: 500;">Kênh tương tác</span>
                 <span class="insight-info-value" style="text-transform: capitalize; font-weight: 600; color: <?= ($selectedContact['channel'] === 'zalo') ? '#0068ff' : '#1877f2' ?>; background: <?= ($selectedContact['channel'] === 'zalo') ? '#e0f2fe' : '#e0e7ff' ?>; padding: 2px 8px; border-radius: 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                     <?php if ($selectedContact['channel'] === 'zalo') { ?>
                         <span style="font-weight: 800; font-size: 10px;">Z</span> Zalo OA
                     <?php } else { ?>
                         <i class="fab fa-facebook-messenger" style="font-size: 10px;"></i> Messenger
                     <?php } ?>
                 </span>
             </div>

             <!-- Mã MID liên kết -->
             <div class="insight-info-row" style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                 <span class="insight-info-label" style="color: #64748b; font-weight: 500;">Mã MID liên kết</span>
                 <span class="insight-info-value" style="font-family: monospace; font-weight: 600; color: #475569; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11px;"><?= esc($selectedContact['mid_code']) ?></span>
             </div>

             <!-- Số điện thoại (Editable) -->
             <div class="insight-info-row" style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                 <span class="insight-info-label" style="color: #64748b; font-weight: 500;">Số điện thoại</span>
                 <div style="display: inline-flex; align-items: center; gap: 4px;">
                     <input type="text" id="leadPhone" class="lead-insight-input" 
                            value="<?= esc($selectedContact['phone_number'] ?? '') ?>" 
                            placeholder="Nhập SĐT..." 
                            data-channel="<?= esc($selectedContact['channel']) ?>" 
                            data-id="<?= esc($selectedContact['id']) ?>" 
                            style="border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 6px; font-size: 12px; text-align: right; width: 120px; outline: none; transition: border-color 0.2s; font-weight: 600;">
                     <button type="button" class="btn-save-field js-save-lead-field" data-field="phone_number" style="background: none; border: none; color: #0ea5e9; cursor: pointer; font-size: 12px; padding: 2px;" title="Lưu số điện thoại"><i class="fas fa-check"></i></button>
                 </div>
             </div>

             <!-- Email (Editable) -->
             <div class="insight-info-row" style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                 <span class="insight-info-label" style="color: #64748b; font-weight: 500;">Địa chỉ Email</span>
                 <div style="display: inline-flex; align-items: center; gap: 4px;">
                     <input type="email" id="leadEmail" class="lead-insight-input" 
                            value="<?= esc($selectedContact['email'] ?? '') ?>" 
                            placeholder="Nhập Email..." 
                            data-channel="<?= esc($selectedContact['channel']) ?>" 
                            data-id="<?= esc($selectedContact['id']) ?>" 
                            style="border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 6px; font-size: 12px; text-align: right; width: 140px; outline: none; transition: border-color 0.2s; font-weight: 500;">
                     <button type="button" class="btn-save-field js-save-lead-field" data-field="email" style="background: none; border: none; color: #0ea5e9; cursor: pointer; font-size: 12px; padding: 2px;" title="Lưu email"><i class="fas fa-check"></i></button>
                 </div>
             </div>

             <!-- Độ nóng của Lead (Editable Select) -->
             <div class="insight-info-row" style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                 <span class="insight-info-label" style="color: #64748b; font-weight: 500;">Độ nóng Lead</span>
                 <div style="display: inline-flex; align-items: center;">
                     <select id="leadWarmthSelect" onchange="ChatApp.saveLeadField('lead_warmth')" 
                             data-channel="<?= esc($selectedContact['channel']) ?>" 
                             data-id="<?= esc($selectedContact['id']) ?>" 
                             style="border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 6px; font-size: 12px; outline: none; background: #fff; font-weight: 600; cursor: pointer; color: #334155;">
                         <option value="cold" <?= ($selectedContact['lead_warmth'] ?? 'cold') === 'cold' ? 'selected' : '' ?>>❄️ Lạnh</option>
                         <option value="warm" <?= ($selectedContact['lead_warmth'] ?? 'cold') === 'warm' ? 'selected' : '' ?>>☀️ Ấm</option>
                         <option value="hot" <?= ($selectedContact['lead_warmth'] ?? 'cold') === 'hot' ? 'selected' : '' ?>>🔥 Nóng</option>
                     </select>
                 </div>
             </div>

             <!-- SLA Phản hồi -->
             <div class="insight-info-row" style="display: flex; flex-direction: column; gap: 4px; font-size: 13px; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                 <div style="display: flex; justify-content: space-between;">
                     <span class="insight-info-label" style="color: #64748b; font-weight: 500;">Hạn phản hồi SLA</span>
                     <span class="insight-info-value" style="font-weight: 600; color: #475569;">
                         <?php if (!empty($selectedContact['ongoing_response_deadline'])) { ?>
                             <span style="color: #f59e0b; font-size: 12px;"><i class="fas fa-clock"></i> <?= date('H:i d/m/Y', strtotime($selectedContact['ongoing_response_deadline'])) ?> (Kế tiếp)</span>
                         <?php } elseif (!empty($selectedContact['first_response_deadline'])) { ?>
                             <?= date('H:i d/m/Y', strtotime($selectedContact['first_response_deadline'])) ?>
                         <?php } else { ?>
                             <span style="color: #94a3b8; font-style: italic; font-weight: normal;">Chưa áp dụng</span>
                         <?php } ?>
                     </span>
                 </div>
                 <?php if (!empty($selectedContact['ongoing_response_deadline'])) { ?>
                     <div style="display: flex; justify-content: space-between; font-size: 11px; align-items: center; margin-top: 2px;">
                         <span style="color: #94a3b8;">Trạng thái SLA</span>
                         <span style="font-weight: 700;">
                             <?php if (($selectedContact['ongoing_is_overdue'] ?? 0) == 1) { ?>
                                 <span style="color: #ef4444; background: #fef2f2; padding: 2px 6px; border-radius: 4px;" class="pulse-alert"><i class="fas fa-exclamation-triangle"></i> Quá hạn (Đang trao đổi)</span>
                             <?php } else { ?>
                                 <span style="color: #f59e0b; background: #fffbeb; padding: 2px 6px; border-radius: 4px;"><i class="fas fa-clock"></i> Đang đếm ngược</span>
                             <?php } ?>
                         </span>
                     </div>
                 <?php } elseif (!empty($selectedContact['first_response_deadline'])) { ?>
                     <div style="display: flex; justify-content: space-between; font-size: 11px; align-items: center; margin-top: 2px;">
                         <span style="color: #94a3b8;">Trạng thái SLA</span>
                         <span style="font-weight: 700;">
                             <?php if (!empty($selectedContact['first_responded_at'])) { ?>
                                 <span style="color: #10b981; background: #ecfdf5; padding: 2px 6px; border-radius: 4px;"><i class="fas fa-check-circle"></i> Đạt SLA phản hồi</span>
                             <?php } elseif (($selectedContact['is_overdue'] ?? 0) == 1) { ?>
                                 <span style="color: #ef4444; background: #fef2f2; padding: 2px 6px; border-radius: 4px;" class="pulse-alert"><i class="fas fa-exclamation-triangle"></i> Quá hạn (Đã chuyển)</span>
                             <?php } else { ?>
                                 <span style="color: #f59e0b; background: #fffbeb; padding: 2px 6px; border-radius: 4px;"><i class="fas fa-clock"></i> Đang đếm ngược</span>
                             <?php } ?>
                         </span>
                     </div>
                 <?php } ?>
             </div>

             <!-- Phát hiện trùng lặp -->
             <?php if (($selectedContact['is_duplicate'] ?? 0) == 1) { ?>
                 <div style="display: flex; flex-direction: column; gap: 4px; font-size: 13px; background: #fffbeb; padding: 10px 12px; border-radius: 6px; border: 1px solid #fde68a;">
                     <div style="color: #b45309; font-weight: 700; display: flex; align-items: center; gap: 4px;">
                         <i class="fas fa-copy"></i> Phát hiện trùng lặp!
                     </div>
                     <div style="color: #d97706; font-size: 11px; line-height: 1.4;">
                         Liên hệ này trùng số điện thoại hoặc email với liên hệ gốc 
                         <a href="?channel=<?= esc($selectedContact['channel']) ?>&selected_channel=<?= esc($selectedContact['channel']) ?>&contact_id=<?= esc($selectedContact['duplicate_of']) ?>" 
                            style="color: #0284c7; font-weight: bold; text-decoration: underline;">
                             [Xem liên hệ gốc #<?= esc($selectedContact['duplicate_of']) ?>]
                         </a>.
                     </div>
                 </div>
             <?php } ?>

             <!-- Trạng thái CRM & Liên kết khách hàng -->
             <div class="insight-info-row" style="display: flex; justify-content: space-between; font-size: 13px; align-items: center; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                 <span class="insight-info-label" style="color: #64748b; font-weight: 500;">Trạng thái CRM</span>
                 <span class="insight-info-value" style="font-weight: 700; color: <?= $selectedContact['customer_id'] ? '#10b981' : '#f59e0b' ?>;">
                     <?php if (!empty($selectedContact['customer_id'])) { ?>
                         <a href="<?= base_url('customers/show/' . $selectedContact['customer_id']) ?>" target="_blank" style="color: #10b981; text-decoration: underline;" title="Nhấn để xem Hồ sơ Khách hàng CRM">
                             Đã liên kết CRM <i class="fas fa-external-link-alt" style="font-size: 10px;"></i>
                         </a>
                     <?php } else { ?>
                         Khách tiềm năng
                     <?php } ?>
                 </span>
             </div>
         </div>

         <div style="padding: 20px;">
             <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                 <div style="font-size: 13px; color: #64748b; font-weight: 500;">Nhãn phân loại</div>
                 <button class="btn-filter-secondary btn-open-tags" style="font-size: 11px; padding: 2px 8px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; cursor: pointer; color: #475569;">
                     <i class="fas fa-edit"></i> Chỉnh sửa
                 </button>
             </div>
             <div id="currentTags" style="display: flex; flex-wrap: wrap; gap: 6px;">
                 <?php 
                     if (!empty($headerTags)) {
                         foreach ($headerTags as $tag) {
                 ?>
                     <span class="tag-badge" style="font-size: 11px; padding: 3px 8px; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-weight: 500;">#<?= esc($tag) ?></span>
                 <?php 
                         }
                     } else { 
                 ?>
                     <span class="tag-badge" style="background: #e2e8f0; color: #64748b; font-size: 11px; padding: 3px 8px; border-radius: 4px;">Chưa có nhãn</span>
                 <?php } ?>
             </div>
         </div>
         
         <?php if (empty($selectedContact['customer_id'])) { ?>
              <div style="padding: 0 20px 20px 20px;">
                  <button type="button" class="btn-premium js-instant-create-customer" style="width: 100%; text-align: center; border: none; background: #0284c7; color: #fff; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer;">
                      <i class="fas fa-user-plus"></i> Tạo hồ sơ KH mới
                  </button>
              </div>
          <?php } ?>
     </div>

     <?php
     /**
      * Tag Edit Modal (Giao diện gắn nhãn khách hàng thông minh)
      */
     ?>

     <div id="tagEditModal" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
         <div class="modal-content" style="background: #fff; width: 420px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 100%;">
             <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                 <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a;"><i class="fas fa-tags" style="color: #0ea5e9; margin-right: 8px;"></i>Gắn nhãn khách hàng</h4>
                 <i class="fas fa-times modal-close" style="cursor: pointer; color: #94a3b8; font-size: 18px; padding: 4px;" title="Đóng"></i>
             </div>
             <div style="padding: 20px;">
                 <!-- Nhãn đang có -->
                 <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Chọn nhãn có sẵn</div>
                 <div id="tagCheckboxList" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; min-height: 36px; padding: 8px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                     <?php foreach ($allTags as $sysTag) { ?>
                         <label style="cursor: pointer; margin: 0;">
                             <input type="checkbox" class="tag-checkbox" value="<?= esc($sysTag['name']) ?>" 
                                 <?= (!empty($headerTags) && in_array($sysTag['name'], $headerTags)) ? 'checked' : '' ?> style="display: none;">
                             <span class="tag-option" style="padding: 4px 12px; border-radius: 20px; border: 1px solid #cbd5e1; font-size: 12px; transition: all 0.2s; display: inline-block; cursor: pointer; background: #fff; color: #475569;">
                                 #<?= esc($sysTag['name']) ?>
                             </span>
                         </label>
                     <?php } ?>
                     <?php if (empty($allTags)) { ?>
                         <span class="no-tags-msg" style="color: #94a3b8; font-size: 12px; align-self: center;">Chưa có nhãn nào trong hệ thống.</span>
                     <?php } ?>
                 </div>
                 
                 <!-- Tạo nhãn mới nhanh -->
                 <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Tạo nhãn mới</div>
                 <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                     <input type="text" id="newTagInput" placeholder="Nhập tên nhãn mới..." 
                         style="flex: 1; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; background: #fff;"
                         onkeypress="if(event.which==13){ChatApp.createNewTag();return false;}">
                    <button type="button" class="js-create-new-tag" style="padding: 8px 14px; background: #0ea5e9; color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; white-space: nowrap; font-weight: 600;">
                         <i class="fas fa-plus"></i> Thêm
                     </button>
                 </div>
                 
                <button type="button" class="btn-premium js-save-tags" data-contact-id="<?= (int)$selectedContact['id'] ?>" style="width: 100%; border: none; background: #0ea5e9; color: #fff; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                     <i class="fas fa-save"></i> Lưu thay đổi
                 </button>
             </div>
         </div>
     </div>

     <?php
     /**
      * Call Modal — Giao diện gọi điện cho khách hàng với bộ đếm thời gian
      */
     ?>
     <div id="callModal" class="modal-backdrop call-modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
         <div class="call-modal-box" style="background: #fff; width: 420px; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 80px rgba(0,0,0,0.35); max-width: 95%; animation: callModalIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
             
             <!-- Header gọi điện -->
             <div class="call-modal-header" style="background: linear-gradient(135deg, #10b981, #059669); padding: 28px 24px 20px; text-align: center; position: relative;">
                 <div style="position: relative; display: inline-block; margin-bottom: 12px;">
                     <img id="callModalAvatar" src="" alt="Avatar" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.5); box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                     <span class="call-avatar-ring"></span>
                 </div>
                 <div style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 4px;" id="callModalName">Khách hàng</div>
                 <div style="font-size: 13px; color: rgba(255,255,255,0.8);" id="callModalPhone">--</div>
                 
                 <!-- Bộ đếm thời gian cuộc gọi -->
                 <div id="callTimerWrap" style="margin-top: 16px; display: none;">
                     <div style="font-size: 11px; color: rgba(255,255,255,0.7); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 1px;">Thời gian cuộc gọi</div>
                     <div id="callTimer" style="font-size: 32px; font-weight: 800; color: #fff; font-family: 'Courier New', monospace; letter-spacing: 2px;">00:00</div>
                 </div>

                 <!-- Trạng thái cuộc gọi -->
                 <div id="callStatus" style="margin-top: 12px; font-size: 13px; color: rgba(255,255,255,0.9); display: flex; align-items: center; justify-content: center; gap: 6px;">
                     <span class="call-status-dot" style="width: 8px; height: 8px; background: #fff; border-radius: 50%; display: inline-block; animation: callPulse 1.5s infinite;"></span>
                     <span id="callStatusText">Đang chuẩn bị kết nối...</span>
                 </div>
             </div>

             <!-- Body: Chọn kết quả & Ghi chú -->
             <div id="callModalBody" style="padding: 24px; display: none;">
                 <div style="margin-bottom: 16px;">
                     <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">Kết quả cuộc gọi</div>
                     <div class="call-result-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                         <label class="call-result-option" data-value="answered" style="cursor: pointer;">
                             <input type="radio" name="call_result" value="answered" checked style="display: none;">
                             <div class="call-result-btn" style="padding: 10px 8px; border-radius: 10px; border: 2px solid #e2e8f0; text-align: center; transition: all 0.2s; background: #fff;">
                                 <div style="font-size: 18px; margin-bottom: 4px;">✅</div>
                                 <div style="font-size: 12px; font-weight: 600; color: #374151;">Đã nghe máy</div>
                             </div>
                         </label>
                         <label class="call-result-option" data-value="no_answer" style="cursor: pointer;">
                             <input type="radio" name="call_result" value="no_answer" style="display: none;">
                             <div class="call-result-btn" style="padding: 10px 8px; border-radius: 10px; border: 2px solid #e2e8f0; text-align: center; transition: all 0.2s; background: #fff;">
                                 <div style="font-size: 18px; margin-bottom: 4px;">📵</div>
                                 <div style="font-size: 12px; font-weight: 600; color: #374151;">Không nghe máy</div>
                             </div>
                         </label>
                         <label class="call-result-option" data-value="callback" style="cursor: pointer;">
                             <input type="radio" name="call_result" value="callback" style="display: none;">
                             <div class="call-result-btn" style="padding: 10px 8px; border-radius: 10px; border: 2px solid #e2e8f0; text-align: center; transition: all 0.2s; background: #fff;">
                                 <div style="font-size: 18px; margin-bottom: 4px;">🔄</div>
                                 <div style="font-size: 12px; font-weight: 600; color: #374151;">Hẹn gọi lại</div>
                             </div>
                         </label>
                         <label class="call-result-option" data-value="rejected" style="cursor: pointer;">
                             <input type="radio" name="call_result" value="rejected" style="display: none;">
                             <div class="call-result-btn" style="padding: 10px 8px; border-radius: 10px; border: 2px solid #e2e8f0; text-align: center; transition: all 0.2s; background: #fff;">
                                 <div style="font-size: 18px; margin-bottom: 4px;">🚫</div>
                                 <div style="font-size: 12px; font-weight: 600; color: #374151;">Từ chối nghe</div>
                             </div>
                         </label>
                     </div>
                 </div>

                 <div style="margin-bottom: 20px;">
                     <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Ghi chú tư vấn</div>
                    <textarea id="callNotes" class="call-notes-textarea" placeholder="Nhập nội dung tư vấn, cam kết, vấn đề khách hàng..."></textarea>
                 </div>

                 <div style="display: flex; gap: 10px;">
                     <button id="btnSaveCall" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
                         <i class="fas fa-save"></i> Lưu lịch sử cuộc gọi
                     </button>
                     <button id="btnCancelCall" style="padding: 12px 16px; background: #f1f5f9; color: #64748b; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                         Hủy
                     </button>
                 </div>
             </div>

             <!-- Nút bấm trong khi đang gọi -->
             <div id="callModalActions" style="padding: 20px 24px; display: flex; gap: 12px; justify-content: center; align-items: center;">
                 <button id="btnEndCall" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; border: none; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(239,68,68,0.4); transition: all 0.2s; flex-shrink: 0;" title="Kết thúc cuộc gọi">
                     <i class="fas fa-phone-slash"></i>
                 </button>
                 <div style="font-size: 12px; color: #64748b; text-align: center; line-height: 1.5;">
                     Bấm để kết thúc cuộc gọi<br>và ghi nhận kết quả
                 </div>
             </div>
         </div>
     </div>
<?php } else { ?>
    <div class="chat-empty-state"><i class="fas fa-inbox"></i><h4>Không có cuộc trò chuyện nào được chọn</h4><p>Chọn một khách hàng tương tác từ danh sách bên trái để bắt đầu tư vấn và chăm sóc.</p></div>
<?php } ?>
