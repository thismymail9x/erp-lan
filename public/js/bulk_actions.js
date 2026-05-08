/**
 * L.A.N ERP - Hệ thống thao tác hàng loạt (Bulk Actions)
 * Áp dụng chung cho toàn bộ Index tables của hệ thống.
 * Tuân thủ Quy tắc 11: Không viết JS inline.
 */

$(document).ready(function() {
    console.log('Bulk Actions Module Loaded');

    /**
     * 1. Xử lý logic Checkbox (Dùng Event Delegation hỗ trợ AJAX)
     */
    $(document).on('change', '#check-all', function() {
        const isChecked = $(this).prop('checked');
        const table = $(this).closest('table');
        table.find('.record-check').prop('checked', isChecked);
        updateBulkBar(table);
    });

    $(document).on('change', '.record-check', function() {
        const table = $(this).closest('table');
        const checkAll = table.find('#check-all');
        const total = table.find('.record-check').length;
        const checked = table.find('.record-check:checked').length;
        
        if (checkAll.length) {
            checkAll.prop('checked', total === checked);
        }
        
        updateBulkBar(table);
    });

    /**
     * Cập nhật hiển thị của thanh công cụ hàng loạt (Floating Bar)
     */
    function updateBulkBar() {
        const bulkBar = $('.bulk-actions-bar');
        const selectedCountEl = bulkBar.find('#selected-count');
        const checkedCount = $('.record-check:checked').length;
        
        if (bulkBar.length) {
            if (checkedCount > 0) {
                bulkBar.css('display', 'flex'); // Flex instead of show() to ensure centering works
                if (selectedCountEl.length) {
                    selectedCountEl.text(checkedCount + ' mục đã chọn');
                }
            } else {
                bulkBar.hide();
            }
        }
    }

    // Lắng nghe sự thay đổi của checkbox sau khi updateBar() đã chạy (hoặc dùng delegation)
    $(document).on('change', '#check-all, .record-check', function() {
        // ... (Logic check-all và check-con đã xử lý ở trên qua event delegation)
        updateBulkBar();
    });

    /**
     * 2. Xử lý nút Xóa hàng loạt
     */
    window.bulkDelete = async function(customModule = null) {
        const checkedBoxes = $('.record-check:checked');
        const ids = checkedBoxes.map(function() { return $(this).val(); }).get();
        if (ids.length === 0) return;
        
        if (!confirm(`CẢNH BÁO: Hệ thống sẽ xóa vĩnh viễn ${ids.length} mục đã chọn. Thao tác này KHÔNG THỂ khôi phục. Bạn chắc chắn chứ?`)) {
            return;
        }

        let moduleName = customModule;
        if (!moduleName) {
            const pathParts = window.location.pathname.split('/').filter(p => p !== '');
            moduleName = pathParts[0] || '';
        }

        const bulkUrl = `${window.location.origin}/${moduleName}/bulk-delete`;

        try {
            const tokenName = csrfToken; // Tên trường CSRF từ layout
            const tokenHash = csrfHash;   // Giá trị băm từ layout
            
            const response = await fetch(bulkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    [tokenName]: tokenHash, // CSRF protection
                    'ids': ids
                }).toString() + ids.map(id => `&ids[]=${id}`).join('')
            });
            
            const result = await response.json();
            
            if (result.status === 'success' || result.code === 0) {
                location.reload();
            } else {
                alert('Lỗi: ' + (result.message || 'Server từ chối yêu cầu.'));
            }
        } catch (error) {
            console.error('Bulk Actions Error:', error);
            alert('Lỗi kết nối máy chủ hoặc module này chưa được hỗ trợ xóa hàng loạt.');
        }
    };
});
