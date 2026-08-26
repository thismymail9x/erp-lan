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
<?php
$currentPage = isset($pager) ? (int)$pager->getCurrentPage() : 1;
$perPage = isset($pager) ? (int)$pager->getPerPage() : max(1, count($documents ?? []));
$rowOffset = max(0, ($currentPage - 1) * $perPage);
$canDeleteDocuments = has_permission('sys.admin');
?>
<table class="premium-table" id="documents-table">
    <thead>
        <tr>
            <?php if ($canDeleteDocuments) { ?>
                <th style="width: 40px; text-align: center;">
                    <input type="checkbox" id="selectAll" class="apple-checkbox">
                </th>
            <?php } ?>
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
                <td colspan="<?= $canDeleteDocuments ? 8 : 7 ?>" style="text-align: center; padding: 50px; opacity: 0.5;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                    Không tìm thấy tài liệu nào phù hợp.
                </td>
            </tr>
        <?php } else { ?>
            <?php foreach ($documents as $idx => $doc) { ?>
                <tr>
                    <?php if ($canDeleteDocuments) { ?>
                        <td style="text-align: center;">
                            <input type="checkbox" name="ids[]" value="<?= $doc['id'] ?>" class="doc-checkbox apple-checkbox">
                        </td>
                    <?php } ?>
                    <td><?= $rowOffset + $idx + 1 ?></td>
                    <td>
                        <div class="file-info-cell">
                            <div class="file-icon-box <?= ((int)($doc['attachment_count'] ?? 1) > 1) ? 'multi' : strtolower($doc['file_type'] ?? 'bin') ?>">
                                <i class="fas <?= ((int)($doc['attachment_count'] ?? 1) > 1) ? 'fa-layer-group' : get_doc_icon($doc['file_type'] ?? 'bin') ?>"></i>
                            </div>
                            <div class="file-meta">
                                <?php
                                    $attachmentCount = (int)($doc['attachment_count'] ?? 1);
                                    $attachmentIds = !empty($doc['attachment_ids']) ? explode(',', $doc['attachment_ids']) : [];
                                    $attachmentNames = !empty($doc['attachment_names']) ? explode("\n", $doc['attachment_names']) : [];
                                    $documentTitle = $doc['file_name'] ?? 'Tài liệu';
                                    if ($attachmentCount > 1 && !empty($attachmentNames)) {
                                        $firstAttachmentTitle = pathinfo($attachmentNames[0], PATHINFO_FILENAME);
                                        if ($documentTitle === $attachmentNames[0] || $documentTitle === $firstAttachmentTitle) {
                                            $documentTitle = 'Bộ tài liệu ' . $attachmentCount . ' tệp';
                                        }
                                    }
                                ?>
                                <div class="document-title-row">
                                    <span class="font-weight-500 text-apple-main clickable-edit-name" onclick="openEditModal(<?= $doc['id'] ?>)"><?= esc($documentTitle) ?></span>
                                    <?php if ($attachmentCount > 1) { ?>
                                        <span class="document-file-count-badge">
                                            <i class="fas fa-layer-group"></i> <?= $attachmentCount ?> tệp
                                        </span>
                                    <?php } ?>
                                </div>
                                <?php if ($attachmentCount > 1 && !empty($attachmentNames)) { ?>
                                    <div class="document-attachment-list">
                                        <?php foreach ($attachmentNames as $fileIndex => $attachmentName) { ?>
                                            <?php
                                                $attachmentId = $attachmentIds[$fileIndex] ?? 0;
                                                $attachmentUrl = base_url('documents/view/' . $doc['id'] . '/file/' . $attachmentId);
                                            ?>
                                            <div class="document-attachment-item">
                                                <span class="document-attachment-name">
                                                    <i class="fas fa-paperclip"></i> <?= esc($attachmentName) ?>
                                                </span>
                                                <span class="document-attachment-actions">
                                                    <a href="<?= $attachmentUrl ?>?preview=1" target="_blank" class="document-attachment-action" title="Xem trước">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $attachmentUrl ?>" class="document-attachment-action" title="Tải xuống">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
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
                                <div class="file-size">
                                    <?= format_bytes($doc['total_size'] ?? $doc['size'] ?? 0) ?>
                                    • v<?= $doc['version_number'] ?? 1 ?>
                                </div>
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
                            <?php if ($attachmentCount <= 1) { ?>
                                <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>?preview=1" target="_blank" class="btn-icon-view" title="Xem trước">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>" class="btn-icon-view" title="Tải xuống">
                                    <i class="fas fa-download"></i>
                                </a>
                            <?php } ?>
                            <a href="javascript:void(0)" class="btn-icon-edit" onclick="openEditModal(<?= $doc['id'] ?>)" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn-icon-share" onclick='openShareModal(<?= $doc['id'] ?>, <?= json_encode($doc['file_name'] ?? 'Tài liệu', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Chia sẻ tài liệu">
                                <i class="fas fa-share-alt"></i>
                            </a>
                            <?php if ($canDeleteDocuments) { ?>
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
<?php if (isset($pager) && $pager->getPageCount() > 1) { ?>
    <div class="pagination-wrapper dms-pagination">
        <?= $pager->links() ?>
    </div>
<?php } ?>
