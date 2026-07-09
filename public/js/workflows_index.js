/**
 * L.A.N ERP - Quản lý Quy trình mẫu (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const gridContainer = $('#workflow-grid-container');
    const filterForm = $('#workflow-filter-form');
    let searchTimeout = null;

    $(document).on('change', '.ajax-filter', function() {
        triggerFilter();
    });

    $(document).on('input', '.ajax-filter-search', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            triggerFilter();
        }, 500);
    });

    $(document).on('click', '.btn-filter-secondary', function(e) {
        if ($(this).attr('href') === filterForm.attr('action')) {
            e.preventDefault();
            filterForm[0].reset();
            $('.ajax-filter-search').val('');
            triggerFilter();
        }
    });

    function triggerFilter() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        fetchUpdate(finalUrl);
        window.history.pushState({path: finalUrl}, '', finalUrl);
    }

    async function fetchUpdate(url) {
        gridContainer.css('opacity', '0.5');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            gridContainer.html(html);
        } catch (err) {
            console.error('Lỗi filter quy trình AJAX:', err);
        } finally {
            gridContainer.css('opacity', '1');
        }
    }
});