<ul class="admin-breadcrumb">
    <li><a :href="'sadmin/' + vue_data.controller_slug">Danh sách {{vue_data.controller_slug}}</a></li>
    <li>Chi tiết {{vue_data.attendance_name}} cua {{ user_info.user_nicename}}</li>
</ul>

<div class="quick-edit-form">
    <div class="row">
        <div class="lf f20">
            <select style="width: 100%; border-radius: 4px; padding: 5px 4px 4px; line-height: 20px;" id="attendanceStatus" onChange="return change_status_attendance_list('attendances','attendanceStatus');">
                <option value="">- Trạng thái
                    -
                </option>
                <option :value="status_error" >{{status_error}}</option>
                <option :value="status_pass" >{{status_pass}}</option>
            </select>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
<ul class="nav nav-tabs card-header-tabs">
    <?php foreach ($list_months as $month): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo ($month == $selected_month) ? 'active font-weight-bold' : ''; ?>"
               href="sadmin/<?php echo $controller_slug; ?>?id=<?php echo $u_id; ?>&month=<?php echo $month; ?>">
                Tháng <?php echo date('m/Y', strtotime($month . '-01')); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
        <div>
            <?php
            // Lấy User ID từ bản ghi hiện tại hoặc biến $user_info đã truyền từ controller
            // Giả sử $data[0]['user_id'] hoặc $user_info['id'] có sẵn
            $export_uid = isset($data[0]['user_id']) ? $data[0]['user_id'] : (isset($user_info['id']) ? $user_info['id'] : 0);
            ?>
            <a href="sadmin/<?php echo $controller_slug; ?>/export_month/<?php echo $export_uid; ?>?month=<?php echo $selected_month; ?>"
               class="btn btn-success btn-sm"
               target="_blank">
                <i class="fa fa-file-excel-o"></i> Xuất Excel tháng <?php echo date('m/Y', strtotime($selected_month . '-01')); ?>
            </a>
        </div>
    </div>

    <div class="card-body">
<div class="table-responsive">
    <table class="table table-bordered table-striped with-check table-list eb-table ">
        <thead>
        <tr>
            <th class="text-center" style="width: 40px;"><input type="checkbox" class="input-checkbox-all"/></th>
            <th>Ngày</th>
            <th>Ghi chú</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>So với chuẩn</th>
            <th>Trạng thái</th>
            <th>Vị trí</th>
            <th>Ảnh</th>
        </tr>
        </thead>
        <tbody id="admin_detail_list" class="ng-main-content">

        <!-- Thay thế dòng tr cũ bằng dòng này -->
        <tr :class="{ 'bold': v.status == status_error }" :data-id="v.id" v-for="v in data">


<!--        <tr style="{{ v.style }" :data-id="v.id" v-for="v in data">-->
            <td class="text-center"><input type="checkbox" name="cid[]" :value="v.id" class="input-checkbox-control"/>
            </td>

            <td> {{v.attendance_date }}</td>
            <td>
                <!--            <div>a{{v.departments }}</div>-->
                <div>{{v.check_in_note }}</div>
                <br>
                <div>{{v.check_out_note }}</div>
            </td>
            <td>
                <div>{{v.check_in_time }}</div>
            </td>
            <td>
                <div>{{v.check_out_time }}</div>
            </td>
            <td>
                <span v-html="v.check_in_change"></span> /
                <span v-html="v.check_out_change"></span>
            </td>
            <td>
                <div>{{v.status }}</div>
            </td>
            <td>
                <span>{{v.check_in }}</span>/
                <span>{{v.check_out }}</span>
            </td>

            <td>
                <!-- Sử dụng white-space: nowrap để đảm bảo ảnh không bị rớt dòng nếu cột đủ rộng -->
                <div style="white-space: nowrap;">
                    <!-- Ảnh Check-in -->
                    <a v-if="v.check_in_photo && v.check_in_photo != ''"
                       :href="v.check_in_photo"
                       target="_blank"
                       style="display: inline-block; margin-right: 5px;"
                       title="Ảnh Check-in">
                        <img :src="v.check_in_photo" alt="Check-in"
                             style="max-width: 80px; max-height: 80px; object-fit: cover; border: 1px solid #ddd; padding: 2px;">
                    </a>

                    <!-- Ảnh Check-out -->
                    <a v-if="v.check_out_photo && v.check_out_photo != ''"
                       :href="v.check_out_photo"
                       target="_blank"
                       style="display: inline-block;"
                       title="Ảnh Check-out">
                        <img :src="v.check_out_photo" alt="Check-out"
                             style="max-width: 80px; max-height: 80px; object-fit: cover; border: 1px solid #ddd; padding: 2px;">
                    </a>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
</div>

<?php
$base_model->JSON_parse(
    [
        'json_data' => $data,
        'user_info' => $user_info,
        'vue_data' => $vue_data,
        'status_error' => \App\Libraries\AttendanceType::STATUS_ERROR,
        'status_pass' => \App\Libraries\AttendanceType::STATUS_PASS,
    ]
);
$base_model->JSON_echo([
    'allow_mysql_delete' => ALLOW_USING_MYSQL_DELETE ? 'true' : 'false',
], [
]);
?>
<script type="text/javascript">

    WGR_vuejs('#for_vue', {
        data: json_data,
        user_info: user_info,
        vue_data: vue_data,
    });
</script>




