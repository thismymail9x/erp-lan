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
        
        // Hủy yêu cầu trước đó nếu đang chạy
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        try {
            console.log('Fetching Finance:', url.toString());
            $('#finance-loader').show();
            tableContainer.style.opacity = '0.5';

            const fetchUrl = new URL(url.toString());
            fetchUrl.searchParams.set('ajax', '1');

            const response = await fetch(fetchUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: abortController.signal
            });

            if (!response.ok) {
                throw new Error('Mạng không ổn định hoặc lỗi máy chủ (HTTP ' + response.status + ')');
            }

            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                // Nếu server trả về HTML thay vì JSON (có thể do redirect login hoặc lỗi 500)
                window.location.reload(); // Tải lại trang để hiện trang login hoặc lỗi gốc
                return;
            }

            const data = await response.json();
            
            // Cập nhật bảng dữ liệu
            tableContainer.innerHTML = data.html;
            
            // Cập nhật các chỉ số thống kê ở header
            if (data.stats) {
                updateStats(data.stats);
            }
            
            // Cập nhật URL trình duyệt
            if (pushToHistory) {
                window.history.pushState(null, '', url);
            }
        } catch (err) {
            if (err.name === 'AbortError') {
                console.log('Fetch aborted');
            } else {
                console.error('Fetch error:', err);
                alert('Có lỗi xảy ra khi tải dữ liệu: ' + err.message);
            }
        } finally {
            if (!abortController.signal.aborted) {
                $('#finance-loader').hide();
                tableContainer.style.opacity = '1';
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
                // Hi ứng nháy nhẹ để người dùng biết số liệu đã cập nhật
                el.style.transition = 'opacity 0.2s';
                el.style.opacity = '0';
                setTimeout(() => {
                    el.innerText = val;
                    el.style.opacity = '1';
                }, 200);
            }
        }
    }

    // Xử lý nút Back/Forward của trình duyệt
    window.addEventListener('popstate', function() {
        const url = new URL(window.location.href);
        
        // Cập nhật lại giá trị các ô input/select từ URL
        $('#finance-search').val(url.searchParams.get('search') || '');
        $('#month-filter').val(url.searchParams.get('month') || '');
        $('#year-filter').val(url.searchParams.get('year') || new Date().getFullYear());
        $('#payment-status-filter').val(url.searchParams.get('payment_status') || '');
        $('#clear-finance-search').toggle(!!$('#finance-search').val());
        
        fetchByUrl(url, false);
    });

    // Event Listeners
    $('#finance-search').on('input', function() {
        $('#clear-finance-search').toggle(!!this.value);
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
        $('#clear-finance-search').hide();
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
    $('#clear-finance-search').toggle(!!$('#finance-search').val());
});
