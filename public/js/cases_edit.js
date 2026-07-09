/*
 * JS cho màn hình chỉnh sửa vụ việc.
 * Tách khỏi view để tuân thủ quy chuẩn không viết JS inline trong HTML.
 */
document.addEventListener('DOMContentLoaded', function () {
    initCaseEditSelect2();
    initVndInputs();
    initPaymentRepeater();
});

function initCaseEditSelect2() {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
        return;
    }

    jQuery('.select2-multi').select2({
        placeholder: 'Chọn nhân sự...',
        allowClear: true,
        width: '100%'
    });

    jQuery('.select2-enable').select2({
        placeholder: '-- Chọn một lựa chọn --',
        allowClear: true,
        width: '100%'
    });
}

function formatVND(value) {
    if (!value) {
        return '';
    }

    return value.toString().replace(/[^\d]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function initVndInputs() {
    document.querySelectorAll('.js-vnd-input').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = formatVND(input.value);
        });
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function initPaymentRepeater() {
    const page = document.querySelector('.case-form-container, .case-edit-container, .case-create-container');
    const container = document.getElementById('payment-progress-container');
    const addBtn = document.getElementById('add-payment-btn');

    if (!page || !container || !addBtn) {
        return;
    }

    let rowCount = 0;
    let existingData = [];
    const rawProgress = page.dataset.paymentProgress || '';

    if (rawProgress) {
        try {
            const parsed = JSON.parse(rawProgress);
            if (Array.isArray(parsed)) {
                existingData = parsed;
            }
        } catch (error) {
            console.error('Không đọc được dữ liệu tiến độ thanh toán:', error);
        }
    }

    function addRow(data) {
        rowCount += 1;

        const title = data ? data.title : `Lần ${rowCount}`;
        const amount = data ? formatVND(data.amount) : '';
        const deadline = data ? data.deadline : '';
        const paidAt = data && data.paid_at ? data.paid_at : '';
        const note = data && data.note ? data.note : '';
        const isPaidHtml = data && data.is_paid == '1' ? 'checked' : '';
        const isVatHtml = data && data.is_vat == '1' ? 'checked' : '';

        const row = document.createElement('div');
        row.className = 'payment-row m-b-8';
        row.innerHTML = `
            <input type="text" name="payments[${rowCount}][title]" class="form-control-premium text-sm" value="${escapeHtml(title)}" placeholder="Tiêu đề (VD: Lần 1, Đặt cọc)">
            <input type="text" name="payments[${rowCount}][amount]" class="form-control-premium text-sm font-weight-600 text-apple-blue js-vnd-input" value="${escapeHtml(amount)}" placeholder="Số tiền">
            <input type="date" name="payments[${rowCount}][deadline]" class="form-control-premium text-sm" value="${escapeHtml(deadline)}" title="Thời hạn (Không bắt buộc)">
            <input type="text" name="payments[${rowCount}][note]" class="form-control-premium text-sm" value="${escapeHtml(note)}" placeholder="Ghi chú đợt thanh toán (nếu có)">
            <input type="date" name="payments[${rowCount}][paid_at]" class="form-control-premium text-sm" value="${escapeHtml(paidAt)}" title="Ngày thực thu">
            <div class="case-payment-check">
                <input type="checkbox" name="payments[${rowCount}][is_paid]" value="1" id="paid_${rowCount}" class="case-payment-checkbox" ${isPaidHtml}>
                <label for="paid_${rowCount}" class="text-apple-main case-payment-check-label">Đã thu</label>
            </div>
            <div class="case-payment-check">
                <input type="checkbox" name="payments[${rowCount}][is_vat]" value="1" id="vat_${rowCount}" class="case-payment-checkbox" ${isVatHtml}>
                <label for="vat_${rowCount}" class="text-apple-main case-payment-check-label">Đã xuất VAT</label>
            </div>
            <button type="button" class="btn-secondary-sm text-apple-red case-payment-remove" title="Xóa đợt thanh toán"><i class="fas fa-trash"></i></button>
        `;

        container.appendChild(row);
    }

    if (existingData.length > 0) {
        existingData.forEach(function (item) {
            addRow(item);
        });
    } else {
        addRow(null);
    }

    addBtn.addEventListener('click', function () {
        addRow(null);
    });

    container.addEventListener('input', function (event) {
        if (event.target.classList.contains('js-vnd-input')) {
            event.target.value = formatVND(event.target.value);
        }
    });

    container.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.case-payment-remove');
        if (removeButton) {
            removeButton.closest('.payment-row').remove();
        }
    });
}
