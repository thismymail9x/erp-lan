<div class="row row-collapse">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped with-check table-list eb-table">
                <tr>
                    <td style="min-width: 60px"><b>Ngày</b></td>
                    <td style="min-width: 200px"><b>Ghi chú</b></td>
                    <td><b>Check-in</b></td>
                    <td><b>Check-out</b></td>
                    <td><b>So với chuẩn</b></td>
                    <td><b>Trạng thái</b></td>
                    <td><b>Ảnh</b></td>
                </tr>
                <tbody id="admin_detail_list" class="ng-main-content">
                <?php if (!empty($data) && is_array($data)): ?>
                    <?php foreach ($data as $v): ?>
                        <?php
                        // Xác định class CSS nếu trạng thái là lỗi (Vi phạm)
                        // Sử dụng hằng số từ thư viện hoặc so sánh chuỗi trực tiếp nếu cần
                        $is_error = ($v['status'] == \App\Libraries\AttendanceType::STATUS_ERROR);
                        $row_class = $is_error ? 'bold text-danger' : '';
                        ?>
                        <tr class="<?php echo $row_class; ?>" data-id="<?php echo $v['id']; ?>">

                            <!-- Ngày -->
                            <td><?php echo date('d-m',strtotime($v['attendance_date'])) ?></td>

                            <!-- Ghi chú -->
                            <td>
                                <div><?php echo $v['check_in_note']; ?></div>
                                <?php if (!empty($v['check_out_note'])): ?>
                                    <br>
                                    <div><?php echo $v['check_out_note']; ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Giờ vào -->
                            <td>
                                <div><?php echo $v['check_in_time']; ?></div>
                            </td>

                            <!-- Giờ ra -->
                            <td>
                                <div><?php echo $v['check_out_time']; ?></div>
                            </td>

                            <!-- So với chuẩn (Hiển thị HTML màu sắc từ controller) -->
                            <td>
                                <span><?php echo $v['check_in_change']; ?></span> /
                                <span><?php echo $v['check_out_change']; ?></span>
                            </td>

                            <!-- Trạng thái -->
                            <td>
                                <div><?php echo $v['status']; ?></div>
                            </td>
                            <!-- Ảnh -->
                            <td>
                                <div style="white-space: nowrap;">
                                    <!-- Ảnh Check-in -->
                                    <?php if (!empty($v['check_in_photo'])): ?>
                                        <a href="<?php echo $v['check_in_photo']; ?>"
                                           target="_blank"
                                           style="display: inline-block; margin-right: 5px;"
                                           title="Ảnh Check-in">
                                            <img src="<?php echo $v['check_in_photo']; ?>"
                                                 alt="Check-in"
                                                 style="max-width: 80px; max-height: 80px; object-fit: cover; border: 1px solid #ddd; padding: 2px;">
                                        </a>
                                    <?php endif; ?>

                                    <!-- Ảnh Check-out -->
                                    <?php if (!empty($v['check_out_photo'])): ?>
                                        <a href="<?php echo $v['check_out_photo']; ?>"
                                           target="_blank"
                                           style="display: inline-block;"
                                           title="Ảnh Check-out">
                                            <img src="<?php echo $v['check_out_photo']; ?>"
                                                 alt="Check-out"
                                                 style="max-width: 80px; max-height: 80px; object-fit: cover; border: 1px solid #ddd; padding: 2px;">
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Không có dữ liệu chấm công.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
