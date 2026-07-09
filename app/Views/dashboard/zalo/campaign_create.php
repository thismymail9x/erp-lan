<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="zalo-page-container zns-campaign-create-page zns-page-container-narrow" data-save-url="<?= base_url('zalo/campaigns/save') ?>" data-customer-fields='<?= esc(json_encode($customerFields, JSON_UNESCAPED_UNICODE), 'attr') ?>'>
    
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title zns-page-title-plain">Tạo Chiến dịch ZNS mới</h2>
            <p class="content-subtitle zns-page-help">Thiết lập bộ lọc, nội dung và các biến số để gửi hàng loạt tin nhắn ZNS</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('zalo/campaigns') ?>" class="btn-filter-secondary zns-back-action">
                <i class="fas fa-arrow-left"></i> Trở về
            </a>
        </div>
    </div>

    <form id="create-campaign-form">
        
        <!-- Phần 1: Thông tin chung -->
        <div class="form-section-card">
            <h3 class="form-section-title">
                <i class="fas fa-info-circle zns-icon-blue"></i> 1. Thông tin chung về chiến dịch
            </h3>
            <div class="form-group-custom">
                <label class="form-label-custom" for="campaign-name">Tên chiến dịch</label>
                <input type="text" class="form-control-custom" id="campaign-name" name="name" placeholder="Ví dụ: Chúc mừng năm mới, Nhắc gia hạn hợp đồng Đất đai..." required>
            </div>
            <div class="form-group-custom">
                <label class="form-label-custom" for="campaign-description">Mô tả chiến dịch (nếu có)</label>
                <textarea class="form-control-custom" id="campaign-description" name="description" rows="3" placeholder="Mô tả mục tiêu chiến dịch gửi ZNS..."></textarea>
            </div>
        </div>

        <!-- Phần 2: Chọn Mẫu tin và Ánh xạ biến -->
        <div class="form-section-card">
            <h3 class="form-section-title">
                <i class="fas fa-file-code zns-icon-green"></i> 2. Chọn Mẫu tin & Khớp biến (Data Mapping)
            </h3>
            <div class="form-group-custom zns-form-group-loose">
                <label class="form-label-custom" for="select-zns-template">Mẫu tin ZNS đã đồng bộ</label>
                <select class="form-control-custom no-select2" id="select-zns-template" name="zns_template_id" required>
                    <option value="">-- Chọn mẫu tin nhắn ZNS --</option>
                    <?php foreach ($templates as $tpl): 
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
                        <option value="<?= $tpl['id'] ?>" data-params='<?= esc($paramsStr, 'attr') ?>' data-mappings='<?= esc($mappingsStr, 'attr') ?>'>
                            <?= esc($tpl['template_name']) ?> (<?= esc($tpl['template_id']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="zns-field-note">Nếu không thấy mẫu tin mong muốn, vui lòng truy cập <a href="<?= base_url('zalo/zns-templates') ?>" class="zns-field-note-link">Quản lý mẫu tin ZNS</a> để đồng bộ trước.</small>
            </div>

            <!-- Khu vực mapping biến sinh động -->
            <div id="mapping-container" class="zns-mapping-container zns-hidden">
                <h4 class="zns-mapping-title">Ánh xạ các trường dữ liệu</h4>
                
                <div id="mapping-rows">
                    <!-- Sẽ được điền động bằng Javascript -->
                </div>
                
                <div class="zns-tip-box">
                    <i class="fas fa-lightbulb zns-icon-info"></i>
                    <strong>Mẹo ánh xạ:</strong> Bạn có thể chọn map với cột dữ liệu của khách hàng hoặc nhập <strong>Giá trị tĩnh</strong> bằng cách nhập ký tự <strong>#</strong> ở đầu (Ví dụ: <code>#Công ty L.A.N</code> hoặc <code>#30/05/2026</code>).
                </div>
            </div>
        </div>

        <!-- Phần 3: Lọc Người nhận -->
        <div class="form-section-card">
            <h3 class="form-section-title">
                <i class="fas fa-users zns-icon-orange"></i> 3. Đối tượng khách hàng mục tiêu
            </h3>
            
            <div class="form-group-grid">
                <div class="form-group-custom">
                    <label class="form-label-custom" for="filter-tag">Lọc theo Nhãn (Tag)</label>
                    <select class="form-control-custom no-select2" id="filter-tag" name="filter_tag_id">
                        <option value="">-- Tất cả các nhãn --</option>
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?= $tag['id'] ?>">#<?= esc($tag['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom" for="filter-segment">Lọc theo Phân khúc</label>
                    <select class="form-control-custom no-select2" id="filter-segment" name="filter_customer_segment">
                        <option value="">-- Tất cả phân khúc --</option>
                        <option value="standard">Khách hàng phổ thông (Standard)</option>
                        <option value="silver">Khách hàng Bạc (Silver)</option>
                        <option value="gold">Khách hàng Vàng (Gold)</option>
                        <option value="vip">Khách hàng VIP</option>
                    </select>
                </div>
            </div>

            <div class="form-group-grid zns-grid-top-gap">
                <div class="form-group-custom">
                    <label class="form-label-custom" for="filter-care-status">Trạng thái tư vấn (SLA)</label>
                    <select class="form-control-custom no-select2" id="filter-care-status" name="filter_care_status">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="chua_tu_van">Chưa được tư vấn</option>
                        <option value="dang_tu_van">Đang tư vấn</option>
                        <option value="doi_ho_so">Đợi khách gửi hồ sơ</option>
                        <option value="nghien_cuu_bao_phi">Đang nghiên cứu báo phí</option>
                        <option value="thuong_luong">Đang thương lượng</option>
                        <option value="chot_hop_dong">Đã chốt hợp đồng</option>
                        <option value="tam_dung">Tạm dừng chăm sóc</option>
                        <option value="khong_tiem_nang">Không tiềm năng / Hủy</option>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom" for="filter-staff">Nhân sự phụ trách</label>
                    <select class="form-control-custom no-select2" id="filter-staff" name="filter_care_staff_id">
                        <option value="">-- Tất cả nhân sự --</option>
                        <?php foreach ($staffs as $s): ?>
                            <option value="<?= $s['user_id'] ?>"><?= esc($s['full_name'] ?: $s['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Buttons hành động -->
        <div class="zns-form-actions">
            <a href="<?= base_url('zalo/campaigns') ?>" class="btn-filter-secondary zns-back-action">Hủy bỏ</a>
            <button type="submit" class="btn-premium zns-primary-action" id="btn-save-campaign">
                <i class="fas fa-save"></i> Lưu Chiến dịch (Nháp)
            </button>
        </div>

    </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/zalo_campaign_create.js') ?>"></script>
<?= $this->endSection() ?>

