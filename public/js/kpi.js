$(document).ready(function() {
    let filterTimeout;
    const $form = $('#kpiFilterForm');
    const $tableBody = $('#kpiTableBody');
    const $loading = $('#kpiLoading');

    function refreshKpi() {
        $tableBody.css('opacity', '0.5');
        $loading.show();

        $.ajax({
            url: $form.data('kpi-url'),
            type: 'GET',
            data: $form.serialize(),
            success: function(response) {
                $tableBody.html(response).css('opacity', '1');
                $loading.hide();
            },
            error: function() {
                alert('C\u00f3 l\u1ed7i x\u1ea3y ra khi t\u1ea3i d\u1eef li\u1ec7u KPI.');
                $tableBody.css('opacity', '1');
                $loading.hide();
            }
        });
    }

    $('#filterYear, #filterMonth, #filterDept').on('change', refreshKpi);

    $('#filterSearch').on('keyup', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(refreshKpi, 500);
    });
});
