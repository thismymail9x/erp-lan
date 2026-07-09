<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<?php
$isAdminOrManager = false;
if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
    $isAdminOrManager = true;
} else {
    $roleName = session()->get('role_name');
    if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
        $isAdminOrManager = true;
    }
}
$isCaretaker = (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == session()->get('employee_id'));
$canEditCareStatus = $isAdminOrManager || $isCaretaker;
?>
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

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="form-group-premium">
                    <label class="label-premium">Nhân sự phụ trách chăm sóc tư vấn</label>
                    <select name="assigned_care_staff_id" class="form-control-premium" <?= !$isAdminOrManager ? 'disabled' : '' ?> title="Nhân sự phụ trách chăm sóc, tư vấn cho khách hàng này">
                        <option value="">-- Chọn nhân sự --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= ($customer['assigned_care_staff_id'] == $emp['id']) ? 'selected' : '' ?>>
                                <?= esc($emp['full_name']) ?> (<?= esc($emp['role_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Số Zalo (Zalo Phone)</label>
                    <input type="text" name="zalo_phone" class="form-control-premium" value="<?= esc($customer['zalo_phone'] ?? '') ?>" placeholder="Nhập số điện thoại Zalo...">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Nghề nghiệp</label>
                    <input type="text" name="occupation" class="form-control-premium" value="<?= esc($customer['occupation'] ?? '') ?>" placeholder="Nhập nghề nghiệp...">
                </div>
            </div>

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="form-group-premium">
                    <label class="label-premium">Nhóm khách hàng (Phân loại A/B/C)</label>
                    <select name="customer_segment" class="form-control-premium">
                        <option value="vip" <?= (($customer['customer_segment'] ?? '') === 'vip') ? 'selected' : '' ?>>Nhóm A — VIP</option>
                        <option value="regular" <?= (($customer['customer_segment'] ?? 'regular') === 'regular') ? 'selected' : '' ?>>Nhóm B — Phổ thông</option>
                        <option value="potential" <?= (($customer['customer_segment'] ?? '') === 'potential') ? 'selected' : '' ?>>Nhóm C — Tiềm năng nguội</option>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Tình trạng CSKH</label>
                    <select name="care_status" class="form-control-premium" <?= !$canEditCareStatus ? 'disabled' : '' ?>>
                        <option value="new" <?= (($customer['care_status'] ?? 'new') === 'new') ? 'selected' : '' ?>>Mới (Chưa chăm sóc)</option>
                        <option value="phase1" <?= (($customer['care_status'] ?? '') === 'phase1') ? 'selected' : '' ?>>Giai đoạn 1 (Ngày 1-7)</option>
                        <option value="phase2" <?= (($customer['care_status'] ?? '') === 'phase2') ? 'selected' : '' ?>>Giai đoạn 2 (Ngày 7-30)</option>
                        <option value="phase3" <?= (($customer['care_status'] ?? '') === 'phase3') ? 'selected' : '' ?>>Giai đoạn 3 (Trên 30 ngày)</option>
                        <option value="completed" <?= (($customer['care_status'] ?? '') === 'completed') ? 'selected' : '' ?>>Hoàn thành quy trình</option>
                        <option value="dormant" <?= (($customer['care_status'] ?? '') === 'dormant') ? 'selected' : '' ?>>Đang tạm ngưng chăm sóc</option>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Ngày hoàn tất dịch vụ gần nhất</label>
                    <input type="date" name="service_completed_date" class="form-control-premium" value="<?= esc($customer['service_completed_date'] ?? '') ?>">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Qu&#224; t&#7863;ng</label>
                    <label class="gift-checkbox-option">
                        <input type="hidden" name="has_received_gift" value="0">
                        <input type="checkbox" name="has_received_gift" value="1" <?= !empty($customer['has_received_gift']) ? 'checked' : '' ?>>
                        <span>&#272;&#227; t&#7863;ng qu&#224;</span>
                    </label>
                </div>
            </div>

            <div class="form-group-premium" style="margin-top: 20px;">
                <label class="label-premium">Nội dung cần tư vấn</label>
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
