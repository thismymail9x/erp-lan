/**
 * L.A.N ERP - Lịch sử Chấm công Cá nhân (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const tableContainer = $('#history-table-container');
    const filterForm = $('.filter-form');

    // Lắng nghe thay đổi trên tháng
    $(document).on('change', 'input[name="month"]', function() {
        triggerAjax();
    });

    async function triggerAjax() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        tableContainer.css('opacity', '0.5');
        
        try {
            const response = await fetch(finalUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            tableContainer.html(html);
            
            // Cập nhật URL trình duyệt
            window.history.pushState({path: finalUrl}, '', finalUrl);
            
        } catch (err) {
            console.error('Lỗi history AJAX:', err);
        } finally {
            tableContainer.css('opacity', '1');
        }
    }

    // Tiện ích xem ảnh minh chứng
    window.previewImage = function(src) {
        if (src) window.open(src, '_blank', 'noopener,noreferrer');
    }
});
