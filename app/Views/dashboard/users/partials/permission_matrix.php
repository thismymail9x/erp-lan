<div class="user-info-header m-b-20" style="background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px;">
    <strong>Tài khoản:</strong> <?= esc($user['email']) ?> <br/>
    <strong>Vai trò gốc:</strong> <span class="badge-info-minimal"><?= esc($user['role_name']) ?></span>
    <p class="text-xs text-muted-dark m-t-5 m-b-0">
        Bạn có thể ghi đè (Cấp thêm hoặc Tước bỏ) từng quyền hạn cụ thể của nhân sự này mà không ảnh hưởng đến người khác cùng Vai trò.
    </p>
</div>

<form id="permissionForm" onsubmit="savePermissions(event, <?= $user['id'] ?>)">
    <?= csrf_field() ?>
    <div class="permission-groups" style="max-height: 400px; overflow-y: auto;">
        <?php foreach($groupedMatrix as $groupName => $perms): ?>
            <div class="permission-group m-b-20">
                <h4 class="font-weight-600 text-apple-main" style="border-bottom: 2px solid #eee; padding-bottom: 5px; margin-bottom: 15px;"><?= esc($groupName) ?></h4>
                <table class="premium-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Tính năng / Quyền thao tác</th>
                            <th class="table-cell-center" style="width: 20%;"><span class="badge-secondary-minimal" title="Áp dụng lại cài đặt gốc của vai trò hiện tại">Mặc định (Role)</span></th>
                            <th class="table-cell-center" style="width: 20%;"><span class="badge-success-minimal" title="Cấp quyền trực tiếp cho cá nhân này">Thêm quyền</span></th>
                            <th class="table-cell-center" style="width: 20%;"><span class="badge-danger-minimal" title="Cấm cá nhân này sử dụng tính năng">Giảm quyền</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($perms as $p): ?>
                            <tr>
                                <td>
                                    <div class="font-weight-500"><?= esc($p['description']) ?></div>
                                    <div class="text-xs text-muted-dark">Mã HT: <?= esc($p['name']) ?></div>
                                </td>
                                <td class="table-cell-center">
                                    <input type="radio" name="permissions[<?= $p['id'] ?>]" value="default" <?= ($p['user_override'] === null) ? 'checked' : '' ?> />
                                    <div class="text-xs m-t-5 <?= $p['is_role_granted'] ? 'text-apple-green' : 'text-muted-dark' ?>">
                                        (<?= $p['is_role_granted'] ? 'Đang Mở' : 'Đang Đóng' ?>)
                                    </div>
                                </td>
                                <td class="table-cell-center">
                                    <input type="radio" name="permissions[<?= $p['id'] ?>]" value="1" <?= ($p['user_override'] === '1') ? 'checked' : '' ?> />
                                </td>
                                <td class="table-cell-center">
                                    <input type="radio" name="permissions[<?= $p['id'] ?>]" value="0" <?= ($p['user_override'] === '0') ? 'checked' : '' ?> />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions-row m-t-20 pt-15" style="border-top: 1px solid #eee;">
        <button type="button" class="btn-secondary-sm" onclick="closePermissionModal()">Hủy thay đổi</button>
        <button type="submit" class="btn-premium"><i class="fas fa-save m-r-8"></i> Áp dụng Phân Quyền</button>
    </div>
</form>
