/**
 * L.A.N ERP - Payroll interactions.
 */

$(document).ready(function() {
    initPayrollAdmin();
    initPayrollPersonal();
});

function payrollFormatNumber(n) {
    const isNegative = String(n).trim().startsWith('-');
    const numberStr = String(n).replace(/\D/g, '');
    if (!numberStr) return isNegative ? '-' : '';
    return (isNegative ? '-' : '') + numberStr.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function payrollParseNumber(value) {
    const normalized = String(value || '').replace(/,/g, '').replace(/[^\d.-]/g, '');
    const number = parseFloat(normalized);
    return Number.isFinite(number) ? number : 0;
}

function payrollFormatWorkingDays(value) {
    const number = Number(value) || 0;
    return Number.isInteger(number) ? String(number) : String(parseFloat(number.toFixed(2)));
}

function initPayrollAdmin() {
    if (!$('.payroll-table-wide').length) {
        return;
    }

    $('#checkAll').on('change', function() {
        $('.emp-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#form-calculate').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        form.find('input[name="employee_ids[]"]').remove();

        let hasChecked = false;
        $('.emp-checkbox:checked').each(function() {
            hasChecked = true;
            form.append('<input type="hidden" name="employee_ids[]" value="' + $(this).val() + '">');
        });

        if (!hasChecked && !confirm('B\u1ea1n ch\u01b0a ch\u1ecdn nh\u00e2n s\u1ef1 n\u00e0o. H\u1ec7 th\u1ed1ng s\u1ebd t\u00ednh to\u00e1n l\u1ea1i cho to\u00e0n b\u1ed9 nh\u00e2n vi\u00ean. Ti\u1ebfp t\u1ee5c?')) {
            return false;
        }

        this.submit();
    });

    $('.format-vnd').on('input', function() {
        if (window.getSelection().toString() !== '') return;
        $(this).val(payrollFormatNumber($(this).val()));
    });

    $('.edit-payroll-item').on('change', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const fieldValue = name => (row.find(`[data-field="${name}"]`).val() || '0').replace(/,/g, '');

        $.post(baseUrl + 'payroll/update-item/' + id, {
            salary_kpi: fieldValue('salary_kpi'),
            salary_bonus: fieldValue('salary_bonus'),
            salary_other: fieldValue('salary_other'),
            pit_tax: fieldValue('pit_tax'),
            petrol_allowance: fieldValue('petrol_allowance'),
            diligence_allowance: fieldValue('diligence_allowance'),
            manual_adjust_days: row.find('[data-field="manual_adjust_days"]').val() || '0',
            notes: ''
        }, function(resp) {
            if (resp.code !== 0) {
                alert(resp.error);
                return;
            }

            $('#net-' + id).text(resp.net_salary);
            if (resp.total_deductions) $('#deduct-' + id).text(payrollFormatNumber(resp.total_deductions));
            if (resp.total_gross) $('#total-gross-' + id).text(payrollFormatNumber(resp.total_gross));
            if (resp.taxable_income) $('#taxable-income-' + id).text(resp.taxable_income);

            if (resp.actual_working_days !== undefined && resp.manual_adjust_days !== undefined) {
                const totalWd = resp.actual_working_days + resp.manual_adjust_days;
                $('#total-wd-val-' + id).text(totalWd);
                const sup = $('#adjust-wd-sup-' + id);
                if (resp.manual_adjust_days > 0) {
                    sup.text('+' + resp.manual_adjust_days).show();
                } else {
                    sup.text('').hide();
                }
                $('#td-total-wd-' + id).attr('title', 'Ch\u1ea5m c\u00f4ng: ' + resp.actual_working_days + ' ng\u00e0y' + (resp.manual_adjust_days > 0 ? ' + B\u00f9 th\u1ee7 c\u00f4ng: ' + resp.manual_adjust_days + ' ng\u00e0y' : ''));
            }

            refreshPayrollFooterTotals();
            $('#net-' + id).css('color', '#34c759').delay(500).queue(function(next) {
                $(this).css('color', '');
                next();
            });
        });
    });

    $(document).on('click', '.employee-cell', function(e) {
        if ($(e.target).closest('input, button, a.btn').length > 0) return;
        $('#notes-edit-row-' + $(this).data('id')).fadeToggle(150);
    });

    bindPayrollNotesEditor('admin');
}

function getPayrollCellNumber(row, cellIndex) {
    const cell = row.children('td').eq(cellIndex);
    const input = cell.find('input:not([type="hidden"])').first();
    return payrollParseNumber(input.length ? input.val() : cell.text());
}

function refreshPayrollFooterTotals() {
    if (!$('#footer-net-salary').length) return;

    const totals = {
        insuranceSalary: 0,
        salaryBase: 0,
        totalWorkingDays: 0,
        taxableIncome: 0,
        diligenceAllowance: 0,
        petrolAllowance: 0,
        salaryKpi: 0,
        salaryBonus: 0,
        totalGross: 0,
        siEmployer: 0,
        siEmployee: 0,
        dependentDeduction: 0,
        pitTax: 0,
        totalDeductions: 0,
        netSalary: 0
    };

    $('.payroll-table-wide tbody tr').has('.emp-checkbox').each(function() {
        const row = $(this);
        totals.insuranceSalary += getPayrollCellNumber(row, 2);
        totals.salaryBase += getPayrollCellNumber(row, 3);
        totals.totalWorkingDays += payrollParseNumber(row.find('[id^="total-wd-val-"]').text());
        totals.taxableIncome += getPayrollCellNumber(row, 8);
        totals.diligenceAllowance += getPayrollCellNumber(row, 9);
        totals.petrolAllowance += getPayrollCellNumber(row, 10);
        totals.salaryKpi += getPayrollCellNumber(row, 11);
        totals.salaryBonus += getPayrollCellNumber(row, 12);
        totals.totalGross += getPayrollCellNumber(row, 13);
        totals.siEmployer += getPayrollCellNumber(row, 14);
        totals.siEmployee += getPayrollCellNumber(row, 15);
        totals.dependentDeduction += getPayrollCellNumber(row, 16);
        totals.pitTax += getPayrollCellNumber(row, 17);
        totals.totalDeductions += getPayrollCellNumber(row, 18);
        totals.netSalary += getPayrollCellNumber(row, 19);
    });

    $('#footer-insurance-salary').text(payrollFormatNumber(Math.round(totals.insuranceSalary)));
    $('#footer-salary-base').text(payrollFormatNumber(Math.round(totals.salaryBase)));
    $('#footer-total-working-days').text(payrollFormatWorkingDays(totals.totalWorkingDays));
    $('#footer-taxable-income').text(payrollFormatNumber(Math.round(totals.taxableIncome)));
    $('#footer-diligence-allowance').text(payrollFormatNumber(Math.round(totals.diligenceAllowance)));
    $('#footer-petrol-allowance').text(payrollFormatNumber(Math.round(totals.petrolAllowance)));
    $('#footer-salary-kpi').text(payrollFormatNumber(Math.round(totals.salaryKpi)));
    $('#footer-salary-bonus').text(payrollFormatNumber(Math.round(totals.salaryBonus)));
    $('#footer-total-gross').text(payrollFormatNumber(Math.round(totals.totalGross)));
    $('#footer-si-employer').text(payrollFormatNumber(Math.round(totals.siEmployer)));
    $('#footer-si-employee').text(payrollFormatNumber(Math.round(totals.siEmployee)));
    $('#footer-dependent-deduction').text(payrollFormatNumber(Math.round(totals.dependentDeduction)));
    $('#footer-pit-tax').text(payrollFormatNumber(Math.round(totals.pitTax)));
    $('#footer-total-deductions').text(payrollFormatNumber(Math.round(totals.totalDeductions)));
    $('#footer-net-salary').text(payrollFormatNumber(Math.round(totals.netSalary)) + ' \u0111');
}

function initPayrollPersonal() {
    if (!$('#net-salary-display').length) {
        return;
    }

    $(document).on('input', '.format-vnd', function() {
        if (window.getSelection().toString() !== '') return;
        $(this).val(payrollFormatNumber($(this).val()));
    });

    $(document).on('click', '.btn-toggle-notes', function() {
        const container = $(this).closest('.p-15');
        const editor = container.find('.notes-editor-container');
        const display = container.find('.notes-display-area');

        if (editor.is(':visible')) {
            editor.slideUp(200);
            display.slideDown(200);
            $(this).html('<i class="fas fa-edit text-blue"></i> Th\u00eam / S\u1eeda chi ph\u00ed');
        } else {
            display.slideUp(200);
            editor.slideDown(200);
            $(this).html('<i class="fas fa-times text-muted"></i> \u0110\u00f3ng');
        }
    });

    bindPayrollNotesEditor('personal');
}

function bindPayrollNotesEditor(mode) {
    $(document).on('click', '.btn-add-note-input', function() {
        const list = $(this).closest('.notes-editor-container').find('.notes-inputs-list');
        const prefix = mode === 'personal' ? '<span class="text-muted date-placeholder" style="font-size: 11px; min-width: 120px; text-align: right;"></span>' : '';
        const newEl = $(`
            <div class="d-flex align-items-center gap-10 mb-2 note-input-item" style="display:none;">
                ${prefix}
                <input type="text" class="form-control form-control-sm note-text-input" placeholder="Nh\u1eadp n\u1ed9i dung ghi ch\u00fa..." style="max-width: 500px;${mode === 'personal' ? 'flex-grow:1;' : ''}">
                <button type="button" class="btn btn-sm btn-light btn-remove-note-input" title="X\u00f3a" style="color: #ff3b30;"><i class="fas fa-trash"></i></button>
            </div>
        `);
        list.append(newEl);
        newEl.fadeIn(150);
        list.find('.btn-remove-note-input').show();
        newEl.find('input').focus();
    });

    $(document).on('click', '.btn-remove-note-input', function() {
        const list = $(this).closest('.notes-inputs-list');
        $(this).closest('.note-input-item').fadeOut(150, function() {
            $(this).remove();
            if (list.find('.note-input-item').length === 1 && list.find('.note-text-input').val().trim() === '') {
                list.find('.btn-remove-note-input').hide();
            } else if (list.find('.note-input-item').length === 0) {
                list.closest('.notes-editor-container').find('.btn-add-note-input').trigger('click');
            }
        });
    });

    $(document).on('click', '.btn-save-notes', function() {
        const btn = $(this);
        const container = btn.closest('.notes-editor-container');
        const id = container.data('id');
        const now = new Date();
        const dateStr = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        const notes = [];

        container.find('.note-input-item').each(function() {
            const input = $(this).find('.note-text-input');
            const text = input.val().trim();
            if (!text) return;

            let date = $(this).find('span').text().replace('[', '').replace(']', '').trim();
            if (!date) date = dateStr;
            notes.push({ id: Date.now() + Math.random(), text, date });
            $(this).find('span').text('[' + date + ']').show();
        });

        const notesJsonStr = JSON.stringify(notes);
        const origHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> \u0110ang l\u01b0u...').prop('disabled', true);

        const payload = { notes_json: notesJsonStr };
        const petrolInput = container.find('.petrol-allowance-input');
        if (petrolInput.length) {
            payload.petrol_allowance = petrolInput.val().replace(/,/g, '');
        }

        $.post(baseUrl + 'payroll/save-notes/' + id, payload, function(res) {
            btn.html(origHtml).prop('disabled', false);
            if (res.code !== 0) {
                alert('C\u00f3 l\u1ed7i x\u1ea3y ra khi l\u01b0u ghi ch\u00fa');
                return;
            }

            if (mode === 'personal') {
                if (res.net_salary) $('#net-salary-display').text(res.net_salary);
                if (payload.petrol_allowance !== undefined) $('#petrol-display').text(payrollFormatNumber(payload.petrol_allowance));
                updatePersonalNotesDisplay(container, notes);
                container.closest('.p-15').find('.btn-toggle-notes').trigger('click');
            } else {
                $('#raw-notes-' + id).val(notesJsonStr);
                updateAdminNotesDisplay(id, notes);
                $('#notes-edit-row-' + id).fadeOut(200);
            }
        }).fail(function() {
            btn.html(origHtml).prop('disabled', false);
            alert('L\u1ed7i k\u1ebft n\u1ed1i. Vui l\u00f2ng th\u1eed l\u1ea1i.');
        });
    });
}

function updateAdminNotesDisplay(id, notes) {
    let html = '';
    notes.forEach(n => {
        html += `<div class="text-xs text-muted" style="font-style: italic; margin-top: 2px;">
            <i class="fas fa-level-up-alt fa-rotate-90 text-blue" style="margin-right: 4px;"></i>${n.text}
        </div>`;
    });
    $('#notes-display-' + id).html(html);
}

function updatePersonalNotesDisplay(container, notes) {
    let html = '';
    if (notes.length > 0) {
        html = '<ul class="m-0 pl-0 text-sm" style="list-style-type: disc;">';
        notes.forEach(n => {
            html += '<li class="m-b-5"><span class="text-muted" style="font-size: 11px;">[' + n.date + ']</span> ' + n.text + '</li>';
        });
        html += '</ul>';
    } else {
        html = '<div class="text-muted text-sm italic">Ch\u01b0a c\u00f3 chi ph\u00ed ph\u00e1t sinh.</div>';
    }
    container.closest('.p-15').find('.notes-display-area').html(html);
}