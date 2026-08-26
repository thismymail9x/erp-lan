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
?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Thêm khách hàng mới</h2>
            <p class="content-subtitle text-center">Hệ thống sẽ tự động kiểm tra trùng lặp để đảm bảo dữ liệu tinh gọn.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('customers') ?>" class="btn-secondary-sm" title="Quay lại danh sách khách hàng">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <!-- Wizard Progress Bar -->
        <div class="wizard-progress" style="display: flex; justify-content: space-between; margin-bottom: 40px; position: relative;">
            <div style="position: absolute; top: 15px; left: 0; width: 100%; height: 2px; background: #f2f2f2; z-index: 1;"></div>
            <div id="progress-line" style="position: absolute; top: 15px; left: 0; width: 0%; height: 2px; background: #0071e3; z-index: 2; transition: width 0.3s ease;"></div>
            
            <div class="step-indicator active" data-step="1" style="z-index: 3; background: #fff; padding: 0 10px;">
                <div class="step-dot" style="width: 30px; height: 30px; border-radius: 50%; border: 2px solid #0071e3; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; background: #fff; color: #0071e3; font-weight: 700;">1</div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Định danh</div>
            </div>
            <div class="step-indicator" data-step="2" style="z-index: 3; background: #fff; padding: 0 10px;">
                <div class="step-dot" style="width: 30px; height: 30px; border-radius: 50%; border: 2px solid #d2d2d7; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; background: #fff; color: #d2d2d7; font-weight: 700;">2</div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Liên lạc & Địa chỉ</div>
            </div>
            <div class="step-indicator" data-step="3" style="z-index: 3; background: #fff; padding: 0 10px;">
                <div class="step-dot" style="width: 30px; height: 30px; border-radius: 50%; border: 2px solid #d2d2d7; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; background: #fff; color: #d2d2d7; font-weight: 700;">3</div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase;">CRM & Phân loại</div>
            </div>
        </div>

        <form action="<?= base_url('customers/store') ?>" method="post" id="customerWizardForm">
            <?= csrf_field() ?>
            
            <!-- Step 1: Basic Identity -->
            <div class="wizard-step active" id="step-1">
                <div class="form-group-premium" style="margin-bottom: 25px;">
                    <label class="label-premium">Loại khách hàng <span style="color:red">*</span></label>
                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;" title="Khách hàng là cá nhân (người dùng đơn lẻ)">
                            <input type="radio" name="type" value="ca_nhan" checked style="width: 18px; height: 18px;"> Cá nhân
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;" title="Khách hàng là công ty hoặc tổ chức">
                            <input type="radio" name="type" value="doanh_nghiep" style="width: 18px; height: 18px;"> Doanh nghiệp
                        </label>
                    </div>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Họ và tên / Tên DN <span style="color:red">*</span></label>
                        <input type="text" name="name" class="form-control-premium" required placeholder="Nguyễn Văn A..." title="Nhập tên đầy đủ của khách hàng hoặc tên doanh nghiệp" value="<?= esc(request()->getGet('name') ?? '') ?>">
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Số điện thoại chính <span style="color:red">*</span></label>
                        <input type="text" name="phone" id="phone_check" class="form-control-premium" required placeholder="09xxxxxxx" title="Số điện thoại dùng để liên lạc chính và kiểm tra trùng lặp" value="<?= esc(request()->getGet('phone') ?? '') ?>">
                        <div id="phone_alert" style="font-size: 11px; color: var(--apple-red); margin-top: 5px; display: none;"></div>
                    </div>
                </div>

                <div id="individual_fields" style="margin-top: 20px;">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group-premium">
                            <label class="label-premium">Ngày sinh</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control-premium datepicker-native" title="Ngày tháng năm sinh của khách hàng">
                        </div>
                        <div class="form-group-premium">
                            <label class="label-premium">Giới tính</label>
                            <select name="gender" class="form-control-premium" title="Giới tính">
                                <option value="nam">Nam</option>
                                <option value="nu">Nữ</option>
                                <option value="khac">Khác</option>
                            </select>
                        </div>
                        <div class="form-group-premium">
                            <label class="label-premium">Số CCCD/Hộ chiếu</label>
                            <input type="text" name="identity_number" id="id_check" class="form-control-premium" placeholder="12 số hoặc số passport" title="Số định danh cá nhân để quản lý hồ sơ">
                            <div id="id_alert" style="font-size: 11px; color: var(--apple-red); margin-top: 5px; display: none;"></div>
                        </div>
                    </div>
                </div>

                <div id="corporate_fields" style="margin-top: 20px; display: none;">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group-premium">
                            <label class="label-premium">Mã số thuế</label>
                            <input type="text" name="tax_code" class="form-control-premium" placeholder="0101xxxxxx" title="Mã số thuế doanh nghiệp">
                        </div>
                        <div class="form-group-premium">
                            <label class="label-premium">Người đại diện</label>
                            <input type="text" name="company_name" class="form-control-premium" placeholder="Tên GĐ/Người được ủy quyền" title="Người chịu trách nhiệm pháp lý của DN">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Contact & Address -->
            <div class="wizard-step" id="step-2" style="display: none;">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Email chính</label>
                        <input type="email" name="email" id="email_check" class="form-control-premium" placeholder="example@gmail.com" title="Địa chỉ email liên hệ chính" value="<?= esc(request()->getGet('email') ?? '') ?>">
                        <div id="email_alert" style="font-size: 11px; color: var(--apple-red); margin-top: 5px; display: none;"></div>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">SĐT phụ</label>
                        <input type="text" name="phone_secondary" class="form-control-premium" title="Số điện thoại dự phòng">
                    </div>
                </div>
                <div class="form-group-premium" style="margin-top: 20px;">
                    <label class="label-premium">Địa chỉ đầy đủ</label>
                    <textarea name="address" class="form-control-premium" rows="3" placeholder="Số nhà, đường, phường, quận, tỉnh..." title="Địa chỉ chi tiết để gửi hồ sơ/giấy tờ"></textarea>
                </div>
            </div>

            <!-- Step 3: CRM Meta -->
            <div class="wizard-step" id="step-3" style="display: none;">
                <!-- Grid 1: Care Staff, Zalo Phone, Occupation -->
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Nhân chăm sóc tư vấn</label>
                        <select name="assigned_care_staff_id" class="form-control-premium" <?= !$isAdminOrManager ? 'disabled' : '' ?> title="Nhân sự phụ trách chăm sóc, tư vấn cho khách hàng này">
                            <option value="">-- Chọn nhân sự --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= esc($emp['full_name']) ?> (<?= esc($emp['role_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Số Zalo (Zalo Phone)</label>
                        <input type="text" name="zalo_phone" class="form-control-premium" placeholder="Nhập số điện thoại Zalo..." title="Số điện thoại dùng để kết nối Zalo">
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Nghề nghiệp</label>
                        <input type="text" name="occupation" class="form-control-premium" placeholder="Nhập nghề nghiệp..." title="Nghề nghiệp của khách hàng">
                    </div>
                </div>

                <!-- Grid 2: Customer Segment, Care Status, Service Completed Date -->
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Nhóm khách hàng (A/B/C)</label>
                        <select name="customer_segment" class="form-control-premium">
                            <option value="vip">Nhóm A — VIP</option>
                            <option value="regular" selected>Nhóm B — Phổ thông</option>
                            <option value="potential">Nhóm C — Tiềm năng nguội</option>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Tình trạng CSKH</label>
                        <select name="care_status" class="form-control-premium" <?= !$isAdminOrManager ? 'disabled' : '' ?>>
                            <option value="new" selected>Mới (Chưa chăm sóc)</option>
                            <option value="phase1">Giai đoạn 1 (Ngày 1-7)</option>
                            <option value="phase2">Giai đoạn 2 (Ngày 7-30)</option>
                            <option value="phase3">Giai đoạn 3 (Trên 30 ngày)</option>
                            <option value="completed">Hoàn thành quy trình</option>
                            <option value="dormant">Đang tạm ngưng chăm sóc</option>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Ngày hoàn tất DV gần nhất</label>
                        <input type="date" name="service_completed_date" class="form-control-premium">
                    </div>
                    <div class="form-group-premium">
                        <label class="gift-checkbox-option">
                            <input type="hidden" name="has_received_gift" value="0">
                            <input type="checkbox" name="has_received_gift" value="1">
                            <span>Đã tặng quà</span>
                        </label>
                    </div>
                </div>

                <!-- Grid 3: Source, Tags -->
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group-premium">
                        <label class="label-premium">Nguồn khách hàng</label>
                        <?php 
                        $sourceVal = request()->getGet('source');
                        if ($sourceVal === 'messenger') {
                            $sourceVal = 'facebook';
                        }
                        ?>
                        <select name="source" class="form-control-premium" title="Khách hàng biết đến chúng ta từ đâu?">
                            <option value="khac" <?= ($sourceVal === 'khac') ? 'selected' : '' ?>>Khác</option>
                            <option value="facebook" <?= ($sourceVal === 'facebook') ? 'selected' : '' ?>>Facebook</option>
                            <option value="zalo" <?= ($sourceVal === 'zalo') ? 'selected' : '' ?>>Zalo</option>
                            <option value="google" <?= ($sourceVal === 'google') ? 'selected' : '' ?>>Google Search</option>
                            <option value="gioi_thieu" <?= ($sourceVal === 'gioi_thieu') ? 'selected' : '' ?>>Được giới thiệu</option>
                            <option value="website" <?= ($sourceVal === 'website') ? 'selected' : '' ?>>Website</option>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Đối tác giới thiệu</label>
                        <select name="referred_partner_id" class="form-control-premium">
                            <option value="">-- Không gán --</option>
                            <?php foreach (($partners ?? []) as $partner): ?>
                                <option value="<?= (int)$partner['id'] ?>"><?= esc($partner['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Tags (Phân loại dữ liệu)</label>
                        <select name="tags[]" class="form-control-premium select2-tags" multiple="multiple" style="width: 100%;">
                            <?php foreach ($availableTags as $tag): ?>
                                <option value="<?= $tag['id'] ?>"><?= esc($tag['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Internal Notes -->
                <div class="form-group-premium" style="margin-top: 20px;">
                    <label class="label-premium">Nội dung cần tư vấn</label>
                    <textarea name="notes_internal" class="form-control-premium" rows="3" placeholder="Nhập nhu cầu dịch vụ, nội dung tư vấn ban đầu..."></textarea>
                </div>
            </div>

            <!-- Wizard Footer Buttons -->
            <div class="wizard-footer" style="margin-top: 40px; display: flex; justify-content: space-between; padding-top: 20px; border-top: 1px solid #f2f2f2;">
                <button type="button" id="prevBtn" class="btn-secondary" style="display: none;">Quay lại</button>
                <div style="flex: 1;"></div>
                <button type="button" id="nextBtn" class="btn-premium">Tiếp theo</button>
                <button type="submit" id="submitBtn" class="btn-premium btn-submit-premium" style="display: none;">Hoàn tất & Lưu</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customer_wizard.js') ?>?v=1.1"></script>
<?= $this->endSection() ?>
