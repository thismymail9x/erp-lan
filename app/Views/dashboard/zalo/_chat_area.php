<?php if ($selectedFollower) { ?>
    <div class="chat-header">
        <a href="<?= base_url('zalo') ?>" class="btn-back-mobile" style="display: none; margin-right: 15px; color: #64748b; font-size: 20px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <img src="<?= $selectedFollower['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($selectedFollower['display_name']).'&background=random' ?>" class="zalo-avatar" style="width: 40px; height: 40px;" alt="Avatar">
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="font-weight: 600; font-size: 16px; color: #0f172a;"><?= esc($selectedFollower['display_name']) ?></div>
                <i class="fas fa-sync-alt" style="font-size: 12px; color: #94a3b8; cursor: pointer;" title="Cập nhật thông tin từ Zalo" onclick="syncZaloProfile('<?= $selectedFollower['zalo_id'] ?>')"></i>
            </div>
            <div style="font-size: 12px; color: #10b981;"><i class="fas fa-circle" style="font-size: 8px;"></i> <?= $selectedFollower['mid_code'] ?></div>
        </div>
        
        <div style="margin-left: auto; display: flex; align-items: center; gap: 10px; margin-right: 15px;">
            <span style="font-size: 12px; color: #64748b;">Phụ trách:</span>
            <?php if ($isAdmin) { ?>
                <select class="form-control" id="staffAssignment" data-follower-id="<?= $selectedFollower['id'] ?>" style="padding: 4px 8px; font-size: 13px; border-radius: 4px; border: 1px solid #cbd5e1;">
                    <option value="">-- Chưa gán --</option>
                    <?php foreach ($staffs as $staff) { ?>
                        <option value="<?= $staff['user_id'] ?>" <?= $selectedFollower['assigned_to'] == $staff['user_id'] ? 'selected' : '' ?>>
                            <?= esc($staff['full_name'] ?: $staff['email']) ?>
                        </option>
                    <?php } ?>
                </select>
            <?php } else { ?>
                <span style="font-weight: 600; font-size: 13px; color: #0ea5e9;">
                    <?= esc($selectedFollower['assigned_staff_name'] ?? 'Chưa có ai nhận') ?>
                </span>
            <?php } ?>
        </div>

        <div class="hide-mobile" style="display: flex; align-items: center; gap: 8px;">
            <!-- Nhãn hiện tại + nút gắn nhãn nhanh -->
            <div id="chatHeaderTags" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; max-width: 220px;">
                <?php 
                    $headerTags = json_decode($selectedFollower['tags'], true);
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
            <button class="btn-premium btn-sync-history" style="background: #3b82f6; white-space: nowrap;" onclick="syncHistory('<?= $selectedFollower['zalo_id'] ?>')" title="Đồng bộ tin nhắn từ Zalo">
                <i class="fas fa-sync-alt"></i> Đồng bộ tin nhắn
            </button>
            <button class="btn-premium" style="white-space: nowrap;" onclick="$('#tagEditModal').css('display','flex')" title="Gắn nhãn">
                <i class="fas fa-tags"></i> Nhãn
            </button>
            <button class="btn-premium" style="background: #10b981" onclick="document.getElementById('insightPanel').classList.toggle('open')">
                <i class="fas fa-address-card"></i> Hồ sơ
            </button>
        </div>
    </div>
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
                                        if ($attach['type'] === 'image') {
                                            $imageUrl = $attach['payload']['url'] ?? ($attach['url'] ?? '');
                                            // Nếu URL từ Zalo chưa có, thử dùng ảnh tạm từ server ERP
                                            if (!$imageUrl && !empty($attach['payload']['local_file'])) {
                                                $imageUrl = base_url('zalo/view-temp/' . $attach['payload']['local_file']);
                                            }
                                            
                                            if ($imageUrl) {
                                                echo '<div class="attach-image"><img src="' . esc($imageUrl) . '" style="max-width: 200px; border-radius: 8px; cursor: pointer;" onclick="window.open(\'' . esc($imageUrl) . '\')"></div>';
                                            }
                                        } elseif ($attach['type'] === 'audio') {
                                            $audioUrl = $attach['payload']['url'] ?? ($attach['url'] ?? '');
                                            if (!$audioUrl && !empty($attach['payload']['token'])) {
                                                $audioUrl = base_url('zalo/download-attachment?msg_id='.$msg['id'].'&token='.urlencode($attach['payload']['token']).'&name=voice.mp3');
                                            }
                                            if ($audioUrl) {
                                                echo '<div class="attach-audio" style="margin-top: 8px;">';
                                                echo '<audio controls style="max-width: 260px; outline: none; border-radius: 30px; height: 36px; display: block;">';
                                                echo '<source src="' . esc($audioUrl) . '" type="audio/mpeg">';
                                                echo '<source src="' . esc($audioUrl) . '" type="audio/ogg">';
                                                echo '<source src="' . esc($audioUrl) . '" type="audio/wav">';
                                                echo 'Trình duyệt không hỗ trợ phát trực tiếp.';
                                                echo '</audio>';
                                                if (!empty($attach['payload']['token'])) {
                                                    echo '<a href="' . base_url('zalo/download-attachment?msg_id='.$msg['id'].'&token='.urlencode($attach['payload']['token']).'&name=voice.mp3') . '" style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #3b82f6; margin-top: 4px; font-weight: 500; text-decoration: none;" title="Tải về máy">';
                                                    echo '<i class="fas fa-cloud-download-alt"></i> Tải file ghi âm</a>';
                                                }
                                                echo '</div>';
                                            }
                                        } elseif ($attach['type'] === 'file' || $attach['type'] === 'video') {
                                            $name = $attach['payload']['name'] ?? (($attach['type'] === 'video') ? 'Video' : 'File');
                                            $size = $attach['payload']['size'] ?? 0;
                                            $token = $attach['payload']['token'] ?? '';
                                            $sizeStr = $size > 1048576 ? round($size/1048576, 2) . ' MB' : round($size/1024, 2) . ' KB';
                                            
                                            echo '<div class="attach-file" style="background: rgba(0,0,0,0.05); padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">';
                                            if ($attach['type'] === 'video') {
                                                echo '<i class="fas fa-video" style="font-size: 20px; color: #ef4444;"></i>';
                                            } else {
                                                echo '<i class="fas fa-file-download" style="font-size: 20px; color: #3b82f6;"></i>';
                                            }
                                            echo '<div style="flex: 1; overflow: hidden;">';
                                            echo '<div style="font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . esc($name) . '</div>';
                                            echo '<div style="font-size: 11px; color: #64748b;">' . $sizeStr . '</div>';
                                            echo '</div>';
                                            echo '<a href="' . base_url('zalo/download-attachment?msg_id='.$msg['id'].'&token='.urlencode($token).'&name='.urlencode($name).'&size='.$size) . '" class="btn-download" style="color: #3b82f6;"><i class="fas fa-cloud-download-alt"></i></a>';
                                            echo '</div>';
                                        } elseif ($attach['type'] === 'sticker') {
                                            $stickerUrl = $attach['payload']['url'] ?? '';
                                            if ($stickerUrl) {
                                                echo '<div class="attach-sticker"><img src="' . esc($stickerUrl) . '" style="width: 100px;"></div>';
                                            }
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
        
        <?php if ($selectedFollower['created_at']) { ?>
            <div style="text-align: center; margin: 20px 0;">
                <span style="background: #f1f5f9; color: #64748b; padding: 4px 12px; border-radius: 12px; font-size: 11px;">
                    <i class="fas fa-robot"></i> Hệ thống đã tự động cấp mã MID: <?= $selectedFollower['mid_code'] ?>
                </span>
            </div>
        <?php } ?>
    </div>
    
    <div class="chat-input-area">
        <div class="chat-input-wrapper">
            <div class="input-actions" style="display: flex; gap: 10px; padding: 0 10px; border-right: 1px solid #e2e8f0; margin-right: 10px;">
                <i class="fas fa-bolt" style="color: #f59e0b; font-size: 18px; cursor: pointer;" title="Trả lời nhanh" onclick="$('#quickReplyModal').fadeIn()"></i>
                <label style="margin: 0; cursor: pointer;">
                    <i class="fas fa-image" style="color: #3b82f6; font-size: 18px;" title="Gửi ảnh"></i>
                    <input type="file" id="imageUpload" accept="image/*" style="display: none;" onchange="handleMediaUpload(this)">
                </label>
                <label style="margin: 0; cursor: pointer;">
                    <i class="fas fa-paperclip" style="color: #94a3b8; font-size: 18px;" title="Đính kèm"></i>
                    <input type="file" id="mediaUpload" style="display: none;" onchange="handleMediaUpload(this)">
                </label>
            </div>
            <input type="text" class="chat-input" placeholder="Nhập tin nhắn hỗ trợ khách hàng...">
            <button class="btn-send"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Insight Panel (Slide from right) -->
    <div class="insight-panel" id="insightPanel">
        <div class="insight-header">
            <h3>Thông tin khách hàng</h3>
            <i class="fas fa-times insight-close" onclick="document.getElementById('insightPanel').classList.remove('open')"></i>
        </div>
        
        <div class="insight-customer-card">
            <img src="<?= $selectedFollower['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($selectedFollower['display_name']).'&background=random' ?>" alt="Avatar">
            <div style="font-weight: 600; font-size: 16px;"><?= esc($selectedFollower['display_name']) ?></div>
            <div style="color: #64748b; font-size: 13px;">SĐT: <?= $selectedFollower['phone_number'] ?: 'Chưa cập nhật' ?></div>
        </div>
        
        <div class="insight-info-row">
            <span class="insight-info-label">Mã Zalo MID</span>
            <span class="insight-info-value"><?= $selectedFollower['mid_code'] ?></span>
        </div>
        <div class="insight-info-row">
            <span class="insight-info-label">Trạng thái CRM</span>
            <span class="insight-info-value" style="color: <?= $selectedFollower['customer_id'] ? '#10b981' : '#f59e0b' ?>;">
                <?= $selectedFollower['customer_id'] ? 'Đã đồng bộ CRM' : 'Khách tiềm năng' ?>
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
                    $tags = json_decode($selectedFollower['tags'], true); 
                    if (!empty($tags)) {
                        foreach ($tags as $tag) {
                ?>
                    <span class="tag-badge">#<?= esc($tag) ?></span>
                <?php 
                        }
                    } else { 
                ?>
                    <span class="tag-badge" style="background: #e2e8f0; color: #64748b;">Chưa có nhãn</span>
                <?php } ?>
            </div>
        </div>
        
        <?php if (!$selectedFollower['customer_id']){ ?>
        <button class="btn-premium" style="width: 100%; margin-top: 30px;">
            Tạo hồ sơ CRM
        </button>
        <?php } ?>
    </div>

    <!-- Tag Edit Modal -->
    <div id="tagEditModal" class="modal-backdrop" style="display: none;">
        <div class="modal-content" style="background: #fff; width: 420px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h4 style="margin: 0; font-size: 16px; color: #0f172a;"><i class="fas fa-tags" style="color: #0ea5e9; margin-right: 8px;"></i>Gắn nhãn khách hàng</h4>
                <i class="fas fa-times" style="cursor: pointer; color: #94a3b8; font-size: 16px;" onclick="$('#tagEditModal').hide()"></i>
            </div>
            <div style="padding: 20px;">
                <!-- Nhãn đang có -->
                <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Chọn nhãn có sẵn</div>
                <div id="tagCheckboxList" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; min-height: 36px; padding: 8px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <?php foreach ($allTags as $sysTag) { ?>
                        <label style="cursor: pointer; margin: 0;">
                            <input type="checkbox" class="tag-checkbox" value="<?= esc($sysTag['name']) ?>" 
                                <?= (!empty($tags) && in_array($sysTag['name'], $tags)) ? 'checked' : '' ?> style="display: none;">
                            <span class="tag-option" style="padding: 4px 12px; border-radius: 20px; border: 1px solid #cbd5e1; font-size: 12px; transition: all 0.2s; display: inline-block; cursor: pointer;">
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
                        style="flex: 1; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none;"
                        onkeypress="if(event.which==13){createNewTag();return false;}">
                    <button onclick="createNewTag()" style="padding: 8px 14px; background: #0ea5e9; color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; white-space: nowrap;">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                </div>
                
                <button class="btn-premium" style="width: 100%;" onclick="saveTags(<?= $selectedFollower['id'] ?>)">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">
        <i class="far fa-comments" style="font-size: 48px; margin-bottom: 16px; color: #cbd5e1;"></i>
        <p>Chọn một khách hàng để xem hội thoại.</p>
    </div>
<?php } ?>

