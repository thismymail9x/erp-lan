/**
 * L.A.N ERP - Quản lý Khách hàng (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const tableContainer = $('#customer-table-container');
    const filterForm = $('#customer-filter-form');
    let searchTimeout = null;

    // 1. Khởi tạo Select2 cho bộ lọc (nếu có) và Modal gắn nhãn
    if ($('#quickTagSelect').length) {
        $('#quickTagSelect').select2({
            placeholder: "Chọn các nhãn...",
            allowClear: true,
            width: '100%'
        });
    }

    $(document).on('change', '.ajax-filter', function() {
        triggerFilter();
    });

    // 2. Lắng nghe sự kiện trên ô tìm kiếm (Debounce 500ms)
    $(document).on('input', '.ajax-filter-search', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            triggerFilter();
        }, 500);
    });

    // 3. Xử lý xóa lọc
    $(document).on('click', '.btn-filter-secondary', function(e) {
        if ($(this).attr('href') === filterForm.attr('action')) {
            e.preventDefault();
            filterForm[0].reset();
            // Reset q input manually as reset() might not trigger input event
            $('.ajax-filter-search').val('');
            triggerFilter();
        }
    });

    /**
     * Thu thập dữ liệu và gọi AJAX.
     */
    function triggerFilter() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        fetchUpdate(finalUrl);
        window.history.pushState({path: finalUrl}, '', finalUrl);
    }

    /**
     * Hàm fetch và cập nhật nội dung bảng.
     */
    async function fetchUpdate(url) {
        tableContainer.css('opacity', '0.5');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            tableContainer.html(html);
        } catch (err) {
            console.error('Lỗi filter khách hàng AJAX:', err);
        } finally {
            tableContainer.css('opacity', '1');
        }
    }
});

/**
 * Xử lý Gắn nhãn nhanh (Quick Tag) - Duy trì từ code cũ
 */
function openQuickTag(id, name, tagIds = []) {
    document.getElementById('quickTagEntityId').value = id;
    document.getElementById('quickTagName').innerText = name;
    document.getElementById('quickTagModal').style.display = 'flex';
    
    // Nếu có Select2 cho multiple tags, điền các nhãn hiện có
    if (typeof $ !== 'undefined' && $('#quickTagSelect').hasClass('select2-hidden-accessible')) {
        $('#quickTagSelect').val(tagIds).trigger('change');
    }
}

// Xử lý gửi form gắn nhãn
$(document).on('submit', '#quickTagForm', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const entityId = $('#quickTagEntityId').val();
    
    try {
        const response = await fetch('/cases/update-tags/' + entityId, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();
        if (res.code === 0) {
            // Cập nhật lại hàng tags trên UI mà không reload
            updateTagsRow(entityId, res.tags);
            $('#quickTagModal').hide();
        } else {
            alert('Lỗi: ' + res.error);
        }
    } catch (err) {
        alert('Lỗi kết nối máy chủ khi gắn nhãn.');
    }
});

function updateTagsRow(entityId, tags) {
    const row = $('#tags-row-' + entityId);
    if (row.length) {
        let html = '';
        const tagIds = [];
        tags.forEach(t => {
            tagIds.push(parseInt(t.id));
            html += `<a href="/tags/show/${t.id}" class="tag-badge-premium" style="background-color: ${t.color}15; color: ${t.color}; border: 1px solid ${t.color}30; font-size: 9px; padding: 1px 6px; text-decoration: none;">${t.name}</a> `;
        });
        row.html(html);

        // CẬP NHẬT QUAN TRỌNG: Cập nhật lại thuộc tính onclick của nút bấm để lần click sau có dữ liệu mới nhất
        const tagBtn = row.closest('tr').find('.text-tag');
        if (tagBtn.length) {
            const customerName = $('#quickTagName').text(); // Lấy lại tên đang hiển thị
            const newOnClick = `openQuickTag(${entityId}, "${customerName}", ${JSON.stringify(tagIds)})`;
            tagBtn.attr('onclick', newOnClick);
        }
    }
}
