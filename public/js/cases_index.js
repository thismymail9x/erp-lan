/**
 * L.A.N ERP - Quản lý Danh sách Vụ việc
 */

$(document).ready(function() {
    const searchInput = document.getElementById('case-search');
    const lawyerFilter = document.getElementById('lawyer-filter');
    const tableContainer = document.getElementById('cases-table-container');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('search', this.value);
                url.searchParams.set('page', 1);
                fetchByUrl(url);
            }, 300);
        });
    }

    if (lawyerFilter) {
        // Áp dụng Select2 cho bộ lọc nhân viên (Hỗ trợ chọn NHIỀU người cùng lúc)
        $(lawyerFilter).select2({
            placeholder: "Lọc theo nhân viên phụ trách...",
            allowClear: true,
            width: '100%'
        }).on('change', function() {
            const url = new URL(window.location.href);
            const selectedValues = $(this).val(); // Đây là 1 mảng
            
            // Xóa sạch các param cũ để nạp mảng mới
            url.searchParams.delete('lawyer_id');
            url.searchParams.delete('lawyer_id[]'); // Xóa cả bản cũ có dấu ngoặc
            
            if (selectedValues && selectedValues.length > 0) {
                selectedValues.forEach(val => {
                    url.searchParams.append('lawyer_id', val);
                });
            }
            
            url.searchParams.set('page', 1);
            fetchByUrl(url);
        });
    }

    if (tableContainer) {
        tableContainer.addEventListener('click', function(e) {
            const link = e.target.closest('.pagination a, .sort-link');
            if (link) {
                e.preventDefault();
                const url = new URL(link.href);
                fetchByUrl(url);
            }
        });
    }

    async function fetchByUrl(url) {
        try {
            tableContainer.style.opacity = '0.5';
            
            if (!url.searchParams.has('search') && searchInput) {
                url.searchParams.set('search', searchInput.value);
            }
            
            // Đồng bộ trạng thái Filter nhân viên vào URL khi Fetch (Tránh mất filter khi bấm phân trang)
            if (!url.searchParams.has('lawyer_id[]') && lawyerFilter) {
                const values = $(lawyerFilter).val();
                if (values && values.length > 0) {
                    values.forEach(v => url.searchParams.append('lawyer_id[]', v));
                }
            }

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const html = await response.text();
            tableContainer.innerHTML = html;
            tableContainer.style.opacity = '1';
            window.history.pushState(null, '', url);
        } catch (err) {
            console.error('Lỗi khi tải dữ liệu vụ việc:', err);
            tableContainer.style.opacity = '1';
        }
    }
});

// Quick Tagging for Cases
function openQuickTag(id, name) {
    if (document.getElementById('quickTagEntityId')) {
        document.getElementById('quickTagEntityId').value = id;
        document.getElementById('quickTagName').innerText = name;
        document.getElementById('quickTagModal').style.display = 'flex';
        
        $.get(baseUrl + '/tags/get-entity-tags', { entity_id: id, entity_type: 'cases' }, function(tags) {
            const currentIds = tags.map(t => t.id);
            $('#quickTagSelect').val(currentIds).trigger('change');
        });
    }
}

$(document).ready(function() {
    if ($('#quickTagSelect').length) {
        $('#quickTagSelect').select2({
            placeholder: "Chọn nhãn dán...",
            allowClear: true
        });

        $('#quickTagForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const entityId = $('#quickTagEntityId').val();
            
            $.post(baseUrl + '/tags/update-entity-tags', formData, function(resp) {
                if (resp.status === 'success') {
                    let tagsHtml = '';
                    resp.tags.forEach(t => {
                        tagsHtml += `<a href="${baseUrl}/tags/show/${t.id}" class="tag-badge-premium" style="background-color: ${t.color}15; color: ${t.color}; border: 1px solid ${t.color}30; text-decoration: none;"><i class="fas fa-tag m-r-4" style="font-size: 8px;"></i> ${t.name}</a>`;
                    });
                    $('#tags-row-' + entityId).html(tagsHtml);
                    document.getElementById('quickTagModal').style.display = 'none';
                }
            });
        });
    }
});
