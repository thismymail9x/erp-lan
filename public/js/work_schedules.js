document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const wsModal = document.getElementById('wsModal');
        const wsForm = document.getElementById('wsForm');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: '100%',
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
                    const type = $(this).data('type');
                    if (type && type !== 'vehicle_hint') {
                        types.push(type);
                    }
                });
                
                $.ajax({
                    url: baseUrl + 'work-schedules/events',
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
                if (props.requires_vehicle) {
                    const titleEl = info.el.querySelector('.fc-event-title');
                    if (titleEl && !titleEl.querySelector('.ws-event-car-icon')) {
                        titleEl.insertAdjacentHTML('afterbegin', '<i class="fas fa-car ws-event-car-icon"></i>');
                    }
                }

                let assignerHtml = props.assigner_name 
                    ? `<div style="color: #ff9500; font-weight: bold; margin-top: 5px;"><i class="fas fa-user-tag"></i> Nhận phân công từ: ${props.assigner_name}</div>`
                    : `<div style="color: #34c759; margin-top: 5px;"><i class="fas fa-user-check"></i> Vụ việc cá nhân</div>`;
                let vehicleHtml = props.requires_vehicle
                    ? `<div style="color: #2563eb; font-weight: bold; margin-top: 8px;"><i class="fas fa-car"></i> Có đăng ký xe</div>`
                    : '';

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
                                        ${vehicleHtml}
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
                $('#wsEmployeeId').val(document.querySelector('.calendar-container').dataset.currentEmployeeId).trigger('change');
                $('#wsAssignedById').val('').trigger('change');
                $('#wsRequiresVehicle').prop('checked', false);
                $('#btnSaveWs').show();
                $('#btnDeleteWs').hide();
                $('#wsForm input, #wsForm select, #wsForm textarea').prop('disabled', false);
            } else {
                $('#modalTitle').text('Chi tiết lịch trình');
                $.get(baseUrl + 'work-schedules/detail/' + data, function(res) {
                    if (res.status === 'success') {
                        const d = res.data;
                        $('#wsId').val(d.id);
                        $('#wsEmployeeId').val(d.employee_id).trigger('change');
                        $('#wsAssignedById').val(d.assigned_by_id || '').trigger('change');
                        $('#wsType').val(d.type);
                        $('#wsTitle').val(d.title);
                        $('#wsLocation').val(d.location);
                        $('#wsRequiresVehicle').prop('checked', d.requires_vehicle == 1);
                        
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
            const url = id ? baseUrl + 'work-schedules/update/' + id : baseUrl + 'work-schedules/store';
            
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
                $.post(baseUrl + 'work-schedules/delete/' + id, function(res) {
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
            if ($(this).data('type') === 'vehicle_hint') {
                return;
            }

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
