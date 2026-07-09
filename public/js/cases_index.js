/**
 * L.A.N ERP - Quản lý Danh sách Vụ việc
 */

$(document).ready(function () {
    const searchInput = document.getElementById('case-search');
    const lawyerFilter = document.getElementById('lawyer-filter');
    const statusFilter = document.getElementById('status-filter');
    const tableContainer = document.getElementById('cases-table-container');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('search', this.value);

                // Đồng bộ Tag và Status vào URL khi gõ tìm kiếm
                const tagFilter = document.getElementById('tag-filter');
                if (tagFilter && tagFilter.value) url.searchParams.set('tag_id', tagFilter.value);

                const statusFilter = document.getElementById('status-filter');
                if (statusFilter && statusFilter.value) url.searchParams.set('status', statusFilter.value);

                url.searchParams.set('page', 1);
                fetchByUrl(url);
            }, 500);
        });

        // Hỗ trợ nhấn Enter để tìm kiếm ngay lập tức
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const url = new URL(window.location.href);
                url.searchParams.set('search', this.value);
                url.searchParams.set('page', 1);
                fetchByUrl(url);
            }
        });
    }

    if (lawyerFilter) {
        // Áp dụng Select2 cho bộ lọc nhân viên (Hỗ trợ chọn NHIỀU người cùng lúc)
        $(lawyerFilter).select2({
            placeholder: "Lọc theo nhân viên phụ trách...",
            allowClear: true,
            width: '100%'
        }).on('change', function () {
            const url = new URL(window.location.href);
            const selectedValues = $(this).val();

            // Đồng bộ bộ lọc trạng thái nếu có
            const statusVal = document.getElementById('status-filter') ? document.getElementById('status-filter').value : '';
            if (statusVal) url.searchParams.set('status', statusVal);
            else url.searchParams.delete('status');

            // Xóa sạch các param cũ để nạp mảng mới
            url.searchParams.delete('lawyer_id');
            url.searchParams.delete('lawyer_id[]');

            if (selectedValues && selectedValues.length > 0) {
                selectedValues.forEach(val => {
                    url.searchParams.append('lawyer_id[]', val); // Dùng [] đồng nhất
                });
            }

            url.searchParams.set('page', 1);
            fetchByUrl(url);
        });
    }

    // Sử dụng bộ bắt sự kiện của jQuery để đảm bảo tính ổn định cao nhất
    $(document).on('change', '#status-filter', function () {
        console.log('--- TRIGGER FILTER STATUS: ' + this.value + ' ---');
        const url = new URL(window.location.href);
        if (this.value) {
            url.searchParams.set('status', this.value);
        } else {
            url.searchParams.delete('status');
        }
        url.searchParams.set('page', 1);
        fetchByUrl(url);
    });

    $(document).on('change', '#tag-filter', function () {
        const url = new URL(window.location.href);
        if (this.value) {
            url.searchParams.set('tag_id', this.value);
        } else {
            url.searchParams.delete('tag_id');
        }
        url.searchParams.set('page', 1);
        fetchByUrl(url);
    });

    $(document).on('change', '#month-year-filter', function() {
        const url = new URL(window.location.href);
        if (this.value) {
            const parts = this.value.split('-');
            url.searchParams.set('year', parts[0]);
            url.searchParams.set('month', parts[1]);
        } else {
            url.searchParams.delete('year');
            url.searchParams.delete('month');
        }
        url.searchParams.set('page', 1);
        fetchByUrl(url); 
    });

    if (tableContainer) {
        tableContainer.addEventListener('click', function (e) {
            const link = e.target.closest('.pagination a, .sort-link');
            if (link) {
                e.preventDefault();
                const url = new URL(link.href);
                fetchByUrl(url);
            }
        });
    }

    $(document).on('click', '.case-stat-filter', function () {
        filterByStat($(this).data('status') || '');
    });

    $(document).on('click', '.quick-tag-close', function () {
        $('#quickTagModal').removeClass('is-open');
    });

    $(document).on('click', '.js-open-quick-tag', function () {
        openQuickTag($(this).data('case-id'), $(this).data('case-name'));
    });

    $(document).on('click', '.js-confirm-link', function (e) {
        const message = $(this).data('confirm-message') || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
        if (!confirm(message)) {
            e.preventDefault();
        }
    });

    $(document).on('click', '.js-case-bulk-delete', function () {
        const selectedIds = $('.record-check:checked').map(function () {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một hồ sơ.');
            return;
        }

        if (!confirm('Bạn có chắc chắn muốn xóa hàng loạt các hồ sơ đã chọn?')) {
            return;
        }

        $.post(baseUrl + '/cases/bulk-delete', { ids: selectedIds }, function (resp) {
            if (resp.status === 'success') {
                location.reload();
            } else {
                alert(resp.message || 'Không thể xóa hàng loạt.');
            }
        });
    });

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

            // Đồng bộ trạng thái Filter Status vào URL khi Fetch
            if (!url.searchParams.has('status') && statusFilter) {
                url.searchParams.set('status', statusFilter.value);
            }

            // Đồng bộ trạng thái Filter Tag vào URL khi Fetch
            const tagFilter = document.getElementById('tag-filter');
            if (!url.searchParams.has('tag_id') && tagFilter) {
                url.searchParams.set('tag_id', tagFilter.value);
            }

            // Đồng bộ tháng/năm vào URL
            const monthYearFilter = document.getElementById('month-year-filter');
            if (!url.searchParams.has('month') && monthYearFilter && monthYearFilter.value) {
                const parts = monthYearFilter.value.split('-');
                url.searchParams.set('year', parts[0]);
                url.searchParams.set('month', parts[1]);
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

// Quick Filtering from Stat Cards
function filterByStat(statusValue) {
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.value = statusValue;

        // Kích hoạt sự kiện change thông qua jQuery để đảm bảo tính tương thích
        $(statusFilter).trigger('change');

        // Cuộn xuống bảng
        const table = document.getElementById('cases-table-container');
        if (table) {
            table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

// Quick Tagging for Cases
function openQuickTag(id, name) {
    if (document.getElementById('quickTagEntityId')) {
        document.getElementById('quickTagEntityId').value = id;
        document.getElementById('quickTagName').innerText = name;
        document.getElementById('quickTagModal').classList.add('is-open');

        $.get(baseUrl + '/tags/get-entity-tags', { entity_id: id, entity_type: 'cases' }, function (tags) {
            const currentIds = tags.map(t => t.id);
            $('#quickTagSelect').val(currentIds).trigger('change');
        });
    }
}

$(document).ready(function () {
    if ($('#quickTagSelect').length) {
        $('#quickTagSelect').select2({
            placeholder: "Chọn nhãn dán...",
            allowClear: true
        });

        $('#quickTagForm').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const entityId = $('#quickTagEntityId').val();

            $.post(baseUrl + '/tags/update-entity-tags', formData, function (resp) {
                if (resp.status === 'success') {
                    let tagsHtml = '';
                    resp.tags.forEach(t => {
                        tagsHtml += `<a href="${baseUrl}/tags/show/${t.id}" class="tag-badge-premium" style="background-color: ${t.color}15; color: ${t.color}; border: 1px solid ${t.color}30; text-decoration: none;"><i class="fas fa-tag m-r-4" style="font-size: 8px;"></i> ${t.name}</a>`;
                    });
                    $('#tags-row-' + entityId).html(tagsHtml);
                    document.getElementById('quickTagModal').classList.remove('is-open');
                }
            });
        });
    }
});
