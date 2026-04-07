<?= $this->extend('layouts/dashboard') ?>

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
    <form action="<?= base_url('documents') ?>" method="get" class="search-filter-bar">
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
        </select>

        <select name="customer_id" class="filter-select select2-basic">
            <option value="">Khách hàng</option>
            <?php if (!empty($customers) && is_array($customers)) { ?>
                <?php foreach ($customers as $c) { ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['customer_id'] == $c['id']) ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
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

                <div class="form-group-premium" style="margin-top: 15px;">
                    <label class="label-premium">Tên tài liệu / Tiêu đề</label>
                    <input type="text" name="file_name" class="form-control-premium" placeholder="Ví dụ: CCCD - Nguyễn Văn A" required>
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
    <!-- DOCUMENT GRID/TABLE -->
    <form id="bulkActionForm" action="<?= base_url('documents/bulk-delete') ?>" method="post">
        <?= csrf_field() ?>
        <div class="premium-card" style="padding: 0; overflow: visible;" id="documents-table-results">
            <?= view('dashboard/documents/index_table') ?>
        </div>
+
+    <script>
+    $(document).ready(function() {
+        let debounceTimer;
+        const filterForm = $('.search-filter-bar');
+        const resultsContainer = $('#documents-table-results');
+
+        function performSearch() {
+            const formData = filterForm.serialize();
+            resultsContainer.css('opacity', '0.5');
+
+            $.ajax({
+                url: '<?= base_url('documents') ?>',
+                type: 'GET',
+                data: formData,
+                success: function(response) {
+                    resultsContainer.html(response);
+                    resultsContainer.css('opacity', '1');
+                    // Re-bind bulk checkbox logic if needed
+                    bindTableEvents();
+                },
+                error: function() {
+                    resultsContainer.css('opacity', '1');
+                }
+            });
+        }
+
+        filterForm.find('input[name="keyword"]').on('input', function() {
+            clearTimeout(debounceTimer);
+            debounceTimer = setTimeout(performSearch, 500);
+        });
+
+        filterForm.find('select').on('change', performSearch);
+
+        function bindTableEvents() {
+            const selectAll = $('#selectAll');
+            const checkboxes = $('.doc-checkbox');
+            
+            selectAll.on('change', function() {
+                $('.doc-checkbox').prop('checked', $(this).prop('checked'));
+                updateBulkBar();
+            });
+
+            $(document).on('change', '.doc-checkbox', function() {
+                updateBulkBar();
+            });
+        }
+
+        function updateBulkBar() {
+            const count = $('.doc-checkbox:checked').length;
+            if (count > 0) {
+                $('.selected-count').text(count);
+                $('#bulkActionBar').fadeIn(200).css('display', 'flex');
+            } else {
+                $('#bulkActionBar').fadeOut(200);
+            }
+        }
+
+        bindTableEvents();
+    });
+    </script>

        <!-- FLOATING BULK ACTIONS BAR -->
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
    </form>
</div>



</style>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Khởi tạo Select2 cho các select trong Modal
    $('.form-control-premium.select2-basic').select2({
        dropdownParent: $('#modalUpload'),
        width: '100%',
        placeholder: '-- Chọn một mục --'
    });

    // Khởi tạo Select2 cho phần Nhãn dán (Hỗ trợ tạo tag mới)
    $('.select2-tags').select2({
        dropdownParent: $('#modalUpload'),
        width: '100%',
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: 'Nhấp để chọn hoặc nhập tag mới...'
    });

    // Xử lý hiển thị tên file khi chọn
    $('#dmsFileInput').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('label').find('span').html('Đã chọn: <strong style="color:var(--apple-blue)">' + fileName + '</strong>');
            
            // Tự động điền vào ô "Tên tài liệu" nếu đang trống
            const nameInput = $('input[name="file_name"]');
            if (!nameInput.val()) {
                nameInput.val(fileName.split('.').shift());
            }
        }
    });

    // Đóng modal khi click ra ngoài
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal-overlay-cust')) {
            $('.modal-overlay-cust').hide();
        }
    });

    // --- BULK ACTION LOGIC ---
    const selectAll = $('#selectAll');
    const checkboxes = $('.doc-checkbox');
    const bulkBar = $('#bulkActionBar');
    const selectedCountSpan = $('.selected-count');

    selectAll.on('change', function() {
        checkboxes.prop('checked', $(this).prop('checked'));
        updateBulkBar();
    });

    checkboxes.on('change', function() {
        if ($('.doc-checkbox:checked').length === checkboxes.length) {
            selectAll.prop('checked', true);
        } else {
            selectAll.prop('checked', false);
        }
        updateBulkBar();
    });

    function updateBulkBar() {
        const count = $('.doc-checkbox:checked').length;
        if (count > 0) {
            selectedCountSpan.text(count);
            bulkBar.fadeIn(200).css('display', 'flex');
        } else {
            bulkBar.fadeOut(200);
        }
    }

    // Function Hủy chọn
    window.cancelSelection = function() {
        checkboxes.prop('checked', false);
        selectAll.prop('checked', false);
        updateBulkBar();
    };
});
</script>
<?= $this->endSection() ?>

<style>
/* Floating Bulk Action Bar (Apple Inspired) */
.bulk-action-bar {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(29, 29, 31, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    padding: 12px 24px;
    border-radius: 100px;
    display: flex;
    align-items: center;
    gap: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    z-index: 9999;
    color: white;
    border: 1px solid rgba(255,255,255,0.1);
    animation: slideUpFade 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUpFade {
    from { opacity: 0; transform: translate(-50%, 20px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}

.bulk-info { font-size: 14px; font-weight: 500; }
.selected-count { color: #0071e3; font-weight: 700; font-size: 16px; margin-right: 2px; }

.bulk-btns { display: flex; gap: 10px; }
.btn-bulk-cancel {
    background: transparent;
    border: none;
    color: #f5f5f7;
    font-size: 14px;
    cursor: pointer;
    font-weight: 500;
    padding: 8px 16px;
}
.btn-bulk-delete {
    background: #ff3b30;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex; align-items: center; gap: 8px;
}
.btn-bulk-delete:hover { background: #d70a00; transform: scale(1.02); }

/* Sorting Headers */
.sort-header {
    color: var(--apple-text-muted);
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}
.sort-header:hover { color: var(--apple-blue); }
.sort-header i { font-size: 10px; opacity: 0.6; }

/* Custom Checkbox Style */
.apple-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--apple-blue);
}
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
        'xlsx' => 'fa-file-excel',
        'zip' => 'fa-file-archive',
        'rar' => 'fa-file-archive'
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
