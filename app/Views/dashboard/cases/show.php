<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/cases.css') ?>">
<?= $this->endSection() ?>

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
            <p class="content-subtitle">Khách hàng: <strong
                        class="text-apple-main"><?= esc($case['customer_name']) ?></strong></p>
        </div>
        <div class="header-controls">
            <!-- Quay lại danh sách tổng -->
            <a href="<?= base_url('cases') ?>" class="btn-secondary-sm" title="Quay lại danh sách vụ việc">
                <i class="fas fa-chevron-left"></i> Danh sách
            </a>
            <!-- Nút cập nhật trạng thái hồ sơ (Mở Modal) -->
            <button class="btn-premium" onclick="document.getElementById('statusModal').style.display='flex'"
                    title="Thay đổi trạng thái hoặc giao việc cho nhân sự khác">
                <i class="fas fa-sync-alt"></i> Cập nhật trạng thái
            </button>
        </div>
    </div>

    <div class="profile-grid-premium">
        <div class="profile-main">
            <!-- Tabs Navigation -->
            <div class="nav-tabs-premium m-b-24">
                <div class="nav-tab-item active" onclick="switchTab('overview')"
                     title="Xem tiến độ và nội dung chi tiết">Tổng quan & Timeline
                </div>
                <div class="nav-tab-item" onclick="switchTab('comments')" title="Trao đổi nội bộ giữa các bộ phận">Bình
                    luận nội bộ (<?= !empty($comments) && is_array($comments) ? count($comments) : 0 ?>)
                </div>
                <div class="nav-tab-item" onclick="switchTab('history')" title="Lịch sử cập nhật của hồ sơ">Lịch sử thay
                    đổi
                </div>
                <div class="nav-tab-item" onclick="switchTab('documents')" title="Các tệp tin, tài liệu đính kèm">Hồ sơ
                    tài liệu (<?= !empty($documents) && is_array($documents) ? count($documents) : 0 ?>)
                </div>
            </div>

            <!-- Overview & Timeline Section -->
            <div id="tab-overview" class="tab-content active">
                <?php if (!empty($steps)) { ?>
                    <!-- Case Progress & Horizontal Stepper -->
                    <div class="case-progress-wrapper m-b-24">
                        <?php
                        $totalStepsCount = (!empty($steps) && is_array($steps)) ? count($steps) : 0;
                        $completedStepsCount = 0;
                        if (!empty($steps) && is_array($steps)) {
                            foreach ($steps as $s) {
                                if (($s['status'] ?? '') === 'completed') $completedStepsCount++;
                            }
                        }
                        $progressPercent = $totalStepsCount > 0 ? round(($completedStepsCount / $totalStepsCount) * 100) : 0;
                        ?>
                        <div class="progress-header">
                            <div class="flex-column">
                                <h4 class="m-0 font-weight-700">Tiến độ quy trình</h4>
                                <?php if ($case['template_name']) { ?>
                                    <span class="text-xs text-muted-dark">Mẫu: <span
                                                class="text-apple-main"><?= esc($case['template_name']) ?></span></span>
                                <?php } ?>
                            </div>
                            <span class="badge-secondary-minimal text-apple-main font-weight-700"><?= $progressPercent ?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $progressPercent ?>%;"></div>
                        </div>

                        <div class="horizontal-stepper">
                            <?php if (!empty($steps) && is_array($steps)) { ?>
                                <?php foreach ($steps as $index => $s) { ?>
                                    <?php
                                    $isCompleted = (($s['status'] ?? '') === 'completed');
                                    $isActive = (($s['status'] ?? '') === 'active');
                                    $deadlineTime = isset($s['deadline']) ? strtotime($s['deadline']) : time();
                                    $isOverdue = !$isCompleted && $deadlineTime < time();

                                    $hClass = '';
                                    if ($isCompleted) $hClass = 'completed';
                                    elseif ($isOverdue) $hClass = 'overdue';
                                    elseif ($isActive) $hClass = 'active';
                                    ?>
                                    <div class="h-step-item <?= $hClass ?>"
                                         title="<?= esc($s['step_name'] ?? 'Bước') ?> (Hạn: <?= date('d/m', $deadlineTime) ?>)">
                                        <div class="h-step-dot"><?= ($isCompleted ? '<i class="fas fa-check"></i>' : $index + 1) ?></div>
                                        <div class="h-step-label"><?= esc($s['step_name'] ?? 'Bước ' . ($index + 1)) ?></div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Active Step Details & Checklist -->
                    <?php if (!empty($active_step)) { ?>
                        <div class="premium-card p-20 m-b-24">
                            <div class="flex-row justify-between align-center m-b-15">
                                <h3 class="section-header-title m-0">
                                    <i class="fas fa-tasks m-r-8"></i> Bước hiện
                                    tại: <?= esc($active_step['step_name']) ?>
                                </h3>
                                <div class="text-right" style="display:flex; gap: 10px; justify-content: flex-end;">
                                    <?php
                                    $role = session()->get('role_name');
                                    // isEmployee: Phải gửi duyệt (mặc định cho Nhân viên / TTS)
                                    $isEmployee = (strpos(strtolower($role), 'nhân viên') !== false || $role == 'Nhân viên chính thức');
                                    // isManager: Quyền quản trị hệ thống
                                    $isManager = in_array(strtolower($role), ['admin', 'trưởng phòng', 'truong_phong']);

                                    // Ưu tiên: Nếu User hiện tại là Người duyệt (Approver) của vụ việc này -> Cho phép Duyệt thẳng và Không bước qua khâu gửi duyệt
                                    $canApproveDirectly = ($isManager || $isApprover);
                                    ?>

                                    <?php if ($active_step['status'] === 'pending_approval') { ?>
                                        <?php if ($canApproveDirectly) { ?>
                                            <form action="<?= base_url('cases/approve-step/' . $active_step['id']) ?>"
                                                  method="POST"
                                                  onsubmit="return confirm('Xác nhận phê duyệt bước này?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-premium btn-sm"
                                                        style="background: var(--apple-main); border-color: var(--apple-main);">
                                                    <i class="fas fa-check"></i> Phê duyệt
                                                </button>
                                            </form>
                                            <form action="<?= base_url('cases/reject-step/' . $active_step['id']) ?>"
                                                  method="POST" onsubmit="return confirm('Xác nhận từ chối bước này?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-premium btn-sm"
                                                        style="background: var(--apple-red); border-color: var(--apple-red);">
                                                    <i class="fas fa-times"></i> Từ chối
                                                </button>
                                            </form>
                                        <?php } else { ?>
                                            <?php if (!empty($is_approval_read)) { ?>
                                                <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>"
                                                      method="POST"
                                                      onsubmit="return confirm('Quản lý đã xem yêu cầu trước đó. Bạn có muốn gửi yêu cầu xét duyệt lại không?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn-premium btn-sm">
                                                        <i class="fas fa-paper-plane"></i> Gửi lại yêu cầu
                                                    </button>
                                                </form>
                                            <?php } else { ?>
                                                <span class="badge-secondary-minimal text-apple-orange"
                                                      style="padding: 8px 15px; border-color: var(--apple-orange);">
                                                <i class="fas fa-hourglass-half"></i> Chờ kiểm duyệt
                                            </span>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>"
                                              method="POST"
                                              onsubmit="return confirm('<?= !$canApproveDirectly ? 'Bạn đã tải đủ tài liệu yêu cầu chưa? Xác nhận gửi yêu cầu duyệt?' : 'Xác nhận hoàn thành bước này?' ?>')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-premium btn-sm">
                                                <i class="fas <?= !$canApproveDirectly ? 'fa-paper-plane' : 'fa-check-double' ?>"></i>
                                                <?= !$canApproveDirectly ? 'Gửi yêu cầu duyệt' : 'Hoàn thành bước' ?>
                                            </button>
                                        </form>
                                    <?php } ?>
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
                                <?php if (!empty($active_step['responsible_display'])) { ?>
                                    <div class="info-item flex-2">
                                        <label class="text-muted-dark text-xs uppercase">Người chịu trách nhiệm / Thông
                                            báo</label>
                                        <div class="m-t-5"><?= $active_step['responsible_display'] ?></div>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="step-checklist-card">
                                <div class="checklist-header">
                                    <span>Checklist tài liệu bắt buộc</span>
                                    <?php
                                    $currentStepRank = 0;
                                    if (!empty($steps) && !empty($active_step)) {
                                        foreach ($steps as $idx => $s) {
                                            if ($s['id'] == $active_step['id']) {
                                                $currentStepRank = $idx + 1;
                                                break;
                                            }
                                        }
                                    }
                                    $totalSteps = (!empty($steps) && is_array($steps) ? count($steps) : 0);
                                    ?>
                                    <span class="text-xs text-muted-dark">Bước <?= $currentStepRank ?>/<?= $totalSteps ?></span>
                                </div>
                                <?php
                                $reqDocs = !empty($active_step['required_documents']) ? json_decode($active_step['required_documents'], true) : [];
                                if (!empty($reqDocs) && is_array($reqDocs)) {
                                    $stepDocs = is_array($documents) ? array_filter($documents, function ($d) use ($active_step) {
                                        return ($d['step_id'] ?? 0) == ($active_step['id'] ?? -1);
                                    }) : [];

                                    foreach ($reqDocs as $rd) {
                                        $isUploaded = false;
                                        foreach ($stepDocs as $sd) {
                                            if (stripos($sd['file_name'] ?? '', $rd) !== false || stripos($sd['type'] ?? '', $rd) !== false) {
                                                $isUploaded = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        <div class="checklist-item">
                                            <div class="doc-status-icon <?= $isUploaded ? 'uploaded' : 'missing' ?>">
                                                <i class="fas <?= $isUploaded ? 'fa-check-circle' : 'fa-circle' ?>"></i>
                                            </div>
                                            <div class="checklist-label"><?= esc($rd) ?></div>
                                            <?php if (!$isUploaded) { ?>
                                                <button class="btn-upload-checklist"
                                                        onclick="openUploadStep(<?= $active_step['id'] ?? 0 ?>, '<?= esc($rd) ?>')">
                                                    <i class="fas fa-upload"></i> Tải lên
                                                </button>
                                            <?php } ?>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>

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
                                <label class="text-muted-dark text-xs font-weight-600 uppercase"
                                       style="display:flex; justify-content:space-between; align-items:center;">
                                    <span>Đội ngũ phụ trách</span>
                                    <?php if (in_array(session()->get('role_name'), ['Admin', 'Trưởng phòng'])) { ?>
                                        <a href="javascript:void(0)"
                                           onclick="document.getElementById('assignMembersModal').style.display='flex'; $('.select2-multi').select2({dropdownParent: $('#assignMembersModal')});"
                                           class="text-apple-blue font-weight-500"
                                           style="text-decoration:none; text-transform:none;"><i
                                                    class="fas fa-user-plus m-r-4"></i> Phân công</a>
                                    <?php } ?>
                                </label>
                                <div class="font-weight-600 m-t-10 flex-column gap-10">
                                    <?php if (empty($members) || !is_array($members)) { ?>
                                        <div class="text-muted-dark font-weight-400"><i
                                                     class="fas fa-info-circle m-r-5"></i> Chưa có nhân sự nào được gán.
                                        </div>
                                    <?php } else { ?>
                                        <?php if (!empty($memberGroups['approver']) && is_array($memberGroups['approver'])) { ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="badge-secondary-minimal text-xs"
                                                      style="min-width: 85px; text-align: center; background: rgba(0,0,0,0.05);">Người duyệt</span>
                                                <div style="flex:1; line-height:1.5;">
                                                    <?= implode(', ', array_map(function ($m) {
                                                        return esc($m['full_name'] ?? 'N/A');
                                                    }, $memberGroups['approver'])) ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($memberGroups['assignee']) && is_array($memberGroups['assignee'])) { ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="badge-info-minimal text-xs"
                                                      style="min-width: 85px; text-align: center;">Chuyên môn</span>
                                                <div style="flex:1; line-height:1.5;">
                                                    <?= implode(', ', array_map(function ($m) {
                                                        return esc($m['full_name'] ?? 'N/A');
                                                    }, $memberGroups['assignee'])) ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($memberGroups['supporter']) && is_array($memberGroups['supporter'])) { ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="text-muted-dark text-xs"
                                                      style="min-width: 85px; text-align: center; border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px;">Hỗ trợ</span>
                                                <div style="flex:1; line-height:1.5; font-weight:500;">
                                                    <?= implode(', ', array_map(function ($m) {
                                                        return esc($m['full_name'] ?? 'N/A');
                                                    }, $memberGroups['supporter'])) ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
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
                            <?php if (empty($comments) || !is_array($comments)) { ?>
                                <div class="empty-state-container p-20 text-center text-muted-dark">Chưa có trao đổi nào về
                                    hồ sơ này.
                                </div>
                            <?php } else { ?>
                                <?php foreach ($comments as $c) { ?>
                                    <div class="timeline-item-premium" title="Phản hồi từ <?= esc($c['user_name'] ?? 'N/A') ?>">
                                        <div class="timeline-dot"></div>
                                        <div class="flex-column gap-5 m-b-10">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span class="font-weight-700 text-sm text-apple-main"><?= esc($c['user_name'] ?? 'Unknown User') ?></span>
                                                <span class="text-xs text-muted-dark"><?= isset($c['created_at']) ? date('H:i d/m/Y', strtotime($c['created_at'])) : '--' ?></span>
                                            </div>
                                        </div>
                                        <div class="text-sm text-apple-main" style="line-height: 1.5;">
                                            <?= nl2br(esc($c['content'] ?? '')) ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>

                    <form action="<?= base_url('cases/add-comment/' . $case['id']) ?>" method="POST"
                          class="premium-form">
                        <?= csrf_field() ?>
                        <div class="form-group-premium">
                            <textarea name="content" rows="3" class="form-control-premium"
                                      placeholder="Nhập ghi chú hoặc hướng dẫn xử lý hồ sơ..." required
                                      title="Nội dung trao đổi nội bộ"></textarea>
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
                        <?php if (empty($history) || !is_array($history)) { ?>
                            <div class="empty-state-container text-center text-muted-dark italic">Chưa có ghi nhận thay
                                đổi nào.
                            </div>
                        <?php } else { ?>
                            <?php foreach ($history as $h) { ?>
                                <div class="log-entry-item p-15" style="border-bottom: 1px solid var(--border-color);">
                                    <div class="log-header"
                                         style="display: flex; justify-content: space-between; align-items: start;">
                                        <div class="log-title">
                                        <span class="badge-log badge-secondary-minimal m-r-8" title="Hành động">
                                            <?php
                                            $actions = [
                                                    'tiep_nhan' => 'Tiếp nhận',
                                                    'cap_nhat_trang_thai' => 'Trạng thái',
                                                    'upload_ho_so' => 'Tài liệu',
                                                    'assign_personnel' => 'Nhân sự'
                                            ];
                                            echo $actions[$h['action'] ?? ''] ?? ($h['action'] ?? 'Hành động');
                                            ?>
                                        </span>
                                            <strong class="text-apple-main"
                                                    title="Giá trị mới cập nhật"><?= esc($h['new_value'] ?? '--') ?></strong>
                                        </div>
                                        <div class="text-xs text-muted-dark"><?= isset($h['created_at']) ? date('H:i d/m/Y', strtotime($h['created_at'])) : '--' ?></div>
                                    </div>
                                    <?php if (!empty($h['note'])) { ?>
                                        <div class="log-details m-t-10 text-sm text-muted-dark">
                                            <?= esc($h['note']) ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div id="tab-documents" class="tab-content" style="display: none;">
                <div class="premium-card p-20 m-b-24">
                    <div class="header-with-action m-b-20"
                         style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="section-header-title p-0 m-0">Danh sách hồ sơ</h3>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn-secondary-sm"
                                    onclick="openVaultModal('tab-documents')"
                                    title="Chọn tài liệu từ kho DMS chung">
                                <i class="fas fa-archive"></i> Kho tài liệu (DMS)
                            </button>
                            <button class="btn-premium-sm"
                                    onclick="document.getElementById('uploadModal').style.display='flex'"
                                    title="Tải tệp tin pháp lý lên hệ thống">
                                <i class="fas fa-upload m-r-8"></i> Tải tài liệu mới
                            </button>
                        </div>
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
                            <?php if (empty($documents) || !is_array($documents)) { ?>
                                <tr>
                                    <td colspan="4" class="empty-state-container text-center text-muted-dark">Chưa có
                                        tài liệu đính kèm.
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($documents as $doc) { ?>
                                    <tr>
                                        <td>
                                            <div class="font-weight-500 text-apple-main"><?= esc($doc['file_name'] ?? 'Tài liệu') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge-secondary-minimal text-xs"><?= esc($doc['type'] ?? 'Khác') ?></span>
                                        </td>
                                        <td class="table-cell-center text-sm"><?= isset($doc['created_at']) ? date('d/m/Y', strtotime($doc['created_at'])) : '--' ?></td>
                                        <td class="table-cell-center">
                                            <a href="<?= base_url('documents/view/' . $doc['id']) ?>" target="_blank"
                                               class="btn-secondary-sm" title="Xem / Tải xuống tài liệu này thông qua bộ lọc DMS Sec">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-sidebar">
            <div class="premium-card p-20 m-b-24">
                <h3 class="sidebar-section-title">Trạng thái hiện tại</h3>
                <div class="status-indicator-box text-center p-20 m-b-15"
                     style="background: rgba(0,0,0,0.02); border-radius: 12px;">
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
                    <div class="badge-log badge-info-minimal text-lg" style="padding: 10px 20px; display: block;"
                         title="Trạng thái xử lý của hồ sơ">
                        <?= $statusLabels[$case['status']] ?? $case['status'] ?>
                    </div>
                </div>

                <div class="deadline-timer m-t-15">
                    <label class="sidebar-section-title">Hạn chót dự kiến</label>
                    <div class="text-xl font-weight-700 <?= (strtotime($case['deadline']) < time()) ? 'text-apple-red' : 'text-apple-main' ?>"
                         title="Ngày hết hạn hoàn thành toàn bộ vụ việc">
                        <?= $case['deadline'] ? date('d/m/Y', strtotime($case['deadline'])) : 'Chưa thiết lập' ?>
                    </div>
                    <?php if ($case['deadline']) { ?>
                        <div class="text-xs text-muted-dark">
                            <?php
                            $days = ceil((strtotime($case['deadline']) - time()) / 86400);
                            echo $days > 0 ? "Còn $days ngày" : "Quá hạn " . abs($days) . " ngày";
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="premium-card p-20">
                <h3 class="sidebar-section-title">Hành động nhanh</h3>
                <div class="quick-actions-list flex-column gap-10">
                    <?php if (has_permission('case.manage')) { ?>
                        <button class="btn-secondary-sm w-100" style="justify-content: flex-start;"
                                onclick="document.getElementById('statusModal').style.display='flex'"
                                title="Cập nhật trạng thái tổng thể hồ sơ">
                            <i class="fas fa-sync-alt m-r-8"></i> Cập nhật trạng thái
                        </button>
                    <?php } ?>

                    <button class="btn-secondary-sm w-100" style="justify-content: flex-start;"
                            onclick="switchTab('comments')" title="Viết trao đổi hoặc chỉ đạo nghiệp vụ">
                        <i class="fas fa-comment-dots m-r-8"></i> Thêm ghi chú nội bộ
                    </button>

                    <a href="tel:0901234567" class="btn-secondary-sm w-100" style="justify-content: flex-start;"
                       title="Gọi điện trực tiếp cho khách hàng">
                        <i class="fas fa-phone-alt m-r-8"></i> Liên hệ khách hàng
                    </a>

                    <button class="btn-secondary-sm w-100 text-apple-red" style="justify-content: flex-start;"
                            onclick="alert('Tính năng đang phát triển: Nhắc nhở deadline cho luật sư')"
                            title="Gửi thông báo nhắc nhở tiến độ">
                        <i class="fas fa-bell m-r-8"></i> Nhắc nhở bộ phận
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="statusModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:450px; position:relative;">
        <h3 class="section-header-title">Cập nhật trạng thái mới</h3>
        <form action="<?= base_url('cases/update-status/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10" style="display:block;">Trạng thái tiếp theo</label>
                <select name="status" class="form-control-premium" required title="Chọn trạng thái mới cho hồ sơ">
                    <?php if (!empty($statusLabels) && is_array($statusLabels)) { ?>
                        <?php foreach ($statusLabels as $val => $lbl) { ?>
                            <option value="<?= $val ?>" <?= ($case['status'] ?? '') == $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>


            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10" style="display:block;">Ghi chú thay đổi</label>
                <textarea name="note" class="form-control-premium"
                          placeholder="Lý do cập nhật hoặc bước công việc tiếp theo..." style="height:100px;"
                          title="Ghi chú giải thích cho việc thay đổi trạng thái"></textarea>
            </div>
            <div class="form-actions-row m-t-20">
                <button type="button" class="btn-secondary-sm"
                        onclick="document.getElementById('statusModal').style.display='none'">Hủy
                </button>
                <button type="submit" class="btn-premium" title="Xác nhận cập nhật">Cập nhật ngay</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Members Modal -->
<div id="assignMembersModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:500px; position:relative;">
        <h3 class="section-header-title">Phân công nhân sự tham gia</h3>
        <form action="<?= base_url('cases/update-members/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">1. Người phê duyệt (Cấp Quản lý)</label>
                <select name="approvers[]" class="form-control-premium select2-multi" multiple="multiple"
                        style="width: 100%;">
                    <?php if (!empty($staffs) && is_array($staffs)) { ?>
                        <?php foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>" <?= (isset($memberGroups['approver']) && is_array($memberGroups['approver']) && in_array($s['id'], array_column($memberGroups['approver'], 'employee_id'))) ? 'selected' : '' ?>><?= esc($s['full_name'] ?? '--') ?>
                                (<?= esc($s['position'] ?? '--') ?>)
                            </option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">2. Phụ trách chính</label>
                <select name="assignees[]" class="form-control-premium select2-multi" multiple="multiple"
                        style="width: 100%;">
                    <?php foreach ($staffs as $s) { ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], array_column($memberGroups['assignee'], 'employee_id')) ? 'selected' : '' ?>><?= esc($s['full_name']) ?>
                            (<?= esc($s['position']) ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">3. Nhân sự hỗ trợ</label>
                <select name="supporters[]" class="form-control-premium select2-multi" multiple="multiple"
                        style="width: 100%;">
                    <?php foreach ($staffs as $s) { ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], array_column($memberGroups['supporter'], 'employee_id')) ? 'selected' : '' ?>><?= esc($s['full_name']) ?>
                            (<?= esc($s['position']) ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-actions-row m-t-20">
                <button type="button" class="btn-secondary-sm"
                        onclick="document.getElementById('assignMembersModal').style.display='none'">Hủy
                </button>
                <button type="submit" class="btn-premium"><i class="fas fa-save m-r-8"></i> Lưu danh sách</button>
            </div>
        </form>
    </div>
</div>

<div id="uploadModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card" style="width:450px;">
        <h3 class="section-title-premium">Tải tài liệu lên hồ sơ</h3>
        <p class="text-xs text-muted-dark m-b-20">Tài liệu sẽ được tự động đồng bộ vào kho dữ liệu khách hàng.</p>
        <form action="<?= base_url('cases/upload-doc/' . $case['id']) ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5">Chọn tệp tin</label>
                <input type="file" name="doc_file" required class="form-control-premium">
            </div>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5">Tên tài liệu gợi nhớ</label>
                <input type="text" name="file_name" id="modal_file_name" placeholder="Ví dụ: Đơn khởi kiện lần 1"
                       class="form-control-premium">
            </div>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5">Ghi chú (Không bắt buộc)</label>
                <textarea name="description" class="form-control-premium" placeholder="Mô tả nội dung tài liệu..."></textarea>
            </div>
            <input type="hidden" name="step_id" id="modal_step_id" value="">
            <div class="form-actions-row" style="margin-top:20px; justify-content: flex-end;">
                <button type="button" class="btn-secondary-sm"
                        onclick="document.getElementById('uploadModal').style.display='none'">Hủy
                </button>
                <button type="submit" class="btn-premium">Tải lên ngay</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Nhập từ kho DMS -->
<div id="vaultModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">
    <div class="premium-card" style="width:650px; max-height: 80vh; display: flex; flex-direction: column;">
        <div class="modal-header-premium" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title-premium" style="margin:0;">Kho tài liệu hệ thống (Vault)</h3>
            <button type="button" class="btn-close-minimal" onclick="document.getElementById('vaultModal').style.display='none'"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="m-b-15">
            <input type="text" id="vaultSearch" placeholder="Tìm kiếm tài liệu trong kho..." class="form-control-premium" onkeyup="filterVault()">
        </div>

        <div id="vaultListContainer" style="flex:1; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Tên tài liệu</th>
                        <th>Phân loại</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody id="vaultTableBody">
                    <!-- Loaded via AJAX -->
                    <tr><td colspan="4" class="text-center p-20">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="form-actions-row m-t-20" style="justify-content: flex-end;">
            <button type="button" class="btn-secondary-sm" onclick="document.getElementById('vaultModal').style.display='none'">Đóng</button>
            <button type="button" id="btnConfirmImport" class="btn-premium" disabled onclick="confirmImport()">Nhập tài liệu đã chọn</button>
        </div>
    </div>
</div>

<!-- 
    Giao diện chi tiết vụ việc (360-degree Case View).
    Sử dụng kiến trúc Tabbed để phân tách các khối dữ liệu: Timeline, Bình luận, Lịch sử, Tài liệu.
-->
<script>
    /**
     * L.A.N ERP - Quản lý Chi tiết Vụ việc
     * Điều khiển các tương tác trên trang chi tiết: Chuyển tab và Quản lý tài liệu theo bước.
     */

    /**
     * Chuyển đổi hiển thị giữa các tab nội dung (Tổng quan, Bình luận, Lịch sử, Tài liệu).
     * @param {string} tabId - Định danh của tab cần hiển thị (ví dụ: 'overview', 'comments', ...).
     */
    function switchTab(tabId) {
        // Ẩn tất cả các khối nội dung tab hiện tại
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        
        // Gỡ bỏ trạng thái 'active' khỏi tất cả các nút điều hướng tab
        document.querySelectorAll('.nav-tab-item').forEach(el => el.classList.remove('active'));
        
        // Hiển thị khối nội dung được chọn
        const targetTab = document.getElementById('tab-' + tabId);
        if (targetTab) {
            targetTab.style.display = 'block';
        }

        // Thêm trạng thái 'active' cho nút tab vừa được click
        // Lưu ý: Sử dụng event.currentTarget để xác định đúng phần tử được nhấn
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
    }

    /**
     * Mở Modal tải tài liệu và gán tự động thông tin vào các trường ẩn (hidden fields).
     * Phục vụ việc tải tài liệu minh chứng cho từng bước (Step) trong quy trình.
     * @param {number} stepId - ID của bước công việc đang thực hiện.
     * @param {string} docName - Tên gợi ý cho tài liệu cần tải lên.
     */
    function openUploadStep(stepId, docName) {
        // Gán ID bước vào input ẩn để server biết tài liệu thuộc bước nào
        document.getElementById('modal_step_id').value = stepId;
        
        // Tự động điền tên tài liệu dựa trên checklist để người dùng không phải nhập lại
        document.getElementById('modal_file_name').value = docName;
        
        // Hiển thị modal upload bằng Flexbox để căn giữa màn hình
        const uploadModal = document.getElementById('uploadModal');
        if (uploadModal) {
            uploadModal.style.display = 'flex';
        }
    }

    let selectedVaultDocId = null;
    let importTargetType = 'case';

    /**
     * Mở Modal kho tài liệu DMS để chọn tệp tin nhập vào vụ việc.
     */
    function openVaultModal(target) {
        const modal = document.getElementById('vaultModal');
        modal.style.display = 'flex';
        selectedVaultDocId = null;
        document.getElementById('btnConfirmImport').disabled = true;

        // Tải danh sách tài liệu từ kho qua AJAX
        fetch('<?= base_url("documents/vault-list") ?>?category=internal')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('vaultTableBody');
                tbody.innerHTML = '';
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center p-20">Kho tài liệu hiện tại đang trống.</td></tr>';
                    return;
                }

                data.forEach(doc => {
                    const tr = document.createElement('tr');
                    tr.style.cursor = 'pointer';
                    tr.onclick = () => selectVaultDoc(doc.id, tr);
                    tr.innerHTML = `
                        <td><input type="radio" name="vault_doc" value="${doc.id}"></td>
                        <td><strong>${doc.file_name}</strong></td>
                        <td><span class="badge-secondary-minimal text-xs">${doc.document_category}</span></td>
                        <td class="text-sm">${new Date(doc.created_at).toLocaleDateString('vi-VN')}</td>
                    `;
                    tbody.appendChild(tr);
                });
            });
    }

    function selectVaultDoc(id, row) {
        selectedVaultDocId = id;
        document.querySelectorAll('#vaultTableBody tr').forEach(r => r.style.background = 'white');
        row.style.background = 'rgba(0, 113, 227, 0.05)';
        row.querySelector('input[type="radio"]').checked = true;
        document.getElementById('btnConfirmImport').disabled = false;
    }

    /**
     * Xác nhận nhập tài liệu từ kho vào vụ việc hiện tại.
     */
    function confirmImport() {
        if (!selectedVaultDocId) return;

        const formData = new FormData();
        formData.append('document_id', selectedVaultDocId);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('<?= base_url("cases/import-doc/" . $case['id']) ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Đã nhập tài liệu thành công.');
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + result.message);
            }
        });
    }

    function filterVault() {
        let input = document.getElementById('vaultSearch');
        let filter = input.value.toUpperCase();
        let tr = document.querySelectorAll('#vaultTableBody tr');
        tr.forEach(row => {
            let text = row.textContent || row.innerText;
            row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        });
    }
</script>
<?= $this->endSection() ?>
