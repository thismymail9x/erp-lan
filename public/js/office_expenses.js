$(function() {
    $('.js-money-input').on('input', function() {
        formatMoneyInput(this);
    });

    $('.js-confirm-delete').on('click', function(event) {
        if (!confirm('Xóa khoản chi phí này?')) {
            event.preventDefault();
        }
    });

    const chartDataNode = document.getElementById('officeExpenseChartData');
    const payload = chartDataNode ? JSON.parse(chartDataNode.textContent || '{}') : {};
    drawMonthlyChart(document.getElementById('officeMonthlyChart'), payload);
    drawCategoryChart(document.getElementById('officeCategoryChart'), payload.categories || []);
});

function formatMoneyInput(input) {
    const rawValue = String(input.value || '');
    const cursor = input.selectionStart || 0;
    const digitsAfterCursor = rawValue.slice(cursor).replace(/[^\d]/g, '').length;
    const digits = rawValue.replace(/[^\d]/g, '');
    const formatted = digits ? Number(digits).toLocaleString('vi-VN') : '';

    input.value = formatted;

    let nextCursor = formatted.length;
    let remainingDigits = digitsAfterCursor;
    while (remainingDigits > 0 && nextCursor > 0) {
        nextCursor--;
        if (/\d/.test(formatted.charAt(nextCursor))) {
            remainingDigits--;
        }
    }

    input.setSelectionRange(nextCursor, nextCursor);
}

function formatMoney(value) {
    if (value >= 1000000000) return (value / 1000000000).toFixed(1).replace('.0', '') + ' tỷ';
    if (value >= 1000000) return (value / 1000000).toFixed(1).replace('.0', '') + ' tr';
    if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
    return String(value || 0);
}

function setupCanvas(canvas) {
    if (!canvas) return null;
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    canvas.width = Math.max(1, rect.width * ratio);
    canvas.height = Math.max(1, canvas.getAttribute('height') * ratio);
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
    return { ctx, width: rect.width, height: Number(canvas.getAttribute('height')) };
}

function drawMonthlyChart(canvas, data) {
    const chart = setupCanvas(canvas);
    if (!chart) return;
    const { ctx, width, height } = chart;
    const labels = data.labels || [];
    const current = data.current || [];
    const previous = data.previous || [];
    const max = Math.max(1, ...current, ...previous);
    const pad = { left: 46, right: 18, top: 18, bottom: 36 };
    const plotW = width - pad.left - pad.right;
    const plotH = height - pad.top - pad.bottom;
    const barGap = plotW / Math.max(1, labels.length);
    const barW = Math.max(7, Math.min(20, barGap * 0.28));

    ctx.clearRect(0, 0, width, height);
    ctx.font = '12px Inter, Arial, sans-serif';
    ctx.strokeStyle = '#e5e7eb';
    ctx.fillStyle = '#64748b';
    ctx.lineWidth = 1;

    for (let i = 0; i <= 4; i++) {
        const y = pad.top + (plotH / 4) * i;
        ctx.beginPath();
        ctx.moveTo(pad.left, y);
        ctx.lineTo(width - pad.right, y);
        ctx.stroke();
        ctx.fillText(formatMoney(max - (max / 4) * i), 4, y + 4);
    }

    labels.forEach((label, index) => {
        const x = pad.left + index * barGap + barGap / 2;
        const prevH = (previous[index] || 0) / max * plotH;
        const curH = (current[index] || 0) / max * plotH;

        ctx.fillStyle = '#cbd5e1';
        ctx.fillRect(x - barW - 2, pad.top + plotH - prevH, barW, prevH);
        ctx.fillStyle = '#2563eb';
        ctx.fillRect(x + 2, pad.top + plotH - curH, barW, curH);
        ctx.fillStyle = '#64748b';
        ctx.fillText(label, x - 9, height - 12);
    });

    ctx.fillStyle = '#2563eb';
    ctx.fillRect(width - 170, 8, 10, 10);
    ctx.fillText(String(data.year || ''), width - 154, 17);
    ctx.fillStyle = '#cbd5e1';
    ctx.fillRect(width - 90, 8, 10, 10);
    ctx.fillStyle = '#64748b';
    ctx.fillText(String(data.previousYear || ''), width - 74, 17);
}

function drawCategoryChart(canvas, categories) {
    const chart = setupCanvas(canvas);
    if (!chart) return;
    const { ctx, width, height } = chart;
    const total = categories.reduce((sum, item) => sum + Number(item.total || 0), 0);
    const colors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#475569', '#db2777', '#65a30d', '#9333ea'];
    const cx = width / 2;
    const cy = height / 2;
    const radius = Math.min(width, height) * 0.34;

    ctx.clearRect(0, 0, width, height);
    if (total <= 0) {
        ctx.fillStyle = '#64748b';
        ctx.font = '13px Inter, Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Chưa có dữ liệu', cx, cy);
        ctx.textAlign = 'left';
        return;
    }

    let start = -Math.PI / 2;
    categories.forEach((item, index) => {
        const angle = (Number(item.total || 0) / total) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, radius, start, start + angle);
        ctx.closePath();
        ctx.fillStyle = colors[index % colors.length];
        ctx.fill();
        start += angle;
    });

    ctx.beginPath();
    ctx.arc(cx, cy, radius * 0.58, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();
    ctx.fillStyle = '#0f172a';
    ctx.font = '700 16px Inter, Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(formatMoney(total), cx, cy + 5);
    ctx.textAlign = 'left';
}
