/**
 * L.A.N ERP - Quáº£n lÃ½ ChuyÃªn cáº§n (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const tableContainer = $('#attendance-table-container');
    const filterForm = $('#attendance-filter-form');
    const ajaxFilters = $('.ajax-filter');

    // 1. Khá»Ÿi táº¡o Select2 cho bá»™ lá»c nhÃ¢n viÃªn
    if ($('#employee-filter').length) {
        $('#employee-filter').select2({
            placeholder: "Ch\u1ecdn nh\u00e2n vi\u00ean...",
            allowClear: true,
            width: '100%'
        });
    }

    // 2. Láº¯ng nghe sá»± kiá»‡n thay Ä‘á»•i trÃªn cÃ¡c Ã´ lá»c
    $(document).on('change', '.ajax-filter', function() {
        // Submit full form when the view type changes because PHP renders a different filter layout.
        // Ä‘á»ƒ PHP render láº¡i khung giao diá»‡n (vÃ¬ structure filter bar thay Ä‘á»•i)
        if ($(this).attr('id') === 'view-type') {
            filterForm.submit();
            return;
        }

        // Vá»›i cÃ¡c bá»™ lá»c khÃ¡c, thá»±c hiá»‡n AJAX
        triggerFilter();
    });

    // 3. Xá»­ lÃ½ sáº¯p xáº¿p (Sorting) qua AJAX
    $(document).on('click', '.sort-link', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        fetchUpdate(url);
    });

    /**
     * Thu tháº­p dá»¯ liá»‡u tá»« form vÃ  kÃ­ch hoáº¡t táº£i láº¡i báº£ng.
     */
    function triggerFilter() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        fetchUpdate(finalUrl);
        
        // Cáº­p nháº­t URL trÃªn browser (khÃ´ng reload) Ä‘á»ƒ user copy link Ä‘Æ°á»£c
        window.history.pushState({path: finalUrl}, '', finalUrl);
    }

    /**
     * HÃ m trung tÃ¢m thá»±c hiá»‡n gá»i AJAX vÃ  cáº­p nháº­t vÃ¹ng chá»©a báº£ng.
     */
    async function fetchUpdate(url) {
        // Hiá»‡u á»©ng loading má» báº£ng
        tableContainer.css('opacity', '0.5');
        
        // Cáº­p nháº­t link Xuáº¥t Excel Ä‘á»ƒ khá»›p vá»›i bá»™ lá»c thÃ¡ng hiá»‡n táº¡i
        const monthVal = $('[name="month"]').val();
        const exportBtn = $('.btn-filter-secondary:contains("Xuáº¥t Excel")');
        if (exportBtn.length && monthVal) {
            const exportUrl = exportBtn.attr('href').split('?')[0] + '?month=' + monthVal;
            exportBtn.attr('href', exportUrl);
        }
        
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            
            tableContainer.html(html);
            
            // Re-initialize checkbox listeners in the new HTML
            initCheckboxListeners();
            
        } catch (err) {
            console.error('L\u1ed7i filter AJAX:', err);
        } finally {
            tableContainer.css('opacity', '1');
        }
    }

    /**
     * Khá»Ÿi táº¡o láº¡i cÃ¡c listener cho tÃ­nh nÄƒng Bulk Actions sau khi náº¡p HTML má»›i.
     */
    function initCheckboxListeners() {
        const checkAll = document.getElementById('check-all');
        const recordChecks = document.querySelectorAll('.record-check');
        const bulkBar = document.getElementById('bulk-bar');
        const selectedCount = document.getElementById('selected-count');

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                recordChecks.forEach(cb => cb.checked = checkAll.checked);
                updateBar();
            });
        }

        recordChecks.forEach(cb => {
            cb.addEventListener('change', updateBar);
        });

        function updateBar() {
            const checked = document.querySelectorAll('.record-check:checked');
            if (bulkBar) {
                if (checked.length > 0) {
                    bulkBar.style.display = 'flex';
                    if (selectedCount) selectedCount.innerText = checked.length + ' m\u1ee5c \u0111\u00e3 ch\u1ecdn';
                } else {
                    bulkBar.style.display = 'none';
                }
            }
        }
    }

    /**
     * Thá»±c hiá»‡n cáº­p nháº­t tráº¡ng thÃ¡i hÃ ng loáº¡t.
     */
    window.applyBulkUpdate = async function() {
        const status = document.getElementById('bulk-status').value;
        if (!status) return alert('Vui l\u00f2ng ch\u1ecdn tr\u1ea1ng th\u00e1i m\u1edbi.');

        const ids = Array.from(document.querySelectorAll('.record-check:checked')).map(cb => cb.value);
        if (!confirm('H\u1ec7 th\u1ed1ng s\u1ebd c\u1eadp nh\u1eadt tr\u1ea1ng th\u00e1i cho ' + ids.length + ' nh\u00e2n vi\u00ean \u0111\u01b0\u1ee3c ch\u1ecdn. Ti\u1ebfp t\u1ee5c?')) return;

        try {
            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            formData.append('status', status);

            const response = await fetch('bulk-update', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const res = await response.json();
            if (res.code === 0) {
                // Sau khi bulk update thÃ nh cÃ´ng, ta chá»‰ cáº§n refresh báº£ng AJAX
                triggerFilter();
                // áº¨n bar bulk
                if (document.getElementById('bulk-bar')) document.getElementById('bulk-bar').style.display = 'none';
                if (document.getElementById('check-all')) document.getElementById('check-all').checked = false;
            } else {
                alert('L\u1ed7i: ' + res.error);
            }
        } catch (err) {
            alert('L\u1ed7i k\u1ebft n\u1ed1i m\u00e1y ch\u1ee7.');
        }
    }

    /**
     * Xem trÆ°á»›c hÃ¬nh áº£nh.
     */
    window.previewImage = function(src) {
        if (src) window.open(src, '_blank', 'noopener,noreferrer');
    }

    // Khá»Ÿi táº¡o láº§n Ä‘áº§u
    initCheckboxListeners();

    $(document).on('click', '.js-apply-bulk-update', function() {
        applyBulkUpdate();
    });
});

/**
 * === INLINE STATUS EDIT ===
 * Ba hÃ m toÃ n cá»¥c Ä‘á»ƒ xá»­ lÃ½ dropdown sá»­a tráº¡ng thÃ¡i trá»±c tiáº¿p trong báº£ng.
 */

// ÄÃ³ng táº¥t cáº£ dropdown khÃ¡c khi má»Ÿ má»™t cÃ¡i má»›i
function closeAllStatusDropdowns(exceptId) {
    document.querySelectorAll('.status-inline-dropdown').forEach(el => {
        const id = el.id.replace('status-drop-', '');
        if (id != exceptId) el.style.display = 'none';
    });
}

// Toggle dropdown sá»­a tráº¡ng thÃ¡i
window.toggleStatusDropdown = function(id) {
    const drop = document.getElementById('status-drop-' + id);
    if (!drop) return;
    const isVisible = drop.style.display !== 'none';
    closeAllStatusDropdowns(id);
    drop.style.display = isVisible ? 'none' : 'block';
};

// ÄÃ³ng má»™t dropdown cá»¥ thá»ƒ
window.closeStatusDropdown = function(id) {
    const drop = document.getElementById('status-drop-' + id);
    if (drop) drop.style.display = 'none';
};

// Map value -> label hiá»ƒn thá»‹ báº¡ch trong badge
const STATUS_LABEL_MAP = {
    'REGULAR':         { label: '\u0110\u00daNG GI\u1edc',      cls: 'att-badge-regular' },
    'LATE':            { label: 'TR\u1ec4 / S\u1edaM',      cls: 'att-badge-late' },
    'EARLY_LEAVE':     { label: 'V\u1ec0 S\u1edaM',         cls: 'att-badge-late' },
    'LEAVE_MORNING':   { label: 'NGH\u1ec8 S\u00c1NG',      cls: 'att-badge-leave-half' },
    'LEAVE_AFTERNOON': { label: 'NGH\u1ec8 CHI\u1ec0U',     cls: 'att-badge-leave-half' },
    'LEAVE_FULL_DAY':  { label: 'NGH\u1ec8 C\u1ea2 NG\u00c0Y',  cls: 'att-badge-leave' },
};

// Ãp dá»¥ng tráº¡ng thÃ¡i má»›i qua AJAX vÃ  cáº­p nháº­t badge
window.applyInlineStatus = async function(attId, newStatus, itemEl) {
    const wrapper = itemEl.closest('.inline-status-wrapper');
    const badge   = wrapper ? wrapper.querySelector('.inline-status-badge') : null;
    const drop    = document.getElementById('status-drop-' + attId);

    // Optimistic UI: áº©n dropdown ngay
    if (drop) drop.style.display = 'none';
    if (badge) badge.style.opacity = '0.5';

    try {
        const fd = new FormData();
        fd.append('status', newStatus);

        const res = await fetch('/attendance/update-status/' + attId, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.code === 0) {
            // Cáº­p nháº­t badge
            const info = STATUS_LABEL_MAP[newStatus] || { label: newStatus, cls: 'att-badge-neutral' };
            if (badge) {
                // XÃ³a táº¥t cáº£ class badge cÅ©
                badge.className = badge.className.replace(/att-badge-\S+/g, '').trim();
                badge.classList.add('att-badge-base', info.cls, 'inline-status-badge');
                badge.innerHTML = info.label + ' <i class="fas fa-pen" style="font-size:9px;margin-left:3px;opacity:0.65;"></i>';
            }

            // Äá»•i mÃ u hÃ ng
            const row = wrapper ? wrapper.closest('tr') : null;
            if (row) {
                const isLeave = newStatus.startsWith('LEAVE_');
                if (isLeave) {
                    row.style.cssText = 'background-color: #f0f9ff; border-left: 4px solid #007aff;';
                } else if (newStatus === 'LATE' || newStatus === 'EARLY_LEAVE') {
                    row.style.cssText = 'background-color: #fff9e6; border-left: 4px solid #ffcc00;';
                } else {
                    row.style.cssText = 'border-bottom: 1px solid #f8f8f8;';
                }
            }

            // Cáº­p nháº­t highlight active trong dropdown
            if (drop) {
                drop.querySelectorAll('.status-drop-item').forEach(el => {
                    if (el.getAttribute('data-val') === newStatus) {
                        el.style.background = '#f0f5ff';
                        el.style.fontWeight = '700';
                    } else {
                        el.style.background = '';
                        el.style.fontWeight = '';
                    }
                });
            }

            // Flash xanh nháº¹
            if (badge) {
                badge.style.opacity = '1';
                badge.style.transition = 'box-shadow 0.3s';
                badge.style.boxShadow = '0 0 0 3px rgba(52,199,89,0.35)';
                setTimeout(() => { badge.style.boxShadow = ''; }, 800);
            }
        } else {
            alert('L\u1ed7i: ' + (data.error || 'Kh\u00f4ng th\u1ec3 c\u1eadp nh\u1eadt tr\u1ea1ng th\u00e1i.'));
            if (badge) badge.style.opacity = '1';
        }
    } catch (err) {
        alert('L\u1ed7i k\u1ebft n\u1ed1i m\u00e1y ch\u1ee7.');
        if (badge) badge.style.opacity = '1';
    }
};

document.addEventListener('click', function(e) {
    const thumb = e.target.closest('.att-thumb');
    if (thumb) {
        previewImage(thumb.src);
        return;
    }

    const badge = e.target.closest('.inline-status-badge');
    if (badge) {
        const wrapper = badge.closest('.inline-status-wrapper');
        if (wrapper) toggleStatusDropdown(wrapper.getAttribute('data-att-id'));
        return;
    }

    const closeItem = e.target.closest('.status-drop-close');
    if (closeItem) {
        const wrapper = closeItem.closest('.inline-status-wrapper');
        if (wrapper) closeStatusDropdown(wrapper.getAttribute('data-att-id'));
        return;
    }

    const statusItem = e.target.closest('.status-drop-item[data-val]');
    if (statusItem) {
        const wrapper = statusItem.closest('.inline-status-wrapper');
        if (wrapper) applyInlineStatus(wrapper.getAttribute('data-att-id'), statusItem.getAttribute('data-val'), statusItem);
    }
});

// ÄÃ³ng dropdown khi click ra ngoÃ i
document.addEventListener('click', function(e) {
    if (!e.target.closest('.inline-status-wrapper')) {
        closeAllStatusDropdowns(null);
    }
});
