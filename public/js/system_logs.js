/**
 * L.A.N ERP - System logs AJAX filtering.
 */

$(document).ready(function() {
    const filterForm = $('.search-filter-bar');
    const resultsContainer = $('#logs-table-results');

    if (!filterForm.length || !resultsContainer.length) {
        return;
    }

    function performSearch(url = window.location.pathname) {
        const formData = filterForm.serialize();
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            data: url === window.location.pathname ? formData : null,
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
            },
            error: function() {
                resultsContainer.css('opacity', '1');
                console.error('Lỗi khi tải nhật ký.');
            }
        });
    }

    filterForm.find('input, select').on('change', function() {
        performSearch();
    });

    $(document).on('click', '#logs-pagination a', function(e) {
        e.preventDefault();
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: $(this).attr('href'),
            type: 'GET',
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
                $('html, body').animate({ scrollTop: 0 }, 500);
            }
        });
    });
});
