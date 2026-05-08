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

    // --- XỬ LÝ CHỌN HÀNG LOẠT (BULK LOGIC) ---
    const bulkBar = $('#att-bulk-bar');
    const selectedCountLabel = $('#selected-count');

    // Chọn tất cả
    $(document).on('change', '#check-all', function() {
        $('.record-check').prop('checked', $(this).prop('checked'));
        updateBulkBar();
    });

    // Chọn lẻ
    $(document).on('change', '.record-check', function() {
        updateBulkBar();
    });

    function updateBulkBar() {
        const selected = $('.record-check:checked');
        const count = selected.length;
        
        if (count > 0) {
            selectedCountLabel.text(count + ' mục đã chọn');
            bulkBar.fadeIn(200).css('display', 'flex');
        } else {
            bulkBar.fadeOut(200);
            $('#check-all').prop('checked', false);
        }
    }

    // Thực thi cập nhật hàng loạt
    window.bulkUpdateAttendance = async function() {
        const selected = $('.record-check:checked');
        const ids = [];
        selected.each(function() { ids.push($(this).val()); });
        
        const status = $('#bulk-status').val();
        if (!status) {
            alert('Vui lòng chọn trạng thái mới!');
            return;
        }

        if (!confirm('Bạn có chắc chắn muốn cập nhật trạng thái cho ' + ids.length + ' bản ghi này?')) return;

        try {
            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            formData.append('status', status);

            const response = await fetch(baseUrl + '/attendance/bulk-update', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            if (result.code === 0) {
                // Thành công: Refresh lại bảng
                triggerAjax();
                alert(result.message);
            } else {
                alert('Lỗi: ' + result.error);
            }
        } catch (err) {
            console.error('Bulk Update error:', err);
            alert('Lỗi kết nối khi cập nhật hàng loạt.');
        }
    }

    // Tiện ích xem ảnh minh chứng
    window.previewImage = function(src) {
        if (src) window.open(src, '_blank', 'noopener,noreferrer');
    }
});
