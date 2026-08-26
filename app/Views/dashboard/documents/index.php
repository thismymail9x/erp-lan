<?php
if (!function_exists('get_doc_icon')) {
    function get_doc_icon($ext) {
        $icons = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'jpg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'zip' => 'fa-file-archive',
            'rar' => 'fa-file-archive'
        ];
        return $icons[strtolower($ext)] ?? 'fa-file-alt';
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes($bytes) {
        if (!is_numeric($bytes) || $bytes < 0) return '0 B';
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }
}

if (!function_exists('translate_category')) {
    function translate_category($cat) {
        $map = [
            'client_intake' => 'Hồ sơ KH',
            'case_file' => 'Hồ sơ vụ việc',
            'correspondence' => 'Thư từ',
            'financial' => 'Tài chính',
            'template' => 'Biểu mẫu',
            'internal' => 'Nội bộ'
        ];
        return $map[$cat] ?? 'Khác';
    }
}
?>
<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/documents.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dms-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Tài liệu</h2>
            <p class="content-subtitle">Hệ thống DMS.</p>
        </div>
        <div class="header-controls">
            <button class="btn-premium" onclick="document.getElementById('modalUpload').style.display='block'">
                <i class="fas fa-upload"></i> Tải lên
            </button>
        </div>
    </div>

    <!-- DMS FILTER BAR -->
    <form action="<?= base_url('documents') ?>" method="get" class="search-filter-bar filter-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="keyword" value="<?= esc($filters['keyword'] ?? '') ?>" placeholder="Tìm file...">
        </div>
        
        <select name="category" class="filter-select">
            <option value="">Tất cả loại</option>
            <option value="client_intake" <?= ($filters['category'] == 'client_intake') ? 'selected' : '' ?>>Hồ sơ khách hàng</option>
            <option value="case_file" <?= ($filters['category'] == 'case_file') ? 'selected' : '' ?>>Hồ sơ vụ việc</option>
            <option value="correspondence" <?= ($filters['category'] == 'correspondence') ? 'selected' : '' ?>>Thư từ - Liên lạc</option>
            <option value="financial" <?= ($filters['category'] == 'financial') ? 'selected' : '' ?>>Tài chính - Thanh toán</option>
            <option value="template" <?= ($filters['category'] == 'template') ? 'selected' : '' ?>>Biểu mẫu - Knowledge Base</option>
            <option value="internal" <?= ($filters['category'] == 'internal') ? 'selected' : '' ?>>Tài liệu nội bộ</option>
        </select>

        <select name="customer_id" class="filter-select select2-basic">
            <option value="">Khách hàng</option>
            <?php if (!empty($customers) && is_array($customers)) { ?>
                <?php foreach ($customers as $c) { ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['customer_id'] == $c['id']) ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php } ?>
            <?php } ?>
        </select>

        <select name="tag_id" class="filter-select">
            <option value="">Tất cả nhãn</option>
            <?php if (!empty($availableTags)) { ?>
                <?php foreach ($availableTags as $tag) { ?>
                    <option value="<?= $tag['id'] ?>" <?= (($filters['tag_id'] ?? '') == $tag['id']) ? 'selected' : '' ?>><?= esc($tag['name']) ?></option>
                <?php } ?>
            <?php } ?>
        </select>
        
<!--        <button type="submit" class="btn-filter-submit">Tìm</button>-->
        <a href="<?= base_url('documents') ?>" class="btn-filter-secondary">Xóa</a>
    </form>
    <!-- Modal: Upload -->
    <div id="modalUpload" class="modal-overlay-cust">
        <div class="premium-card modal-content-600">
            <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-cloud-upload-alt text-blue"></i> Tải lên tài liệu DMS
            </h3>
            <form id="formUploadDocument" action="<?= base_url('documents/upload') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-grid-2">
                    <div class="form-group-premium">
                        <label class="label-premium">Phân loại tài liệu</label>
                        <select name="document_category" class="form-control-premium" required>
                            <option value="case_file">Hồ sơ vụ việc</option>
                            <option value="client_intake">Hồ sơ định danh khách hàng</option>
                            <option value="correspondence">Công văn - Phản hồi</option>
                            <option value="financial">Hóa đơn - Chứng từ</option>
                            <option value="template">Mẫu hợp đồng - Đơn từ</option>
                            <option value="internal">Tài liệu nội bộ</option>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Độ bảo mật</label>
                        <select name="is_confidential" class="form-control-premium">
                            <option value="0">Công khai nội bộ</option>
                            <option value="1">Tài liệu MẬT (Chỉ Người có quyền)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <label class="label-premium">Tên tài liệu / Tiêu đề</label>
                    <input type="text" name="file_name" class="form-control-premium" placeholder="Ví dụ: CCCD - Nguyễn Văn A">
                    <small class="text-muted">Để trống để hệ thống tự lấy tên tệp. Khi chọn nhiều tệp, tiêu đề này sẽ là tiền tố.</small>
                </div>

                <div class="form-grid-2" style="margin-top: 15px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Liên kết Vụ việc (Case)</label>
                        <select name="case_id" class="form-control-premium select2-basic">
                            <option value="">-- Không liên kết --</option>
                            <?php if (!empty($cases) && is_array($cases)) { ?>
                                <?php foreach ($cases as $case) { ?>
                                    <option value="<?= $case['id'] ?>"><?= esc($case['code'] ?? '--') ?> - <?= esc($case['title'] ?? '--') ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Liên kết Khách hàng</label>
                        <select name="customer_id" class="form-control-premium select2-basic">
                            <option value="">-- Không liên kết --</option>
                            <?php if (!empty($customers) && is_array($customers)) { ?>
                                <?php foreach ($customers as $c) { ?>
                                    <option value="<?= $c['id'] ?>"><?= esc($c['name'] ?? '--') ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <label class="label-premium">Nhãn dán (Tags)</label>
                    <select name="tags_raw[]" class="form-control-premium select2-tags" multiple="multiple">
                        <?php if (!empty($availableTags)) { ?>
                            <?php foreach ($availableTags as $tag) { ?>
                                <option value="<?= esc($tag['name']) ?>"><?= esc($tag['name']) ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                    <small class="text-muted">Chọn từ danh mục hoặc nhập mới để đồng bộ hệ thống.</small>
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <div class="dms-upload-zone">
                        <input type="file" name="document[]" id="dmsFileInput" multiple required>
                        <label for="dmsFileInput">
                            <i class="fas fa-file-export"></i>
                            <span>Click để chọn một hoặc nhiều tệp</span>
                            <small>Hỗ trợ PDF, DOCX, JPG, PNG (Max 20MB)</small>
                        </label>
                    </div>
                    <div id="dmsSelectedFiles" class="dms-selected-files"></div>
                </div>

                <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="window.document.getElementById('modalUpload').style.display='none'">Đóng</button>
                    <button type="submit" class="btn-premium">Bắt đầu lưu trữ</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Share Document -->
    <div id="modalShare" class="modal-overlay-cust" style="display: none;">
        <div class="premium-card modal-content-600">
            <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-share-alt text-blue"></i> Chia sẻ tài liệu
            </h3>
            <form id="formShareDocument" method="post">
                <?= csrf_field() ?>
                <div class="form-group-premium">
                    <label class="label-premium">Tài liệu</label>
                    <input type="text" id="share_file_name" class="form-control-premium" readonly style="background: #f8f9fa;">
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label class="label-premium" style="margin-bottom: 0;">Người nhận (Chọn nhiều)</label>
                        <a href="javascript:void(0)" onclick="selectAllUsers()" style="font-size: 12px; color: var(--apple-blue); font-weight: 500;">Chọn tất cả</a>
                    </div>
                    <select name="user_ids[]" id="share_user_ids" class="form-control-premium select2-share" multiple="multiple" required>
                        <?php if (!empty($allUsers)) { ?>
                            <?php foreach ($allUsers as $user) { ?>
                                <option value="<?= $user['id'] ?>"><?= esc($user['full_name']) ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <label class="label-premium">Lời nhắn (Nội dung thông báo)</label>
                    <textarea name="message" id="share_message" class="form-control-premium" rows="3" placeholder="Nhập lời nhắn gửi đến người nhận..."></textarea>
                </div>

                <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="$('#modalShare').fadeOut(200)">Đóng</button>
                    <button type="submit" class="btn-premium">Gửi thông báo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Document -->
    <div id="modalEdit" class="modal-overlay-cust" style="display: none;">
        <div class="premium-card modal-content-600">
            <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-edit text-blue"></i> Chỉnh sửa thông tin tài liệu
            </h3>
            <form id="formEditDocument" action="" method="post">
                <?= csrf_field() ?>
                <div class="form-grid-2">
                    <div class="form-group-premium">
                        <label class="label-premium">Phân loại tài liệu</label>
                        <select name="document_category" id="edit_category" class="form-control-premium" required>
                            <option value="case_file">Hồ sơ vụ việc</option>
                            <option value="client_intake">Hồ sơ định danh khách hàng</option>
                            <option value="correspondence">Công văn - Phản hồi</option>
                            <option value="financial">Hóa đơn - Chứng từ</option>
                            <option value="template">Mẫu hợp đồng - Đơn từ</option>
                            <option value="internal">Tài liệu nội bộ</option>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Độ bảo mật</label>
                        <select name="is_confidential" id="edit_confidential" class="form-control-premium">
                            <option value="0">Công khai nội bộ</option>
                            <option value="1">Tài liệu MẬT (Chỉ Người có quyền)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <label class="label-premium">Tên tài liệu / Tiêu đề</label>
                    <input type="text" name="file_name" id="edit_file_name" class="form-control-premium" required>
                </div>

                <div class="form-grid-2" style="margin-top: 15px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Liên kết Vụ việc (Case)</label>
                        <select name="case_id" id="edit_case_id" class="form-control-premium select2-basic-edit">
                            <option value="">-- Không liên kết --</option>
                            <?php if (!empty($cases) && is_array($cases)) { ?>
                                <?php foreach ($cases as $case) { ?>
                                    <option value="<?= $case['id'] ?>"><?= esc($case['code'] ?? '--') ?> - <?= esc($case['title'] ?? '--') ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Liên kết Khách hàng</label>
                        <select name="customer_id" id="edit_customer_id" class="form-control-premium select2-basic-edit">
                            <option value="">-- Không liên kết --</option>
                            <?php if (!empty($customers) && is_array($customers)) { ?>
                                <?php foreach ($customers as $c) { ?>
                                    <option value="<?= $c['id'] ?>"><?= esc($c['name'] ?? '--') ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <label class="label-premium">Nhãn dán (Tags)</label>
                    <select name="tags_raw[]" id="edit_tags" class="form-control-premium select2-tags-edit" multiple="multiple">
                        <?php if (!empty($availableTags)) { ?>
                            <?php foreach ($availableTags as $tag) { ?>
                                <option value="<?= esc($tag['name']) ?>"><?= esc($tag['name']) ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium" style="margin-top: 15px;">
                    <label class="label-premium">Mô tả thêm</label>
                    <textarea name="description" id="edit_description" class="form-control-premium" rows="3"></textarea>
                </div>

                <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('modalEdit').style.display='none'">Đóng</button>
                    <button type="submit" class="btn-premium">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
    <!-- DOCUMENT GRID/TABLE -->
    <form id="bulkActionForm" action="<?= base_url('documents/bulk-delete') ?>" method="post">
        <?= csrf_field() ?>
        <div class="premium-card" style="padding: 0; overflow: visible;" id="documents-table-results">
            <?= view('dashboard/documents/index_table') ?>
        </div><!-- FLOATING BULK ACTIONS BAR -->
        <?php if (has_permission('sys.admin')) { ?>
        <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
            <div class="bulk-info">
                <span class="selected-count">0</span> tài liệu đã được chọn
            </div>
            <div class="bulk-btns">
                <button type="button" class="btn-bulk-cancel" onclick="cancelSelection()">Hủy chọn</button>
                <button type="submit" class="btn-bulk-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa tất cả các tài liệu đã chọn? Thao tác này không thể hoàn tác!')">
                    <i class="fas fa-trash-alt"></i> Xóa chọn
                </button>
            </div>
        </div>
        <?php } ?>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/documents.js') ?>"></script>
<?= $this->endSection() ?>
