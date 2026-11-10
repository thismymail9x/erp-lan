<?php /** Chọn nhân sự quản lý trực tiếp, không tạo danh mục team hoặc thành viên mới. */ ?>
<?php if (!empty($managerChoices)) { ?>
    <div class="form-group-premium case-edit-span-2">
        <label for="case_manager_id">Trưởng team vụ việc</label>
        <select name="case_manager_id" id="case_manager_id" class="form-control-premium select2-enable" data-search="true">
            <option value="">Chưa giao </option>
            <?php foreach ($managerChoices as $manager) { ?>
                <option value="<?= (int) $manager['id'] ?>" data-specialties="<?= esc($manager['specialties'] ?? '', 'attr') ?>" <?= (int) old('case_manager_id', $case['case_manager_id'] ?? '') === (int) $manager['id'] ? 'selected' : '' ?>><?= esc($manager['full_name']) ?></option>
            <?php } ?>
        </select>
        <small id="case-manager-hint" class="content-subtitle" aria-live="polite">Người quản lý theo dõi toàn bộ vụ được giao và phân người thực hiện.</small>
    </div>
<?php } ?>
