/**
 * CONTACTS MODULE JS - ERP L.A.N
 * Simplified & standardizing with existing project patterns (jQuery style)
 */

$(document).ready(function() {
    console.log('--- CONTACTS JS LOADED ---');
    
    const tableContainer = document.getElementById('contact-table-container');
    const searchInput = document.getElementById('contact-search');
    let searchTimeout;

    // --- Filter & Search Logic ---
    function triggerSearch() {
        console.log('--- TRIGGER SEARCH ---');
        const url = new URL(window.location.href);
        
        const search = $('#contact-search').val();
        const source = $('#source-filter').val();
        const province = $('#province-filter').val();
        const isPrivate = $('#private-filter').val();

        if (search) url.searchParams.set('search', search);
        else url.searchParams.delete('search');

        if (source) url.searchParams.set('source', source);
        else url.searchParams.delete('source');

        if (province) url.searchParams.set('province', province);
        else url.searchParams.delete('province');

        if (isPrivate !== undefined && isPrivate !== '') url.searchParams.set('is_private', isPrivate);
        else url.searchParams.delete('is_private');

        url.searchParams.set('page', 1);

        fetchByUrl(url);
    }

    async function fetchByUrl(url) {
        if (!tableContainer) return;
        
        try {
            console.log('Fetching:', url.toString());
            $('#search-loader').show();
            tableContainer.style.opacity = '0.5';

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const html = await response.text();
            tableContainer.innerHTML = html;
            tableContainer.style.opacity = '1';
            
            // Update URL without reloading
            window.history.pushState(null, '', url);

            // Re-init events for the new table content
            initCheckboxes();
        } catch (err) {
            console.error('Fetch error:', err);
        } finally {
            $('#search-loader').hide();
            tableContainer.style.opacity = '1';
        }
    }

    // Event Listeners
    $('#contact-search').on('input', function() {
        $('#clear-search').toggle(!!this.value);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(triggerSearch, 500);
    });

    $('#clear-search').on('click', function() {
        $('#contact-search').val('').trigger('input');
        $(this).hide();
    });

    $('#btn-reset-filters').on('click', function() {
        console.log('--- RESET FILTERS ---');
        $('#contact-search').val('');
        $('#source-filter').val('');
        $('#province-filter').val('');
        $('#private-filter').val('');
        $('#clear-search').hide();
        triggerSearch();
    });

    $(document).on('change', '#source-filter, #province-filter, #private-filter', function() {
        triggerSearch();
    });

    // Pagination AJAX
    $(document).on('click', '#contact-table-container .pagination a', function(e) {
        e.preventDefault();
        const url = new URL(this.href);
        fetchByUrl(url);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // --- Batch Actions ---
    function initCheckboxes() {
        const $main = $('#check-all-contacts');
        const $subs = $('.contact-checkbox');
        const $batchBar = $('#contact-batch-bar');

        $main.on('change', function() {
            $subs.prop('checked', this.checked);
            updateBatchBar();
        });

        $subs.on('change', updateBatchBar);

        function updateBatchBar() {
            const count = $('.contact-checkbox:checked').length;
            if (count > 0) {
                $batchBar.css('display', 'flex');
                $('#selected-count').text(count);
            } else {
                $batchBar.hide();
            }
        }
    }

    initCheckboxes();
    
    // Initial state
    $('#clear-search').toggle(!!$('#contact-search').val());
});

// --- Modal Functions (Globally accessible) ---
function openContactModal(id = null, data = null) {
    const $modal = $('#contactModal');
    const $form = $('#contact-form');
    const $title = $('#modal-title');

    $form[0].reset();
    
    if (id && data) {
        $title.html('<i class="fas fa-edit"></i> Chỉnh sửa liên hệ');
        $form.attr('action', baseUrl + '/contacts/save/' + id);
        
        // Fill data
        for (let key in data) {
            const $input = $form.find(`[name="${key}"]`);
            if ($input.length) {
                if ($input.attr('type') === 'checkbox') $input.prop('checked', data[key] == 1);
                else $input.val(data[key]);
            }
        }
    } else {
        $title.html('<i class="fas fa-plus-circle"></i> Thêm liên hệ mới');
        $form.attr('action', baseUrl + '/contacts/save');
    }

    $modal.css('display', 'flex');
}

function closeContactModal() {
    $('#contactModal').hide();
}

// Handle Form Submit
$(document).on('submit', '#contact-form', function(e) {
    e.preventDefault();
    const $form = $(this);
    const actionUrl = $form.attr('action');

    $.ajax({
        url: actionUrl,
        method: 'POST',
        data: $form.serialize(),
        success: function(data) {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message || 'Lỗi lưu dữ liệu');
            }
        },
        error: function() {
            alert('Lỗi hệ thống khi lưu dữ liệu');
        }
    });
});

function deleteContact(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa liên hệ này?')) return;

    $.get(baseUrl + '/contacts/delete/' + id, function(data) {
        if (data.status === 'success') {
            location.reload();
        } else {
            alert(data.message || 'Lỗi khi xóa');
        }
    }).fail(function() {
        alert('Lỗi hệ thống khi xóa');
    });
}

function handleBatchAction(status) {
    const selectedIds = $('.contact-checkbox:checked').map(function() { return this.value; }).get();
    if (selectedIds.length === 0) return;

    const confirmMsg = status === 1 
        ? "Gắn cờ PRIVATE cho các liên hệ đã chọn? (Nhân viên sẽ không thấy SĐT)" 
        : "Gỡ cờ PRIVATE cho các liên hệ đã chọn?";
    
    if (!confirm(confirmMsg)) return;

    $.post(baseUrl + '/contacts/toggle-private', {
        ids: selectedIds,
        status: status,
        csrf_test_name: $('meta[name="csrf-token"]').attr('content')
    }, function(data) {
        if (data.status === 'success') {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    });
}
