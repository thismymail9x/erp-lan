$(function() {
    $('.select2-single').select2({ width: '100%' });
    const appBaseUrl = (typeof baseUrl === 'string' ? baseUrl : '/').replace(/\/?$/, '/');
    const defaultExpenseDate = $('#caseExpenseDate').val() || '';
    const $expenseFilterForm = $('#case-expense-filter-form');
    let expenseFilterTimer = null;

    function submitExpenseFilter() {
        if (!$expenseFilterForm.length) {
            return;
        }

        const params = new URLSearchParams();
        const formData = new FormData($expenseFilterForm[0]);

        formData.forEach(function(value, key) {
            if (String(value).trim() !== '') {
                params.append(key, value);
            }
        });

        const queryString = params.toString();
        window.location.href = $expenseFilterForm.attr('action') + (queryString ? '?' + queryString : '');
    }

    function debounceExpenseFilter() {
        clearTimeout(expenseFilterTimer);
        expenseFilterTimer = setTimeout(submitExpenseFilter, 500);
    }

    if ($expenseFilterForm.length) {
        $expenseFilterForm.on('submit', function(e) {
            e.preventDefault();
            submitExpenseFilter();
        });

        $expenseFilterForm.find('input[type="text"], input[type="number"]').on('input', debounceExpenseFilter);
        $expenseFilterForm.find('select').on('change', submitExpenseFilter);

        $expenseFilterForm.find('.js-expense-filter-reset').on('click', function(e) {
            e.preventDefault();
            window.location.href = $expenseFilterForm.data('default-url') || $expenseFilterForm.attr('action');
        });
    }

    $('.expense-form, .expense-approval-form, .expense-edit-form').on('submit', function(e) {
        const form = this;
        const submitter = e.originalEvent && e.originalEvent.submitter;

        if (form.dataset.submitting === '1') {
            e.preventDefault();
            return false;
        }

        if (submitter && submitter.name) {
            $('<input>', {
                type: 'hidden',
                name: submitter.name,
                value: submitter.value
            }).appendTo(form);
        }

        form.dataset.submitting = '1';
        $(form).find('button[type="submit"]').prop('disabled', true).addClass('is-submitting');
    });

    $('.js-money-input').on('input', function() {
        const digits = String(this.value).replace(/[^\d]/g, '');
        this.value = digits ? Number(digits).toLocaleString('vi-VN') : '';
    });

    function fillFromScheduleOption() {
        const $option = $('#caseExpenseScheduleId option:selected');
        if (!$option.val()) {
            $('#caseExpenseDate').val(defaultExpenseDate);
            $('#caseExpenseStartAt').val('');
            $('#caseExpenseEndAt').val('');
            $('#caseExpenseHours').val('');
            return;
        }

        $('#caseExpenseDate').val($option.attr('data-expense-date') || '');
        $('#caseExpenseStartAt').val($option.attr('data-start-at') || '');
        $('#caseExpenseEndAt').val($option.attr('data-end-at') || '');
        $('#caseExpenseHours').val($option.attr('data-hours') || '');
    }

    function renderScheduleOptions(rows, selectedScheduleId) {
        const $schedule = $('#caseExpenseScheduleId');
        if (!$schedule.length) {
            return;
        }

        $schedule.empty().append(new Option('-- Không gắn lịch --', ''));
        rows.forEach(function(row) {
            const option = new Option(row.label, row.id, false, String(row.id) === String(selectedScheduleId || ''));
            $(option)
                .attr('data-expense-date', row.expense_date || '')
                .attr('data-start-at', row.start_at ? row.start_at.replace(' ', 'T').substring(0, 16) : '')
                .attr('data-end-at', row.end_at ? row.end_at.replace(' ', 'T').substring(0, 16) : '')
                .attr('data-hours', row.actual_hours || '');
            $schedule.append(option);
        });

        fillFromScheduleOption();
    }

    function loadSchedulesForCase(caseId, selectedScheduleId) {
        if (!caseId || !$('#caseExpenseScheduleId').length) {
            renderScheduleOptions([], '');
            return;
        }

        $.get(appBaseUrl + 'case-expenses/schedules', { case_id: caseId }, function(res) {
            if (res && res.status === 'success') {
                renderScheduleOptions(res.rows || [], selectedScheduleId);
            }
        });
    }

    $('#caseExpenseCaseId').on('change', function() {
        loadSchedulesForCase($(this).val(), '');
    });

    $('#caseExpenseScheduleId').on('change', fillFromScheduleOption);

    const selectedScheduleId = $('#caseExpenseCaseId').data('selected-schedule');
    if ($('#caseExpenseCaseId').val() && selectedScheduleId) {
        fillFromScheduleOption();
    }

    $('.expense-edit-toggle').on('click', function() {
        const targetId = $(this).data('edit-target');
        const $target = $('#' + targetId);
        const willOpen = $target.prop('hidden');

        $('.expense-edit-row').prop('hidden', true);
        $('.expense-edit-toggle').attr('aria-expanded', 'false');

        if (willOpen) {
            $target.prop('hidden', false);
            $(this).attr('aria-expanded', 'true');
        }
    });

    $('.expense-edit-close, .expense-edit-cancel').on('click', function() {
        const targetId = $(this).data('edit-target');
        $('#' + targetId).prop('hidden', true);
        $('.expense-edit-toggle[data-edit-target="' + targetId + '"]').attr('aria-expanded', 'false');
    });
});
