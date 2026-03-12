<?php
$base_model->add_css('wp-admin/css/' . $attendance_type . '.css');

?>


<ul class="admin-breadcrumb">
<li>Danh sách {{vue_data.attendance_name}} ({{vue_data.totalThread}})</li>
</ul>
<div class="cf admin-search-form">
<div class="lf f50">
<form name="frm_admin_search_controller" :action="'./sadmin/' + controller_slug" method="get">
<div class="cf">
<div class="lf f30">
<input v-if="vue_data.by_is_deleted > 0" type="hidden" name="is_deleted" :value="vue_data.by_is_deleted">
<!--<input name="s" :value="vue_data.by_keyword" :placeholder="'Tìm kiếm ' + vue_data.attendance_name" autofocus aria-required="true" required>-->
    <input type="date"
           name="attendance_date"
           :value="vue_data.attendance_date"
    >
</div>
<div class="lf f20">
<button type="submit" class="btn-success"><i class="fa fa-search"></i> Tìm kiếm</button>
</div>
</div>
</form>
</div>
<div class="lf f50 text-right">
<div class="d-inline"><a :href="'sadmin/' + controller_slug + '?is_deleted=' + DeletedStatus_DELETED" class="btn btn-mini"> <i class="fa fa-trash"></i> Lưu trữ</a></div>
</div>
</div>
<br>
    <div class="quick-edit-form">
        <div class="row">
            <div class="lf f20">
                <div v-if="is_deleted == DeletedStatus_DELETED">
                    <button type="button" onClick="return click_restore_checked('attendances');" class="btn btn-info"><i
                                class="fa fa-bars"></i> Chuyển thành Bản nháp
                    </button>
                    <button type="button" onClick="return click_remove_checked('attendances');" class="btn btn-danger"><i
                                class="fa fa-remove"></i> XÓA <span v-if="allow_mysql_delete == true">Hoàn toàn</span>
                    </button>
                </div>
                <div v-if="is_deleted != DeletedStatus_DELETED">
                    <button type="button" onClick="return click_delete_checked('attendances');" class="btn btn-danger"><i
                                class="fa fa-trash"></i> Lưu trữ
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php
$has_private_view = false;
if ($attendance_type != '') {
$theme_default_view = ADMIN_ROOT_VIEWS . $attendance_type . '/list_table.php';
include VIEWS_PATH . 'private_view.php';
}
if ($has_private_view === false) {
$theme_default_view = __DIR__ . '/list_table.php';
include VIEWS_PATH . 'private_view.php';
}
?>
<div class="public-part-page">
<?php echo $pagination; ?> Trên tổng số {{vue_data.totalThread}} bản ghi.
</div>
<?php
$base_model->JSON_parse(
[
'json_data' => $data,
'vue_data' => $vue_data,
 'status_error' => \App\Libraries\AttendanceType::STATUS_ERROR,
]
);
$base_model->JSON_echo([
    'allow_mysql_delete' => ALLOW_USING_MYSQL_DELETE ? 'true' : 'false',
], [
]);
?>
<script type="text/javascript">
WGR_vuejs('#for_vue', {
for_action: '<?php echo $for_action; ?>',
controller_slug: '<?php echo $controller_slug; ?>',
DeletedStatus_DELETED: '<?php echo $DeletedStatus_DELETED; ?>',
    is_deleted: '<?php echo $by_is_deleted; ?>',
data: json_data,
vue_data: vue_data,
});
</script>

<?php
//$base_model->add_js('wp-admin/js/attendances.js');
//$base_model->add_js('wp-admin/js/' . $attendance_type . '.js');
$base_model->add_js(THEMEPATH. 'wp-admin/js/custom.js');

?>