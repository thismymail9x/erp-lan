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

<div class="case-detail-container" data-vault-url="<?= base_url('documents/vault-list') ?>?category=internal" data-import-doc-url="<?= base_url('cases/import-doc/' . $case['id']) ?>" data-csrf-name="<?= csrf_token() ?>" data-csrf-hash="<?= csrf_hash() ?>">
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
                <button class="btn-premium-sm" data-modal-open="statusModal"
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
                <div class="nav-tab-item active" data-tab="overview">
                    <i class="fas fa-stream"></i> Tổng quan
                </div>
                <div class="nav-tab-item" data-tab="comments">
                    <i class="fas fa-comments"></i> Trao đổi (<?= !empty($comments) && is_array($comments) ? count($comments) : 0 ?>)
                </div>
                <div class="nav-tab-item" data-tab="history">
                    <i class="fas fa-history"></i> Lịch sử
                </div>
                <div class="nav-tab-item" data-tab="documents">
                    <i class="fas fa-file-contract"></i> Tài liệu (<?= !empty($documents) && is_array($documents) ? count($documents) : 0 ?>)
                </div>
                <div class="nav-tab-item" data-tab="expenses">
                    <i class="fas fa-receipt"></i> Chi phí (<?= !empty($caseExpenses) && is_array($caseExpenses) ? count($caseExpenses) : 0 ?>)
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
                            <div class="progress-bar-fill" data-progress-percent="<?= $progressPercent ?>"></div>
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
                                <h3 class="section-header-title active-step-heading">
                                    <i class="fas fa-tasks"></i> 
                                    <span>Bước: <?= esc($active_step['step_name']) ?></span>
                                    <span class="badge-secondary-minimal text-xs m-l-5 active-step-duration">
                                        <i class="fas fa-clock m-r-4"></i> Quy định: <?= $active_step['duration_days'] ?> ngày
                                    </span>
                                    <?php if (($active_step['kpi_reward'] ?? 0) > 0) { ?>
                                        <div class="badge-reward-vibrant" title="Thưởng KPI">
                                            <i class="fas fa-gift"></i>
                                            +<?= number_format($active_step['kpi_reward'], 0, ',', '.') ?>đ
                                        </div>
                                    <?php } ?>
                                </h3>
                                <div class="text-right case-action-row">
                                    <?php
                                    $role = session()->get('role_name');
                                    $roleLower = strtolower($role ?? '');
                                    $isManager = has_permission('sys.admin')
                                        || has_permission('case.edit_all')
                                        || has_permission('case.approve')
                                        || in_array($roleLower, ['admin', 'trưởng phòng', 'truong_phong'], true);

                                    // Ưu tiên: Nếu User hiện tại là Người duyệt (Approver) của vụ việc này -> Cho phép Duyệt thẳng và Không bước qua khâu gửi duyệt
                                    $canApproveDirectly = ($isManager || $isApprover);
                                    ?>

                                    <?php if ($active_step['status'] === 'pending_approval') { ?>
                                        <?php if ($canApproveDirectly) { ?>
                                            <form action="<?= base_url('cases/approve-step/' . $active_step['id']) ?>"
                                                  method="POST"
                                                  data-confirm="Phê duyệt bước này?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-premium-sm btn-sm btn-approve-step">
                                                    <i class="fas fa-check"></i> Duyệt
                                                </button>
                                            </form>
                                            <form action="<?= base_url('cases/reject-step/' . $active_step['id']) ?>"
                                                  method="POST" data-confirm="Từ chối bước này?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-premium-sm btn-sm btn-reject-step">
                                                    <i class="fas fa-times"></i> Hủy
                                                </button>
                                            </form>
                                        <?php } else { ?>
                                            <?php if (!empty($is_approval_read)) { ?>
                                                <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>"
                                                      method="POST"
                                                      data-confirm="Gửi yêu cầu xét duyệt lại?">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn-premium-sm btn-sm">
                                                        <i class="fas fa-paper-plane"></i> Gửi lại
                                                    </button>
                                                </form>
                                            <?php } else { ?>
                                                <span class="badge-secondary-minimal text-apple-orange badge-awaiting-approval">
                                                <i class="fas fa-hourglass-half"></i> Chờ duyệt
                                            </span>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <form action="<?= base_url('cases/complete-step/' . $active_step['id']) ?>"
                                              method="POST"
                                              data-confirm="Xác nhận hoàn thành?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-premium-sm btn-sm">
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
                                            <div class="checklist-label"><?= esc($rd) ?></div>
                                            
                                            <div class="checklist-actions">
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
                                                                data-preview-url="<?= $docUrl . '?preview=1' ?>" data-preview-title="<?= esc($matchedDoc['file_name']) ?>" 
                                                                title="Xem nhanh ảnh">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <button class="btn-upload-checklist"
                                                            data-upload-step="<?= $active_step['id'] ?? 0 ?>" data-doc-name="<?= esc($rd) ?>">
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
                <div class="premium-card p-20 m-b-24 case-finance-card">
                    <div class="case-card-header-row m-b-20">
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
                            <div class="font-weight-500 text-apple-main case-payment-text">
                                <?php 
                                if (!empty($case['payment_progress'])) {
                                    $payments = json_decode($case['payment_progress'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($payments)) {
                                        echo '<ul class="case-payment-list">';
                                        foreach ($payments as $p) {
                                            $amountHtml = !empty($p['amount']) ? '<span class="text-apple-blue font-weight-600">' . number_format($p['amount'], 0, ',', '.') . 'đ</span>' : '';
                                            $deadlineHtml = !empty($p['deadline']) ? ' - Hạn: <span class="text-apple-red">' . date('d/m/Y', strtotime($p['deadline'])) . '</span>' : '';
                                            $paidIcon = (!empty($p['is_paid']) && $p['is_paid'] == 1) ? '<span class="badge-success-minimal text-xs m-l-8"><i class="fas fa-check-circle"></i> Đã thu</span>' : '<span class="badge-warning-minimal text-xs m-l-8"><i class="fas fa-clock"></i> Chờ thu</span>';
                                            $vatIcon = (!empty($p['is_vat']) && $p['is_vat'] == 1) ? '<span class="badge-success-minimal text-xs m-l-8 badge-vat-issued"><i class="fas fa-file-invoice-dollar"></i> Đã xuất VAT</span>' : '<span class="badge-warning-minimal text-xs m-l-8"><i class="fas fa-file-invoice"></i> Chưa xuất VAT</span>';
                                            $noteHtml = !empty($p['note']) ? ' <span class="text-xs text-muted-dark italic case-payment-note">(Ghi chú: ' . esc($p['note']) . ')</span>' : '';
                                            echo '<li>' . esc($p['title']) . ': ' . $amountHtml . $deadlineHtml . $paidIcon . $vatIcon . $noteHtml . '</li>';
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
                        <div class="description-box text-apple-main m-b-20 case-description-text">
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
                            <div class="info-item case-info-wide">
                                <label class="text-muted-dark text-xs font-weight-600 uppercase case-member-header">
                                    <span>Đội ngũ phụ trách</span>
                                    <?php if (in_array(session()->get('role_name'), ['Admin', 'Trưởng phòng'])) { ?>
                                        <a href="javascript:void(0)"
                                           data-modal-open="assignMembersModal"
                                           class="text-apple-blue font-weight-500 case-tags-edit-link"><i
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
                                            <div class="case-member-row">
                                                <span class="badge-secondary-minimal text-xs case-member-role">Người duyệt</span>
                                                <div class="case-member-list">
                                                    <?php foreach ($memberGroups['approver'] as $idx => $m) { ?>
                                                        <a href="<?= base_url('employees/edit/' . $m['employee_id']) ?>" class="link-premium text-sm font-weight-600">
                                                            <?= esc($m['full_name'] ?? 'N/A') ?><?= ($idx < count($memberGroups['approver']) - 1) ? ',' : '' ?>
                                                        </a>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($memberGroups['assignee']) && is_array($memberGroups['assignee'])) { ?>
                                            <div class="case-member-row">
                                                <span class="badge-info-minimal text-xs case-member-role">Chuyên môn</span>
                                                <div class="case-member-list">
                                                    <?php foreach ($memberGroups['assignee'] as $idx => $m) { ?>
                                                        <a href="<?= base_url('employees/edit/' . $m['employee_id']) ?>" class="link-premium text-sm font-weight-600">
                                                            <?= esc($m['full_name'] ?? 'N/A') ?><?= ($idx < count($memberGroups['assignee']) - 1) ? ',' : '' ?>
                                                        </a>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($memberGroups['supporter']) && is_array($memberGroups['supporter'])) { ?>
                                            <div class="case-member-row">
                                                <span class="text-muted-dark text-xs case-member-role case-member-support">Hỗ trợ</span>
                                                <div class="case-member-list font-weight-500">
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
                            <h3 class="section-header-title">Lộ trình & Định mức thưởng</h3>
                            
                            <?php if (has_permission('sys.admin') || has_permission('case.edit_all')) { ?>
                                <div class="case-roadmap-actions">
                                    <button type="button" data-modal-open="addStepModal" class="btn-secondary-sm flex-item-center">
                                        <i class="fas fa-plus text-apple-blue m-r-5"></i> Thêm bước
                                    </button>
                                    <a href="<?= base_url('cases/sync-rewards/' . $case['id']) ?>" 
                                       class="btn-secondary-sm flex-item-center" 
                                       data-confirm="Hệ thống sẽ cập nhật lại định mức thưởng mới nhất từ Quy trình gốc cho tất cả các bước của vụ việc này. Tiếp tục?"
                                       class="case-roadmap-action-sm">
                                        <i class="fas fa-sync-alt text-muted m-r-5"></i> Đồng bộ thưởng
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="roadmap-timeline m-t-20">
                            <?php foreach ($steps as $idx => $s) { 
                                $isCompleted = (($s['status'] ?? '') === 'completed');
                                $isActive = (($s['status'] ?? '') === 'active');
                                $isLate = $isCompleted && strtotime($s['completed_at']) > strtotime($s['deadline']);
                                $isKpiOverrideApproved = !empty($s['kpi_override_approved']);
                                $canApproveStepKpi = (has_permission('sys.admin') || has_permission('case.edit_all') || has_permission('case.approve') || !empty($isApprover));
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
                                                <span class="badge-secondary-minimal text-xxs m-l-5 roadmap-duration-badge">
                                                    <i class="fas fa-hourglass-start m-r-4"></i><?= $s['duration_days'] ?> ngày
                                                </span>
                                                <?php if ($isCompleted) { ?> <i class="fas fa-check-circle text-green m-l-5"></i> <?php } ?>
                                            </div>
                                            <div class="text-xs text-muted-dark m-t-4">
                                                <?php if ($isCompleted) { ?>
                                                    Hạn quy định: <span class="text-apple-main font-weight-600"><?= date('d/m/Y', strtotime($s['deadline'])) ?></span> 
                                                    - Thực tế: <span class="<?= $isLate ? 'text-apple-red' : 'text-apple-main' ?> font-weight-600"><?= date('d/m/Y H:i', strtotime($s['completed_at'])) ?></span>
                                                    <?php if ($isLate && $isKpiOverrideApproved) { ?>
                                                        <span class="badge-success-minimal text-xxs m-l-5" title="<?= esc($s['kpi_override_reason'] ?? 'Đã được quản lý ghi nhận KPI') ?>">
                                                            <i class="fas fa-check-double m-r-4"></i>KPI đã ghi nhận
                                                        </span>
                                                    <?php } elseif ($isLate) { ?>
                                                        <span class="badge-warning-minimal text-xxs m-l-5">
                                                            <i class="fas fa-exclamation-triangle m-r-4"></i>Chưa ghi nhận KPI
                                                        </span>
                                                    <?php } ?>
                                                <?php } else { 
                                                    $isOverdue = strtotime($s['deadline']) < time();
                                                ?>
                                                    Hạn xử lý: <span class="<?= $isOverdue ? 'text-apple-red' : 'text-apple-main' ?> font-weight-700"><?= date('d/m/Y', strtotime($s['deadline'])) ?></span>
                                                <?php } ?>
                                            </div>
                                            <?php 
                                            $sDocs = !empty($s['required_documents']) ? json_decode($s['required_documents'], true) : [];
                                            if (!empty($sDocs)) { ?>
                                                <div class="roadmap-docs-list m-t-8 roadmap-docs-list">
                                                    <?php foreach ($sDocs as $docName) { ?>
                                                        <span class="badge-secondary-minimal text-xxs roadmap-doc-badge">
                                                            <i class="fas fa-file-alt m-r-4"></i> <?= esc($docName) ?>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="roadmap-reward-row">
                                            <?php if (($s['kpi_reward'] ?? 0) > 0) { ?>
                                                <div class="<?= $isActive ? 'badge-reward-vibrant' : 'reward-label-mini' ?> roadmap-reward-badge">
                                                    <i class="fas fa-gift"></i> +<?= number_format($s['kpi_reward'], 0, ',', '.') ?> VNĐ
                                                </div>
                                            <?php } else { ?>
                                                <div class="text-xs text-muted-dark italic roadmap-zero-reward">0đ</div>
                                            <?php } ?>

                                            <?php if ($isCompleted && $isLate && !$isKpiOverrideApproved && ($s['kpi_reward'] ?? 0) > 0 && $canApproveStepKpi) { ?>
                                                <form action="<?= base_url('cases/approve-step-kpi/' . $s['id']) ?>"
                                                      method="POST"
                                                      class="flex-item-center gap-5"
                                                      data-confirm="Ghi nhận KPI cho bước hoàn thành trễ này?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="reason" value="Quản lý chấp thuận lý do giải trình hợp lệ.">
                                                    <button type="submit" class="btn-secondary-sm flex-item-center text-apple-blue" title="Ghi nhận KPI cho step này">
                                                        <i class="fas fa-award"></i>
                                                    </button>
                                                </form>
                                            <?php } elseif ($isCompleted && (!$isLate || $isKpiOverrideApproved) && ($s['kpi_reward'] ?? 0) > 0) { ?>
                                                <span class="badge-success-minimal text-xxs" title="KPI được tính cho nhân sự hoàn thành">
                                                    <i class="fas fa-check m-r-4"></i>KPI
                                                </span>
                                            <?php } ?>
                                            
                                            <?php if (has_permission('sys.admin') || has_permission('case.edit_all')) { ?>
                                                <a href="<?= base_url('cases/delete-step/' . $s['id']) ?>" 
                                                   data-confirm="Xác nhận xóa bỏ bước này khỏi quy trình? Lưu ý: Hành động này không thể hoàn tác."
                                                   class="btn-secondary-sm flex-item-center text-apple-red" title="Xóa bước này" 
                                                   class="btn-delete-step">
                                                    <i class="fas fa-trash-alt" ></i>
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
            <div id="tab-comments" class="tab-content">
                <div class="premium-card p-20 m-b-24">
                    <h3 class="section-header-title">Trao đổi (Chỉ nhân viên)</h3>

                        <div class="comment-feed m-b-20">
                            <?php if (empty($comments) || !is_array($comments)) { ?>
                                <div class="empty-state-container p-20 text-center text-muted-dark">Chưa có trao đổi nào về
                                    hồ sơ này.
                                </div>
                            <?php } else { ?>
                                <?php foreach ($comments as $c) { ?>
                                    <div class="timeline-item-premium" title="Phản hồi từ <?= esc($c['user_name'] ?? 'N/A') ?>">
                                        <div class="timeline-dot"></div>
                                        <div class="flex-column gap-5 m-b-10">
                                            <div class="case-log-row">
                                                <span class="font-weight-700 text-sm text-apple-main"><?= esc($c['user_name'] ?? 'Unknown User') ?></span>
                                                <span class="text-xs text-muted-dark"><?= isset($c['created_at']) ? date('H:i d/m/Y', strtotime($c['created_at'])) : '--' ?></span>
                                            </div>
                                        </div>
                                        <div class="text-sm text-apple-main case-comment-text">
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
                            <button type="submit" class="btn-premium-sm btn-sm" title="Gửi ghi chú cho đồng nghiệp">
                                <i class="fas fa-paper-plane"></i> Gửi ghi chú
                            </button>
                        </div>
                    </form>
                </div>
            </div>


            <!-- History Section -->
            <div id="tab-history" class="tab-content">
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
                                <div class="log-entry-item p-15" >
                                    <div class="log-header case-log-row">
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
            <div id="tab-documents" class="tab-content">
                <div class="premium-card p-20 m-b-24">
                    <div class="header-with-action m-b-20 case-log-row">
                        <h3 class="section-header-title p-0 m-0">Danh sách hồ sơ</h3>
                        <div class="case-doc-actions">
                            <button class="btn-secondary-sm"
                                    data-vault-open="true"
                                    title="Chọn tài liệu từ kho DMS chung">
                                <i class="fas fa-archive"></i> Kho tài liệu
                            </button>
                            <button class="btn-premium-sm"
                                    data-modal-open="uploadModal"
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
                                            <div class="case-doc-row-actions">
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
                                                            data-preview-url="<?= $docUrl . '?preview=1' ?>" data-preview-title="<?= esc($doc['file_name']) ?>" 
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

            <div id="tab-expenses" class="tab-content">
                <div class="premium-card p-20 m-b-24">
                    <div class="header-with-action m-b-20 case-log-row">
                        <h3 class="section-header-title p-0 m-0">Chi phí xử lý vụ việc</h3>
                        <?php if (has_permission('case_expense.submit')) { ?>
                            <a href="<?= base_url('case-expenses?case_id=' . $case['id']) ?>" class="btn-premium-sm">
                                <i class="fas fa-plus"></i> Nhập chi phí
                            </a>
                        <?php } ?>
                    </div>

                    <div class="case-expense-summary">
                        <div>
                            <span>Đề nghị</span>
                            <strong><?= number_format($caseExpenseStats['requested_total'] ?? 0, 0, ',', '.') ?>đ</strong>
                        </div>
                        <div>
                            <span>Đã duyệt</span>
                            <strong><?= number_format($caseExpenseStats['approved_total'] ?? 0, 0, ',', '.') ?>đ</strong>
                        </div>
                        <div>
                            <span>Chờ duyệt</span>
                            <strong><?= number_format($caseExpenseStats['pending_total'] ?? 0, 0, ',', '.') ?>đ</strong>
                        </div>
                        <div>
                            <span>Giờ duyệt</span>
                            <strong><?= number_format($caseExpenseStats['approved_hours'] ?? 0, 2, ',', '.') ?>h</strong>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="premium-table">
                            <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Nhân sự</th>
                                <th>Loại</th>
                                <th class="table-cell-center">Số tiền</th>
                                <th class="table-cell-center">Giờ</th>
                                <th>Ghi chú</th>
                                <th class="table-cell-center">Trạng thái</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($caseExpenses)) { ?>
                                <tr><td colspan="7" class="empty-state-container text-center text-muted-dark">Chưa có chi phí xử lý.</td></tr>
                            <?php } ?>
                            <?php foreach (($caseExpenses ?? []) as $expense) { ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($expense['expense_date'])) ?></td>
                                    <td><?= esc($expense['employee_name']) ?></td>
                                    <td><?= esc($caseExpenseCategoryLabels[$expense['category']] ?? $expense['category']) ?></td>
                                    <td class="table-cell-center"><?= number_format($expense['amount'], 0, ',', '.') ?>đ</td>
                                    <td class="table-cell-center"><?= number_format(abs((float)$expense['actual_hours']), 2, ',', '.') ?>h</td>
                                    <td>
                                        <?php if (!empty($expense['note'])) { ?>
                                            <div class="case-expense-note"><?= esc($expense['note']) ?></div>
                                        <?php } ?>
                                        <?php if (!empty($expense['approval_note'])) { ?>
                                            <div class="case-expense-note case-expense-approval-note"><?= esc($expense['approval_note']) ?></div>
                                        <?php } ?>
                                        <?php if (empty($expense['note']) && empty($expense['approval_note'])) { ?>
                                            <span class="text-muted-dark">-</span>
                                        <?php } ?>
                                    </td>
                                    <td class="table-cell-center">
                                        <span class="badge-secondary-minimal text-xs"><?= esc($caseExpenseStatusLabels[$expense['status']] ?? $expense['status']) ?></span>
                                    </td>
                                </tr>
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
                <div class="status-indicator-box text-center p-20 m-b-15 case-status-card">
                    <?php
                    $statusClasses = [
                            'cho_tiep_nhan' => 'badge-info-minimal',
                            'dang_xu_ly'    => 'badge-warning-minimal',
                            'tam_dung'      => 'badge-secondary-minimal',
                            'da_hoan_thanh' => 'badge-success-minimal',
                            'huy'           => 'badge-danger-minimal'
                    ];
                    $currentStatusClass = $statusClasses[$case['status']] ?? 'badge-secondary-minimal';
                    ?>
                    <div class="badge-log <?= $currentStatusClass ?> text-lg case-status-badge-large"
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
                <div class="case-tags-header">
                    <h3 class="sidebar-section-title m-0">Nhãn dán Vụ việc</h3>
                    <a href="javascript:void(0)" data-modal-open="tagModal" 
                       class="text-apple-blue font-weight-500 text-xs case-tags-edit-link">
                        <i class="fas fa-edit m-r-4"></i> Quản lý
                    </a>
                </div>
                
                <div class="tags-container-minimal flex-wrap">
                    <?php if (empty($tags)) { ?>
                        <div class="text-xs text-muted-dark italic">Chưa gắn nhãn dán nào.</div>
                    <?php } else { ?>
                        <?php foreach ($tags as $t) { ?>
                            <span class="tag-badge-premium" data-tag-color="<?= esc($t['color']) ?>">
                                <i class="fas fa-tag m-r-4"></i> <?= esc($t['name']) ?>
                            </span>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="premium-card p-20">
                <h3 class="sidebar-section-title">Hành động nhanh</h3>
                <div class="quick-actions-list flex-column gap-10">
                    <?php if ($canEditCase) { ?>
                        <button class="btn-secondary-sm w-100 case-sidebar-button"
                                data-modal-open="statusModal"
                                title="Cập nhật trạng thái tổng thể hồ sơ">
                            <i class="fas fa-sync-alt m-r-8"></i> Cập nhật trạng thái
                        </button>
                    <?php } ?>

                    <button class="btn-secondary-sm w-100 case-sidebar-button"
                            data-tab="comments" title="Viết trao đổi hoặc chỉ đạo nghiệp vụ">
                        <i class="fas fa-comment-dots m-r-8"></i> Thêm ghi chú nội bộ
                    </button>

                    <button class="btn-secondary-sm w-100 case-sidebar-button"
                            data-navigate-url="<?= base_url('knowledge/create?case_id=' . $case['id']) ?>" 
                            title="Bạn có kinh nghiệm gì về Case này không? Chia sẻ ngay">
                        <i class="fas fa-lightbulb m-r-8 text-warning"></i> Chia sẻ kinh nghiệm ngay
                    </button>

<!--                    --><?php //if (!empty($case['customer_phone'])) { ?>
<!--                    <a href="tel:--><?php //= esc($case['customer_phone']) ?><!--" class="btn-secondary-sm w-100 case-sidebar-button"-->
<!--                       title="Gọi điện trực tiếp cho khách hàng: --><?php //= esc($case['customer_phone']) ?><!--">-->
<!--                        <i class="fas fa-phone-alt m-r-8 text-apple-green"></i> Liên hệ: --><?php //= esc($case['customer_phone']) ?>
<!--                    </a>-->
<!--                    --><?php //} ?>

                    <button class="btn-secondary-sm w-100 text-apple-red case-sidebar-button"
                            data-modal-open="reminderModal"
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
<div id="reminderModal" class="modal-overlay case-modal">
    <div class="premium-card p-20 case-modal-card">
        <h3 class="section-header-title">Gửi nhắc nhở bộ phận</h3>
        <p class="text-xs text-muted-dark m-b-20">Thông báo này sẽ xuất hiện trên thanh Navbar của người nhận ngay lập tức.</p>
        
        <form action="<?= base_url('cases/send-reminder/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10 case-form-label">Gửi đến nhân sự</label>
                <select name="recipient_user_id" class="form-control-premium select2-single case-select-full" required >
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
                <label class="info-list-label m-b-10 case-form-label">Nội dung nhắc nhở</label>
                <textarea name="message" class="form-control-premium case-textarea-reminder" required
                          placeholder="Nhập nội dung cần chỉ đạo hoặc nhắc nhở..."
                          title="Nội dung sẽ hiển thị cho người nhận"></textarea>
            </div>
            
            <div class="form-actions-row m-t-20 ">
                <button type="submit" class="btn-premium-sm btn-danger-action m-b-10">
                    <i class="fas fa-paper-plane m-r-8"></i> Gửi thông báo
                </button>
                <button type="button" class="btn-secondary-sm"
                        data-modal-close="reminderModal">Hủy
                </button>

            </div>
        </form>
    </div>
</div>
<div id="statusModal" class="modal-overlay case-modal">
    <div class="premium-card p-20 case-modal-card case-modal-card-sm">
        <h3 class="section-header-title">Cập nhật trạng thái mới</h3>
        <form action="<?= base_url('cases/update-status/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10 case-form-label">Trạng thái tiếp theo</label>
                <select name="status" class="form-control-premium" required title="Chọn trạng thái mới cho hồ sơ">
                    <?php 
                    $labels = \Config\AppConstants::CASE_STATUS_LABELS;
                    foreach ($labels as $val => $lbl) { ?>
                        <option value="<?= $val ?>" <?= ($case['status'] ?? '') == $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php } ?>
                </select>
            </div>


            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-10 case-form-label">Ghi chú thay đổi</label>
                <textarea name="note" class="form-control-premium case-textarea-status"
                          placeholder="Lý do cập nhật hoặc bước công việc tiếp theo..."
                          title="Ghi chú giải thích cho việc thay đổi trạng thái"></textarea>
            </div>
            <div class="form-actions-row m-t-20">

                <button type="submit" class="btn-premium-sm m-b-10" title="Xác nhận cập nhật">Cập nhật ngay</button>
                <button type="button" class="btn-secondary-sm"
                        data-modal-close="statusModal">Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Members Modal -->
<div id="assignMembersModal" class="modal-overlay case-modal">
    <div class="premium-card p-20 case-modal-card">
        <h3 class="section-header-title">Phân công nhân sự tham gia</h3>
        <form action="<?= base_url('cases/update-members/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5 case-form-label">1. Người phê duyệt (Cấp Quản lý)</label>
                <select name="approvers[]" class="form-control-premium select2-multi case-select-full" multiple="multiple"
                        >
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
                <label class="info-list-label m-b-5 case-form-label">2. Phụ trách chính</label>
                <select name="assignees[]" class="form-control-premium select2-multi case-select-full" multiple="multiple"
                        >
                    <?php foreach ($staffs as $s) { ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], array_column($memberGroups['assignee'], 'employee_id')) ? 'selected' : '' ?>><?= esc($s['full_name']) ?>
                            (<?= esc($s['position']) ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5 case-form-label">3. Nhân sự hỗ trợ</label>
                <select name="supporters[]" class="form-control-premium select2-multi case-select-full" multiple="multiple"
                        >
                    <?php foreach ($staffs as $s) { ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], array_column($memberGroups['supporter'], 'employee_id')) ? 'selected' : '' ?>><?= esc($s['full_name']) ?>
                            (<?= esc($s['position']) ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-actions-row m-t-20">
                <button type="button" class="btn-secondary-sm"
                        data-modal-close="assignMembersModal">Hủy
                </button>
                <button type="submit" class="btn-premium-sm"><i class="fas fa-save m-r-8"></i> Lưu danh sách</button>
            </div>
        </form>
    </div>
</div>

<div id="uploadModal" class="modal-overlay case-modal">
    <div class="premium-card case-modal-card case-modal-card-sm">
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
            <div class="form-actions-row case-upload-actions">
                <button type="button" class="btn-secondary-sm"
                        data-modal-close="uploadModal">Hủy
                </button>
                <button type="submit" class="btn-premium-sm">Tải lên ngay</button>
            </div>
        </form>
    </div>
</div>

<!-- Tag Management Modal -->
<div id="tagModal" class="modal-overlay case-modal modal-lg">
    <div class="premium-card p-20 case-modal-card case-modal-card-sm">
        <h3 class="section-header-title">Quản lý nhãn dán hồ sơ</h3>
        <p class="text-xs text-muted-dark m-b-20">Gắn các thẻ phân loại để dễ dàng theo dõi và tìm lọc vụ việc.</p>
        
        <form action="<?= base_url('cases/update-tags/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <div class="tags-selector-list gap-15">
                    <?php if (empty($availableTags)) { ?>
                        <div class="text-xs text-muted-dark italic">Chưa có nhãn dán nào trong hệ thống.</div>
                    <?php } else { ?>
                        <?php 
                            $currentTagIds = array_column($tags, 'id');
                            foreach ($availableTags as $at) { 
                        ?>
                            <label class="custom-checkbox-premium flex-item-center case-checkbox-label">
                                <input type="checkbox" name="tag_ids[]" value="<?= $at['id'] ?>" 
                                       <?= in_array($at['id'], $currentTagIds) ? 'checked' : '' ?>
                                       class="case-checkbox-input">
                                <div class="flex-column">
                                    <div class="flex-item-center gap-10">
                                        <span class="tag-badge-premium" data-tag-color="<?= esc($at['color']) ?>">
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
            
            <div class="form-actions-row m-t-25 text-center case-form-actions-end">
                <button type="button" class="btn-secondary-sm"
                        data-modal-close="tagModal">Hủy
                </button>
                <button type="submit" class="btn-premium-sm">
                    <i class="fas fa-save m-r-8"></i> Lưu
                </button>
            </div>
        </form>

        <?php 
            $isPowerUser = (has_permission('sys.admin') || strpos(strtolower(session()->get('role_name')), 'trưởng phòng') !== false);
            if ($isPowerUser) { 
        ?>
            <hr class="m-y-20 case-modal-separator">
            <div class="quick-create-tag-section">
                <h4 class="text-xs font-weight-700 uppercase m-b-15 case-quick-tag-title">Tạo nhãn dán</h4>
                <form action="<?= base_url('cases/create-tag') ?>" method="POST" class="flex-column gap-15">
                    <?= csrf_field() ?>
                    <div class="case-quick-tag-grid">
                        <input type="text" name="name" class="form-control-premium" placeholder="Tên nhãn dán..." required >
                        <input type="color" name="color" value="#007aff" class="form-control-premium case-color-input">
                    </div>
                    <div class="case-quick-tag-grid">
                        <select name="type" class="form-control-premium text-xs case-select-compact">
                            <option value="global">Dùng chung (Toàn công ty)</option>
                            <option value="private">Dùng riêng (Cá nhân)</option>
                        </select>
                        <input type="hidden" name="module_scope" value="cases">
                        <input type="hidden" name="ref_case_id" value="<?= $case['id'] ?>">
                        <button type="submit" class="btn-secondary-sm case-btn-xs">
                            <i class="fas fa-plus m-r-5"></i> Tạo mới
                        </button>
                    </div>
                </form>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Add Step Modal -->
<div id="addStepModal" class="modal-overlay case-modal modal-lg">
    <div class="premium-card p-20 case-modal-card">
        <h3 class="section-header-title">Thêm bước phát sinh</h3>
        <p class="text-xs text-muted-dark m-b-20">Bước mới sẽ được thêm vào cuối quy trình (hoặc tự động sắp xếp theo thời hạn).</p>
        
        <form action="<?= base_url('cases/add-step/' . $case['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5 case-form-label">Tên bước công việc <span class="text-apple-red">*</span></label>
                <input type="text" name="step_name" class="form-control-premium" required placeholder="Ví dụ: Bổ sung hồ sơ tài chính phụ">
            </div>

            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5 case-form-label">Tài liệu bắt buộc (Cách nhau bằng dấu phẩy)</label>
                <input type="text" name="required_documents_raw" class="form-control-premium" placeholder="Ví dụ: CCCD, Hợp đồng lao động, Giấy xác nhận...">
            </div>

            <div class="form-group-premium m-b-15">
                <label class="info-list-label m-b-5 case-form-label">Vị trí chèn <span class="text-apple-red">*</span></label>
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
            
            
            <div class="case-two-col-grid">
                <div class="form-group-premium m-b-15">
                    <label class="info-list-label m-b-5 case-form-label">Thời gian dự kiến (Ngày)</label>
                    <input type="number" name="duration_days" class="form-control-premium" min="1" value="3" required>
                </div>
                <div class="form-group-premium m-b-15">
                    <label class="info-list-label m-b-5 case-form-label">Thưởng KPI (VNĐ)</label>
                    <input type="text" name="kpi_reward" class="form-control-premium" value="0" data-money-format="true">
                </div>
            </div>
            
            <div class="form-actions-row m-t-20 text-center case-form-actions-end">
                <button type="button" class="btn-secondary-sm" data-modal-close="addStepModal">Hủy</button>
                <button type="submit" class="btn-premium-sm"><i class="fas fa-save m-r-8"></i> Thêm bước</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Nhập từ kho DMS -->
<div id="vaultModal" class="modal-overlay case-modal modal-lg">
    <div class="premium-card case-modal-card case-modal-card-lg">
        <div class="modal-header-premium case-modal-header m-b-20">
            <h3 class="section-title-premium case-modal-title">Kho tài liệu hệ thống (Vault)</h3>
            <button type="button" class="btn-close-minimal" data-modal-close="vaultModal"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="m-b-15">
            <input type="text" id="vaultSearch" placeholder="Tìm kiếm tài liệu trong kho..." class="form-control-premium">
        </div>

        <div id="vaultListContainer" class="vault-list-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th class="vault-radio-col"></th>
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

        <div class="form-actions-row m-t-20 case-form-actions-end">
            <button type="button" class="btn-secondary-sm" data-modal-close="vaultModal">Đóng</button>
            <button type="button" id="btnConfirmImport" class="btn-premium-sm" disabled >Nhập tài liệu đã chọn</button>
        </div>
    </div>
</div>

<!-- Image Preview Modal (Apple-Style) -->
<div id="imagePreviewModal" class="modal-overlay case-modal image-preview-modal">
    <div class="image-preview-content">
        <button data-modal-close="imagePreviewModal" 
                class="image-preview-close">
            <i class="fas fa-times"></i>
        </button>
        <div class="image-preview-frame">
            <img id="previewImgElement" src="" class="image-preview-img">
            <div id="previewImgTitle" class="image-preview-title"></div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/case_show.js') ?>"></script>
<?= $this->endSection() ?>
