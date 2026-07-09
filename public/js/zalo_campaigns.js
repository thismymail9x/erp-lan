/*
 * JS cho danh sách chiến dịch ZNS remarketing.
 * Tách khỏi view để giữ HTML chỉ còn dữ liệu và markup theo quy chuẩn dự án.
 */
document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.zalo-campaigns-page');
    const tabs = document.querySelectorAll('.premium-tab-item');
    const tabPanels = document.querySelectorAll('.tab-content-panel');

    document.querySelectorAll('[data-progress-percent]').forEach(function (bar) {
        const percent = Number.parseInt(bar.dataset.progressPercent || '0', 10);
        bar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
    });

    function switchTab(tabId) {
        tabs.forEach(function (tab) {
            tab.classList.toggle('active', tab.dataset.target === tabId);
        });

        tabPanels.forEach(function (panel) {
            panel.classList.toggle('active', panel.id === tabId);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.dataset.target;
            switchTab(target);

            const url = new URL(window.location);
            url.searchParams.set('tab', target === 'tab-individual' ? 'individual' : 'campaigns');
            window.history.pushState({}, '', url);
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('page_individual_logs') || urlParams.get('tab') === 'individual') {
        switchTab('tab-individual');
    } else {
        switchTab('tab-campaigns');
    }

    document.querySelectorAll('.btn-execute-campaign').forEach(function (button) {
        button.addEventListener('click', function () {
            const campaignId = button.dataset.id;
            const executeUrl = page ? page.dataset.campaignExecuteUrl : '';

            if (!confirm('Bạn có chắc chắn muốn thực thi chiến dịch này ngay bây giờ? Tin nhắn ZNS sẽ bắt đầu được gửi hàng loạt tới khách hàng.')) {
                return;
            }

            const originText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

            fetch(`${executeUrl}${campaignId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                        return;
                    }

                    alert(data.message || 'Có lỗi xảy ra khi thực thi chiến dịch.');
                    button.disabled = false;
                    button.innerHTML = originText;
                })
                .catch(function (error) {
                    console.error(error);
                    alert('Có lỗi mạng xảy ra. Vui lòng thử lại.');
                    button.disabled = false;
                    button.innerHTML = originText;
                });
        });
    });
});
