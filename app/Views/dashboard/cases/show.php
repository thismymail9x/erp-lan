<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/cases.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $myEmpId = session()->get('employee_id');
    $isPrimaryAssignee = false;
    if (!empty($memberGroups['assignee']) && is_array($memberGroups['assignee'])) {
        foreach ($memberGroups['assignee'] as $m) {
            if ($m['employee_id'] == $myEmpId) {
                $isPrimaryAssignee = true;
                break;
            }
        }
    }
    
    // Quyền chỉnh sửa tổng thể
    $canEditCase = has_permission('sys.admin') || has_permission('case.manage') || has_permission('case.edit_all') || $isPrimaryAssignee;
?>

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
            <p class="content-subtitle">Khách hàng: 
                <a href="<?= base_url('customers/show/' . $case['customer_id']) ?>" class="link-premium font-weight-700">
                    <?= esc($case['customer_name']) ?>
                </a>
            </p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('cases') ?>" class="btn-secondary-sm" title="Quay lại">
                <i class="fas fa-chevron-left"></i> Danh sách
            </a>
            
            <?php if ($canEditCase) { ?>
                <a href="<?= base_url('cases/edit/' . $case['id']) ?>" class="btn-secondary-sm" title="Sửa">
                    <i class="fas fa-edit"></i> Sửa
                </a>
                <button class="btn-premium" onclick="document.getElementById('statusModal').style.display='flex'"
                        title="Cập nhật trạng thái tổng thể">
                    <i class="fas fa-sync-alt"></i> Cập nhật
                </button>
            <?php } ?>
        </div>
    </div>

    <div class="profile-grid-premium">
        <div class="profile-main">
            <!-- Tabs Navigation -->
            <div class="nav-tabs-premium m-b-24">
                <div class="nav-tab-item active" onclick="switchTab('overview')">
                    <i class="fas fa-stream"></i> Tổng quan
                </div>
                <div class="nav-tab-item" onclick="switchTab('comments')">
                    <i class="fas fa-comments"></i> Trao đổi (<?= !empty($comments) && is_array($comments) ? count($comments) : 0 ?>)
                </div>
                <div class="nav-tab-item" onclick="switchTab('history')">
                    <i class="fas fa-history"></i> Lịch sử
                </div>
                <div class="nav-tab-item" onclick="switchTab('documents')">
                    <i class="fas fa-file-contract"></i> Tài liệu (<?= !empty($documents) && is_array($documents) ? count($documents) : 0 ?>)
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
                                <h4 class="m-0 font-weight-700">Tiến độ</h4>
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
                                         title="<?= esc($s['step_name'] ?? 'Bước') ?> (Hạn: <?= date('d/m', $deadlineTime) ?>) - Thưởng: <?= number_format($s['kpi_reward'] ?? 0, 0, ',', '.') ?> VNĐ">
                                        <div class="h-step-dot"><?= ($isCompleted ? '<i class="fas fa-check"></i>' : $index + 1) ?></div>
                                        <div class="h-step-label">
                                            <?= esc($s['step_name'] ?? 'Bước ' . ($index + 1)) ?>
                                            <?php if (($s['kpi_reward'] ?? 0) > 0) { ?>
                                                <div class="reward-label-mini m-t-5"><?= number_format($s['kpi_reward'], 0, ',', '.') ?>đ</div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Active Step Details & Checklist -->
                    <?php if (!empty($active_step)) { ?>
                        <div class="premium-card p-20 m-b-24">
                            <div class="flex-row justify-between align-center m-b-15">
                                <h3 class="section-header-title m-0" style="display: flex; align-items: center; gap: 12px;">
                                    <i class="fas fa-tasks"></i> 
                                    <span>Bước: <?= esc($active_step['step_name']) ?></span>
                                    <span class="badge-secondary-minimal text-xs m-l-5" style="font-weight: 600;">
                                        <i class="fas fa-clock m-r-4"></i> Quy định: <?= $active_step['duration_days'] ?> ngày
                                    </span>
                                    <?php if (($active_step['kpi_reward'] ?? 0) > 0) { ?>
                                        <div class="badge-reward-vibrant" title="Thưởng KPI">
                                            <i class="fas fa-gift"></i>
                                            +<?= number_format($active_step['kpi_reward'], 0, ',', '.') ?>đ
                                        </div>
                                    <?php } ?>
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
                                                  onsubmit="return confirm('Phê duyệt bước này?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-premium btn-sm"
                                                        style="background: var(--apple-main); border-color: var(--apple-main);">
                                                    <i class="fas fa-check"></i> Duyệt
                                                </button>
                                            </form>
                                            <form action="<?= base_url('cases/reject-step/' . $active_step['id']) ?>"
                                                  method="POST" onsubmit="return confirm('Từ chối bước này?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-premium btn-sm"
                                                        style="background: var(--apple-red); border-color: var(--apple-red);">
                                                    <i class="fas fa-times"></i> Hủy
                                                </button>
                                            </form>
                                        <?php } else { ?>
                                            <?php if (!empty($is_approval_read)) { ?>
                                                <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>"
                                                      method="POST"
                                                      onsubmit="return confirm('Gửi yêu cầu xét duyệt lại?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn-premium btn-sm">
                                                        <i class="fas fa-paper-plane"></i> Gửi lại
                                                    </button>
                                                </form>
                                            <?php } else { ?>
                                                <span class="badge-secondary-minimal text-apple-orange"
                                                      style="padding: 8px 15px; border-color: var(--apple-orange);">
                                                <i class="fas fa-hourglass-half"></i> Chờ duyệt
                                            </span>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>"
                                              method="POST"
                                              onsubmit="return confirm('Xác nhận hoàn thành?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-premium btn-sm">
                                                <i class="fas <?= !$canApproveDirectly ? 'fa-paper-plane' : 'fa-check-double' ?>"></i>
                                                <?= !$canApproveDirectly ? 'Gửi duyệt' : 'Hoàn thành' ?>
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
                                        $matchedDoc = null;
                                        foreach ($stepDocs as $sd) {
                                            if (stripos($sd['file_name'] ?? '', $rd) !== false || stripos($sd['type'] ?? '', $rd) !== false) {
                                                $isUploaded = true;
                                                $matchedDoc = $sd;
                                                break;
                                            }
                                        }
                                        ?>
                                        <div class="checklist-item">
                                            <div class="doc-status-icon <?= $isUploaded ? 'uploaded' : 'missing' ?>">
                                                <i class="fas <?= $isUploaded ? 'fa-check-circle' : 'fa-circle' ?>"></i>
                                            </div>
                                            <div class="checklist-label" style="flex:1;"><?= esc($rd) ?></div>
                                            
                                            <div class="checklist-actions" style="display: flex; gap: 8px;">
                                                <?php if ($isUploaded) { ?>
                                                    <?php 
                                                        $isImg = in_array(strtolower($matchedDoc['file_type'] ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                        $docUrl = base_url('documents/view/' . $matchedDoc['id']);
                                                    ?>
                                                    <a href="<?= $docUrl ?>" target="_blank" class="btn-secondary-sm" title="Tải về trực tiếp">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <?php if ($isImg) { ?>
                                                        <button type="button" class="btn-secondary-sm text-apple-blue" 
                                                                onclick="previewImage('<?= $docUrl . '?preview=1' ?>', '<?= esc($matchedDoc['file_name']) ?>')" 
                                                                title="Xem nhanh ảnh">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <button class="btn-upload-checklist"
                                                            onclick="openUploadStep(<?= $active_step['id'] ?? 0 ?>, '<?= esc($rd) ?>')">
                                                        <i class="fas fa-upload"></i> Tải lên
                                                    </button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>

                <?php
                $isHanhChinhOrAdmin = (session()->get('role_name') === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
                if ($isHanhChinhOrAdmin) { 
                ?>
                <div class="premium-card p-20 m-b-24" style="background: rgba(var(--apple-blue-rgb), 0.03); border-color: rgba(var(--apple-blue-rgb), 0.2);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                        <h3 class="section-header-title m-0 text-apple-blue"><i class="fas fa-file-invoice-dollar m-r-8"></i> Tài khoản, Hợp đồng & Thanh toán</h3>
                        <a href="<?= base_url('cases/edit/' . $case['id']) ?>" class="btn-secondary-sm text-xs"><i class="fas fa-edit m-r-4"></i> Cập nhật</a>
                    </div>
                    <div class="info-grid-premium">
                        <div class="info-item">
                            <label class="text-muted-dark text-xs font-weight-600 uppercase">Giá trị hợp đồng</label>
                            <div class="font-weight-700 text-lg text-apple-main">
                                <?= !empty($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') . ' VNĐ' : '<span class="text-muted-dark font-weight-400 italic">Chưa cập nhật</span>' ?>
                            </div>
                        </div>
                        <div class="info-item flex-2">
                            <label class="text-muted-dark text-xs font-weight-600 uppercase">Tiến độ thanh toán</label>
                            <div class="font-weight-500 text-apple-main" style="line-height: 1.5;">
                                <?php 
                                if (!empty($case['payment_progress'])) {
                                    $payments = json_decode($case['payment_progress'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($payments)) {
                                        echo '<ul style="padding-left: 20px; margin: 0;">';
                                        foreach ($payments as $p) {
                                            $amountHtml = !empty($p['amount']) ? '<span class="text-apple-blue font-weight-600">' . number_format($p['amount'], 0, ',', '.') . 'đ</span>' : '';
                                            $deadlineHtml = !empty($p['deadline']) ? ' - Hạn: <span class="text-apple-red">' . date('d/m/Y', strtotime($p['deadline'])) . '</span>' : '';
                                            $paidIcon = (!empty($p['is_paid']) && $p['is_paid'] == 1) ? '<span class="badge-success-minimal text-xs m-l-8"><i class="fas fa-check-circle"></i> Đã thu</span>' : '<span class="badge-warning-minimal text-xs m-l-8"><i class="fas fa-clock"></i> Chờ thu</span>';
                                            echo '<li>' . esc($p['title']) . ': ' . $amountHtml . $deadlineHtml . $paidIcon . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        echo nl2br(esc($case['payment_progress']));
                                    }
                                } else {
                                    echo '<span class="text-muted-dark font-weight-400 italic">Chưa ghi chú</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
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
                                                    <?php foreach ($memberGroups['approver'] as $idx => $m) { ?>
                                                        <a href="<?= base_url('employees/edit/' . $m['employee_id']) ?>" class="link-premium text-sm font-weight-600">
                                                            <?= esc($m['full_name'] ?? 'N/A') ?><?= ($idx < count($memberGroups['approver']) - 1) ? ',' : '' ?>
                                                        </a>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($memberGroups['assignee']) && is_array($memberGroups['assignee'])) { ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="badge-info-minimal text-xs"
                                                      style="min-width: 85px; text-align: center;">Chuyên môn</span>
                                                <div style="flex:1; line-height:1.5;">
                                                    <?php foreach ($memberGroups['assignee'] as $idx => $m) { ?>
                                                        <a href="<?= base_url('employees/edit/' . $m['employee_id']) ?>" class="link-premium text-sm font-weight-600">
                                                            <?= esc($m['full_name'] ?? 'N/A') ?><?= ($idx < count($memberGroups['assignee']) - 1) ? ',' : '' ?>
                                                        </a>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($memberGroups['supporter']) && is_array($memberGroups['supporter'])) { ?>
                                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                                <span class="text-muted-dark text-xs"
                                                      style="min-width: 85px; text-align: center; border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px;">Hỗ trợ</span>
                                                <div style="flex:1; line-height:1.5; font-weight:500;">
                                                    <?php foreach ($memberGroups['supporter'] as $idx => $m) { ?>
                                                        <a href="<?= base_url('employees/edit/' . $m['employee_id']) ?>" class="link-premium text-sm font-weight-600">
                                                            <?= esc($m['full_name'] ?? 'N/A') ?><?= ($idx < count($memberGroups['supporter']) - 1) ? ',' : '' ?>
                                                        </a>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NEW: Detailed Vertical Roadmap & Rewards -->
                    <div class="row-v-timeline m-t-40 m-b-24">
                        <div class="flex-row justify-between align-center m-b-20">
                            <h3 class="section-header-title m-0">Lộ trình & Định mức thưởng</h3>
                            
                            <?php if (has_permission('sys.admin') || has_permission('case.edit_all')) { ?>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" onclick="document.getElementById('addStepModal').style.display='flex'" class="btn-secondary-sm flex-item-center" style="padding: 4px 12px; font-size: 12px; font-weight: 600; height: 32px;cursor: pointer">
                                        <i class="fas fa-plus text-apple-blue m-r-5"></i> Thêm bước
                                    </button>
                                    <a href="<?= base_url('cases/sync-rewards/' . $case['id']) ?>" 
                                       class="btn-secondary-sm flex-item-center" 
                                       onclick="return confirm('Hệ thống sẽ cập nhật lại định mức thưởng mới nhất từ Quy trình gốc cho tất cả các bước của vụ việc này. Tiếp tục?')"
                                       style="padding: 4px 12px; font-size: 12px; font-weight: 600; height: 32px;">
                                        <i class="fas fa-sync-alt text-muted m-r-5"></i> Đồng bộ thưởng
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="roadmap-timeline m-t-20">
                            <?php foreach ($steps as $idx => $s) { 
                                $isCompleted = (($s['status'] ?? '') === 'completed');
                                $isActive = (($s['status'] ?? '') === 'active');
                                $rClass = '';
                                if ($isCompleted) $rClass = 'completed';
                                elseif ($isActive) $rClass = 'active';
                            ?>
                                <div class="roadmap-item <?= $rClass ?>">
                                    <div class="roadmap-dot"></div>
                                    <div class="roadmap-content">
                                        <div class="flex-column">
                                            <div class="roadmap-step-name">
                                                Bước <?= $idx + 1 ?>: <?= esc($s['step_name']) ?>
                                                <span class="badge-secondary-minimal text-xxs m-l-5" style="vertical-align: middle; padding: 2px 8px; font-weight: 600;">
                                                    <i class="fas fa-hourglass-start m-r-4"></i><?= $s['duration_days'] ?> ngày
                                                </span>
                                                <?php if ($isCompleted) { ?> <i class="fas fa-check-circle text-green m-l-5"></i> <?php } ?>
                                            </div>
                                            <div class="text-xs text-muted-dark m-t-4">
                                                <?php if ($isCompleted) { 
                                                    $isLate = strtotime($s['completed_at']) > strtotime($s['deadline']);
                                                ?>
                                                    Hạn quy định: <span class="text-apple-main font-weight-600"><?= date('d/m/Y', strtotime($s['deadline'])) ?></span> 
                                                    - Thực tế: <span class="<?= $isLate ? 'text-apple-red' : 'text-apple-main' ?> font-weight-600"><?= date('d/m/Y H:i', strtotime($s['completed_at'])) ?></span>
                                                <?php } else { 
                                                    $isOverdue = strtotime($s['deadline']) < time();
                                                ?>
                                                    Hạn xử lý: <span class="<?= $isOverdue ? 'text-apple-red' : 'text-apple-main' ?> font-weight-700"><?= date('d/m/Y', strtotime($s['deadline'])) ?></span>
                                                <?php } ?>
                                            </div>
                                            <?php 
                                            $sDocs = !empty($s['required_documents']) ? json_decode($s['required_documents'], true) : [];
                                            if (!empty($sDocs)) { ?>
                                                <div class="roadmap-docs-list m-t-8" style="display: flex; flex-wrap: wrap; gap: 6px;">
                                                    <?php foreach ($sDocs as $docName) { ?>
                                                        <span class="badge-secondary-minimal text-xxs" style="padding: 2px 8px; border-radius: 4px; background: rgba(0,0,0,0.03); color: var(--apple-text-muted); font-weight: 500;">
                                                            <i class="fas fa-file-alt m-r-4"></i> <?= esc($docName) ?>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php if (($s['kpi_reward'] ?? 0) > 0) { ?>
                                                <div class="<?= $isActive ? 'badge-reward-vibrant' : 'reward-label-mini' ?>" style="font-size: 13px; font-weight: 600; padding: 4px 12px; border-radius: 20px;">
                                                    <i class="fas fa-gift"></i> +<?= number_format($s['kpi_reward'], 0, ',', '.') ?> VNĐ
                                                </div>
                                            <?php } else { ?>
                                                <div class="text-xs text-muted-dark italic" style="opacity: 0.5;">0đ</div>
                                            <?php } ?>
                                            
                                            <?php if (has_permission('sys.admin') || has_permission('case.edit_all')) { ?>
                                                <a href="<?= base_url('cases/delete-step/' . $s['id']) ?>" 
                                                   onclick="return confirm('Xác nhận xóa bỏ bước này khỏi quy trình? Lưu ý: Hành động này không thể hoàn tác.')"
                                                   class="btn-secondary-sm flex-item-center text-apple-red" title="Xóa bước này" 
                                                   style="padding: 0; width: 28px; height: 28px; justify-content: center; border-radius: 6px; background: rgba(255,59,48,0.1); border: 1px solid rgba(255,59,48,0.2);">
                                                    <i class="fas fa-trash-alt" style="font-size: 12px;"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section (Internal) -->
            <div id="tab-comments" class="tab-content" style="display: none;">
                <div class="premium-card p-20 m-b-24">
                    <h3 class="section-header-title">Trao đổi (Chỉ nhân viên)</h3>

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
                                      title="Nội dung trao đổi"></textarea>
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
                    <div class="header-controls">
                        <a href="<?= base_url('knowledge/create?case_id=' . $case['id']) ?>" class="btn-secondary-sm" title="Soạn tài liệu kinh nghiệm và bài học cho riêng vụ việc này.">
                            <i class="fas fa-lightbulb text-warning"></i> Rút kinh nghiệm
                        </a>
                    </div>
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
                                <i class="fas fa-archive"></i> Kho tài liệu
                            </button>
                            <button class="btn-premium-sm"
                                    onclick="document.getElementById('uploadModal').style.display='flex'"
                                    title="Tải tệp tin pháp lý lên hệ thống">
                                <i class="fas fa-upload m-r-8"></i> Tải tài liệu mới
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
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
                                            <div style="display: flex; gap: 8px; justify-content: center;">
                                                <?php 
                                                    $isImg = in_array(strtolower($doc['file_type'] ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                    $docUrl = base_url('documents/view/' . $doc['id']);
                                                ?>
                                                <a href="<?= $docUrl ?>" target="_blank"
                                                   class="btn-secondary-sm" title="Tải xuống tài liệu">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php if ($isImg) { ?>
                                                    <button type="button" class="btn-secondary-sm text-apple-blue" 
                                                            onclick="previewImage('<?= $docUrl . '?preview=1' ?>', '<?= esc($doc['file_name']) ?>')" 
                                                            title="Xem nhanh ảnh">
                                                        <i class="fas fa-eye"></i>
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
                </div>
            </div>
        </div>

        <div class="profile-sidebar">
            <div class="premium-card p-20 m-b-24">
                <h3 class="sidebar-section-title">Trạng thái hiện tại</h3>
                <div class="status-indicator-box text-center p-20 m-b-15"
                     style="background: rgba(0,0,0,0.02); border-radius: 12px;">
                    <?php
                    $statusClasses = [
                            'cho_tiep_nhan' => 'badge-info-minimal',
                            'dang_xu_ly'    => 'badge-warning-minimal',
                            'da_hoan_thanh' => 'badge-success-minimal',
                            'huy'           => 'badge-danger-minimal'
                    ];
                    $currentStatusClass = $statusClasses[$case['status']] ?? 'badge-secondary-minimal';
                    ?>
                    <div class="badge-log <?= $currentStatusClass ?> text-lg" style="padding: 10px 20px; display: block;"
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

            <div class="premium-card p-20 m-b-24">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 class="sidebar-section-title m-0">Nhãn dán Vụ việc</h3>
                    <a href="javascript:void(0)" onclick="document.getElementById('tagModal').style.display='flex'" 
                       class="text-apple-blue font-weight-500 text-xs" style="text-decoration:none;">
                        <i class="fas fa-edit m-r-4"></i> Quản lý
                    </a>
                </div>
                
                <div class="tags-container-minimal flex-wrap" style="gap: 8px;">
                    <?php if (empty($tags)) { ?>
                        <div class="text-xs text-muted-dark italic">Chưa gắn nhãn dán nào.</div>
                    <?php } else { ?>
                        <?php foreach ($tags as $t) { ?>
                            <span class="tag-badge-premium" style="background-color: <?= esc($t['color']) ?>15; color: <?= esc($t['color']) ?>; border: 1px solid <?= esc($t['color']) ?>30;">
                                <i class="fas fa-tag m-r-4" style="font-size: 8px;"></i> <?= esc($t['name']) ?>
                            </span>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="premium-card p-20">
                <h3 class="sidebar-section-title">Hành động nhanh</h3>
                <div class="quick-actions-list flex-column gap-10">
                    <?php if ($canEditCase) { ?>
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

                    <button class="btn-secondary-sm w-100" style="justify-content: flex-start;"
                            onclick="window.location.href='<?= base_url('knowledge/create?case_id=' . $case['id']) ?>'" 
                            title="Bạn có kinh nghiệm gì về Case này không? Chia sẻ ngay">
                        <i class="fas fa-lightbulb m-r-8 text-warning"></i> Chia sẻ kinh nghiệm ngay
                    </button>

<!--                    --><?php //if (!empty($case['customer_phone'])) { ?>
<!--                    <a href="tel:--><?php //= esc($case['customer_phone']) ?><!--" class="btn-secondary-sm w-100" style="justify-content: flex-start;"-->
<!--                       title="Gọi điện trực tiếp cho khách hàng: --><?php //= esc($case['customer_phone']) ?><!--">-->
<!--                        <i class="fas fa-phone-alt m-r-8 text-apple-green"></i> Liên hệ: --><?php //= esc($case['customer_phone']) ?>
<!--                    </a>-->
<!--                    --><?php //} ?>

                    <button class="btn-secondary-sm w-100 text-apple-red" style="justify-content: flex-start;"
                            onclick="document.getElementById('reminderModal').style.display='flex'; $('.select2-single').select2({dropdownParent: $('#reminderModal')});"
                            title="Gửi thông báo nhắc nhở tiến độ">
                        <i class="fas fa-bell m-r-8"></i> Nhắc nhở bộ phận
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Reminder Modal -->
<div id="reminderModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:500px; position:relative;">
        <h3 class="section-header-title">Gửi nhắc nhở bộ phận</h3>
        <p class="text-xs text-muted-dark m-b-20">Thông báo này sẽ xuất hiện trên thanh Navbar của người nhận ngay lập tức.</p>
        
        <form action="<?= base_url('cases/send-reminder/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10" style="display:block;">Gửi đến nhân sự</label>
                <select name="recipient_user_id" class="form-control-premium select2-single" required style="width:100%;">
                    <option value="">-- Chọn người nhận --</option>
                    <?php if (!empty($staffs)) { ?>
                        <?php foreach ($staffs as $s) { ?>
                            <?php if ($s['user_id'] != session()->get('user_id')) { ?>
                                <option value="<?= $s['user_id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10" style="display:block;">Nội dung nhắc nhở</label>
                <textarea name="message" class="form-control-premium" required
                          placeholder="Nhập nội dung cần chỉ đạo hoặc nhắc nhở..." style="height:120px;"
                          title="Nội dung sẽ hiển thị cho người nhận"></textarea>
            </div>
            
            <div class="form-actions-row m-t-20">
                <button type="button" class="btn-secondary-sm"
                        onclick="document.getElementById('reminderModal').style.display='none'">Hủy
                </button>
                <button type="submit" class="btn-premium" style="background: var(--apple-red); border-color: var(--apple-red);">
                    <i class="fas fa-paper-plane m-r-8"></i> Gửi thông báo
                </button>
            </div>
        </form>
    </div>
</div>
<div id="statusModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:450px; position:relative;">
        <h3 class="section-header-title">Cập nhật trạng thái mới</h3>
        <form action="<?= base_url('cases/update-status/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10" style="display:block;">Trạng thái tiếp theo</label>
                <select name="status" class="form-control-premium" required title="Chọn trạng thái mới cho hồ sơ">
                    <?php 
                    $labels = \Config\AppConstants::CASE_STATUS_LABELS;
                    foreach ($labels as $val => $lbl) { ?>
                        <option value="<?= $val ?>" <?= ($case['status'] ?? '') == $val ? 'selected' : '' ?>><?= $lbl ?></option>
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
                <input type="text" required name="file_name" id="modal_file_name" placeholder="Ví dụ: Đơn khởi kiện lần 1"
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

<!-- Tag Management Modal -->
<div id="tagModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:450px; position:relative;">
        <h3 class="section-header-title">Quản lý nhãn dán hồ sơ</h3>
        <p class="text-xs text-muted-dark m-b-20">Gắn các thẻ phân loại để dễ dàng theo dõi và tìm lọc vụ việc.</p>
        
        <form action="<?= base_url('cases/update-tags/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <div class="tags-selector-list gap-15" style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                    <?php if (empty($availableTags)) { ?>
                        <div class="text-xs text-muted-dark italic">Chưa có nhãn dán nào trong hệ thống.</div>
                    <?php } else { ?>
                        <?php 
                            $currentTagIds = array_column($tags, 'id');
                            foreach ($availableTags as $at) { 
                        ?>
                            <label class="custom-checkbox-premium flex-item-center" style="cursor: pointer;">
                                <input type="checkbox" name="tag_ids[]" value="<?= $at['id'] ?>" 
                                       <?= in_array($at['id'], $currentTagIds) ? 'checked' : '' ?>
                                       style="width: 18px; height: 18px; margin-right: 12px; cursor: pointer;">
                                <div class="flex-column">
                                    <div class="flex-item-center gap-10">
                                        <span class="tag-badge-premium" style="background-color: <?= esc($at['color']) ?>15; color: <?= esc($at['color']) ?>; border: 1px solid <?= esc($at['color']) ?>30;">
                                            <?= esc($at['name']) ?>
                                        </span>
                                        <?php if ($at['type'] == 'private') { ?>
                                            <span class="text-xxs text-muted" title="Đây là nhãn dán cá nhân của bạn">(Riêng)</span>
                                        <?php } ?>
                                    </div>
                                </div>
                            </label>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
            
            <div class="form-actions-row m-t-25 text-center" style="justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn-secondary-sm"
                        onclick="document.getElementById('tagModal').style.display='none'">Hủy
                </button>
                <button type="submit" class="btn-premium">
                    <i class="fas fa-save m-r-8"></i> Lưu
                </button>
            </div>
        </form>

        <?php 
            $isPowerUser = (has_permission('sys.admin') || strpos(strtolower(session()->get('role_name')), 'trưởng phòng') !== false);
            if ($isPowerUser) { 
        ?>
            <hr class="m-y-20" style="opacity: 0.1;">
            <div class="quick-create-tag-section">
                <h4 class="text-xs font-weight-700 uppercase m-b-15" style="color: var(--apple-text-muted);">Tạo nhãn dán</h4>
                <form action="<?= base_url('cases/create-tag') ?>" method="POST" class="flex-column gap-15">
                    <?= csrf_field() ?>
                    <div style="display: grid; grid-template-columns: 1fr 100px; gap: 10px;">
                        <input type="text" name="name" class="form-control-premium" placeholder="Tên nhãn dán..." required style="height: 38px;">
                        <input type="color" name="color" value="#007aff" class="form-control-premium" style="height: 38px; padding: 2px; width: 100%;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 100px; gap: 10px;">
                        <select name="type" class="form-control-premium text-xs" style="width: auto; height: 32px; padding: 0 10px;">
                            <option value="global">Dùng chung (Toàn công ty)</option>
                            <option value="private">Dùng riêng (Cá nhân)</option>
                        </select>
                        <input type="hidden" name="module_scope" value="cases">
                        <input type="hidden" name="ref_case_id" value="<?= $case['id'] ?>">
                        <button type="submit" class="btn-secondary-sm" style="font-size: 11px;">
                            <i class="fas fa-plus m-r-5"></i> Tạo mới
                        </button>
                    </div>
                </form>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Add Step Modal -->
<div id="addStepModal" class="modal-overlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:500px; position:relative;">
        <h3 class="section-header-title">Thêm bước phát sinh</h3>
        <p class="text-xs text-muted-dark m-b-20">Bước mới sẽ được thêm vào cuối quy trình (hoặc tự động sắp xếp theo thời hạn).</p>
        
        <form action="<?= base_url('cases/add-step/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">Tên bước công việc <span class="text-apple-red">*</span></label>
                <input type="text" name="step_name" class="form-control-premium" required placeholder="Ví dụ: Bổ sung hồ sơ tài chính phụ">
            </div>

            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">Tài liệu bắt buộc (Cách nhau bằng dấu phẩy)</label>
                <input type="text" name="required_documents_raw" class="form-control-premium" placeholder="Ví dụ: CCCD, Hợp đồng lao động, Giấy xác nhận...">
            </div>

            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5" style="display:block;">Vị trí chèn <span class="text-apple-red">*</span></label>
                <select name="insert_after_step_id" class="form-control-premium" required>
                    <?php 
                        $lastCompletedOrder = 0;
                        foreach($steps as $s) {
                            if ($s['status'] === 'completed') $lastCompletedOrder = $s['sort_order'];
                        }
                    ?>
                    <option value="0" <?= $lastCompletedOrder > 0 ? 'disabled' : '' ?>>-- Chèn vào đầu quy trình --</option>
                    <?php foreach ($steps as $idx => $s) { 
                        $isCompleted = ($s['status'] === 'completed');
                        $isDisabled = ($isCompleted && $s['sort_order'] < $lastCompletedOrder);
                    ?>
                        <option value="<?= $s['id'] ?>" <?= $isDisabled ? 'disabled' : '' ?>>
                            Sau bước <?= $idx + 1 ?>: <?= esc($s['step_name']) ?> <?= $isCompleted ? '(Đã xong)' : '' ?>
                        </option>
                    <?php } ?>
                    <option value="-1" selected>-- Chèn vào cuối quy trình --</option>
                </select>
            </div>
            
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group-premium m-b-15">
                    <label class="info-list-label m-b-5" style="display:block;">Thời gian dự kiến (Ngày)</label>
                    <input type="number" name="duration_days" class="form-control-premium" min="1" value="3" required>
                </div>
                <div class="form-group-premium m-b-15">
                    <label class="info-list-label m-b-5" style="display:block;">Thưởng KPI (VNĐ)</label>
                    <input type="text" name="kpi_reward" class="form-control-premium" value="0" onkeyup="this.value=this.value.replace(/[^\d]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                </div>
            </div>
            
            <div class="form-actions-row m-t-20 text-center" style="justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn-secondary-sm" onclick="document.getElementById('addStepModal').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium"><i class="fas fa-save m-r-8"></i> Thêm bước</button>
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

<!-- Image Preview Modal (Apple-Style) -->
<div id="imagePreviewModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:2000; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
    <div style="position:relative; max-width:90%; max-height:90%;">
        <button onclick="document.getElementById('imagePreviewModal').style.display='none'" 
                style="position:absolute; top:-40px; right:-40px; background:none; border:none; color:white; font-size:30px; cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
        <div style="background:white; padding:10px; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
            <img id="previewImgElement" src="" style="max-width:100%; max-height:80vh; display:block; border-radius:8px;">
            <div id="previewImgTitle" style="text-align:center; padding-top:10px; font-weight:600; color:#333; font-size:14px;"></div>
        </div>
    </div>
</div>

<script>
    /**
     * L.A.N ERP - Quản lý Chi tiết Vụ việc
     */
    $(document).ready(function() {
        // Kích hoạt Select2 cho các ô chọn nhân sự trong Modal Phân công
        $('.select2-multi').select2({
            dropdownParent: $('#assignMembersModal'),
            width: '100%',
            placeholder: '-- Chọn nhân sự --'
        });
    });

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

    function previewImage(url, title) {
        const modal = document.getElementById('imagePreviewModal');
        const img = document.getElementById('previewImgElement');
        const titleEl = document.getElementById('previewImgTitle');
        
        img.src = url;
        titleEl.innerText = title;
        modal.style.display = 'flex';
    }

    // Close modal on outside click
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('imagePreviewModal');
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    /**
     * Lọc tài liệu trong Kho (Vault Search)
     */
    function filterVault() {
        let input = document.getElementById('vaultSearch');
        if (!input) return;
        let filter = input.value.toUpperCase();
        let tr = document.querySelectorAll('#vaultTableBody tr');
        tr.forEach(row => {
            let text = row.textContent || row.innerText;
            row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        });
    }

    /**
     * Switch Tab Function
     */
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
        document.querySelectorAll('.nav-tab-item').forEach(t => t.classList.remove('active'));
        
        const targetTab = document.getElementById('tab-' + tabName);
        if (targetTab) targetTab.style.display = 'block';
        
        const targetItem = document.querySelector(`.nav-tab-item[onclick*="${tabName}"]`);
        if (targetItem) targetItem.classList.add('active');
    }
</script>

<!-- Global Image Preview Modal -->
<div id="imagePreviewModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:2000; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
    <div style="position:relative; max-width:90%; max-height:90%;">
        <button onclick="document.getElementById('imagePreviewModal').style.display='none'" 
                style="position:absolute; top:-40px; right:-40px; background:none; border:none; color:white; font-size:30px; cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
        <div style="background:white; padding:10px; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
            <img id="previewImgElement" src="" style="max-width:100%; max-height:80vh; display:block; border-radius:8px;">
            <div id="previewImgTitle" style="text-align:center; padding-top:10px; font-weight:600; color:#333; font-size:14px;"></div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
