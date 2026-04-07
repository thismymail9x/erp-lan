/**
 * L.A.N ERP - Customer Update (Edit)
 */
$(document).ready(function() {
    if ($('.select2-tags').length) {
        $('.select2-tags').select2({
            placeholder: "Chọn nhãn dán...",
            allowClear: true,
            width: 'resolve'
        });
    }

    // --- KIỂM TRA TRÙNG LẶP TRONG KHI SỬA ---
    async function checkDuplicate(field, value, alertId) {
        if (!value) return;
        const customerId = $('form').attr('action').split('/').pop(); // Lấy ID hiện tại để bỏ qua chính nó
        const alertDiv = document.getElementById(alertId);
        if (!alertDiv) return;

        try {
            const response = await fetch(`${baseUrl}/customers/check-duplicate?${field}=${value}&exclude_id=${customerId}`);
            const result = await response.json();
            
            if (result.exists) {
                const dup = result.duplicates[field];
                alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> Cảnh báo: Thông tin này đang trùng với khách hàng <a href="${baseUrl}/customers/show/${dup.id}" target="_blank" style="color: inherit; text-decoration: underline; font-weight: 600;">${dup.code} - ${dup.name}</a>`;
                alertDiv.style.display = 'block';
                alertDiv.style.color = 'var(--apple-red)';
            } else {
                alertDiv.style.display = 'none';
            }
        } catch(e) {
            console.error("Lỗi khi kiểm tra trùng lặp:", e);
        }
    }

    // Gán sự kiện cho các ô nhập quan trọng
    $('input[name="phone"]').on('blur', function() {
        if (!document.getElementById('phone_alert')) {
             $(this).after('<div id="phone_alert" style="font-size: 11px; margin-top: 5px; display: none;"></div>');
        }
        checkDuplicate('phone', $(this).val(), 'phone_alert');
    });

    $('input[name="identity_number"]').on('blur', function() {
        if (!document.getElementById('id_alert')) {
             $(this).after('<div id="id_alert" style="font-size: 11px; margin-top: 5px; display: none;"></div>');
        }
        checkDuplicate('identity_number', $(this).val(), 'id_alert');
    });
});
