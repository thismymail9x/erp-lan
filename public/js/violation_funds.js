(function() {
    function parseJson(id, fallback) {
        const node = document.getElementById(id);
        if (!node) {
            return fallback;
        }

        try {
            return JSON.parse(node.textContent || '');
        } catch (error) {
            return fallback;
        }
    }

    function formatMoney(value) {
        const number = String(value || '').replace(/[^\d]/g, '');
        return number ? Number(number).toLocaleString('vi-VN') : '';
    }

    function bindMoneyInputs() {
        document.querySelectorAll('.js-money-input').forEach(function(input) {
            input.addEventListener('input', function() {
                input.value = formatMoney(input.value);
            });
        });
    }

    function setupCanvas(canvas) {
        if (!canvas) {
            return null;
        }

        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        canvas.width = Math.max(1, rect.width * ratio);
        canvas.height = Math.max(1, Number(canvas.getAttribute('height')) * ratio);
        const ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);

        return { ctx: ctx, width: rect.width, height: Number(canvas.getAttribute('height')) };
    }

    function shortMoney(value) {
        if (value >= 1000000) {
            return (value / 1000000).toFixed(1).replace('.0', '') + ' tr';
        }
        if (value >= 1000) {
            return Math.round(value / 1000) + 'k';
        }
        return String(value || 0);
    }

    function renderCharts() {
        const payload = parseJson('violationChartData', { categories: [], employees: [] });
        const categoryCanvas = document.getElementById('violationCategoryChart');
        if (!categoryCanvas) {
            return;
        }

        const chart = setupCanvas(categoryCanvas);
        if (!chart) {
            return;
        }

        const ctx = chart.ctx;
        const width = chart.width;
        const height = chart.height;
        const categories = payload.categories || [];
        const total = categories.reduce(function(sum, item) {
            return sum + Number(item.total || 0);
        }, 0);
        const colors = ['#2563eb', '#16a34a', '#f97316', '#dc2626', '#7c3aed', '#0891b2', '#475569', '#db2777'];
        const cx = width / 2;
        const cy = height / 2 - 8;
        const radius = Math.min(width, height) * 0.32;

        ctx.clearRect(0, 0, width, height);
        ctx.font = '12px Inter, Arial, sans-serif';

        if (total <= 0) {
            ctx.fillStyle = '#64748b';
            ctx.textAlign = 'center';
            ctx.fillText('Chưa có dữ liệu', cx, cy);
            ctx.textAlign = 'left';
            return;
        }

        let start = -Math.PI / 2;
        categories.forEach(function(item, index) {
            const angle = Number(item.total || 0) / total * Math.PI * 2;
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
        ctx.fillStyle = '#111827';
        ctx.font = '700 15px Inter, Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(shortMoney(total), cx, cy + 5);
        ctx.textAlign = 'left';

        categories.slice(0, 4).forEach(function(item, index) {
            const x = 14 + (index % 2) * (width / 2);
            const y = height - 40 + Math.floor(index / 2) * 20;
            ctx.fillStyle = colors[index % colors.length];
            ctx.fillRect(x, y - 9, 9, 9);
            ctx.fillStyle = '#64748b';
            ctx.font = '11px Inter, Arial, sans-serif';
            ctx.fillText(String(item.label).slice(0, 22), x + 14, y);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindMoneyInputs();
        renderCharts();

        document.querySelectorAll('.js-confirm-delete').forEach(function(link) {
            link.addEventListener('click', function(event) {
                if (!confirm('Xóa khoản vi phạm này?')) {
                    event.preventDefault();
                }
            });
        });
    });
})();
