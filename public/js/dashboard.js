/**
 * L.A.N ERP - Core Dashboard Logic
 * QuÃ¡ÂºÂ£n lÃƒÂ½ cÃƒÂ¡c tÃ†Â°Ã†Â¡ng tÃƒÂ¡c giao diÃ¡Â»â€¡n chÃƒÂ­nh vÃƒÂ  toast thÃƒÂ´ng bÃƒÂ¡o toÃƒÂ n hÃ¡Â»â€¡ thÃ¡Â»â€˜ng.
 */

(function () {
    const config = {
        success: {
            title: 'Thành công',
            icon: 'fa-check-circle'
        },
        error: {
            title: 'Lỗi',
            icon: 'fa-circle-exclamation'
        },
        warning: {
            title: 'Cảnh báo',
            icon: 'fa-triangle-exclamation'
        },
        info: {
            title: 'Thông báo',
            icon: 'fa-circle-info'
        }
    };

    function ensureStack() {
        let stack = document.querySelector('.toast-stack');

        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            stack.setAttribute('aria-live', 'polite');
            stack.setAttribute('aria-atomic', 'true');
            document.body.appendChild(stack);
        }

        return stack;
    }

    function normalizeType(type) {
        return config[type] ? type : 'info';
    }

    function closeToast(toast) {
        toast.classList.add('hide');
        toast.addEventListener('transitionend', function () {
            toast.remove();
        }, { once: true });
    }

    function showToast(message, type, options) {
        const normalizedType = normalizeType(type || 'info');
        const meta = config[normalizedType];
        const settings = Object.assign({
            title: meta.title,
            duration: normalizedType === 'error' ? 6500 : 4500
        }, options || {});

        const stack = ensureStack();
        const toast = document.createElement('div');
        toast.className = 'toast-item toast-' + normalizedType;
        toast.setAttribute('role', normalizedType === 'error' ? 'alert' : 'status');

        toast.innerHTML = [
            '<div class="toast-icon"><i class="fas ' + meta.icon + '"></i></div>',
            '<div class="toast-body">',
            '<div class="toast-title"></div>',
            '<div class="toast-message"></div>',
            '</div>',
            '<button type="button" class="toast-close" aria-label="Đóng thông báo">&times;</button>'
        ].join('');

        toast.querySelector('.toast-title').textContent = settings.title;
        toast.querySelector('.toast-message').textContent = message || '';
        toast.querySelector('.toast-close').addEventListener('click', function () {
            closeToast(toast);
        });

        stack.appendChild(toast);
        requestAnimationFrame(function () {
            toast.classList.add('show');
        });

        if (settings.duration > 0) {
            setTimeout(function () {
                closeToast(toast);
            }, settings.duration);
        }

        return toast;
    }

    window.LANToast = {
        show: showToast,
        success: function (message, options) { return showToast(message, 'success', options); },
        error: function (message, options) { return showToast(message, 'error', options); },
        warning: function (message, options) { return showToast(message, 'warning', options); },
        info: function (message, options) { return showToast(message, 'info', options); }
    };
    window.showToast = showToast;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-toast]').forEach(function (alert) {
            const type = alert.getAttribute('data-toast-type') || 'info';
            const text = alert.textContent.replace(/\s+/g, ' ').trim();

            if (text) {
                showToast(text, type);
            }
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    // 1. QuÃ¡ÂºÂ£n lÃƒÂ½ Sidebar vÃƒÂ  Mobile Menu.
    const mobileToggle = document.getElementById('mobile_toggle') || document.getElementById('mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    const appWrapper = document.querySelector('.app-wrapper');
    const sidebarCollapseToggle = document.getElementById('sidebarCollapseToggle');
    const sidebarStateKey = 'lan.erp.sidebar.collapsed';

    function setSidebarCollapsed(collapsed) {
        if (!appWrapper || !sidebarCollapseToggle) return;

        appWrapper.classList.toggle('sidebar-collapsed', collapsed);
        sidebarCollapseToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        sidebarCollapseToggle.setAttribute('title', collapsed ? 'M\u1edf r\u1ed9ng menu' : 'Thu g\u1ecdn menu');
        sidebarCollapseToggle.setAttribute('aria-label', collapsed ? 'M\u1edf r\u1ed9ng menu' : 'Thu g\u1ecdn menu');
        localStorage.setItem(sidebarStateKey, collapsed ? '1' : '0');
    }

    if (appWrapper && sidebarCollapseToggle) {
        const shouldCollapse = window.innerWidth > 1024 && localStorage.getItem(sidebarStateKey) === '1';
        setSidebarCollapsed(shouldCollapse);

        sidebarCollapseToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (window.innerWidth <= 1024) return;

            setSidebarCollapsed(!appWrapper.classList.contains('sidebar-collapsed'));
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth <= 1024) {
                appWrapper.classList.remove('sidebar-collapsed');
            } else if (localStorage.getItem(sidebarStateKey) === '1') {
                setSidebarCollapsed(true);
            }
        });
    }

    if (mobileToggle && sidebar) {
        /**
         * Toggle sidebar trÃƒÂªn thiÃ¡ÂºÂ¿t bÃ¡Â»â€¹ di Ã„â€˜Ã¡Â»â„¢ng.
         * NgÃ„Æ’n sÃ¡Â»Â± kiÃ¡Â»â€¡n click lan ra ngoÃƒÂ i Ã„â€˜Ã¡Â»Æ’ trÃƒÂ¡nh Ã„â€˜ÃƒÂ³ng ngay lÃ¡ÂºÂ­p tÃ¡Â»Â©c.
         */
        mobileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        /**
         * Ã„ÂÃƒÂ³ng sidebar khi ngÃ†Â°Ã¡Â»Âi dÃƒÂ¹ng nhÃ¡ÂºÂ¥n ra vÃƒÂ¹ng bÃƒÂªn ngoÃƒÂ i main-content.
         * ChÃ¡Â»â€° ÃƒÂ¡p dÃ¡Â»Â¥ng cho mÃƒÂ n hÃƒÂ¬nh nhÃ¡Â»Â (<= 1024px).
         */
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }

    /**
     * 2. XÃ¡Â»Â­ lÃƒÂ½ trÃ¡ÂºÂ¡ng thÃƒÂ¡i active cho cÃƒÂ¡c liÃƒÂªn kÃ¡ÂºÂ¿t Ã„â€˜iÃ¡Â»Âu hÃ†Â°Ã¡Â»â€ºng.
     * Khi click vÃƒÂ o menu item, highlight mÃ¡Â»Â¥c Ã„â€˜ÃƒÂ³ vÃƒÂ  Ã„â€˜ÃƒÂ³ng sidebar nÃ¡ÂºÂ¿u lÃƒÂ  mobile.
     */
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            if (this.classList.contains('dropdown-toggle')) {
                return;
            }

            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            if (window.innerWidth <= 1024 && sidebar) {
                sidebar.classList.remove('active');
            }
        });
    });

    document.querySelectorAll('.submenu-link').forEach(link => {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024 && sidebar) {
                sidebar.classList.remove('active');
            }
        });
    });

    /**
     * 3. TÃ¡Â»Â± Ã„â€˜ÃƒÂ³ng alert inline sau khi toast Ã„â€˜ÃƒÂ£ hiÃ¡Â»Æ’n thÃ¡Â»â€¹.
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
 * 4. KhÃ¡Â»Å¸i tÃ¡ÂºÂ¡o Select2 cho cÃƒÂ¡c thÃ¡ÂºÂ» select.
 * GiÃƒÂºp menu chÃ¡Â»Ân lÃ¡Â»Â±a hÃ¡Â»â€” trÃ¡Â»Â£ tÃƒÂ¬m kiÃ¡ÂºÂ¿m nÃ¡Â»â„¢i bÃ¡Â»â„¢.
 */
$(document).ready(function () {
    if ($.fn.select2) {
        $('select:not(.no-select2)').each(function () {
            var $el = $(this);
            var $modal = $el.closest('.modal, .modal-overlay, .modal-overlay-cust');
            var customMin = $el.data('search') === true ? 0 : 5;

            var options = {
                width: '100%',
                minimumResultsForSearch: customMin,
                language: {
                    noResults: function () {
                        return 'KhÃƒÂ´ng tÃƒÂ¬m thÃ¡ÂºÂ¥y kÃ¡ÂºÂ¿t quÃ¡ÂºÂ£';
                    }
                }
            };

            if ($modal.length) {
                options.dropdownParent = $modal;
            }

            $el.select2(options);
        });
    }
});

/**
 * 5. TiÃ¡Â»â€¡n ÃƒÂ­ch tÃƒÂ¬m kiÃ¡ÂºÂ¿m chung cho cÃƒÂ¡c trang cÃƒÂ³ input search.
 * HÃƒÂ m nÃƒÂ y cÃƒÂ³ thÃ¡Â»Æ’ Ã„â€˜Ã†Â°Ã¡Â»Â£c gÃ¡Â»Âi tÃ¡Â»Â« view riÃƒÂªng Ã„â€˜Ã¡Â»Æ’ lÃ¡Â»Âc nhanh.
 */
function globalSearch(inputId, targetSelector) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener('keyup', function () {
        const value = this.value.toLowerCase();
        const targets = document.querySelectorAll(targetSelector);

        targets.forEach(el => {
            const text = el.textContent.toLowerCase();
            el.style.display = text.includes(value) ? '' : 'none';
        });
    });
}

/**
 * 6. QuÃ¡ÂºÂ£n lÃƒÂ½ lÃ¡Â»â€”i Ã„â€˜Ã¡Â»â€¹nh dÃ¡ÂºÂ¡ng ngÃƒÂ y/thÃƒÂ¡ng.
 * TÃ¡Â»Â± Ã„â€˜Ã¡Â»â„¢ng hiÃ¡Â»â€¡n thÃƒÂ´ng bÃƒÂ¡o dÃ†Â°Ã¡Â»â€ºi ÃƒÂ´ nhÃ¡ÂºÂ­p liÃ¡Â»â€¡u nÃ¡ÂºÂ¿u sai Ã„â€˜Ã¡Â»â€¹nh dÃ¡ÂºÂ¡ng.
 */
function handleDateError(input) {
    let errorMsg = input.nextElementSibling;

    // NÃ¡ÂºÂ¿u chÃ†Â°a cÃƒÂ³ thÃ¡ÂºÂ» bÃƒÂ¡o lÃ¡Â»â€”i bÃƒÂªn dÃ†Â°Ã¡Â»â€ºi thÃƒÂ¬ tÃ¡ÂºÂ¡o mÃ¡Â»â€ºi.
    if (!errorMsg || !errorMsg.classList.contains('date-error-label')) {
        errorMsg = document.createElement('div');
        errorMsg.classList.add('date-error-label');
        errorMsg.style.color = '#ff3b30';
        errorMsg.style.fontSize = '11px';
        errorMsg.style.marginTop = '4px';
        errorMsg.style.fontWeight = '500';
        input.parentNode.insertBefore(errorMsg, input.nextSibling);
    }

    if (input.value && !input.validity.valid) {
        input.style.borderColor = '#ff3b30';
        errorMsg.innerText = 'NgÃƒÂ y thÃƒÂ¡ng khÃƒÂ´ng hÃ¡Â»Â£p lÃ¡Â»â€¡ (VÃƒÂ­ dÃ¡Â»Â¥: ngÃƒÂ y 31/02 lÃƒÂ  sai).';
        errorMsg.style.display = 'block';
    } else {
        input.style.borderColor = '';
        errorMsg.style.display = 'none';
        errorMsg.innerText = '';
    }
}

document.addEventListener('input', function (e) {
    if (e.target && (e.target.type === 'date' || e.target.type === 'month')) {
        handleDateError(e.target);
    }
});

document.addEventListener('blur', function (e) {
    if (e.target && (e.target.type === 'date' || e.target.type === 'month')) {
        handleDateError(e.target);
    }
}, true);

