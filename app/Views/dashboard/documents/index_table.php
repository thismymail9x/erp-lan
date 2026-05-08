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
<table class="premium-table" id="documents-table">
    <thead>
        <tr>
            <th style="width: 40px; text-align: center;">
                <input type="checkbox" id="selectAll" class="apple-checkbox">
            </th>
            <th>#</th>
            <th>Tài liệu</th>
            <th>Loại</th>
            <th>Nguồn</th>
            <th>User</th>
            <th>Ngày</th>
            <th style="text-align: right;">#</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($documents)) { ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 50px; opacity: 0.5;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                    Không tìm thấy tài liệu nào phù hợp.
                </td>
            </tr>
        <?php } else { ?>
            <?php foreach ($documents as $idx => $doc) { ?>
                <tr>
                    <td style="text-align: center;">
                        <input type="checkbox" name="ids[]" value="<?= $doc['id'] ?>" class="doc-checkbox apple-checkbox">
                    </td>
                    <td><?= $idx + 1 ?></td>
                    <td>
                        <div class="file-info-cell">
                            <div class="file-icon-box <?= strtolower($doc['file_type'] ?? 'bin') ?>">
                                <i class="fas <?= get_doc_icon($doc['file_type'] ?? 'bin') ?>"></i>
                            </div>
                            <div class="file-meta">
                                <div class="font-weight-500 text-apple-main clickable-edit-name" onclick="openEditModal(<?= $doc['id'] ?>)"><?= esc($doc['file_name'] ?? 'Tài liệu') ?></div>
                        <?php if (!empty($doc['tags'])) { 
                            $tagList = [];
                            if (strpos($doc['tags'], '[') === 0) {
                                $tagList = json_decode($doc['tags'], true) ?: [];
                            } else {
                                $tagList = explode(',', $doc['tags']);
                            }
                            foreach ($tagList as $tag) { ?>
                                <span class="tag-badge-mini"><?= esc(trim($tag)) ?></span>
                            <?php } 
                        } ?>
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
                            <a href="<?= base_url('cases/show/' . $doc['case_id']) ?>" class="link-item link-premium" title="<?= esc($doc['case_title'] ?? '') ?>">
                                <i class="fas fa-briefcase"></i> <?= esc($doc['case_title'] ?? '') ?>
                            </a>
                        <?php } ?>
                        <?php if (!empty($doc['customer_id'])) { ?>
                            <a href="<?= base_url('customers/show/' . $doc['customer_id']) ?>" class="link-item link-premium" title="Xem hồ sơ khách hàng">
                                <i class="fas fa-user"></i> <?= esc($doc['customer_name'] ?? "KH #" . $doc['customer_id']) ?>
                            </a>
                        <?php } ?>
                        <?php if (empty($doc['case_id']) && empty($doc['customer_id'])) { ?>
                            <span class="text-muted-xs italic">(Chưa liên kết)</span>
                        <?php } ?>
                    </td>
                    <td><?= esc($doc['uploader_name'] ?? 'System') ?></td>
                    <td><?= isset($doc['created_at']) ? date('d/m/Y H:i', strtotime($doc['created_at'])) : '--' ?></td>
                    <td style="text-align: right;">
                        <div class="action-buttons-flex">
                            <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>?preview=1" target="_blank" class="btn-icon-view" title="Xem trước">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>" class="btn-icon-view" title="Tải xuống">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn-icon-share" onclick="openShareModal(<?= $doc['id'] ?>, '<?= esc($doc['file_name']) ?>')" title="Chia sẻ">
                                <i class="fas fa-share-alt"></i>
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
    </tbody>
</table>

