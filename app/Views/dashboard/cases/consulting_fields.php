            <?php if (has_permission('sys.admin') || has_permission('kpi.consulting')) { ?>
                <div class="form-group-premium case-kpi-section">
                    <h4 class="text-apple-main font-weight-600 m-b-15"><i class="fas fa-chart-line m-r-8 text-apple-blue"></i> KPI tư vấn</h4>
                </div>
                <div class="form-group-premium">
                    <label for="consultant_id">Nhân sự tư vấn chốt khách</label>
                    <select name="consultant_id" id="consultant_id" class="form-control-premium select2-enable" data-search="true">
                        <option value="">-- Chưa ghi nhận --</option>
                        <?php foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>" <?= (int)($case['consultant_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label for="consultation_closed_at">Ngày ghi nhận chốt</label>
                    <?php $closedAtValue = !empty($case['consultation_closed_at']) ? date('Y-m-d', strtotime($case['consultation_closed_at'])) : ''; ?>
                    <input type="date" name="consultation_closed_at" id="consultation_closed_at" class="form-control-premium" value="<?= esc($closedAtValue) ?>">
                    <small class="text-muted-dark m-t-4 case-kpi-note">KPI tháng được tính theo ngày ghi nhận chốt và giá trị hợp đồng.</small>
                </div>
            <?php } ?>
