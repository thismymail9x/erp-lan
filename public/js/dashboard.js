/**
 * L.A.N ERP - Core Dashboard Logic
 * Quản lý các tương tác giao diện chính (sidebar, mobile navigation, tooltips).
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Quản lý Sidebar & Mobile Menu
    const mobileToggle = document.getElementById('mobile_toggle') || document.getElementById('mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileToggle && sidebar) {
        /**
         * Toggle Sidebar trên thiết bị di động.
         * Ngăn chặn sự kiện click lan ra ngoài để tránh đóng ngay lập tức.
         */
        mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        /**
         * Đóng sidebar khi người dùng nhấn ra vùng bên ngoài main-content.
         * Chỉ áp dụng cho màn hình nhỏ (<= 1024px).
         */
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }

    /**
     * 2. Xử lý trạng thái Active cho các liên kết điều hướng (Nav Links).
     * Khi click vào một menu item, sẽ highlight mục đó và đóng sidebar nếu là mobile.
     */
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            if (window.innerWidth <= 1024 && sidebar) {
                sidebar.classList.remove('active');
            }
        });
    });

    /**
     * 3. Tiện ích Auto-close cho các Alert thông báo.
     * Các thông báo flash data (thành công/lỗi) sẽ tự ẩn sau 5 giây.
     */
    const flashMessages = document.querySelectorAll('.alert-auto-hide');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        }, 5000);
    });
});

/**
 * 4. Khởi tạo thư viện Select2 cho các thẻ Select.
 * Giúp các menu chọn lựa trở nên thông minh và hỗ trợ tìm kiếm nội bộ.
 */
$(document).ready(function() {
    if ($.fn.select2) {
        $('select:not(.no-select2)').select2({
            width: '100%',
            minimumResultsForSearch: 10,
            language: {
                noResults: function() { return "Không tìm thấy kết quả"; }
            }
        });
    }
});

/**
 * 5. Global Search Utility (Nếu có các trang dùng chung input search).
 * Hàm này có thể được gọi từ các view lẻ để xử lý logic filter nhanh.
 */
function globalSearch(inputId, targetSelector) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        const targets = document.querySelectorAll(targetSelector);
        
        targets.forEach(el => {
            const text = el.textContent.toLowerCase();
            el.style.display = text.includes(value) ? '' : 'none';
        });
    });
}
