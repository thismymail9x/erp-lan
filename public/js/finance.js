$(document).ready(function() {
    console.log('--- FINANCE JS LOADED ---');
    
    const tableContainer = document.getElementById('finance-table-container');
    const searchInput = document.getElementById('finance-search');
    let searchTimeout;
    let abortController = null;

    function triggerSearch() {
        const url = new URL(window.location.href);
        
        const search = $('#finance-search').val();
        const month = $('#month-filter').val();
        const year = $('#year-filter').val();
        const paymentStatus = $('#payment-status-filter').val();

        if (search) url.searchParams.set('search', search);
        else url.searchParams.delete('search');

        if (month) url.searchParams.set('month', month);
        else url.searchParams.delete('month');

        if (year) url.searchParams.set('year', year);
        else url.searchParams.delete('year');

        if (paymentStatus) url.searchParams.set('payment_status', paymentStatus);
        else url.searchParams.delete('payment_status');

        url.searchParams.set('page', 1);

        fetchByUrl(url);
    }

    async function fetchByUrl(url, pushToHistory = true) {
        if (!tableContainer) return;
        
        // Há»§y yÃªu cáº§u trÆ°á»›c Ä‘Ã³ náº¿u Ä‘ang cháº¡y
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        try {
            console.log('Fetching Finance:', url.toString());
            $('#finance-loader').removeClass('finance-hidden');
            tableContainer.classList.add('finance-loading');

            const fetchUrl = new URL(url.toString());
            fetchUrl.searchParams.set('ajax', '1');

            const response = await fetch(fetchUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: abortController.signal
            });

            if (!response.ok) {
                throw new Error('M\u1ea1ng kh\u00f4ng \u1ed5n \u0111\u1ecbnh ho\u1eb7c l\u1ed7i m\u00e1y ch\u1ee7 (HTTP ' + response.status + ')');
            }

            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                // Náº¿u server tráº£ vá» HTML thay vÃ¬ JSON (cÃ³ thá»ƒ do redirect login hoáº·c lá»—i 500)
                window.location.reload(); // Táº£i láº¡i trang Ä‘á»ƒ hiá»‡n trang login hoáº·c lá»—i gá»‘c
                return;
            }

            const data = await response.json();
            
            // Cáº­p nháº­t báº£ng dá»¯ liá»‡u
            tableContainer.innerHTML = data.html;
            
            // Cáº­p nháº­t cÃ¡c chá»‰ sá»‘ thá»‘ng kÃª á»Ÿ header
            if (data.stats) {
                updateStats(data.stats);
            }
            
            // Cáº­p nháº­t URL trÃ¬nh duyá»‡t
            if (pushToHistory) {
                window.history.pushState(null, '', url);
            }
        } catch (err) {
            if (err.name === 'AbortError') {
                console.log('Fetch aborted');
            } else {
                console.error('Fetch error:', err);
                alert('C\u00f3 l\u1ed7i x\u1ea3y ra khi t\u1ea3i d\u1eef li\u1ec7u: ' + err.message);
            }
        } finally {
            if (!abortController.signal.aborted) {
                $('#finance-loader').addClass('finance-hidden');
                tableContainer.classList.remove('finance-loading');
            }
        }
    }

    function updateStats(stats) {
        const elements = {
            'stat-total': stats.total,
            'stat-paid': stats.paid,
            'stat-unpaid': stats.unpaid
        };
        
        for (const [id, val] of Object.entries(elements)) {
            const el = document.getElementById(id);
            if (el) {
                // Hi á»©ng nhÃ¡y nháº¹ Ä‘á»ƒ ngÆ°á»i dÃ¹ng biáº¿t sá»‘ liá»‡u Ä‘Ã£ cáº­p nháº­t
                el.classList.add('finance-stat-updating');
                setTimeout(() => {
                    el.innerText = val;
                    el.classList.remove('finance-stat-updating');
                }, 200);
            }
        }
    }

    // Xá»­ lÃ½ nÃºt Back/Forward cá»§a trÃ¬nh duyá»‡t
    window.addEventListener('popstate', function() {
        const url = new URL(window.location.href);
        
        // Cáº­p nháº­t láº¡i giÃ¡ trá»‹ cÃ¡c Ã´ input/select tá»« URL
        $('#finance-search').val(url.searchParams.get('search') || '');
        $('#month-filter').val(url.searchParams.get('month') || '');
        $('#year-filter').val(url.searchParams.get('year') || new Date().getFullYear());
        $('#payment-status-filter').val(url.searchParams.get('payment_status') || '');
        $('#clear-finance-search').toggleClass('finance-hidden', !$('#finance-search').val());
        
        fetchByUrl(url, false);
    });

    // Event Listeners
    $('#finance-search').on('input', function() {
        $('#clear-finance-search').toggleClass('finance-hidden', !this.value);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(triggerSearch, 500);
    });

    $('#clear-finance-search').on('click', function() {
        $('#finance-search').val('').trigger('input');
    });

    $('#btn-reset-finance-filters').on('click', function() {
        $('#finance-search').val('');
        $('#month-filter').val('');
        $('#year-filter').val(new Date().getFullYear());
        $('#payment-status-filter').val('');
        $('#clear-finance-search').addClass('finance-hidden');
        triggerSearch();
    });

    $(document).on('change', '#month-filter, #year-filter, #payment-status-filter', function() {
        triggerSearch();
    });

    // Pagination AJAX
    $(document).on('click', '#finance-table-container .pagination a', function(e) {
        e.preventDefault();
        const url = new URL(this.href);
        fetchByUrl(url);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Initial state
    $('#clear-finance-search').toggleClass('finance-hidden', !$('#finance-search').val());
});