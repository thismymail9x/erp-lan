<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="case-detail-container">
    <!-- 
        Tiêu đề trang chi tiết Vụ việc:
        Hiển thị Mã số (Code), Tên vụ việc và Khách hàng liên quan một cách trang nhã.
    -->
    <div class="dashboard-header-wrapper m-b-24">
        <div class="header-title-container">
            <span class="badge-secondary-minimal text-monospace text-xs m-b-5" title="Mã số hồ sơ vụ việc">
                <?= esc($case['code']) ?>
            </span>
            <h2 class="content-title"><?= esc($case['title']) ?></h2>
            <p class="content-subtitle">Khách hàng: <strong class="text-apple-main"><?= esc($case['customer_name']) ?></strong></p>
        </div>
        <div class="header-controls">
            <!-- Quay lại danh sách tổng -->
            <a href="<?= base_url('cases') ?>" class="btn-secondary-sm" title="Quay lại danh sách vụ việc">
                <i class="fas fa-chevron-left"></i> Danh sách
            </a>
            <!-- Nút cập nhật trạng thái hồ sơ (Mở Modal) -->
            <button class="btn-premium" onclick="document.getElementById('statusModal').style.display='flex'" title="Thay đổi trạng thái hoặc giao việc cho nhân sự khác">
                <i class="fas fa-sync-alt"></i> Cập nhật trạng thái
            </button>
        </div>
    </div>

    <div class="profile-grid-premium">
        <div class="profile-main">
            <!-- Tabs Navigation -->
            <div class="nav-tabs-premium m-b-24">
                <div class="nav-tab-item active" onclick="switchTab('overview')" title="Xem tiến độ và nội dung chi tiết">Tổng quan & Timeline</div>
                <div class="nav-tab-item" onclick="switchTab('comments')" title="Trao đổi nội bộ giữa các bộ phận">Bình luận nội bộ (<?= count($comments) ?>)</div>
                <div class="nav-tab-item" onclick="switchTab('history')" title="Lịch sử cập nhật của hồ sơ">Lịch sử thay đổi</div>
                <div class="nav-tab-item" onclick="switchTab('documents')" title="Các tệp tin, tài liệu đính kèm">Hồ sơ tài liệu (<?= count($documents) ?>)</div>
            </div>

            <!-- Overview & Timeline Section -->
            <div id="tab-overview" class="tab-content active">
                <?php if(!empty($steps)): ?>
                    <!-- Case Progress & Horizontal Stepper -->
                    <div class="case-progress-wrapper m-b-24">
                        <?php
                            $totalStepsCount = count($steps);
                            $completedStepsCount = 0;
                            foreach($steps as $s) { if($s['status'] === 'completed') $completedStepsCount++; }
                            $progressPercent = $totalStepsCount > 0 ? round(($completedStepsCount / $totalStepsCount) * 100) : 0;
                        ?>
                        <div class="progress-header">
                            <div class="flex-column">
                                <h4 class="m-0 font-weight-700">Tiến độ quy trình</h4>
                                <?php if($case['template_name']): ?>
                                    <span class="text-xs text-muted-dark">Mẫu: <span class="text-apple-main"><?= esc($case['template_name']) ?></span></span>
                                <?php endif; ?>
                            </div>
                            <span class="badge-secondary-minimal text-apple-main font-weight-700"><?= $progressPercent ?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $progressPercent ?>%;"></div>
                        </div>

                        <div class="horizontal-stepper">
                            <?php foreach($steps as $index => $s): ?>
                                <?php 
                                    $isCompleted = ($s['status'] === 'completed');
                                    $isActive = ($s['status'] === 'active');
                                    $isOverdue = !$isCompleted && strtotime($s['deadline']) < time();
                                    
                                    $hClass = '';
                                    if ($isCompleted) $hClass = 'completed';
                                    elseif ($isOverdue) $hClass = 'overdue';
                                    elseif ($isActive) $hClass = 'active';
                                ?>
                                <div class="h-step-item <?= $hClass ?>" title="<?= esc($s['step_name']) ?> (Hạn: <?= date('d/m', strtotime($s['deadline'])) ?>)">
                                    <div class="h-step-dot"><?= ($isCompleted ? '<i class="fas fa-check"></i>' : $index + 1) ?></div>
                                    <div class="h-step-label"><?= esc($s['step_name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Active Step Details & Checklist -->
                    <?php if(!empty($active_step)): ?>
                    <div class="premium-card p-20 m-b-24">
                        <div class="flex-row justify-between align-center m-b-15">
                            <h3 class="section-header-title m-0">
                                <i class="fas fa-tasks m-r-8"></i> Bước hiện tại: <?= esc($active_step['step_name']) ?>
                            </h3>
                            <div class="text-right" style="display:flex; gap: 10px; justify-content: flex-end;">
                                <?php 
                                    $role = session()->get('role_name');
                                    $isEmployee = (strpos(strtolower($role), 'nhân viên') !== false || $role == 'Nhân viên chính thức');
                                    $isManager = in_array(strtolower($role), ['admin', 'trưởng phòng', 'truong_phong']);
                                ?>
                                
                                <?php if ($active_step['status'] === 'pending_approval'): ?>
                                    <?php if ($isManager): ?>
                                        <form action="<?= base_url('cases/approve-step/' . $active_step['id']) ?>" method="POST" onsubmit="return confirm('Xác nhận phê duyệt bước này?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-premium btn-sm" style="background: var(--apple-main); border-color: var(--apple-main);">
                                                <i class="fas fa-check"></i> Phê duyệt
                                            </button>
                                        </form>
                                        <form action="<?= base_url('cases/reject-step/' . $active_step['id']) ?>" method="POST" onsubmit="return confirm('Xác nhận từ chối bước này?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-premium btn-sm" style="background: var(--apple-red); border-color: var(--apple-red);">
                                                <i class="fas fa-times"></i> Từ chối
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php if (!empty($is_approval_read)): ?>
                                            <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>" method="POST" onsubmit="return confirm('Quản lý đã xem yêu cầu trước đó. Bạn có muốn gửi yêu cầu xét duyệt lại không?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-premium btn-sm">
                                                    <i class="fas fa-paper-plane"></i> Gửi lại yêu cầu
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge-secondary-minimal text-apple-orange" style="padding: 8px 15px; border-color: var(--apple-orange);">
                                                <i class="fas fa-hourglass-half"></i> Chờ kiểm duyệt
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>" method="POST" onsubmit="return confirm('<?= $isEmployee ? 'Bạn đã tải đủ tài liệu yêu cầu chưa? Xác nhận gửi yêu cầu duyệt?' : 'Xác nhận hoàn thành bước này?' ?>')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-premium btn-sm">
                                            <i class="fas <?= $isEmployee ? 'fa-paper-plane' : 'fa-check-double' ?>"></i> 
                                            <?= $isEmployee ? 'Gửi yêu cầu duyệt' : 'Hoàn thành bước' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-grid-premium m-b-20">
                            <div class="info-item">
                                <label class="text-muted-dark text-xs uppercase">Bắt đầu từ</label>
                                <div class="font-weight-600"><?= date('d/m/Y', strtotime($active_step['created_at'])) ?></div>
                            </div>
                            <div class="info-item">
                                <label class="text-muted-dark text-xs uppercase">Hạn chót</label>
                                <div class="font-weight-600 text-apple-red"><?= date('d/m/Y', strtotime($active_step['deadline'])) ?></div>
                            </div>
                            <?php if(!empty($active_step['responsible_display'])): ?>
                            <div class="info-item flex-2">
                                <label class="text-muted-dark text-xs uppercase">Người chịu trách nhiệm / Thông báo</label>
                                <div class="m-t-5"><?= $active_step['responsible_display'] ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="step-checklist-card">
                            <div class="checklist-header">
                                <span>Checklist tài liệu bắt buộc</span>
                                <span class="text-xs text-muted-dark">Bước <?= ($active_step['sort_order'] + 1) ?>/<?= count($steps) ?></span>
                            </div>
                            <?php 
                                $reqDocs = json_decode($active_step['required_documents'], true);
                                $stepDocs = array_filter($documents, function($d) use ($active_step) {
                                    return $d['step_id'] == $active_step['id'];
                                });
                            ?>
                            <?php foreach($reqDocs as $rd): ?>
                                <?php 
                                    $isUploaded = false;
                                    foreach($stepDocs as $sd) {
                                        if (stripos($sd['file_name'], $rd) !== false || stripos($sd['type'], $rd) !== false) {
                                            $isUploaded = true; break;
                                        }
                                    }
                                ?>
                                <div class="checklist-item">
                                    <div class="doc-status-icon <?= $isUploaded ? 'uploaded' : 'missing' ?>">
                                        <i class="fas <?= $isUploaded ? 'fa-check-circle' : 'fa-circle' ?>"></i>
                                    </div>
                                    <div class="checklist-label"><?= esc($rd) ?></div>
                                    <?php if(!$isUploaded): ?>
                                        <button class="btn-upload-checklist" onclick="openUploadStep(<?= $active_step['id'] ?>, '<?= esc($rd) ?>')">
                                            <i class="fas fa-upload"></i> Tải lên
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="premium-card p-20 m-b-24">
                    <div class="card-section">
                        <h3 class="section-header-title">Nội dung & Lưu ý</h3>
                        <div class="description-box text-apple-main m-b-20" style="line-height: 1.6;">
                            <?= nl2br(esc($case['description'] ?: 'Không có mô tả chi tiết.')) ?>
                        </div>
                        <h3 class="section-header-title">Thông tin bổ sung</h3>
                        <div class="info-grid-premium">
                            <div class="info-item">
                                <label class="text-muted-dark text-xs font-weight-600 uppercase">Mức độ ưu tiên</label>
                                <div class="font-weight-600">
                                    <?php 
                                        $priorities = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'critical' => 'Khẩn cấp'];
                                        echo $priorities[$case['priority']] ?? $case['priority'];
                                    ?>
                                </div>
                            </div>
                            <div class="info-item" style="grid-column: span 2;">
                                <label class="text-muted-dark text-xs font-weight-600 uppercase" style="display:flex; justify-content:space-between; align-items:center;">
                                    <span>Đội ngũ phụ trách</span>
                                    <?php if (in_array(session()->get('role_name'), ['Admin', 'Trưởng phòng'])): ?>
                                        <a href="javascript:void(0)" onclick="document.getElementById('assignMembersModal').style.display='flex'; $('.select2-multi').select2({dropdownParent: $('#assignMembersModal')});" class="text-apple-blue font-weight-500" style="text-decoration:none; text-transform:none;"><i class="fas fa-user-plus m-r-4"></i> Phân công</a>
                                    <?php endif; ?>
                                </label>
                                <div class="font-weight-600 m-t-10 flex-column gap-10">
                                    <?php if(empty($members)): ?>
                                        <div class="text-muted-dark font-weight-400"><i class="fas fa-info-circle m-r-5"></i> Chưa có nhân sự nào được gán.</div>
                                    <?php else: ?>
                                        <?php if(!empty($memberGroups['approver'])): ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="badge-secondary-minimal text-xs" style="min-width: 85px; text-align: center; background: rgba(0,0,0,0.05);">Người duyệt</span> 
                                                <div style="flex:1; line-height:1.5;">
                                                    <?= implode(', ', array_map(function($m) { return esc($m['full_name']); }, $memberGroups['approver'])) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!empty($memberGroups['assignee'])): ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="badge-info-minimal text-xs" style="min-width: 85px; text-align: center;">Chuyên môn</span> 
                                                <div style="flex:1; line-height:1.5;">
                                                    <?= implode(', ', array_map(function($m) { return esc($m['full_name']); }, $memberGroups['assignee'])) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!empty($memberGroups['supporter'])): ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="text-muted-dark text-xs" style="min-width: 85px; text-align: center; border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px;">Hỗ trợ</span> 
                                                <div style="flex:1; line-height:1.5; font-weight:500;">
                                                    <?= implode(', ', array_map(function($m) { return esc($m['full_name']); }, $memberGroups['supporter'])) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section (Internal) -->
            <div id="tab-comments" class="tab-content" style="display: none;">
                <div class="premium-card p-20 m-b-24">
                    <h3 class="section-header-title">Trao đổi nội bộ (Chỉ nhân viên)</h3>
                    
                    <div class="comment-feed m-b-20" style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                        <?php if(empty($comments)): ?>
                            <div class="empty-state-container p-20 text-center text-muted-dark">Chưa có trao đổi nào về hồ sơ này.</div>
                        <?php else: ?>
                            <?php foreach($comments as $c): ?>
                                <div class="timeline-item-premium" title="Phản hồi từ <?= esc($c['user_name']) ?>">
                                    <div class="timeline-dot"></div>
                                    <div class="flex-column gap-5 m-b-10">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span class="font-weight-700 text-sm text-apple-main"><?= esc($c['user_name']) ?></span>
                                            <span class="text-xs text-muted-dark"><?= date('H:i d/m/Y', strtotime($c['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="text-sm text-apple-main" style="line-height: 1.5;">
                                        <?= nl2br(esc($c['content'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form action="<?= base_url('cases/add-comment/' . $case['id']) ?>" method="POST" class="premium-form">
                        <?= csrf_field() ?>
                        <div class="form-group-premium">
                            <textarea name="content" rows="3" class="form-control-premium" placeholder="Nhập ghi chú hoặc hướng dẫn xử lý hồ sơ..." required title="Nội dung trao đổi nội bộ"></textarea>
                        </div>
                        <div class="text-right m-t-10">
                            <button type="submit" class="btn-premium btn-sm" title="Gửi ghi chú cho đồng nghiệp">
                                <i class="fas fa-paper-plane"></i> Gửi ghi chú
                            </button>
                        </div>
                    </form>
                </div>
            </div>


            <!-- History Section -->
            <div id="tab-history" class="tab-content" style="display: none;">
                <div class="premium-card p-20 m-b-24">
                    <div class="log-entry-list">
                        <?php if (empty($history)): ?>
                            <div class="empty-state-container text-center text-muted-dark italic">Chưa có ghi nhận thay đổi nào.</div>
                        <?php else: ?>
                            <?php foreach ($history as $h): ?>
                            <div class="log-entry-item p-15" style="border-bottom: 1px solid var(--border-color);">
                                <div class="log-header" style="display: flex; justify-content: space-between; align-items: start;">
                                    <div class="log-title">
                                        <span class="badge-log badge-secondary-minimal m-r-8" title="Hành động">
                                            <?php
                                                $actions = [
                                                    'tiep_nhan' => 'Tiếp nhận',
                                                    'cap_nhat_trang_thai' => 'Trạng thái',
                                                    'upload_ho_so' => 'Tài liệu',
                                                    'assign_personnel' => 'Nhân sự'
                                                ];
                                                echo $actions[$h['action']] ?? $h['action'];
                                            ?>
                                        </span>
                                        <strong class="text-apple-main" title="Giá trị mới cập nhật"><?= esc($h['new_value']) ?></strong>
                                    </div>
                                    <div class="text-xs text-muted-dark"><?= date('H:i d/m/Y', strtotime($h['created_at'])) ?></div>
                                </div>
                                <?php if($h['note']): ?>
                                    <div class="log-details m-t-10 text-sm text-muted-dark">
                                        <?= esc($h['note']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div id="tab-documents" class="tab-content" style="display: none;">
                <div class="premium-card p-20 m-b-24">
                    <div class="header-with-action m-b-20" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="section-header-title p-0 m-0">Danh sách hồ sơ</h3>
                        <button class="btn-secondary-sm" onclick="document.getElementById('uploadModal').style.display='flex'" title="Tải tệp tin pháp lý lên hệ thống">
                            <i class="fas fa-upload"></i> Tải tài liệu lên
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Tên tài liệu</th>
                                    <th>Loại</th>
                                    <th class="table-cell-center">Ngày tải</th>
                                    <th class="table-cell-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                    <tr><td colspan="4" class="empty-state-container text-center text-muted-dark">Chưa có tài liệu đính kèm.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <div class="font-weight-500 text-apple-main"><?= esc($doc['file_name']) ?></div>
                                        </td>
                                        <td><span class="badge-secondary-minimal text-xs"><?= esc($doc['type'] ?: 'Khác') ?></span></td>
                                        <td class="table-cell-center text-sm"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                                        <td class="table-cell-center">
                                            <a href="<?= base_url($doc['file_path']) ?>" target="_blank" class="btn-secondary-sm" title="Tải xuống tài liệu này">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-sidebar">
            <div class="premium-card p-20 m-b-24">
                <h3 class="sidebar-section-title">Trạng thái hiện tại</h3>
                <div class="status-indicator-box text-center p-20 m-b-15" style="background: rgba(0,0,0,0.02); border-radius: 12px;">
                    <?php 
                        $statusLabels = [
                            'moi_tiep_nhan' => 'Mới tiếp nhận',
                            'dang_xu_ly' => 'Đang xử lý',
                            'cho_tham_tam' => 'Chờ thẩm định',
                            'da_giai_quyet' => 'Đã giải quyết',
                            'dong_ho_so' => 'Đã đóng hồ sơ',
                            'huy' => 'Đã hủy'
                        ];
                    ?>
                    <div class="badge-log badge-info-minimal text-lg" style="padding: 10px 20px; display: block;" title="Trạng thái xử lý của hồ sơ">
                        <?= $statusLabels[$case['status']] ?? $case['status'] ?>
                    </div>
                </div>

                <div class="deadline-timer m-t-15">
                    <label class="sidebar-section-title">Hạn chót dự kiến</label>
                    <div class="text-xl font-weight-700 <?= (strtotime($case['deadline']) < time()) ? 'text-apple-red' : 'text-apple-main' ?>" title="Ngày hết hạn hoàn thành toàn bộ vụ việc">
                        <?= $case['deadline'] ? date('d/m/Y', strtotime($case['deadline'])) : 'Chưa thiết lập' ?>
                    </div>
                    <?php if($case['deadline']): ?>
                        <div class="text-xs text-muted-dark">
                            <?php 
                                $days = ceil((strtotime($case['deadline']) - time()) / 86400);
                                echo $days > 0 ? "Còn $days ngày" : "Quá hạn " . abs($days) . " ngày";
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="premium-card p-20">
                <h3 class="sidebar-section-title">Hành động nhanh</h3>
                <div class="quick-actions-list flex-column gap-10">
                    <?php if (has_permission('case.manage')): ?>
                        <button class="btn-secondary-sm w-100" style="justify-content: flex-start;" onclick="document.getElementById('statusModal').style.display='flex'" title="Cập nhật trạng thái tổng thể hồ sơ">
                            <i class="fas fa-sync-alt m-r-8"></i> Cập nhật trạng thái
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn-secondary-sm w-100" style="justify-content: flex-start;" onclick="switchTab('comments')" title="Viết trao đổi hoặc chỉ đạo nghiệp vụ">
                        <i class="fas fa-comment-dots m-r-8"></i> Thêm ghi chú nội bộ
                    </button>
                    
                    <a href="tel:0901234567" class="btn-secondary-sm w-100" style="justify-content: flex-start;" title="Gọi điện trực tiếp cho khách hàng">
                        <i class="fas fa-phone-alt m-r-8"></i> Liên hệ khách hàng
                    </a>
                    
                    <button class="btn-secondary-sm w-100 text-apple-red" style="justify-content: flex-start;" onclick="alert('Tính năng đang phát triển: Nhắc nhở deadline cho luật sư')" title="Gửi thông báo nhắc nhở tiến độ">
                        <i class="fas fa-bell m-r-8"></i> Nhắc nhở bộ phận
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="statusModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:450px; position:relative;">
        <h3 class="section-header-title">Cập nhật trạng thái mới</h3>
        <form action="<?= base_url('cases/update-status/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10" style="display:block;">Trạng thái tiếp theo</label>
                <select name="status" class="form-control-premium" required title="Chọn trạng thái mới cho hồ sơ">
                    <?php foreach($statusLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $case['status'] == $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10" style="display:block;">Ghi chú thay đổi</label>
                <textarea name="note" class="form-control-premium" placeholder="Lý do cập nhật hoặc bước công việc tiếp theo..." style="height:100px;" title="Ghi chú giải thích cho việc thay đổi trạng thái"></textarea>
            </div>
            <div class="form-actions-row m-t-20">
                <button type="button" class="btn-secondary-sm" onclick="document.getElementById('statusModal').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium" title="Xác nhận cập nhật">Cập nhật ngay</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Members Modal -->
<div id="assignMembersModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:500px; position:relative;">
        <h3 class="section-header-title">Phân công nhân sự tham gia</h3>
        <form action="<?= base_url('cases/update-members/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">1. Người phê duyệt (Cấp Quản lý)</label>
                <select name="approvers[]" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;">
                    <?php foreach($staffs as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], array_column($memberGroups['approver'], 'employee_id')) ? 'selected' : '' ?>><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">2. Phụ trách chính</label>
                <select name="assignees[]" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;">
                    <?php foreach($staffs as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], array_column($memberGroups['assignee'], 'employee_id')) ? 'selected' : '' ?>><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">3. Nhân sự hỗ trợ</label>
                <select name="supporters[]" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;">
                    <?php foreach($staffs as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], array_column($memberGroups['supporter'], 'employee_id')) ? 'selected' : '' ?>><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions-row m-t-20">
                <button type="button" class="btn-secondary-sm" onclick="document.getElementById('assignMembersModal').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium"><i class="fas fa-save m-r-8"></i> Lưu danh sách</button>
            </div>
        </form>
    </div>
</div>

<div id="uploadModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card" style="width:450px;">
        <h3 class="section-title-premium">Tải tài liệu lên hồ sơ</h3>
        <form action="<?= base_url('cases/upload-doc/' . $case['id']) ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Chọn tệp tin</label>
                <input type="file" name="doc_file" required style="width:100%;">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Tên tài liệu gợi nhớ</label>
                <input type="text" name="file_name" id="modal_file_name" placeholder="Ví dụ: Đơn khởi kiện lần 1" style="width:100%;">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Phân loại</label>
                <input type="text" name="doc_type" id="modal_doc_type" placeholder="Ví dụ: Đơn từ, Chứng cứ..." style="width:100%;">
            </div>
            <input type="hidden" name="step_id" id="modal_step_id" value="">
            <div class="form-actions-row" style="margin-top:20px; justify-content: flex-end;">
                <button type="button" class="btn-secondary-sm" onclick="document.getElementById('uploadModal').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium">Tải lên</button>
            </div>
        </form>
    </div>
</div>

<!-- 
    Giao diện chi tiết vụ việc (360-degree Case View).
    Sử dụng kiến trúc Tabbed để phân tách các khối dữ liệu: Timeline, Bình luận, Lịch sử, Tài liệu.
-->
<script>
/**
 * Chuyển đổi giữa các tab nội dung.
 * 
 * @param string tabId Tên của tab cần hiển thị (overview, comments, history, documents)
 */
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.nav-tab-item').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    event.currentTarget.classList.add('active');
}

/**
 * Mở modal upload tài liệu cho một bước cụ thể
 */
function openUploadStep(stepId, docName) {
    document.getElementById('modal_step_id').value = stepId;
    document.getElementById('modal_file_name').value = docName;
    document.getElementById('modal_doc_type').value = 'Hồ sơ quy trình';
    document.getElementById('uploadModal').style.display = 'flex';
}
</script>
<?= $this->endSection() ?>
