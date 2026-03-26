<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customers.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="customers-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý khách hàng</h2>
            <p class="content-subtitle hide-mobile">Hệ thống CRM thông minh giúp theo dõi và chăm sóc khách hàng pháp lý.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('customers/create') ?>" class="btn-premium" title="Thêm một khách hàng mới vào hệ thống CRM">
                <i class="fas fa-plus"></i> Thêm khách hàng mới
            </a>
        </div>
    </div>

    <!-- 
        CRM Stats Row:
        Bảng điều khiển các chỉ số kinh doanh chính (KPIs) của module CRM.
        Dữ liệu được lấy từ CustomerService để đảm bảo tính thời gian thực.
    -->
    <div class="stats-grid-premium">
        <!-- Tổng số khách hàng hiện có trong database -->
        <div class="stat-card-premium" title="Tổng số khách hàng đã đăng ký">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-label">Tổng số khách</div>
                <div class="stat-value"><?= $stats['total_customers'] ?></div>
            </div>
        </div>
        <!-- Khách mới được thêm vào hệ thống trong tháng hiện tại -->
        <div class="stat-card-premium" title="Số lượng khách hàng tạo mới trong tháng này">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <div class="stat-label">Khách mới tháng này</div>
                <div class="stat-value"><?= $stats['new_this_month'] ?></div>
            </div>
        </div>
        <!-- Thống kê Doanh nghiệp -->
        <div class="stat-card-premium" title="Số lượng khách hàng là tổ chức/doanh nghiệp">
            <div class="stat-icon-wrapper stat-icon-orange">
                <i class="fas fa-building"></i>
            </div>
            <div>
                <div class="stat-label">Doanh nghiệp</div>
                <div class="stat-value"><?= $stats['total_corporate'] ?? 0 ?></div>
            </div>
        </div>
        <!-- Thống kê Khách VIP -->
        <div class="stat-card-premium" title="Khách hàng có doanh thu tích lũy cao">
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="fas fa-crown"></i>
            </div>
            <div>
                <div class="stat-label">Khách hàng VIP</div>
                <div class="stat-value"><?= $stats['total_vip'] ?? 0 ?></div>
            </div>
        </div>
    </div>

    <!-- Filter & Table: Công cụ tìm kiếm và bảng dữ liệu chính -->
    <div class="premium-card premium-card-full">
        <!-- Bộ lọc tìm kiếm (Search Bar & Filter dropdown) -->
        <!-- Bộ lọc và tìm kiếm chuyên sâu -->
        <form action="<?= base_url('customers') ?>" method="get" class="search-filter-bar">
            <!-- Ô tìm kiếm -->
            <div class="search-input-group">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Tìm tên, SĐT, CCCD, MST..." value="<?= esc(service('request')->getGet('q')) ?>">
            </div>

            <!-- Loại khách -->
            <select name="type" class="filter-select">
                <option value="">Tất cả loại khách</option>
                <option value="ca_nhan" <?= service('request')->getGet('type') == 'ca_nhan' ? 'selected' : '' ?>>Cá nhân</option>
                <option value="doanh_nghiep" <?= service('request')->getGet('type') == 'doanh_nghiep' ? 'selected' : '' ?>>Doanh nghiệp</option>
            </select>
            
            <button type="submit" class="btn-filter-submit">
                <i class="fas fa-search"></i> Lọc
            </button>
            
            <?php if (service('request')->getUri()->getQuery() !== '') { ?>
                <a href="<?= base_url('customers') ?>" class="btn-filter-secondary">Xóa lọc</a>
            <?php } ?>
        </form>

        <!-- Bảng danh sách khách hàng -->
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Mã KH</th>
                        <th>Họ tên / Doanh nghiệp</th>
                        <th>Liên hệ</th>
                        <th>Định danh / MST</th>
                        <th class="table-cell-right">Tổng vụ việc</th>
                        <th class="table-cell-right">Doanh thu (VND)</th>
                        <th class="table-cell-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)) { ?>
                        <!-- Giao diện khi không có kết quả tìm kiếm -->
                        <tr>
                            <td colspan="7" class="empty-state-container">
                                <i class="fas fa-users-slash empty-state-icon" title="Không có dữ liệu"></i>
                                Không tìm thấy khách hàng nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($customers as $customer) { ?>
                        <tr>
                            <!-- Mã KH tự động (KH-YYYY-XXX) -->
                            <td data-label="Mã KH">
                                <span class="badge-secondary-minimal text-monospace font-weight-600" title="Mã định danh duy nhất của khách hàng"><?= esc($customer['code']) ?></span>
                            </td>
                            <!-- Tên và Phân loại (Có cảnh báo Blacklist nếu có) -->
                            <td data-label="Khách hàng">
                                <div class="font-weight-600 text-apple-main"><?= esc($customer['name']) ?></div>
                                <div class="text-xs text-muted-dark" title="Loại khách hàng">
                                    <?= ($customer['type'] == 'ca_nhan') ? 'Cá nhân' : 'Doanh nghiệp' ?>
                                    <?php if ($customer['is_blacklist']) { ?>
                                        <span class="text-apple-red m-l-5" title="Khách hàng này nằm trong danh sách hạn chế"><i class="fas fa-user-slash"></i> Blacklist</span>
                                    <?php } ?>
                                </div>
                            </td>
                            <!-- Thông tin liên lạc cơ bản -->
                            <td data-label="Liên hệ">
                                <div class="font-weight-500 text-sm" title="Số điện thoại"><?= esc($customer['phone'] ?: 'N/A') ?></div>
                                <div class="text-xs text-muted-dark" title="Địa chỉ Email"><?= esc($customer['email'] ?: 'N/A') ?></div>
                            </td>
                            <!-- Mã số định danh (PDPL Sensitive data) -->
                            <td data-label="Định danh">
                                <div class="text-sm" title="Số CMND/CCCD hoặc Mã số thuế">
                                    <?= esc($customer['identity_number'] ?: $customer['tax_code'] ?: '--') ?>
                                </div>
                            </td>
                            <!-- Chỉ số Tổng vụ việc (Cache field từ database) -->
                            <td class="table-cell-right" data-label="Vụ việc">
                                <span class="badge-info-minimal p-2-8 font-weight-600" title="Tổng số vụ việc pháp lý đã tiếp nhận">
                                    <?= $customer['total_cases'] ?>
                                </span>
                            </td>
                            <!-- Tổng doanh thu tích lũy (VND) -->
                            <td class="table-cell-right font-weight-700 text-apple-main" data-label="Doanh thu" title="Tổng giá trị hợp đồng lũy kế">
                                <?= number_format($customer['total_revenue'], 0, ',', '.') ?>
                            </td>
                            <!-- Xem hồ sơ 360 độ hoặc Chỉnh sửa -->
                            <td class="table-cell-center" data-label="Thao tác">
                                <div class="actions-group">
                                    <a href="<?= base_url('customers/show/' . $customer['id']) ?>" class="btn-secondary-sm" title="Chi tiết">
                                        <i class="fas fa-eye"></i> <span class="show-mobile-only">Xem hồ sơ</span>
                                    </a>
                                    <a href="<?= base_url('customers/edit/' . $customer['id']) ?>" class="btn-secondary-sm" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
