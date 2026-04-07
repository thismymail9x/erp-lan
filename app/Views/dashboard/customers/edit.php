<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="customer-create-container" style="max-width: 900px; margin: 0 auto;">
    <div class="dashboard-header-wrapper" style="margin-bottom: 40px;">
        <div class="header-title-container" style="text-align: center; width: 100%;">
            <h2 class="content-title">Chỉnh sửa hồ sơ khách hàng</h2>
            <p class="content-subtitle">Cập nhật thông tin định danh và quản lý hồ sơ CRM.</p>
        </div>
        <div style="position: absolute; left: 0; top: 0;">
            <a href="<?= base_url('customers/show/' . $customer['id']) ?>" class="btn-secondary-sm" title="Quay lại hồ sơ khách hàng">
                <i class="fas fa-chevron-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card" style="padding: 40px;">
        <form action="<?= base_url('customers/update/' . $customer['id']) ?>" method="post" id="customerUpdateForm">
            <?= csrf_field() ?>
            
            <div class="form-group-premium" style="margin-bottom: 25px;">
                <label class="label-premium">Loại khách hàng <span style="color:red">*</span></label>
                <div style="display: flex; gap: 20px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="type" value="ca_nhan" <?= ($customer['type'] == 'ca_nhan') ? 'checked' : '' ?> style="width: 18px; height: 18px;"> Cá nhân
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="type" value="doanh_nghiep" <?= ($customer['type'] == 'doanh_nghiep') ? 'checked' : '' ?> style="width: 18px; height: 18px;"> Doanh nghiệp
                    </label>
                </div>
            </div>

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group-premium">
                    <label class="label-premium">Mã khách hàng</label>
                    <input type="text" name="code" class="form-control-premium" value="<?= esc($customer['code']) ?>" readonly title="Mã định danh duy nhất của khách hàng">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Họ và tên / Tên DN <span style="color:red">*</span></label>
                    <input type="text" name="name" class="form-control-premium" required value="<?= esc($customer['name']) ?>">
                </div>
            </div>

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="form-group-premium">
                    <label class="label-premium">Số điện thoại chính <span style="color:red">*</span></label>
                    <input type="text" name="phone" class="form-control-premium" required value="<?= esc($customer['phone']) ?>">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Email chính</label>
                    <input type="email" name="email" class="form-control-premium" value="<?= esc($customer['email']) ?>">
                </div>
            </div>

            <div class="form-group-premium" style="margin-top: 20px;">
                <label class="label-premium">Địa chỉ đầy đủ</label>
                <textarea name="address" class="form-control-premium" rows="2"><?= esc($customer['address']) ?></textarea>
            </div>

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="form-group-premium">
                    <label class="label-premium">Nguồn khách hàng</label>
                    <select name="source" class="form-control-premium">
                        <option value="khac" <?= ($customer['source'] == 'khac') ? 'selected' : '' ?>>Khác</option>
                        <option value="facebook" <?= ($customer['source'] == 'facebook') ? 'selected' : '' ?>>Facebook</option>
                        <option value="zalo" <?= ($customer['source'] == 'zalo') ? 'selected' : '' ?>>Zalo</option>
                        <option value="google" <?= ($customer['source'] == 'google') ? 'selected' : '' ?>>Google Search</option>
                        <option value="gioi_thieu" <?= ($customer['source'] == 'gioi_thieu') ? 'selected' : '' ?>>Được giới thiệu</option>
                        <option value="website" <?= ($customer['source'] == 'website') ? 'selected' : '' ?>>Website</option>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Tags (Phân loại dữ liệu)</label>
                    <select name="tags[]" class="form-control-premium select2-tags" multiple="multiple" style="width: 100%;">
                        <?php foreach ($availableTags as $tag): ?>
                            <option value="<?= $tag['id'] ?>" <?= in_array($tag['id'], $selectedTags) ? 'selected' : '' ?>>
                                <?= esc($tag['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-premium" style="margin-top: 20px;">
                <label class="label-premium">Ghi chú nội bộ (Chỉ quản lý thấy)</label>
                <textarea name="notes_internal" class="form-control-premium" rows="3"><?= esc($customer['notes_internal']) ?></textarea>
            </div>

            <div class="form-actions-row" style="margin-top: 40px; display: flex; justify-content: flex-end; gap: 15px; border-top: 1px solid #f2f2f2; padding-top: 30px;">
                <a href="<?= base_url('customers/show/' . $customer['id']) ?>" class="btn-secondary">Hủy bỏ</a>
                <button type="submit" class="btn-premium">Lưu các thay đổi</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customer_edit.js') ?>"></script>
<?= $this->endSection() ?>

