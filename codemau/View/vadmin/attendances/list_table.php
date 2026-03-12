<div class="table-responsive">
<table class="table table-bordered table-striped with-check table-list eb-table ">
    <thead>
    <tr>
        <th class="text-center" style="width: 40px;"><input type="checkbox" class="input-checkbox-all" /></th>
        <th>Nhân viên</th>
        <th>Ghi chú</th>
        <th>Check-in</th>
        <th>Check-out</th>
        <th>So với chuẩn</th>
        <th>Trạng thái</th>
        <th>Vị trí</th>
        <th>Ảnh</th>
    </tr>
    </thead>
    <tbody id="admin_main_list" class="ng-main-content">
    <tr :class="{ 'bold': v.status == status_error }" :data-id="v.id" v-for="v in data">
                    <td class="text-center"><input type="checkbox" name="cid[]" :value="v.uid" class="input-checkbox-control" /></td>

        <td><a :href="'sadmin/' + controller_slug + '?id=' + v.uid">
                {{v.display_name}} {{v.user_nicename}} <i
                        class="fa fa-edit"></i></a></td>
        <td>
<!--            <div>a{{v.departments }}</div>-->
            <div>{{v.check_in_note }} </div>
            <br>
            <div>{{v.check_out_note }} </div>
        </td>
        <td><div>{{v.check_in_time }}</div></td>
        <td><div>{{v.check_out_time }}</div></td>
        <td>
            <span v-html="v.check_in_change"></span> /
            <span v-html="v.check_out_change"></span>
        </td>
        <td> <div>{{v.status }}</div></td>
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
                    <img :src="v.check_in_photo" alt="Check-in" style="max-width: 80px; max-height: 80px; object-fit: cover; border: 1px solid #ddd; padding: 2px;">
                </a>

                <!-- Ảnh Check-out -->
                <a v-if="v.check_out_photo && v.check_out_photo != ''"
                   :href="v.check_out_photo"
                   target="_blank"
                   style="display: inline-block;"
                   title="Ảnh Check-out">
                    <img :src="v.check_out_photo" alt="Check-out" style="max-width: 80px; max-height: 80px; object-fit: cover; border: 1px solid #ddd; padding: 2px;">
                </a>
            </div>
        </td>
    </tr>
    </tbody>
</table>
</div>

