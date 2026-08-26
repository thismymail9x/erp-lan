<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customers.css') ?>">
<?= $this->endSection() ?>
<?php $tagService = new \App\Services\TagService(); ?>

<?= $this->section('content') ?>
<div class="customers-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Kh&#225;ch h&#224;ng</h2>
            <p class="content-subtitle hide-mobile">CRM</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('customers/create') ?>" class="btn-premium" title="Th&#234;m kh&#225;ch h&#224;ng">
                <i class="fas fa-plus-circle"></i> Th&#234;m
            </a>
        </div>
    </div>

    <div class="stats-grid-premium">
        <div class="stat-card-premium" title="T&#7893;ng s&#7889; kh&#225;ch h&#224;ng &#273;&#227; &#273;&#259;ng k&#253;">
            <div class="stat-icon-wrapper stat-icon-blue"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-label">T&#7893;ng</div>
                <div class="stat-value"><?= $stats['total_customers'] ?></div>
            </div>
        </div>
        <div class="stat-card-premium" title="Kh&#225;ch m&#7899;i th&#225;ng n&#224;y">
            <div class="stat-icon-wrapper stat-icon-green"><i class="fas fa-user-plus"></i></div>
            <div>
                <div class="stat-label">M&#7899;i</div>
                <div class="stat-value"><?= $stats['new_this_month'] ?></div>
            </div>
        </div>
        <div class="stat-card-premium" title="Doanh nghi&#7879;p">
            <div class="stat-icon-wrapper stat-icon-orange"><i class="fas fa-building"></i></div>
            <div>
                <div class="stat-label">Cty</div>
                <div class="stat-value"><?= $stats['total_corporate'] ?? 0 ?></div>
            </div>
        </div>
        <div class="stat-card-premium" title="Kh&#225;ch h&#224;ng VIP">
            <div class="stat-icon-wrapper stat-icon-purple"><i class="fas fa-crown"></i></div>
            <div>
                <div class="stat-label">VIP</div>
                <div class="stat-value"><?= $stats['total_vip'] ?? 0 ?></div>
            </div>
        </div>
    </div>

    <form id="customer-filter-form" action="<?= base_url('customers') ?>" method="get" class="search-filter-bar filter-bar">
        <input type="hidden" name="sort" id="filter-sort" value="<?= esc(service('request')->getGet('sort') ?: 'created_at') ?>">
        <input type="hidden" name="order" id="filter-order" value="<?= esc(service('request')->getGet('order') ?: 'desc') ?>">

        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="T&#236;m t&#234;n, S&#272;T, CCCD, MST..." value="<?= esc(service('request')->getGet('q')) ?>" class="ajax-filter-search">
        </div>

        <select name="care_staff_id" class="filter-select ajax-filter select2-basic">
            <option value="">T&#7845;t c&#7843; nh&#226;n s&#7921; t&#432; v&#7845;n</option>
            <?php foreach ($employees as $emp) { ?>
                <option value="<?= $emp['id'] ?>" <?= service('request')->getGet('care_staff_id') == $emp['id'] ? 'selected' : '' ?>>
                    <?= esc($emp['full_name']) ?>
                </option>
            <?php } ?>
        </select>

        <select name="care_status" class="filter-select ajax-filter">
            <option value="">T&#7845;t c&#7843; tr&#7841;ng th&#225;i t&#432; v&#7845;n</option>
            <?php foreach ($slaSettings as $s) { ?>
                <option value="<?= esc($s['status_key']) ?>" <?= service('request')->getGet('care_status') === $s['status_key'] ? 'selected' : '' ?>>
                    <?= esc($s['status_name']) ?>
                </option>
            <?php } ?>
        </select>

        <select name="monitoring_status" class="filter-select ajax-filter select2-basic">
            <option value="">T&#7845;t c&#7843; tr&#7841;ng th&#225;i gi&#225;m s&#225;t</option>
            <?php foreach ($monitoringSettings as $s) { ?>
                <option value="<?= esc($s['status_key']) ?>" <?= service('request')->getGet('monitoring_status') === $s['status_key'] ? 'selected' : '' ?>>
                    <?= esc($s['status_name']) ?>
                </option>
            <?php } ?>
        </select>

        <select name="type" class="filter-select ajax-filter">
            <option value="">T&#7845;t c&#7843; lo&#7841;i kh&#225;ch</option>
            <option value="ca_nhan" <?= service('request')->getGet('type') == 'ca_nhan' ? 'selected' : '' ?>>C&#225; nh&#226;n/H&#7897;</option>
            <option value="doanh_nghiep" <?= service('request')->getGet('type') == 'doanh_nghiep' ? 'selected' : '' ?>>Doanh nghi&#7879;p</option>
        </select>

        <select name="tag_id" class="filter-select ajax-filter">
            <option value="">T&#7845;t c&#7843; nh&#227;n (Tags)</option>
            <?php foreach ($availableTags as $tag) { ?>
                <option value="<?= $tag['id'] ?>" <?= service('request')->getGet('tag_id') == $tag['id'] ? 'selected' : '' ?>>
                    <?= esc($tag['name']) ?>
                </option>
            <?php } ?>
        </select>

        <select name="month" class="filter-select ajax-filter">
            <option value="">T&#7845;t c&#7843; th&#225;ng</option>
            <?php for ($m = 1; $m <= 12; $m++) { ?>
                <option value="<?= $m ?>" <?= service('request')->getGet('month') == $m ? 'selected' : '' ?>>Th&#225;ng <?= $m ?></option>
            <?php } ?>
        </select>

        <select name="year" class="filter-select ajax-filter">
            <option value="">T&#7845;t c&#7843; n&#259;m</option>
            <?php
                $currentYear = intval(date('Y'));
                for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
            ?>
                <option value="<?= $y ?>" <?= service('request')->getGet('year') == $y ? 'selected' : '' ?>>N&#259;m <?= $y ?></option>
            <?php } ?>
        </select>

        <?php if (service('request')->getUri()->getQuery() !== '') { ?>
            <a href="<?= base_url('customers') ?>" class="btn-filter-secondary">X&#243;a l&#7885;c</a>
        <?php } ?>
    </form>

    <div class="premium-card premium-card-full" id="customer-table-container">
        <?= view('dashboard/customers/index_table') ?>
    </div>
</div>

<div id="quickTagModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">
    <div class="premium-card p-24" style="width:400px;">
        <div class="flex-row justify-between align-center m-b-20">
            <h3 class="section-header-title">G&#7855;n nh&#227;n nhanh</h3>
            <span class="close-btn-minimal" onclick="document.getElementById('quickTagModal').style.display='none'">&times;</span>
        </div>
        <p class="text-sm m-b-15">Kh&#225;ch h&#224;ng: <strong id="quickTagName">--</strong></p>
        <form id="quickTagForm" class="flex-column gap-15">
            <input type="hidden" name="entity_id" id="quickTagEntityId">
            <input type="hidden" name="entity_type" value="customers">
            <div class="form-group-premium">
                <label class="label-premium">L&#7921;a ch&#7885;n nh&#227;n d&#225;n</label>
                <select name="tag_ids[]" id="quickTagSelect" class="form-control-premium" multiple="multiple" style="width: 100%;">
                    <?php if (isset($availableTags)) {
                        foreach ($availableTags as $tag) { ?>
                            <option value="<?= $tag['id'] ?>"><?= esc($tag['name']) ?></option>
                        <?php }
                    } ?>
                </select>
            </div>
            <div class="form-actions-row m-t-15" style="justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('quickTagModal').style.display='none'">H&#7911;y</button>
                <button type="submit" class="btn-premium">C&#7853;p nh&#7853;t ngay</button>
            </div>
        </form>
    </div>
</div>

<?php
$roleName = session()->get('role_name');
$canSendZnsQuick = has_permission('sys.admin') || $roleName === \Config\AppConstants::ROLE_ADMIN || $roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || has_permission('zalo.send_individual');
?>
<?php if ($canSendZnsQuick) { ?>
<div id="bulkZnsModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1150; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="premium-card p-24" style="width:550px; background:#fff; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,0.1); padding:24px; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f1f5f9; padding-bottom:12px; margin-bottom:18px;">
            <h3 class="section-header-title" style="font-size:16px; font-weight:700; color:#0f172a; margin:0;"><i class="fas fa-paper-plane" style="color:#0068ff; margin-right:8px;"></i>G&#7917;i Zalo ZNS h&#224;ng lo&#7841;t</h3>
            <span class="close-btn-minimal" onclick="closeBulkZnsModal()" style="font-size:20px; color:#94a3b8; cursor:pointer;">&times;</span>
        </div>

        <p class="text-sm" style="color:#475569; margin-bottom:15px;">&#272;ang ch&#7885;n: <strong id="bulkZnsSelectedCount" style="color:#0068ff;">0</strong> kh&#225;ch h&#224;ng.</p>

        <form id="bulkZnsForm" class="flex-column gap-15">
            <div class="form-group-premium" style="margin-bottom:16px;">
                <label class="label-premium" style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">L&#7921;a ch&#7885;n m&#7851;u tin ZNS</label>
                <select name="template_id" id="bulkZnsTemplateSelect" class="form-control-premium" style="width: 100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1; outline:none;" required>
                    <option value="">-- Ch&#7885;n m&#7851;u tin nh&#7855;n ZNS --</option>
                    <?php if (isset($znsTemplates)) {
                        foreach ($znsTemplates as $tpl) {
                            $paramsStr = '[]';
                            if (isset($tpl['template_params'])) {
                                if (is_array($tpl['template_params']) || is_object($tpl['template_params'])) {
                                    $paramsStr = json_encode($tpl['template_params'], JSON_UNESCAPED_UNICODE);
                                } elseif (is_string($tpl['template_params'])) {
                                    $paramsStr = $tpl['template_params'];
                                }
                            }
                            if (empty($paramsStr) || !is_string($paramsStr)) {
                                $paramsStr = '[]';
                            }

                            $mappingsStr = '{}';
                            if (isset($tpl['default_mappings'])) {
                                if (is_array($tpl['default_mappings']) || is_object($tpl['default_mappings'])) {
                                    $mappingsStr = json_encode($tpl['default_mappings'], JSON_UNESCAPED_UNICODE);
                                } elseif (is_string($tpl['default_mappings'])) {
                                    $mappingsStr = $tpl['default_mappings'];
                                }
                            }
                            if (empty($mappingsStr) || !is_string($mappingsStr)) {
                                $mappingsStr = '{}';
                            }
                            ?>
                            <option value="<?= $tpl['id'] ?>" data-params='<?= esc($paramsStr) ?>' data-mappings='<?= esc($mappingsStr) ?>'><?= esc($tpl['template_name']) ?></option>
                        <?php }
                    } ?>
                </select>
            </div>

            <div id="bulk-zns-mapping-container" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px;">
                <h4 style="font-size:13px; font-weight:700; color:#1e293b; margin:0 0 10px 0;">&#193;nh x&#7841; bi&#7871;n d&#7919; li&#7879;u (Data Mapping)</h4>
                <div id="bulk-zns-mapping-rows"></div>
            </div>

            <div class="form-actions-row" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="closeBulkZnsModal()" style="padding:10px 16px; border-radius:8px; border:none; background:#f1f5f9; color:#475569; font-weight:600; cursor:pointer;">H&#7911;y</button>
                <button type="submit" class="btn-premium" id="btn-submit-bulk-zns" style="padding:10px 20px; border-radius:8px; border:none; background:#0068ff; color:#fff; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-paper-plane"></i> G&#7917;i ngay
                </button>
            </div>
        </form>
    </div>
</div>
<?php } ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customers_index.js') ?>"></script>
<?= $this->endSection() ?>
