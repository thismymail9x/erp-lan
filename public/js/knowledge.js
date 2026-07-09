/**
 * L.A.N ERP - Knowledge base list and detail interactions.
 */

$(document).ready(function() {
    initKnowledgeFeedSearch();
    initKnowledgeCopyLink();
});

function initKnowledgeFeedSearch() {
    const filterForm = $('.knowledge-feed-container .search-filter-bar');
    const resultsContainer = $('#knowledge-feed-results');

    if (!filterForm.length || !resultsContainer.length) {
        return;
    }

    let debounceTimer;

    function performSearch() {
        const formData = filterForm.serialize();
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            data: formData,
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
            },
            error: function() {
                resultsContainer.css('opacity', '1');
                console.error('Lỗi khi tải kết quả tìm kiếm.');
            }
        });
    }

    filterForm.find('input[name="search"]').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSearch, 500);
    });

    filterForm.find('input[name="search"]').on('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            performSearch();
        }
    });

    filterForm.find('select').on('change', performSearch);

    $(document).on('click', '#knowledge-pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
                $('html, body').animate({ scrollTop: $('.knowledge-feed-container').offset().top - 100 }, 500);
            }
        });
    });
}

function initKnowledgeCopyLink() {
    $('.btn-copy-link').on('click', function() {
        const link = $(this).data('link');
        const el = document.createElement('textarea');
        el.value = link;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);

        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-check"></i> Đã Copy!');
        btn.addClass('bg-success text-white');

        setTimeout(() => {
            btn.html(originalHtml);
            btn.removeClass('bg-success text-white');
        }, 2000);
    });
}
