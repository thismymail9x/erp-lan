<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dms-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý Tài liệu Số (DMS)</h2>
            <p class="content-subtitle">Kho lưu trữ hồ sơ, biểu mẫu và bằng chứng tập trung.</p>
        </div>
        <div class="header-controls">
            <button class="btn-premium" onclick="document.getElementById('modalUpload').style.display='flex'">
                <i class="fas fa-upload"></i> Tải lên tài liệu mới
            </button>
        </div>
    </div>

    <!-- DMS FILTER BAR -->
    <form action="<?= base_url('documents') ?>" method="get" class="search-filter-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="keyword" value="<?= esc($filters['keyword'] ?? '') ?>" placeholder="Tên file, tags...">
        </div>
        
        <select name="category" class="filter-select">
            <option value="">Tất cả loại</option>
            <option value="client_intake" <?= ($filters['category'] == 'client_intake') ? 'selected' : '' ?>>Hồ sơ khách hàng</option>
            <option value="case_file" <?= ($filters['category'] == 'case_file') ? 'selected' : '' ?>>Hồ sơ vụ việc</option>
            <option value="correspondence" <?= ($filters['category'] == 'correspondence') ? 'selected' : '' ?>>Thư từ - Liên lạc</option>
            <option value="financial" <?= ($filters['category'] == 'financial') ? 'selected' : '' ?>>Tài chính - Thanh toán</option>
            <option value="template" <?= ($filters['category'] == 'template') ? 'selected' : '' ?>>Biểu mẫu - Knowledge Base</option>
        </select>

        <select name="customer_id" class="filter-select select2-basic">
            <option value="">Theo Khách hàng</option>
            <?php if (!empty($customers) && is_array($customers)) { ?>
                <?php foreach ($customers as $c) { ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['customer_id'] == $c['id']) ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php } ?>
            <?php } ?>
        </select>
        
        <button type="submit" class="btn-filter-submit">Lọc</button>
        <a href="<?= base_url('documents') ?>" class="btn-filter-secondary">Xóa</a>
    </form>

    <!-- DOCUMENT GRID/TABLE -->
    <div class="premium-card" style="padding: 0; overflow: hidden;">
        <table class="premium-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Tên tài liệu</th>
                    <th>Phân loại</th>
                    <th>Liên kết</th>
                    <th>Người tải</th>
                    <th>Ngày tải</th>
                    <th style="text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)) { ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 50px; opacity: 0.5;">
                            <i class="fas fa-folder-open" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                            Không tìm thấy tài liệu nào phù hợp.
                        </td>
                    </tr>
                <?php } else { ?>
                <?php if (!empty($documents) && is_array($documents)) { ?>
                    <?php foreach ($documents as $idx => $doc) { ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <div class="file-info-cell">
                                    <div class="file-icon-box <?= strtolower($doc['file_type'] ?? 'bin') ?>">
                                        <i class="fas <?= get_doc_icon($doc['file_type'] ?? 'bin') ?>"></i>
                                    </div>
                                    <div class="file-meta">
                                        <div class="file-name"><?= esc($doc['file_name'] ?? 'Undefined') ?></div>
                                        <div class="file-size"><?= format_bytes($doc['size'] ?? 0) ?> • v<?= $doc['version_number'] ?? 1 ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-log badge-<?= $doc['document_category'] ?? 'internal' ?>">
                                    <?= translate_category($doc['document_category'] ?? 'internal') ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($doc['case_id'])) { ?>
                                    <div class="link-item"><i class="fas fa-briefcase"></i> Vụ việc #<?= $doc['case_id'] ?></div>
                                <?php } ?>
                                <?php if (!empty($doc['customer_id'])) { ?>
                                    <div class="link-item"><i class="fas fa-user"></i> KH: #<?= $doc['customer_id'] ?></div>
                                <?php } ?>
                            </td>
                            <td><?= $doc['uploaded_by'] ?? 'System' ?></td>
                            <td><?= isset($doc['created_at']) ? date('d/m/Y H:i', strtotime($doc['created_at'])) : '--' ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons-flex">
                                    <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>" class="btn-icon-view" title="Xem/Tải xuống">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php if (has_permission('sys.admin')) { ?>
                                        <a href="<?= base_url('documents/delete/' . ($doc['id'] ?? 0)) ?>" class="btn-icon-delete" onclick="return confirm('Xác nhận xóa tài liệu này?')" title="Xóa">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Upload -->
<div id="modalUpload" class="modal-overlay-cust">
    <div class="premium-card modal-content-600">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-cloud-upload-alt text-blue"></i> Tải lên tài liệu DMS
        </h3>
        <form action="<?= base_url('documents/upload') ?>" method="post" enctype="multipart/form-data">
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
                <label class="label-premium">Ghi chú / Tags</label>
                <input type="text" name="tags_raw" class="form-control-premium" placeholder="Ví dụ: cccd, quan trong, ban an">
            </div>

            <div class="form-group-premium" style="margin-top: 15px;">
                <div class="dms-upload-zone">
                    <input type="file" name="document" id="dmsFileInput" required>
                    <label for="dmsFileInput">
                        <i class="fas fa-file-export"></i>
                        <span>Click để chọn tệp hoặc kéo thả vào đây</span>
                        <small>Hỗ trợ PDF, DOCX, JPG, PNG (Max 20MB)</small>
                    </label>
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="window.document.getElementById('modalUpload').style.display='none'">Đóng</button>
                <button type="submit" class="btn-premium">Bắt đầu lưu trữ</button>
            </div>
        </form>
    </div>
</div>

<style>
/* DMS Specific Styles */
.dms-filter-card { padding: 20px; margin-bottom: 25px; }
.dms-filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
.filter-group { flex: 1; min-width: 200px; display: flex; align-items: center; border: 1px solid #e0e0e0; border-radius: 8px; padding: 0 12px; background: #fff; }
.filter-icon { color: #888; margin-right: 10px; }
.filter-group input, .filter-group select { border: none; padding: 10px 0; width: 100%; outline: none; font-size: 0.9rem; background: transparent; }

.file-info-cell { display: flex; gap: 12px; align-items: center; }
.file-icon-box { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 1.1rem; }
.file-icon-box.pdf { background: #fee2e2; color: #ef4444; }
.file-icon-box.docx, .file-icon-box.doc { background: #e0f2fe; color: #0ea5e9; }
.file-icon-box.png, .file-icon-box.jpg { background: #fef9c3; color: #ca8a04; }
.file-name { font-weight: 600; font-size: 0.95rem; color: #333; }
.file-size { font-size: 0.8rem; color: #888; margin-top: 2px; }

.badge-client_intake { background: #ebf5ff; color: #007bff; }
.badge-case_file { background: #f0fdf4; color: #15803d; }
.badge-correspondence { background: #fff7ed; color: #c2410c; }
.badge-financial { background: #fefce8; color: #a16207; }

.link-item { font-size: 0.8rem; color: #666; margin-bottom: 3px; display: flex; align-items: center; gap: 5px; }
.action-buttons-flex { display: flex; gap: 8px; justify-content: flex-end; }

.dms-upload-zone { border: 2px dashed #e0e0e0; border-radius: 12px; padding: 30px; text-align: center; position: relative; transition: all 0.2s; }
.dms-upload-zone:hover { border-color: var(--blue-premium); background: #f8fbff; }
.dms-upload-zone input { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.dms-upload-zone i { font-size: 2rem; color: #999; margin-bottom: 10px; display: block; }
.dms-upload-zone span { font-weight: 600; color: #444; display: block; }
.dms-upload-zone small { color: #888; margin-top: 5px; display: block; }
</style>

<?php
function get_doc_icon($ext) {
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'jpg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel'
    ];
    return $icons[strtolower($ext)] ?? 'fa-file-alt';
}

function format_bytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
    return $bytes . ' B';
}

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
?>

<?= $this->endSection() ?>
