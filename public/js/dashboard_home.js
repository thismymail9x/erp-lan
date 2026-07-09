document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const wsModal = document.getElementById('wsModal');
    const wsForm = document.getElementById('wsForm');

    if (!calendarEl || !wsModal || !wsForm || typeof FullCalendar === 'undefined') {
        return;
    }

    const appBaseUrl = (typeof baseUrl === 'string' ? baseUrl : '/').replace(/\/?$/, '/');
    const currentEmployeeId = wsForm.dataset.currentEmployeeId || '';

    const workScheduleUrl = function(path) {
        return appBaseUrl + 'work-schedules/' + path;
    };

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
        height: '100%',
        locale: 'vi',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'H\u00f4m nay',
            month: 'Th\u00e1ng',
            week: 'Tu\u1ea7n',
            day: 'Ng\u00e0y'
        },
        firstDay: 1,
        navLinks: true,
        selectable: true,
        dayMaxEvents: true,
        events: function(info, successCallback) {
            const employeeId = $('#filterEmployee').val();
            const deptId = $('#filterDept').val();
            const types = [];

            $('.legend-item.active').each(function() {
                const type = $(this).data('type');
                if (type !== 'vehicle_hint') {
                    types.push(type);
                }
            });

            $.ajax({
                url: workScheduleUrl('events'),
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
            if (info.event.id.toString().startsWith('leave_')) {
                return;
            }
            openModal('edit', info.event.id);
        },
        eventDidMount: function(info) {
            const props = info.event.extendedProps;
            let sourceHtml = '';

            if (props.requires_vehicle) {
                const titleEl = info.el.querySelector('.fc-event-title');
                if (titleEl && !titleEl.querySelector('.ws-event-car-icon')) {
                    titleEl.insertAdjacentHTML('afterbegin', '<i class="fas fa-car ws-event-car-icon"></i>');
                }
            }

            if (props.type === 'leave') {
                sourceHtml = '';
            } else if (props.assigner_name) {
                sourceHtml = `<div style="display: flex; align-items: center;"><i class="fas fa-user-tag" style="width: 25px; color: blue; font-size: 1.2rem;"></i> <span style="color: orange; font-weight: 700;">Nh\u1eadn ph\u00e2n c\u00f4ng: ${props.assigner_name}</span></div>`;
            } else {
                sourceHtml = '<div style="display: flex; align-items: center;"><i class="fas fa-user-tag" style="width: 25px; color: blue; font-size: 1.2rem;"></i> <span style="color: green; font-weight: 700;">V\u1ee5 vi\u1ec7c c\u00e1 nh\u00e2n</span></div>';
            }

            const eventTitle = props.type === 'leave'
                ? (props.employee_name || '')
                : (info.event.title.split(': ').slice(1).join(': ') || info.event.title);
            const locationHtmlForTooltip = props.location
                ? `<div style="display: flex; align-items: center;"><i class="fas fa-map-marker-alt" style="width: 25px; color: #ff3b30; font-size: 1.1rem;"></i> <b>\u0110\u1ecba \u0111i\u1ec3m:</b> &nbsp; ${props.location}</div>`
                : '';
            const vehicleHtml = props.requires_vehicle
                ? '<div style="display: flex; align-items: center;"><i class="fas fa-car" style="width: 25px; color: #2563eb; font-size: 1.1rem;"></i> <b>\u0110\u0103ng k\u00fd xe:</b> &nbsp; <span style="color: #2563eb; font-weight: 700;">C\u00f3</span></div>'
                : '';

            tippy(info.el, {
                content: `<div style="padding: 15px; min-width: 320px; font-size: 1rem; line-height: 1.6; background: #fff;">
                            <div style="font-weight: 800; color: #007aff; border-bottom: 2px solid #f2f2f7; margin-bottom: 12px; padding-bottom: 8px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                                <span>[${props.type_label}]</span>
                            </div>
                            <div style="font-weight: 700; color: #000; margin-bottom: 10px; font-size: 1.1rem;">
                                ${eventTitle}
                            </div>
                            <div style="display: grid; gap: 8px; color: #333;">
                                <div style="display: flex; align-items: center;"><i class="fas fa-clock" style="width: 25px; color: #007aff; font-size: 1.1rem;"></i> <b>Th\u1eddi gian:</b> &nbsp; ${props.time_display}</div>
                                <div style="display: flex; align-items: center;"><i class="fas fa-calendar-alt" style="width: 25px; color: #007aff; font-size: 1.1rem;"></i> <b>Ng\u00e0y:</b> &nbsp; ${props.date_display}</div>
                                ${locationHtmlForTooltip}
                                ${vehicleHtml}
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                    <div style="display: flex; align-items: center; margin-bottom: 5px;"><i class="fas fa-user-tie" style="width: 25px; color: #5856d6; font-size: 1.1rem;"></i> <b>Th\u1ef1c hi\u1ec7n:</b> &nbsp; <span style="color: #007aff; font-weight: 700;">${props.employee_name}</span></div>
                                    ${sourceHtml}
                                </div>
                            </div>
                          </div>`,
                allowHTML: true,
                theme: 'light-border',
                placement: 'top',
                animation: 'fade',
                maxWidth: 400,
                zIndex: 9999,
                appendTo: document.body
            });
        }
    });

    calendar.render();

    $('#filterDept, #filterEmployee').on('change', function() {
        calendar.refetchEvents();
    });

    $('.legend-item').click(function() {
        if ($(this).data('type') === 'vehicle_hint') {
            return;
        }
        $(this).toggleClass('active');
        calendar.refetchEvents();
    });

    function openModal(mode, data) {
        wsForm.reset();
        $('#wsId').val('');
        $('#btnDeleteWs').hide();

        if (mode === 'create') {
            $('#wsRequiresVehicle').prop('checked', false);
            $('#modalTitle').text('T\u1ea1o l\u1ecbch tr\u00ecnh l\u00e0m vi\u1ec7c');
            $('#wsStartDate').val(data.start_at.substring(0, 10));
            $('#wsStartTime').val(data.start_at.substring(11, 16));
            $('#wsEndDate').val(data.end_at.substring(0, 10));
            $('#wsEndTime').val(data.end_at.substring(11, 16));
            $('#wsEmployeeId').val(currentEmployeeId).trigger('change');
        } else {
            $('#modalTitle').text('Chi ti\u1ebft l\u1ecbch tr\u00ecnh');
            $.get(workScheduleUrl('detail/' + data), function(res) {
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
                    if (d.can_edit) {
                        $('#btnSaveWs').show();
                    } else {
                        $('#btnSaveWs').hide();
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

    $('#btnOpenCreate').click(function() {
        const now = new Date();
        const startStr = now.toISOString().substring(0, 10) + 'T08:00';
        const endStr = now.toISOString().substring(0, 10) + 'T17:00';
        openModal('create', { start_at: startStr, end_at: endStr });
    });

    $('#btnCloseModal, #btnCancelModal, .modal-overlay').click(function(e) {
        if (e.target === this || this.id === 'btnCloseModal' || this.id === 'btnCancelModal') {
            wsModal.style.display = 'none';
        }
    });

    $('#wsForm').submit(function(e) {
        e.preventDefault();
        $('#wsStartAt').val($('#wsStartDate').val() + ' ' + $('#wsStartTime').val());
        $('#wsEndAt').val($('#wsEndDate').val() + ' ' + $('#wsEndTime').val());

        const id = $('#wsId').val();
        const url = id ? workScheduleUrl('update/' + id) : workScheduleUrl('store');

        $.post(url, $(this).serialize(), function(res) {
            if (res.status === 'success') {
                wsModal.style.display = 'none';
                calendar.refetchEvents();
            } else {
                alert(res.message);
            }
        });
    });

    $('#btnDeleteWs').click(function() {
        if (confirm('X\u00f3a l\u1ecbch tr\u00ecnh n\u00e0y?')) {
            const id = $('#wsId').val();
            $.post(workScheduleUrl('delete/' + id), function(res) {
                if (res.status === 'success') {
                    wsModal.style.display = 'none';
                    calendar.refetchEvents();
                }
            });
        }
    });
});
