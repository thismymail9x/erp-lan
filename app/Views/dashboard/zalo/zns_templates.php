<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="zalo-page-container zns-templates-page" data-sync-url="<?= base_url('zalo/zns-templates/sync') ?>" data-save-mappings-url="<?= base_url('zalo/zns-templates/save-mappings') ?>" data-delete-url="<?= base_url('zalo/zns-templates/delete/') ?>">
    
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title zns-page-title-plain">Quản lý Mẫu tin ZNS</h2>
            <p class="content-subtitle zns-page-help">Đồng bộ và khai báo các mẫu thông báo ZNS được Zalo phê duyệt vào ERP để sử dụng</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('zalo/campaigns') ?>" class="btn-filter-secondary zns-back-action">
                <i class="fas fa-arrow-left"></i> Danh sách chiến dịch
            </a>
            <button class="btn-premium zns-primary-action" id="btn-open-sync-modal">
                <i class="fas fa-sync-alt"></i> Đồng bộ mẫu tin Zalo
            </button>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="premium-card premium-card-full zns-template-card">
        <div class="table-responsive">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Tên mẫu tin hiển thị</th>
                        <th>ID mẫu tin Zalo</th>
                        <th>Biến dữ liệu (Parameters)</th>
                        <th>Trạng thái</th>
                        <th>Ngày cập nhật</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="6" class="zns-empty-cell">
                                <i class="fas fa-file-invoice"></i>
                                Chưa có mẫu tin ZNS nào được đồng bộ. Bấm nút <strong>Đồng bộ mẫu tin Zalo</strong> để bắt đầu.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($templates as $tpl): ?>
                              <?php 
                                 $params = [];
                                 if (isset($tpl['template_params'])) {
                                     if (is_array($tpl['template_params'])) {
                                         $params = $tpl['template_params'];
                                     } elseif (is_object($tpl['template_params'])) {
                                         $params = (array)$tpl['template_params'];
                                     } elseif (is_string($tpl['template_params'])) {
                                         $params = json_decode($tpl['template_params'], true) ?: [];
                                     }
                                 }
                              ?>
                            <tr>
                                <td>
                                    <div class="zns-template-name-text truncate-line" title="<?= esc($tpl['template_name']) ?>"><?= esc($tpl['template_name']) ?></div>
                                    <?php if (!empty($tpl['template_content'])): ?>
                                        <div class="zns-template-preview truncate-line" title="<?= esc($tpl['template_content']) ?>">
                                            Preview: <?= esc($tpl['template_content']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= esc($tpl['template_id']) ?>
                                </td>
                                <td>
                                    <?php if (empty($params)): ?>
                                        <span class="zns-muted-italic">Không có biến</span>
                                    <?php else: ?>
                                        <?php foreach ($params as $p): ?>
                                            <span class="param-badge"><?= esc($p) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="zns-active-badge">
                                        Hoạt động
                                    </span>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($tpl['updated_at'])) ?>
                                </td>
                                <td>
                                     <?php
                                     // Chuyển đổi template_params sang JSON string an toàn
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

                                     // Chuyển đổi default_mappings sang JSON string an toàn
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
                                    <button type="button" class="btn-filter-secondary btn-open-mapping-modal zns-secondary-action zns-action-compact" 
                                            data-id="<?= $tpl['id'] ?>" 
                                            data-name="<?= esc($tpl['template_name']) ?>" 
                                            data-params='<?= esc($paramsStr, 'attr') ?>'
                                            data-mappings='<?= esc($mappingsStr, 'attr') ?>'
                                           >
                                        <i class="fas fa-sliders-h"></i> Ánh xạ trường
                                    </button>
                                    <button type="button" class="btn-delete-template zns-danger-action zns-action-compact zns-action-spaced" 
                                            data-id="<?= $tpl['id'] ?>" 
                                            data-name="<?= esc($tpl['template_name']) ?>"
                                           >
                                        <i class="fas fa-trash-alt"></i> Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sync Template Modal -->
<div class="zns-modal" id="sync-modal">
    <div class="zns-modal-card">
        <div class="zns-modal-header">
            <h3 class="zns-modal-title">Đồng bộ Mẫu tin từ Zalo OA</h3>
            <button class="zns-modal-close" id="btn-close-sync-modal">&times;</button>
        </div>
        <div class="zns-modal-body">
            <form id="sync-template-form">
                <div class="form-group-custom">
                    <label for="input-template-id">ID Mẫu tin ZNS (Zalo Template ID)</label>
                    <input type="text" class="form-input-custom" id="input-template-id" placeholder="Ví dụ: 348231..." required>
                    <small class="zns-field-note">Lấy ID này từ trang quản lý Zalo Cloud Account (ZCA) của bạn</small>
                </div>
                <div class="form-group-custom">
                    <label for="input-template-name">Tên hiển thị nội bộ trong ERP</label>
                    <input type="text" class="form-input-custom" id="input-template-name" placeholder="Ví dụ: Thông báo phí dịch vụ, Chúc mừng sinh nhật..." required>
                </div>
                <div class="zns-modal-actions">
                    <button type="button" class="btn-filter-secondary zns-secondary-action" id="btn-cancel-sync">Hủy</button>
                    <button type="submit" class="btn-premium zns-primary-action" id="btn-submit-sync">
                        <i class="fas fa-cloud-download-alt"></i> Đồng bộ ngay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cấu hình Ánh xạ mặc định (Admin Setup Mappings) -->
<div class="zns-modal" id="mapping-modal">
    <div class="zns-modal-card zns-modal-card-wide">
        <div class="zns-modal-header">
            <h3 class="zns-modal-title">Cấu hình Ánh xạ mặc định</h3>
            <button class="zns-modal-close" id="btn-close-mapping-modal">&times;</button>
        </div>
        <div class="zns-modal-body">
            <div class="zns-modal-tip">
                <i class="fas fa-info-circle zns-icon-info"></i>
                Thiết lập cấu hình ánh xạ mặc định cho mẫu tin nhắn: <strong id="mapping-template-name" class="zns-strong-dark"></strong>.<br>
                Hệ thống sẽ **tự động bốc trường này và điền sẵn** khi nhân viên thực hiện gửi ZNS, hạn chế sai sót.
            </div>
            
            <form id="save-mappings-form">
                <input type="hidden" id="mapping-template-id" name="id">
                
                <div id="mapping-fields-container" class="zns-mapping-fields-container">
                    <!-- Sẽ sinh tự động các trường qua JS -->
                </div>
                
                <div class="zns-modal-actions">
                    <button type="button" class="btn-filter-secondary zns-secondary-action" id="btn-cancel-mapping">Hủy</button>
                    <button type="submit" class="btn-premium zns-primary-action" id="btn-submit-mapping">
                        <i class="fas fa-save"></i> Lưu cấu hình
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/zalo_zns_templates.js') ?>"></script>
<?= $this->endSection() ?>

