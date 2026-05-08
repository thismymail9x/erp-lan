<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<style>
    :root {
        --kpi-card-bg: #fff;
        --kpi-accent-blue: #0071e3;
        --kpi-accent-green: #34c759;
        --kpi-accent-orange: #ff9500;
        --kpi-accent-red: #ff3b30;
    }

    .kpi-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .kpi-header-title h1 {
        font-size: 1.7rem;
        font-weight: 700;
        color: #1d1d1f;
        margin-bottom: 5px;
    }

    .kpi-header-title p {
        color: #86868b;
        font-size: 0.95rem;
    }

    /* Filters Bar */
    .kpi-filters-bar {
        background: #fff;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #515154;
    }

    .filter-input {
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #d2d2d7;
        font-size: 0.9rem;
        min-width: 180px;
        outline: none;
        transition: border-color 0.2s;
    }

    .filter-input:focus {
        border-color: var(--kpi-accent-blue);
    }

    .btn-apply-filters {
        background: var(--kpi-accent-blue);
        color: #fff;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-apply-filters:hover {
        opacity: 0.9;
    }

    /* KPI Table */
    .kpi-table-container {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .kpi-table {
        width: 100%;
        border-collapse: collapse;
    }

    .kpi-table th {
        background: #f5f5f7;
        padding: 16px 20px;
        text-align: left;
        font-weight: 600;
        font-size: 0.85rem;
        color: #515154;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .kpi-table td {
        padding: 18px 20px;
        border-bottom: 1px solid #f2f2f2;
        font-size: 0.95rem;
        color: #1d1d1f;
    }

    .kpi-table tr:hover {
        background-color: #fafafa;
    }

    /* Performance Progress */
    .perf-progress-wrapper {
        width: 100%;
        max-width: 150px;
    }

    .perf-progress-bar {
        height: 8px;
        background: #e5e5ea;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 4px;
    }

    .perf-progress-fill {
        height: 100%;
        border-radius: 4px;
    }

    .perf-percent-text {
        font-size: 0.75rem;
        font-weight: 700;
    }

    .percent-low { color: var(--kpi-accent-red); background-color: var(--kpi-accent-red); }
    .percent-mid { color: var(--kpi-accent-orange); background-color: var(--kpi-accent-orange); }
    .percent-high { color: var(--kpi-accent-green); background-color: var(--kpi-accent-green); }

    /* Tags & Status */
    .emp-info {
        display: flex;
        flex-direction: column;
    }

    .emp-info .name {
        font-weight: 600;
        color: #1d1d1f;
    }

    .emp-info .position {
        font-size: 0.8rem;
        color: #86868b;
    }

    .amount-earned {
        color: var(--kpi-accent-green);
        font-weight: 700;
    }

    .amount-potential {
        color: #515154;
        font-weight: 500;
    }

    .amount-total {
        color: var(--kpi-accent-blue);
        font-weight: 700;
    }

    .amount-lost {
        color: var(--kpi-accent-red);
        font-weight: 700;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="kpi-header-row">
    <div class="kpi-header-title">
        <h1>Báo cáo Hiệu suất (KPI)</h1>
        <p>Theo dõi tiến độ thưởng và khối lượng công việc của toàn bộ nhân viên.</p>
    </div>
</div>

<form id="kpiFilterForm" class="kpi-filters-bar">
    <div class="filter-group">
        <label>Tìm kiếm</label>
        <input type="text" name="search" id="filterSearch" class="filter-input" style="min-width: 250px;" placeholder="Tên nhân viên..." value="<?= esc($filters['search']) ?>">
    </div>
    
    <div class="filter-group">
        <label>Năm giám sát</label>
        <select name="year" id="filterYear" class="filter-input" style="min-width: 150px;">
            <?php 
            $startYear = 2026;
            $endYear = max(date('Y') + 1, 2027);
            for ($y = $startYear; $y <= $endYear; $y++) { ?>
                <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="filter-group">
        <label>Bộ phận</label>
        <select name="department_id" id="filterDept" class="filter-input">
            <option value="">-- Tất cả bộ phận --</option>
            <?php foreach ($departments as $dept) { ?>
                <option value="<?= $dept['id'] ?>" <?= $filters['department_id'] == $dept['id'] ? 'selected' : '' ?>>
                    <?= esc($dept['name']) ?>
                </option>
            <?php } ?>
        </select>
    </div>
</form>

<div class="kpi-table-container">
    <table class="kpi-table">
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Phòng ban</th>
                <th>Đã đạt (VNĐ)</th>
                <th>Tiềm năng (VNĐ)</th>
                <th>Bỏ lỡ (VNĐ)</th>
                <th>Tổng thưởng</th>
                <th>Tỉ lệ hoàn thành</th>
                <th style="width: 80px;"></th>
            </tr>
        </thead>
        <tbody id="kpiTableBody">
            <?= view('dashboard/kpi/table_partial', ['stats' => $stats]) ?>
        </tbody>
    </table>
    <div id="kpiLoading" style="display: none; padding: 40px; text-align: center;">
        <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: var(--kpi-accent-blue);"></i>
        <p style="margin-top: 10px; color: #888;">Đang cập nhật dữ liệu...</p>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    let filterTimeout;

    function refreshKpi() {
        const formData = $('#kpiFilterForm').serialize();
        $('#kpiTableBody').css('opacity', '0.5');
        $('#kpiLoading').show();

        $.ajax({
            url: '<?= base_url('kpi') ?>',
            type: 'GET',
            data: formData,
            success: function(response) {
                $('#kpiTableBody').html(response).css('opacity', '1');
                $('#kpiLoading').hide();
            },
            error: function() {
                alert('Có lỗi xảy ra khi tải dữ liệu KPI.');
                $('#kpiTableBody').css('opacity', '1');
                $('#kpiLoading').hide();
            }
        });
    }

    // Lắng nghe thay đổi trên các Select
    $('#filterYear, #filterDept').on('change', function() {
        refreshKpi();
    });

    // Lắng nghe gõ phím trên ô tìm kiếm (Debounce 500ms)
    $('#filterSearch').on('keyup', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(refreshKpi, 500);
    });
});
</script>
<?= $this->endSection() ?>
