<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<style>
    :root {
        --fc-border-color: #f1f5f9;
        --fc-daygrid-dot-event-hover-bg-color: #f8fafc;
        --fc-button-bg-color: #fff;
        --fc-button-border-color: #e2e8f0;
        --fc-button-hover-bg-color: #f8fafc;
        --fc-button-hover-border-color: #cbd5e1;
        --fc-button-active-bg-color: #f1f5f9;
        --fc-button-active-border-color: #94a3b8;
        --fc-button-text-color: #475569;
    }

    .calendar-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 20px;
        height: calc(100vh - 120px);
    }

    .calendar-sidebar {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        gap: 20px;
        overflow-y: auto;
    }

    .calendar-main {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    #calendar {
        height: 100%;
    }

    /* Apple-style FullCalendar customization */
    .fc .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1d1d1f;
    }

    .fc .fc-button {
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 8px;
        text-transform: none;
        box-shadow: none;
    }

    .fc .fc-button-primary:not(:disabled):active, 
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background-color: #f1f5f9;
        border-color: #94a3b8;
        color: #1d1d1f;
    }

    .fc .fc-daygrid-day-number {
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        padding: 8px;
    }

    .fc .fc-col-header-cell-cushion {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        padding: 10px;
        text-transform: uppercase;
    }

    .fc-event {
        border: none;
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .fc-event-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Sidebar Components */
    .sidebar-section-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 12px;
    }

    .type-legend {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.85rem;
        color: #475569;
        cursor: pointer;
        padding: 5px 8px;
        border-radius: 6px;
        transition: all 0.2s;
        opacity: 0.6;
    }
    .legend-item.active {
        opacity: 1;
        background: #f1f5f9;
        font-weight: 600;
    }
    .legend-item:hover {
        background: #f8fafc;
        opacity: 1;
    }

    .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 3px;
    }

    .filter-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.85rem;
        outline: none;
        transition: border 0.2s;
    }

    .filter-group select:focus {
        border-color: #007aff;
    }

    .btn-create-ws {
        background: #007aff;
        color: #fff;
        border: none;
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
    }

    .btn-create-ws:hover {
        background: #0062cc;
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(0, 122, 255, 0.3);
    }

    /* Modal Styling */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content-custom {
        background: #fff;
        width: 500px;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .close-modal {
        background: #f1f5f9;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #64748b;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 6px;
    }

    .form-control-custom {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.9rem;
        outline: none;
        transition: border 0.2s;
    }

    .form-control-custom:focus {
        border-color: #007aff;
    }

    .row-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
    }

    .btn-secondary-custom {
        background: #f1f5f9;
        color: #475569;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-primary-custom {
        background: #007aff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    @media (max-width: 992px) {
        .calendar-container {
            grid-template-columns: 1fr;
            height: auto;
        }
        .calendar-sidebar {
            order: 2;
        }
    }

    /* Tippy Tooltip Custom Style */
    .tippy-box[data-theme~='light-border'] {
        background-color: #fff;
        color: #1d1d1f;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border: 2px solid #007aff;
        border-radius: 12px;
    }
    .tippy-box[data-theme~='light-border'] .tippy-arrow {
        color: #007aff;
    }
    .tippy-content {
        padding: 0;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="calendar-container">
    <aside class="calendar-sidebar">
        <button class="btn-create-ws" id="btnOpenCreate">
            <i class="fas fa-plus"></i> Tạo lịch mới
        </button>

        <div class="filter-section">
            <div class="sidebar-section-title">Bộ lọc nhân sự</div>
            <div class="filter-group m-b-12">
                <select id="filterDept">
                    <option value="">Tất cả phòng ban</option>
                    <?php foreach ($departments as $dept) : ?>
                        <option value="<?= $dept['id'] ?>"><?= esc($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select id="filterEmployee" class="select2-basic">
                    <option value="">Tất cả nhân viên</option>
                    <?php foreach ($employees as $emp) : ?>
                        <option value="<?= $emp['id'] ?>" data-dept="<?= $emp['department_id'] ?>"><?= esc($emp['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="legend-section">
            <div class="sidebar-section-title">Loại lịch trình</div>
            <div class="type-legend">
                <div class="legend-item active" data-type="work">
                    <div class="legend-color" style="background: #3498db;"></div>
                    <span>Tại văn phòng</span>
                </div>
                <div class="legend-item active" data-type="business_trip">
                    <div class="legend-color" style="background: #e74c3c;"></div>
                    <span>Đi công tác</span>
                </div>
                <div class="legend-item active" data-type="leave">
                    <div class="legend-color" style="background: #10b981;"></div>
                    <span>Nghỉ phép</span>
                </div>
            </div>
        </div>

        <div class="info-box premium-card-lite" style="background: #f0f7ff; border: 1px solid #d0e7ff; padding: 15px; border-radius: 12px; font-size: 0.8rem; color: #0056b3;">
            <i class="fas fa-info-circle"></i> <b>Tip:</b> Bạn có thể nhấn trực tiếp vào lịch để tạo nhanh sự kiện mới. Mọi người sẽ nhận được thông báo khi bạn tạo lịch công tác.
        </div>
    </aside>

    <main class="calendar-main">
        <div id="calendar"></div>
    </main>
</div>

<!-- Modal Tạo/Sửa Lịch Trình -->
<div class="modal-overlay" id="wsModal">
    <div class="modal-content-custom">
        <div class="modal-header">
            <h3 id="modalTitle">Tạo lịch trình mới</h3>
            <button class="close-modal" id="btnCloseModal"><i class="fas fa-times"></i></button>
        </div>
        <form id="wsForm">
            <input type="hidden" name="id" id="wsId">
            
            <div class="form-group">
                <label>Tiêu đề / Mục đích</label>
                <input type="text" name="title" id="wsTitle" class="form-control-custom" placeholder="Ví dụ: Họp với khách hàng A, Công tác Hà Nội..." required>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label>Loại lịch trình</label>
                    <select name="type" id="wsType" class="form-control-custom">
                        <option value="business_trip">Đi công tác</option>
                        <option value="work">Tại văn phòng</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Địa điểm</label>
                    <input type="text" name="location" id="wsLocation" class="form-control-custom" placeholder="Địa chỉ hoặc tên văn phòng">
                </div>
            </div>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ngày bắt đầu</label>
                    <input type="date" id="wsStartDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Giờ</label>
                    <input type="time" id="wsStartTime" class="form-control-custom" value="08:00" required>
                </div>
            </div>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" id="wsEndDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Giờ</label>
                    <input type="time" id="wsEndTime" class="form-control-custom" value="17:00" required>
                </div>
            </div>

            <!-- Hidden inputs to sync with original start_at and end_at -->
            <input type="hidden" name="start_at" id="wsStartAt">
            <input type="hidden" name="end_at" id="wsEndAt">

            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 15px; border: 1px solid #e2e8f0;">
                <div class="form-group">
                    <label>Nhân sự thực hiện</label>
                    <select name="employee_id" id="wsEmployeeId" class="form-control-custom select2-basic">
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $current_employee_id ? 'selected' : '' ?>><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label><i class="fas fa-user-tag"></i> Vụ việc cá nhân / Nhận phân công từ</label>
                    <select name="assigned_by_id" id="wsAssignedById" class="form-control-custom select2-basic">
                        <option value="">-- Vụ việc cá nhân --</option>
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>"><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" id="btnDeleteWs" style="display: none; background: #fee2e2; color: #dc2626;">Xóa</button>
                <button type="button" class="btn-secondary-custom" id="btnCancelModal">Hủy</button>
                <button type="submit" class="btn-primary-custom" id="btnSaveWs">Lưu lịch trình</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const wsModal = document.getElementById('wsModal');
        const wsForm = document.getElementById('wsForm');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'vi',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            firstDay: 1, // Thứ 2
            navLinks: true,
            selectable: true,
            editable: false,
            dayMaxEvents: true,
            events: function(info, successCallback, failureCallback) {
                const employeeId = $('#filterEmployee').val();
                const deptId = $('#filterDept').val();
                
                // Lấy các loại đang chọn từ legend
                let types = [];
                $('.legend-item.active').each(function() {
                    types.push($(this).data('type'));
                });
                
                $.ajax({
                    url: '<?= base_url('work-schedules/events') ?>',
                    data: {
                        start: info.startStr,
                        end: info.endStr,
                        employee_id: employeeId,
                        dept_id: deptId,
                        types: types.join(',')
                    },
                    success: function(data) {
                        successCallback(data);
                    }
                });
            },
            select: function(info) {
                openModal('create', {
                    start_at: info.startStr.includes('T') ? info.startStr.substring(0, 16) : info.startStr + 'T08:00',
                    end_at: info.endStr.includes('T') ? info.endStr.substring(0, 16) : info.startStr + 'T17:00'
                });
            },
            eventClick: function(info) {
                if (info.event.id.toString().startsWith('leave_')) return;
                openModal('edit', info.event.id);
            },
            eventDidMount: function(info) {
                const props = info.event.extendedProps;
                let assignerHtml = props.assigner_name 
                    ? `<div style="color: #ff9500; font-weight: bold; margin-top: 5px;"><i class="fas fa-user-tag"></i> Nhận phân công từ: ${props.assigner_name}</div>`
                    : `<div style="color: #34c759; margin-top: 5px;"><i class="fas fa-user-check"></i> Vụ việc cá nhân</div>`;

                tippy(info.el, {
                    content: `<div style="padding: 15px; min-width: 300px; font-size: 1.1rem; line-height: 1.6; background: #fff;">
                                <div style="font-weight: 800; color: #007aff; border-bottom: 2px solid #f2f2f7; margin-bottom: 12px; padding-bottom: 8px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
                                    ${props.type_label == 'Tại văn phòng' ? '🏠' : '🚗'} <span>[${props.type_label}]</span>
                                </div>
                                <div style="font-weight: 700; color: #000; margin-bottom: 10px; font-size: 1.15rem;">
                                    ${info.event.title.split(': ').slice(1).join(': ')}
                                </div>
                                <div style="display: grid; gap: 8px; color: #333;">
                                    <div style="display: flex; align-items: center;"><i class="fas fa-clock" style="width: 25px; color: #007aff; font-size: 1.2rem;"></i> <b>Thời gian:</b> &nbsp; ${props.time_display}</div>
                                    <div style="display: flex; align-items: center;"><i class="fas fa-calendar-alt" style="width: 25px; color: #007aff; font-size: 1.2rem;"></i> <b>Ngày:</b> &nbsp; ${props.date_display}</div>
                                    <div style="display: flex; align-items: center;"><i class="fas fa-map-marker-alt" style="width: 25px; color: #ff3b30; font-size: 1.2rem;"></i> <b>Địa điểm:</b> &nbsp; ${props.location || 'Tại văn phòng'}</div>
                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                        <div style="display: flex; align-items: center;"><i class="fas fa-user-tie" style="width: 25px; color: #5856d6; font-size: 1.2rem;"></i> <b>Thực hiện:</b> &nbsp; <span style="color: #007aff; font-weight: 700;">${props.employee_name}</span></div>
                                        ${assignerHtml}
                                    </div>
                                </div>
                              </div>`,
                    allowHTML: true,
                    placement: 'top',
                    theme: 'light-border',
                    interactive: false, // Chuyển thành false để biến mất ngay khi rời chuột, tránh bị trùng
                    animation: 'fade',
                    maxWidth: 400,
                    zIndex: 9999,
                    appendTo: document.body // Đảm bảo không bị che khuất bởi các phần tử khác
                });
            }
        });
        
        calendar.render();

        // Filter Logic
        $('#filterDept').on('change', function() {
            const deptId = $(this).val();
            $('#filterEmployee option').each(function() {
                if (deptId === '' || $(this).data('dept') == deptId || $(this).val() === '') {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            $('#filterEmployee').val('').trigger('change');
            calendar.refetchEvents();
        });

        $('#filterEmployee').on('change', function() {
            calendar.refetchEvents();
        });

        // Modal Logic
        function openModal(mode, data) {
            wsForm.reset();
            $('#wsId').val('');
            $('#btnDeleteWs').hide();
            
            if (mode === 'create') {
                $('#modalTitle').text('Tạo lịch trình mới');
                $('#wsStartDate').val(data.start_at.substring(0, 10));
                $('#wsStartTime').val(data.start_at.substring(11, 16));
                $('#wsEndDate').val(data.end_at.substring(0, 10));
                $('#wsEndTime').val(data.end_at.substring(11, 16));
                $('#wsEmployeeId').val('<?= $current_employee_id ?>').trigger('change');
                $('#wsAssignedById').val('').trigger('change');
                $('#btnSaveWs').show();
                $('#btnDeleteWs').hide();
                $('#wsForm input, #wsForm select, #wsForm textarea').prop('disabled', false);
            } else {
                $('#modalTitle').text('Chi tiết lịch trình');
                $.get('<?= base_url('work-schedules/detail/') ?>' + data, function(res) {
                    if (res.status === 'success') {
                        const d = res.data;
                        $('#wsId').val(d.id);
                        $('#wsEmployeeId').val(d.employee_id).trigger('change');
                        $('#wsAssignedById').val(d.assigned_by_id || '').trigger('change');
                        $('#wsType').val(d.type);
                        $('#wsTitle').val(d.title);
                        $('#wsLocation').val(d.location);
                        
                        $('#wsStartDate').val(d.start_at.substring(0, 10));
                        $('#wsStartTime').val(d.start_at.substring(11, 16));
                        $('#wsEndDate').val(d.end_at.substring(0, 10));
                        $('#wsEndTime').val(d.end_at.substring(11, 16));
                        // Điều khiển hiển thị nút bấm
                        if (d.can_edit) {
                            $('#btnSaveWs').show();
                            $('#wsForm input, #wsForm select, #wsForm textarea').prop('disabled', false);
                        } else {
                            $('#btnSaveWs').hide();
                            $('#wsForm input, #wsForm select, #wsForm textarea').prop('disabled', true);
                        }

                        if (d.can_delete) {
                            $('#btnDeleteWs').show();
                        } else {
                            $('#btnDeleteWs').hide();
                        }
                    }
                });
            }
            wsModal.style.display = 'flex';
        }

        function closeModal() {
            wsModal.style.display = 'none';
        }

        $('#btnOpenCreate').click(() => openModal('create', {
            start_at: new Date().toISOString().substring(0, 11) + '08:00',
            end_at: new Date().toISOString().substring(0, 11) + '17:00'
        }));
        
        $('#btnCloseModal, #btnCancelModal, .modal-overlay').click(function(e) {
            if (e.target === this || this.id === 'btnCloseModal' || this.id === 'btnCancelModal') {
                closeModal();
            }
        });

        // Form Submit
        $('#wsForm').submit(function(e) {
            e.preventDefault();
            
            // Sync split inputs to hidden start_at/end_at
            $('#wsStartAt').val($('#wsStartDate').val() + ' ' + $('#wsStartTime').val());
            $('#wsEndAt').val($('#wsEndDate').val() + ' ' + $('#wsEndTime').val());

            const id = $('#wsId').val();
            const url = id ? '<?= base_url('work-schedules/update/') ?>' + id : '<?= base_url('work-schedules/store') ?>';
            
            $.post(url, $(this).serialize(), function(res) {
                if (res.status === 'success') {
                    closeModal();
                    calendar.refetchEvents();
                    // Custom toast if available, else alert
                    alert(res.message);
                } else {
                    alert(res.message);
                }
            });
        });

        // Delete Logic
        $('#btnDeleteWs').click(function() {
            if (confirm('Bạn có chắc chắn muốn xóa lịch trình này?')) {
                const id = $('#wsId').val();
                $.post('<?= base_url('work-schedules/delete/') ?>' + id, function(res) {
                    if (res.status === 'success') {
                        closeModal();
                        calendar.refetchEvents();
                        alert(res.message);
                    }
                });
            }
        });

        // Xử lý bộ lọc Phòng ban
        $('#filterDept').change(function() {
            const deptId = $(this).val();
            // Lọc danh sách nhân viên tương ứng
            $('#filterEmployee option').each(function() {
                const empDeptId = $(this).data('dept');
                if (!deptId || !empDeptId || empDeptId == deptId) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            // Reset nhân viên về "Tất cả" khi đổi phòng ban
            $('#filterEmployee').val('').trigger('change');
            calendar.refetchEvents();
        });

        // Xử lý bộ lọc Nhân viên
        $('#filterEmployee').change(function() {
            calendar.refetchEvents();
        });

        // Xử lý bộ lọc nhanh theo Loại (Legend)
        $('.legend-item').click(function() {
            $(this).toggleClass('active');
            // Thêm hiệu ứng UI
            if ($(this).hasClass('active')) {
                $(this).css('opacity', '1').css('border', '1px solid #007aff');
            } else {
                $(this).css('opacity', '0.6').css('border', '1px solid transparent');
            }
            calendar.refetchEvents();
        });
    });
</script>
<?= $this->endSection() ?>
