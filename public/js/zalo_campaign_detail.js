/*
 * JS cho màn hình chi tiết chiến dịch ZNS.
 */
document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.zns-campaign-detail-page');
    if (!page) {
        return;
    }

    document.querySelectorAll('[data-progress-percent]').forEach(function (bar) {
        const percent = Number.parseInt(bar.dataset.progressPercent || '0', 10);
        bar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
    });

    const btnExecute = document.getElementById('btn-execute-campaign-detail');
    if (!btnExecute) {
        return;
    }

    btnExecute.addEventListener('click', function () {
        const campaignId = this.dataset.id;
        const executeBaseUrl = page.dataset.executeUrl || '';
        const originalText = this.innerHTML;

        if (!confirm('Bạn có chắc chắn muốn thực thi chiến dịch này ngay bây giờ? Tin nhắn ZNS sẽ bắt đầu được gửi hàng loạt tới khách hàng.')) {
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi chiến dịch...';

        fetch(`${executeBaseUrl}${campaignId}`, {
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

                alert(data.message || 'Có lỗi xảy ra.');
                btnExecute.disabled = false;
                btnExecute.innerHTML = originalText;
            })
            .catch(function (error) {
                console.error(error);
                alert('Có lỗi mạng xảy ra. Vui lòng thử lại.');
                btnExecute.disabled = false;
                btnExecute.innerHTML = originalText;
            });
    });
});
