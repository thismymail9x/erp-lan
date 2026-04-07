<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper m-b-24">
    <div class="header-title-container">
        <h2 class="content-title">Quản lý Đơn nghỉ phép</h2>
        <p class="content-subtitle">Theo dõi, phê duyệt và đồng bộ dữ liệu nghỉ phép vào hệ thống chấm công.</p>
    </div>
    <div class="header-controls">
        <?php if (has_permission('leave.manage')) { ?>
            <a href="<?= base_url('leave-requests/create') ?>" class="btn-premium">
                <i class="fas fa-plus"></i>&nbsp; Tạo đơn mới
            </a>
        <?php } ?>
    </div>
</div>

<!-- Search/Filter Bar (Apple Style Row) -->
<form action="<?= base_url('leave-requests') ?>" method="GET" class="search-filter-bar m-b-24" id="leave-filter-form">
    <div class="search-input-group">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Tìm theo lý do, mã đơn..." value="<?= esc(request()->getGet('search')) ?>">
    </div>
    
    <select name="status" class="filter-select">
        <option value="">Tất cả trạng thái</option>
        <?php foreach ($statusLabels as $key => $label) { ?>
            <option value="<?= $key ?>" <?= $filters['status'] == $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php } ?>
    </select>

    <?php if (has_permission('leave.approve') || has_permission('sys.admin')) { ?>
        <select name="department_id" class="filter-select">
            <option value="">Tất cả phòng ban</option>
            <?php foreach ($departments as $dept) { ?>
                <option value="<?= $dept['id'] ?>" <?= $filters['department_id'] == $dept['id'] ? 'selected' : '' ?>><?= $dept['name'] ?></option>
            <?php } ?>
        </select>
    <?php } ?>
    
    <a href="<?= base_url('leave-requests') ?>" class="btn-filter-secondary">Xóa bộ lọc</a>
</form>

<!-- Danh mục Đơn nghỉ phép -->
<div class="premium-card no-padding overflow-hidden" id="leave-table-container">
    <?= view('dashboard/leave_requests/index_table') ?>
</div>

<!-- Modal Chi tiết & Phê duyệt -->
<div id="leaveModal" class="premium-modal" style="display: none;">
    <div class="modal-content-premium-800">
        <div class="modal-header">
            <h3>Chi tiết Đơn nghỉ phép <span id="modalId"></span></h3>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body p-24">
            <div class="row m-b-24">
                <div class="col-md-6 border-right">
                    <p class="color-muted m-b-8">Thông tin nhân sự</p>
                    <h4 id="modalName" class="m-b-4"></h4>
                    <p id="modalPos" class="color-secondary"></p>
                </div>
                <div class="col-md-6 p-l-24">
                    <p class="color-muted m-b-8">Chi tiết thời gian</p>
                    <div class="d-flex align-items-center">
                        <span class="tag-premium tag-primary" id="modalDays"></span>
                        <span class="m-x-16 color-muted">từ</span>
                        <span id="modalRange" class="font-weight-bold text-lg"></span>
                    </div>
                </div>
            </div>
            <div class="p-16 bg-light rounded m-b-24">
                <p class="color-muted m-b-8">Lý do xin nghỉ:</p>
                <p id="modalReason" class="text-lg"></p>
            </div>
            
            <div id="approvalSection" style="display: none;">
                <hr class="m-b-24">
                <div class="form-group-premium">
                    <label>Phản hồi / Ghi chú phê duyệt</label>
                    <textarea id="approvalNote" class="form-control-premium" rows="3" placeholder="Nhập lý do phê duyệt hoặc từ chối..."></textarea>
                </div>
                <div class="d-flex justify-content-end gap-16 m-t-16">
                    <button class="btn-danger" id="btnReject">Từ chối đơn</button>
                    <button class="btn-premium" id="btnApprove">Phê duyệt ngay</button>
                </div>
            </div>

            <div id="approvedInfo" style="display: none;" class="p-16 border-success-left">
                <p class="color-muted m-b-8">Thông tin phê duyệt:</p>
                <p class="m-b-4">Người duyệt: <strong id="modalApprover"></strong></p>
                <p class="m-b-4">Ngày duyệt: <span id="modalAppDate"></span></p>
                <p>Nội dung: <em id="modalAppNote"></em></p>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        let debounceTimer;
        const filterForm = $('#leave-filter-form');
        const resultsContainer = $('#leave-table-container');

        function performSearch() {
            const formData = filterForm.serialize();
            resultsContainer.css('opacity', '0.5');

            $.ajax({
                url: window.location.pathname,
                type: 'GET',
                data: formData,
                success: function(response) {
                    resultsContainer.html(response);
                    resultsContainer.css('opacity', '1');
                },
                error: function() {
                    resultsContainer.css('opacity', '1');
                    console.error('Lỗi khi tải kết quả lọc.');
                }
            });
        }

        // Search Input
        filterForm.find('input[name="search"]').on('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(performSearch, 500);
        });

        // Select Filters
        filterForm.find('select').on('change', performSearch);

        // AJAX Pagination
        $(document).on('click', '#leave-pagination a', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            resultsContainer.css('opacity', '0.5');

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    resultsContainer.html(response);
                    resultsContainer.css('opacity', '1');
                    $('html, body').animate({ scrollTop: $(".dashboard-header-wrapper").offset().top - 20 }, 500);
                }
            });
        });
    });

    const modal = document.getElementById('leaveModal');
    let currentId = null;

    function viewDetails(req) {
        currentId = req.id;
        document.getElementById('modalId').innerText = '#' + req.id;
        document.getElementById('modalName').innerText = req.employee_name;
        document.getElementById('modalPos').innerText = req.position + ' (' + req.department_name + ')';
        document.getElementById('modalDays').innerText = req.total_days + ' ngày';
        document.getElementById('modalRange').innerText = formatDate(req.start_date) + ' - ' + formatDate(req.end_date);
        document.getElementById('modalReason').innerText = req.reason;

        const approvalSection = document.getElementById('approvalSection');
        const approvedInfo = document.getElementById('approvedInfo');

        if (req.status === 'pending' && (<?= has_permission('leave.approve') || has_permission('sys.admin') ? 'true' : 'false' ?>)) {
            approvalSection.style.display = 'block';
            approvedInfo.style.display = 'none';
        } else if (req.status !== 'pending') {
            approvalSection.style.display = 'none';
            approvedInfo.style.display = 'block';
            document.getElementById('modalApprover').innerText = req.approver_name || 'Hệ thống';
            document.getElementById('modalAppDate').innerText = req.approved_at || '...';
            document.getElementById('modalAppNote').innerText = req.approval_note || '(Không có ghi chú)';
        } else {
            approvalSection.style.display = 'none';
            approvedInfo.style.display = 'none';
        }

        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '...';
        const d = new Date(dateStr);
        return d.toLocaleDateString('vi-VN');
    }

    function handleApproval(id, action) {
        const note = (action === 'approved') ? 'Đã đồng ý cho nghỉ.' : 'Không được phê duyệt.';
        if (!confirm(`Bạn có chắc muốn thực hiện hành động này?`)) return;

        submitApproval(id, action, note);
    }

    document.getElementById('btnApprove')?.addEventListener('click', () => {
        submitApproval(currentId, 'approved', document.getElementById('approvalNote').value);
    });

    document.getElementById('btnReject')?.addEventListener('click', () => {
        submitApproval(currentId, 'rejected', document.getElementById('approvalNote').value);
    });

    function submitApproval(id, action, note) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('note', note);
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch(`<?= base_url('leave-requests/approve') ?>/${id}`, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    window.onclick = function(event) {
        if (event.target == modal) closeModal();
    }
</script>
<?= $this->endSection() ?>
