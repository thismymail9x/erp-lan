/**
 * L.A.N ERP - Quản lý Chuyên cần (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const tableContainer = $('#attendance-table-container');
    const filterForm = $('#attendance-filter-form');
    const ajaxFilters = $('.ajax-filter');

    // 1. Khởi tạo Select2 cho bộ lọc nhân viên
    if ($('#employee-filter').length) {
        $('#employee-filter').select2({
            placeholder: "Chọn nhân viên...",
            allowClear: true,
            width: '100%'
        });
    }

    // 2. Lắng nghe sự kiện thay đổi trên các ô lọc
    $(document).on('change', '.ajax-filter', function() {
        // Nếu thay đổi loại hiển thị (Ngày/Tháng), ta cần submit form thực sự 
        // để PHP render lại khung giao diện (vì structure filter bar thay đổi)
        if ($(this).attr('id') === 'view-type') {
            filterForm.submit();
            return;
        }

        // Với các bộ lọc khác, thực hiện AJAX
        triggerFilter();
    });

    // 3. Xử lý sắp xếp (Sorting) qua AJAX
    $(document).on('click', '.sort-link', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        fetchUpdate(url);
    });

    /**
     * Thu thập dữ liệu từ form và kích hoạt tải lại bảng.
     */
    function triggerFilter() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        fetchUpdate(finalUrl);
        
        // Cập nhật URL trên browser (không reload) để user copy link được
        window.history.pushState({path: finalUrl}, '', finalUrl);
    }

    /**
     * Hàm trung tâm thực hiện gọi AJAX và cập nhật vùng chứa bảng.
     */
    async function fetchUpdate(url) {
        // Hiệu ứng loading mờ bảng
        tableContainer.css('opacity', '0.5');
        
        // Cập nhật link Xuất Excel để khớp với bộ lọc tháng hiện tại
        const monthVal = $('[name="month"]').val();
        const exportBtn = $('.btn-filter-secondary:contains("Xuất Excel")');
        if (exportBtn.length && monthVal) {
            const exportUrl = exportBtn.attr('href').split('?')[0] + '?month=' + monthVal;
            exportBtn.attr('href', exportUrl);
        }
        
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            
            tableContainer.html(html);
            
            // Re-initialize checkbox listeners in the new HTML
            initCheckboxListeners();
            
        } catch (err) {
            console.error('Lỗi filter AJAX:', err);
        } finally {
            tableContainer.css('opacity', '1');
        }
    }

    /**
     * Khởi tạo lại các listener cho tính năng Bulk Actions sau khi nạp HTML mới.
     */
    function initCheckboxListeners() {
        const checkAll = document.getElementById('check-all');
        const recordChecks = document.querySelectorAll('.record-check');
        const bulkBar = document.getElementById('bulk-bar');
        const selectedCount = document.getElementById('selected-count');

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                recordChecks.forEach(cb => cb.checked = checkAll.checked);
                updateBar();
            });
        }

        recordChecks.forEach(cb => {
            cb.addEventListener('change', updateBar);
        });

        function updateBar() {
            const checked = document.querySelectorAll('.record-check:checked');
            if (bulkBar) {
                if (checked.length > 0) {
                    bulkBar.style.display = 'flex';
                    if (selectedCount) selectedCount.innerText = checked.length + ' mục đã chọn';
                } else {
                    bulkBar.style.display = 'none';
                }
            }
        }
    }

    /**
     * Thực hiện cập nhật trạng thái hàng loạt.
     */
    window.applyBulkUpdate = async function() {
        const status = document.getElementById('bulk-status').value;
        if (!status) return alert('Vui lòng chọn trạng thái mới.');

        const ids = Array.from(document.querySelectorAll('.record-check:checked')).map(cb => cb.value);
        if (!confirm('Hệ thống sẽ cập nhật trạng thái cho ' + ids.length + ' nhân viên được chọn. Tiếp tục?')) return;

        try {
            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            formData.append('status', status);

            const response = await fetch('bulk-update', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const res = await response.json();
            if (res.code === 0) {
                // Sau khi bulk update thành công, ta chỉ cần refresh bảng AJAX
                triggerFilter();
                // Ẩn bar bulk
                if (document.getElementById('bulk-bar')) document.getElementById('bulk-bar').style.display = 'none';
                if (document.getElementById('check-all')) document.getElementById('check-all').checked = false;
            } else {
                alert('Lỗi: ' + res.error);
            }
        } catch (err) {
            alert('Lỗi kết nối máy chủ.');
        }
    }

    /**
     * Xem trước hình ảnh.
     */
    window.previewImage = function(src) {
        if (src) window.open(src, '_blank', 'noopener,noreferrer');
    }

    // Khởi tạo lần đầu
    initCheckboxListeners();
});
