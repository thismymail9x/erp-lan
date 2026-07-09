/**
 * L.A.N ERP - Qu?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n l?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ Kh?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ch h?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const tableContainer = $('#customer-table-container');
    const filterForm = $('#customer-filter-form');
    let searchTimeout = null;

    // 1. Kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?.ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i t?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½o Select2 cho b?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½??zÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ l?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½c (n?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½u c?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½) v?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ Modal g?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n nh?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n
    if ($('#quickTagSelect').length) {
        $('#quickTagSelect').select2({
            placeholder: "Ch\u1ecdn c\u00e1c nh\u00e3n...",
            allowClear: true,
            width: '100%'
        });
    }

    $(document).on('change', '.ajax-filter', function() {
        triggerFilter();
    });

    // SÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¯p xÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¿p ?"??~ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?"?ng qua ti?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½u ?"??~ÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â cÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?"?t AJAX
    $(document).on('click', '.sortable-header', function() {
        const sort = $(this).data('sort');
        const order = $(this).data('order');

        $('#filter-sort').val(sort);
        $('#filter-order').val(order);

        triggerFilter();
    });

    // 2. L?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng nghe s?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ ki?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,?ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n tr?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n ?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ t?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½m ki?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½m (Debounce 500ms)
    $(document).on('input', '.ajax-filter-search', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            triggerFilter();
        }, 500);
    });

    // 3. X?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ l?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ x?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½a l?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½c
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
     * Thu th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½p d?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ li?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,?ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½u v?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ g?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i AJAX.
     */
    function triggerFilter() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;

        fetchUpdate(finalUrl);
        window.history.pushState({path: finalUrl}, '', finalUrl);
    }

    /**
     * H?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½m fetch v?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½p nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½t n?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½??zÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i dung b?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng.
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
            console.error('L\u1ed7i filter kh\u00e1ch h\u00e0ng AJAX:', err);
        } finally {
            tableContainer.css('opacity', '1');
        }
    }
});

/**
 * X? l?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ G?n nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n nhanh (Quick Tag) - Duy tr?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ t? code cu
 */
function openQuickTag(id, name, tagIds = []) {
    document.getElementById('quickTagEntityId').value = id;
    document.getElementById('quickTagName').innerText = name;
    document.getElementById('quickTagModal').style.display = 'flex';

    // N?u c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ Select2 cho multiple tags, di?n c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½c nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n hi?n c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½
    if (typeof $ !== 'undefined' && $('#quickTagSelect').hasClass('select2-hidden-accessible')) {
        $('#quickTagSelect').val(tagIds).trigger('change');
    }
}

// X?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ l?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ g?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i form g?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n nh?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n
$(document).on('submit', '#quickTagForm', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const entityId = $('#quickTagEntityId').val();

    try {
        const response = await fetch(baseUrl + 'cases/update-tags/' + entityId, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();
        if (res.code === 0) {
            // C?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½p nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½t l?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i h?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng tags tr?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n UI m?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ kh?f?'?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng reload
            updateTagsRow(entityId, res.tags);
            $('#quickTagModal').hide();
        } else {
            alert('L\u1ed7i: ' + res.error);
        }
    } catch (err) {
        alert('L\u1ed7i k\u1ebft n\u1ed1i m\u00e1y ch\u1ee7 khi g\u1eafn nh\u00e3n.');
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

        // C?P NH?T QUAN TR?NG: C?p nh?t l?i thu?c t?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh onclick c?a n?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½t b?m d? l?n click sau c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ d? li?u m?i nh?t
        const tagBtn = row.closest('tr').find('.text-tag');
        if (tagBtn.length) {
            const customerName = $('#quickTagName').text();
            const newOnClick = `openQuickTag(${entityId}, "${customerName}", ${JSON.stringify(tagIds)})`;
            tagBtn.attr('onclick', newOnClick);
        }
    }
}

/**
 * THAO T?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½C AJAX INLINE: Thay ?"??~ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½???i Nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n sÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â± phÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¥ tr?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ch ch?"?'m s?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½c t??ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ vÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥n trÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â±c tiÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¿p tÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â« danh s?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ch
 */
$(document).ready(function() {
    // 1. K?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ch ho?t chuy?n d?i t? text hi?n th? th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh select dropdown khi:
    // - ?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½p click (dblclick) v?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½o t?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n d?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½
    // - Click don (click) v?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½o ch? "Tr?ng" (chua g?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n)
    $(document).on('dblclick click', '.clickable-care-staff', function(e) {
        // N?u click don nhung KH?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½NG ph?i l?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ ch? "Tr?ng" th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ b? qua (ch? dblclick)
        if (e.type === 'click' && !$(this).find('.text-muted').length) {
            return;
        }

        const wrapper = $(this).closest('.care-staff-display-wrapper');
        const displaySpan = $(this);
        const select = wrapper.find('.care-staff-select-inline');

        // ?n nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n ch?, hi?n th? select dropdown
        displaySpan.hide();
        select.show().focus();
    });

    // 2. T? d?ng g?i AJAX c?p nh?t khi ngu?i d?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng thay d?i l?a ch?n
    $(document).on('change', '.care-staff-select-inline', async function() {
        const select = $(this);
        const wrapper = select.closest('.care-staff-display-wrapper');
        const displaySpan = wrapper.find('.care-staff-display-name');
        const cell = select.closest('.care-staff-cell');
        const customerId = cell.data('customer-id');
        const oldStaffId = cell.data('current-staff-id');
        const newStaffId = select.val();

        // ?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh d?u select dang trong tr?ng th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i c?p nh?t d? kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng b? ?n b?i s? ki?n blur
        select.addClass('updating');
        // Kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½a select d? tr?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh spam click khi dang g?i request
        select.attr('disabled', true);
        wrapper.css('opacity', '0.5');

        try {
            // L?y token CSRF n?u c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ trong form
            const csrfToken = $('input[name="csrf_test_name"]').val() || '';
            const bodyParams = new URLSearchParams({
                'assigned_care_staff_id': newStaffId
            });
            if (csrfToken) {
                bodyParams.append('csrf_test_name', csrfToken);
            }

            const response = await fetch(baseUrl + 'customers/update-care-staff/' + customerId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: bodyParams
            });
            const res = await response.json();

            if (res.code === 0) {
                // C?p nh?t thu?c t?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh ID hi?n t?i d? d?i chi?u l?n sau
                cell.data('current-staff-id', newStaffId);

                // C?p nh?t l?i nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n text hi?n th?
                if (res.care_staff_name) {
                    displaySpan.html(`<span class="text-apple-main"><i class="fas fa-user-shield text-apple-blue m-r-5"></i>${res.care_staff_name}</span>`);
                } else {
                    displaySpan.html(`<span class="text-muted italic text-xs"><i class="fas fa-plus-circle text-muted m-r-5"></i>Tr\u1ed1ng</span>`);
                }

                // Hi?u ?ng micro-animation: Highlight m?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½u xanh l?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ d?u nh? d? b?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½o th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng
                cell.css('background-color', 'rgba(52, 199, 89, 0.15)');
                setTimeout(() => {
                    cell.css('transition', 'background-color 0.5s ease');
                    cell.css('background-color', '');
                }, 1000);
            } else {
                alert('Kh\u00f4ng th\u1ec3 c\u1eadp nh\u1eadt: ' + res.message);
                // Kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i ph?c l?i gi?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ tr? cu c?a dropdown
                select.val(oldStaffId);
            }
        } catch (err) {
            console.error('L\u1ed7i AJAX:', err);
            alert('L\u1ed7i k\u1ebft n\u1ed1i m\u00e1y ch\u1ee7 khi c\u1eadp nh\u1eadt nh\u00e2n s\u1ef1 t\u01b0 v\u1ea5n.');
            select.val(oldStaffId);
        } finally {
            select.removeClass('updating');
            select.removeAttr('disabled');
            wrapper.css('opacity', '1');
            select.hide();
            displaySpan.show();
        }
    });

    // 3. ?n dropdown quay l?i text hi?n th? khi click ra ngo?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i m?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng d?i d? li?u
    $(document).on('blur', '.care-staff-select-inline', function() {
        const select = $(this);
        // N?u dang trong qu?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ tr?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh AJAX g?i d? li?u (updating), b? qua kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng ?n ? s? ki?n blur n?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½y
        if (select.hasClass('updating')) {
            return;
        }

        const wrapper = select.closest('.care-staff-display-wrapper');
        const displaySpan = wrapper.find('.care-staff-display-name');

        setTimeout(() => {
            if (!select.hasClass('updating')) {
                select.hide();
                displaySpan.show();
            }
        }, 150); // Timeout ng?n d? s? ki?n 'change' k?p ch?y tru?c khi blur ?n
    });

    // 4. Nh?n ph?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½m Escape (ESC) d? h?y ch?nh s?a t?c th?i
    $(document).on('keyup', '.care-staff-select-inline', function(e) {
        if (e.key === 'Escape') {
            const select = $(this);
            const wrapper = select.closest('.care-staff-display-wrapper');
            const displaySpan = wrapper.find('.care-staff-display-name');

            select.hide();
            displaySpan.show();
        }
    });

    // ==========================================================
    // THAO T?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½C AJAX INLINE: Thay ?"??~ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½???i TrÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¡ng th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i T??ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ vÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥n/SLA trÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â±c tiÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¿p tÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â« danh s?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ch KH
    // ==========================================================

    // 1. K?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ch ho?t chuy?n d?i t? badge hi?n th? th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh select dropdown khi d?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½p click
    $(document).on('dblclick click', '.clickable-care-status', function(e) {
        const wrapper = $(this).closest('.care-status-display-wrapper');
        const displaySpan = $(this);
        const select = wrapper.find('.care-status-select-inline');

        // ?n nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n ch?, hi?n th? select dropdown
        displaySpan.hide();
        select.show().focus();
    });

    // 2. T? d?ng g?i AJAX c?p nh?t khi ngu?i d?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng thay d?i l?a ch?n
    $(document).on('change', '.care-status-select-inline', async function() {
        const select = $(this);
        const wrapper = select.closest('.care-status-display-wrapper');
        const displaySpan = wrapper.find('.care-status-display-name');
        const cell = select.closest('.care-status-cell');
        const customerId = cell.data('customer-id');
        const oldStatusKey = cell.data('current-status-key');
        const newStatusKey = select.val();

        // ?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh d?u select dang trong tr?ng th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i c?p nh?t d? kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng b? ?n b?i s? ki?n blur
        select.addClass('updating');
        // Kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½a select d? tr?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh spam click khi dang g?i request
        select.attr('disabled', true);
        wrapper.css('opacity', '0.5');

        try {
            // L?y token CSRF n?u c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ trong form
            const csrfToken = $('input[name="csrf_test_name"]').val() || '';
            const bodyParams = new URLSearchParams({
                'status_key': newStatusKey
            });
            if (csrfToken) {
                bodyParams.append('csrf_test_name', csrfToken);
            }

            const response = await fetch(baseUrl + 'customers/transition-status/' + customerId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: bodyParams
            });
            const res = await response.json();

            if (res.status === 'success') {
                const data = res.data;
                // C?p nh?t thu?c t?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh key hi?n t?i d? d?i chi?u l?n sau
                cell.data('current-status-key', data.status_key);

                // C?p nh?t l?i nh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n text hi?n th? v?i m?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½u s?c v?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ t?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½n tr?ng th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i m?i
                displaySpan.html(`
                    <span class="badge-care-status" style="background-color: ${data.color}15; color: ${data.color}; padding: 3px 8px; border-radius: 12px; font-size: 11px; border: 1px solid ${data.color}25;">
                        ${data.status_name}
                    </span>
                `);

                // Hi?u ?ng micro-animation: Highlight m?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½u xanh l?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ d?u nh? d? b?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½o th?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh c?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng
                cell.css('background-color', 'rgba(52, 199, 89, 0.15)');
                setTimeout(() => {
                    cell.css('transition', 'background-color 0.5s ease');
                    cell.css('background-color', '');
                }, 1000);
            } else {
                alert('Kh\u00f4ng th\u1ec3 c\u1eadp nh\u1eadt: ' + res.message);
                // Kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i ph?c l?i gi?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ tr? cu c?a dropdown
                select.val(oldStatusKey);
            }
        } catch (err) {
            console.error('L\u1ed7i AJAX:', err);
            alert('L\u1ed7i k\u1ebft n\u1ed1i m\u00e1y ch\u1ee7 khi c\u1eadp nh\u1eadt tr\u1ea1ng th\u00e1i t\u01b0 v\u1ea5n.');
            select.val(oldStatusKey);
        } finally {
            select.removeClass('updating');
            select.removeAttr('disabled');
            wrapper.css('opacity', '1');
            select.hide();
            displaySpan.show();
        }
    });

    // 3. ?n dropdown quay l?i text hi?n th? khi click ra ngo?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½i m?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng d?i d? li?u
    $(document).on('blur', '.care-status-select-inline', function() {
        const select = $(this);
        // N?u dang trong qu?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ tr?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½nh AJAX g?i d? li?u (updating), b? qua kh?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½ng ?n ? s? ki?n blur n?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½y
        if (select.hasClass('updating')) {
            return;
        }

        const wrapper = select.closest('.care-status-display-wrapper');
        const displaySpan = wrapper.find('.care-status-display-name');

        setTimeout(() => {
            if (!select.hasClass('updating')) {
                select.hide();
                displaySpan.show();
            }
        }, 150); // Timeout ng?n d? s? ki?n 'change' k?p ch?y tru?c khi blur ?n
    });

    // 4. Nh?n ph?fÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½?,ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¿Ãƒâ€šÃ‚Â½m Escape (ESC) d? h?y ch?nh s?a t?c th?i
    $(document).on('keyup', '.care-status-select-inline', function(e) {
        if (e.key === 'Escape') {
            const select = $(this);
            const wrapper = select.closest('.care-status-display-wrapper');
            const displaySpan = wrapper.find('.care-status-display-name');

            select.hide();
            displaySpan.show();
        }
    });
});

$(document).on('change', '.gift-status-checkbox', async function() {
    const checkbox = $(this);
    const cell = checkbox.closest('.gift-status-cell');
    const customerId = cell.data('customer-id');
    const oldChecked = !checkbox.prop('checked');
    const nextStatus = checkbox.prop('checked') ? 1 : 0;
    const csrfToken = $('input[name="csrf_test_name"]').val() || '';
    const bodyParams = new URLSearchParams({
        has_received_gift: String(nextStatus)
    });

    if (csrfToken) {
        bodyParams.append('csrf_test_name', csrfToken);
    }

    checkbox.prop('disabled', true);

    try {
        const response = await fetch(baseUrl + 'customers/update-gift-status/' + customerId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: bodyParams
        });
        const res = await response.json();

        if (res.status !== 'success') {
            alert(res.message || 'Kh\u00f4ng th\u1ec3 c\u1eadp nh\u1eadt tr\u1ea1ng th\u00e1i qu\u00e0 t\u1eb7ng.');
            checkbox.prop('checked', oldChecked);
            return;
        }

        const hasGift = Number(res.data.has_received_gift) === 1;
        checkbox.prop('checked', hasGift);
    } catch (err) {
        console.error('Gift status update error:', err);
        alert('L\u1ed7i k\u1ebft n\u1ed1i m\u00e1y ch\u1ee7 khi c\u1eadp nh\u1eadt tr\u1ea1ng th\u00e1i qu\u00e0 t\u1eb7ng.');
        checkbox.prop('checked', oldChecked);
    } finally {
        checkbox.prop('disabled', false);
    }
});

const customerFieldsForZns = {
    name: 'T\u00ean kh\u00e1ch h\u00e0ng (name)',
    code: 'M\u00e3 kh\u00e1ch h\u00e0ng (code)',
    phone: 'S\u1ed1 \u0111i\u1ec7n tho\u1ea1i ch\u00ednh (phone)',
    zalo_phone: 'S\u1ed1 \u0111i\u1ec7n tho\u1ea1i Zalo (zalo_phone)',
    email: 'Email ch\u00ednh (email)',
    company: 'T\u00ean c\u00f4ng ty (company)',
    address: '\u0110\u1ecba ch\u1ec9 (address)',
    care_status: 'Tr\u1ea1ng th\u00e1i t\u01b0 v\u1ea5n (care_status)',
    has_received_gift: 'Tr\u1ea1ng th\u00e1i qu\u00e0 t\u1eb7ng (has_received_gift)',
    customer_segment: 'Ph\u00e2n kh\u00fac kh\u00e1ch h\u00e0ng (customer_segment)'
};

let znsTargetCustomerIds = [];

function openBulkZnsModal(singleId = null) {
    if (singleId) {
        znsTargetCustomerIds = [singleId];
        document.getElementById('bulkZnsSelectedCount').innerText = 1;
    } else {
        const checkedBoxes = $('.record-check:checked');
        const count = checkedBoxes.length;
        if (count === 0) {
            alert('Vui l\u00f2ng ch\u1ecdn \u00edt nh\u1ea5t m\u1ed9t kh\u00e1ch h\u00e0ng \u0111\u1ec3 g\u1eedi ZNS.');
            return;
        }
        znsTargetCustomerIds = checkedBoxes.map(function() { return $(this).val(); }).get();
        document.getElementById('bulkZnsSelectedCount').innerText = count;
    }

    const modal = document.getElementById('bulkZnsModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeBulkZnsModal() {
    const modal = document.getElementById('bulkZnsModal');
    const form = document.getElementById('bulkZnsForm');
    const mappingContainer = document.getElementById('bulk-zns-mapping-container');
    const mappingRows = document.getElementById('bulk-zns-mapping-rows');

    if (modal) modal.style.display = 'none';
    if (form) form.reset();
    if (mappingContainer) mappingContainer.style.display = 'none';
    if (mappingRows) mappingRows.innerHTML = '';

    znsTargetCustomerIds = [];
}

document.addEventListener('DOMContentLoaded', function() {
    const tplSelect = document.getElementById('bulkZnsTemplateSelect');
    const mappingContainer = document.getElementById('bulk-zns-mapping-container');
    const mappingRows = document.getElementById('bulk-zns-mapping-rows');
    const znsForm = document.getElementById('bulkZnsForm');
    const btnSubmit = document.getElementById('btn-submit-bulk-zns');

    if (tplSelect && mappingContainer && mappingRows) {
        tplSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                mappingContainer.style.display = 'none';
                mappingRows.innerHTML = '';
                return;
            }

            let params = [];
            let mappings = {};
            try {
                params = JSON.parse(selectedOption.dataset.params || '[]');
                mappings = JSON.parse(selectedOption.dataset.mappings || '{}');
            } catch (e) {
                console.error(e);
            }

            mappingRows.innerHTML = '';
            if (params.length === 0) {
                mappingRows.innerHTML = '<div style="font-size:12px; color:#94a3b8; font-style:italic; padding:6px;">M\u1eabu tin n\u00e0y kh\u00f4ng c\u00f3 bi\u1ebfn.</div>';
            } else {
                params.forEach(param => {
                    const row = document.createElement('div');
                    row.className = 'zns-mapping-row';
                    row.style.display = 'grid';
                    row.style.gridTemplateColumns = '120px 1fr';
                    row.style.gap = '12px';
                    row.style.alignItems = 'center';
                    row.style.marginBottom = '10px';
                    row.style.paddingBottom = '8px';
                    row.style.borderBottom = '1px dashed #f1f5f9';

                    const label = document.createElement('div');
                    label.className = 'zns-param-label';
                    label.style.fontSize = '12px';
                    label.style.fontWeight = '600';
                    label.style.color = '#334155';
                    label.innerText = param;
                    row.appendChild(label);

                    const valContainer = document.createElement('div');
                    const selectField = document.createElement('select');
                    selectField.className = 'form-control-premium';
                    selectField.style.width = '100%';
                    selectField.style.padding = '6px';
                    selectField.style.borderRadius = '6px';
                    selectField.style.border = '1px solid #cbd5e1';
                    selectField.required = true;

                    let optionsHtml = '<option value="">-- Ch\u1ecdn tr\u01b0\u1eddng --</option>';
                    const currentVal = mappings[param] || '';
                    const isStaticValue = currentVal.startsWith('#');

                    for (const [key, fieldLabel] of Object.entries(customerFieldsForZns)) {
                        const isSelected = (currentVal && !isStaticValue && currentVal === key) ||
                                           (!currentVal && param.toLowerCase().includes('name') && key === 'name') ||
                                           (!currentVal && param.toLowerCase().includes('phone') && key === 'phone') ||
                                           (!currentVal && param.toLowerCase().includes('code') && key === 'code');
                        optionsHtml += `<option value="${key}" ${isSelected ? 'selected' : ''}>${fieldLabel}</option>`;
                    }

                    optionsHtml += `<option value="static_opt" ${isStaticValue ? 'selected' : ''}>-- Nh\u1eadp gi\u00e1 tr\u1ecb t\u0129nh --</option>`;
                    selectField.innerHTML = optionsHtml;
                    valContainer.appendChild(selectField);

                    const textStatic = document.createElement('input');
                    textStatic.type = 'text';
                    textStatic.placeholder = 'Nh\u1eadp gi\u00e1 tr\u1ecb t\u0129nh (VD: #L.A.N)...';
                    textStatic.className = 'form-control-premium';
                    textStatic.style.width = '100%';
                    textStatic.style.padding = '6px';
                    textStatic.style.borderRadius = '6px';
                    textStatic.style.border = '1px solid #cbd5e1';
                    textStatic.style.marginTop = '6px';
                    textStatic.style.display = 'none';

                    if (isStaticValue) {
                        textStatic.style.display = 'block';
                        textStatic.value = currentVal.substring(1);
                    }
                    valContainer.appendChild(textStatic);

                    selectField.addEventListener('change', function() {
                        if (this.value === 'static_opt') {
                            textStatic.style.display = 'block';
                            textStatic.required = true;
                            textStatic.focus();
                        } else {
                            textStatic.style.display = 'none';
                            textStatic.required = false;
                        }
                    });

                    row.appendChild(valContainer);
                    mappingRows.appendChild(row);
                });
            }

            mappingContainer.style.display = 'block';
        });
    }

    if (znsForm && btnSubmit && mappingRows) {
        znsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (znsTargetCustomerIds.length === 0) {
                alert('Vui l\u00f2ng ch\u1ecdn kh\u00e1ch h\u00e0ng.');
                return;
            }

            if (!confirm('B\u1ea1n c\u00f3 ch\u1eafc ch\u1eafn mu\u1ed1n g\u1eedi Zalo ZNS cho ' + znsTargetCustomerIds.length + ' kh\u00e1ch h\u00e0ng \u0111\u00e3 ch\u1ecdn?')) {
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> \u0110ang g\u1eedi ZNS...';

            const formData = new FormData(znsForm);
            znsTargetCustomerIds.forEach(id => {
                formData.append('customer_ids[]', id);
            });

            mappingRows.querySelectorAll('.zns-mapping-row').forEach(row => {
                const labelField = row.querySelector('.zns-param-label');
                const selectField = row.querySelector('select');
                if (!labelField || !selectField) return;

                const paramName = labelField.innerText.trim();
                if (!paramName) return;

                if (selectField.value === 'static_opt') {
                    const inputStatic = row.querySelector('input[type="text"]');
                    let val = inputStatic ? inputStatic.value.trim() : '';
                    if (val && !val.startsWith('#')) {
                        val = '#' + val;
                    }
                    if (val) {
                        formData.set(`mapping[${paramName}]`, val);
                    }
                } else if (selectField.value !== '') {
                    formData.set(`mapping[${paramName}]`, selectField.value);
                }
            });

            fetch(baseUrl + 'zalo/zns/send-quick', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    closeBulkZnsModal();
                    $('#check-all').prop('checked', false);
                    $('.record-check').prop('checked', false);
                    $('.bulk-actions-bar').hide();
                } else {
                    alert(data.message || 'L\u1ed7i g\u1eedi ZNS h\u00e0ng lo\u1ea1t.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('C\u00f3 l\u1ed7i m\u1ea1ng x\u1ea3y ra khi g\u1eedi ZNS.');
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-paper-plane"></i> G\u1eedi ngay';
            });
        });
    }
});
