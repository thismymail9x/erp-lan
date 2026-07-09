/**
 * L.A.N ERP - Lá»‹ch sá»­ Cháº¥m cÃ´ng CÃ¡ nhÃ¢n (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const tableContainer = $('#history-table-container');
    const filterForm = $('.filter-form');

    // Láº¯ng nghe thay Ä‘á»•i trÃªn thÃ¡ng
    $(document).on('change', 'input[name="month"]', function() {
        triggerAjax();
    });

    async function triggerAjax() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        tableContainer.css('opacity', '0.5');
        
        try {
            const response = await fetch(finalUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            tableContainer.html(html);
            
            // Cáº­p nháº­t URL trÃ¬nh duyá»‡t
            window.history.pushState({path: finalUrl}, '', finalUrl);
            
        } catch (err) {
            console.error('L\u1ed7i history AJAX:', err);
        } finally {
            tableContainer.css('opacity', '1');
        }
    }

    // --- Xá»¬ LÃ CHá»ŒN HÃ€NG LOáº T (BULK LOGIC) ---
    const bulkBar = $('#bulk-bar');
    const selectedCountLabel = $('#selected-count');

    // Chá»n táº¥t cáº£
    $(document).on('change', '#check-all', function() {
        $('.record-check').prop('checked', $(this).prop('checked'));
        updateBulkBar();
    });

    // Chá»n láº»
    $(document).on('change', '.record-check', function() {
        updateBulkBar();
    });

    function updateBulkBar() {
        const selected = $('.record-check:checked');
        const count = selected.length;
        
        if (count > 0) {
            selectedCountLabel.text(count + ' m\u1ee5c \u0111\u00e3 ch\u1ecdn');
            bulkBar.fadeIn(200).css('display', 'flex');
        } else {
            bulkBar.fadeOut(200);
            $('#check-all').prop('checked', false);
        }
    }

    // Thá»±c thi cáº­p nháº­t hÃ ng loáº¡t
    window.applyBulkUpdate = async function() {
        const selected = $('.record-check:checked');
        const ids = [];
        selected.each(function() { ids.push($(this).val()); });
        
        const status = $('#bulk-status').val();
        if (!status) {
            alert('Vui l\u00f2ng ch\u1ecdn tr\u1ea1ng th\u00e1i m\u1edbi!');
            return;
        }

        if (!confirm('B\u1ea1n c\u00f3 ch\u1eafc ch\u1eafn mu\u1ed1n c\u1eadp nh\u1eadt tr\u1ea1ng th\u00e1i cho ' + ids.length + ' b\u1ea3n ghi n\u00e0y?')) return;

        try {
            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            formData.append('status', status);

            const response = await fetch('bulk-update', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            if (result.code === 0) {
                // ThÃ nh cÃ´ng: Refresh láº¡i báº£ng
                triggerAjax();
                alert(result.message);
            } else {
                alert('L\u1ed7i: ' + result.error);
            }
        } catch (err) {
            console.error('Bulk Update error:', err);
            alert('L\u1ed7i k\u1ebft n\u1ed1i khi c\u1eadp nh\u1eadt h\u00e0ng lo\u1ea1t.');
        }
    }

    // Tiá»‡n Ã­ch xem áº£nh minh chá»©ng
    window.previewImage = function(src) {
        if (src) window.open(src, '_blank', 'noopener,noreferrer');
    }

    $(document).on('click', '.js-apply-bulk-update', function() {
        applyBulkUpdate();
    });
});

/**
 * === INLINE STATUS EDIT ===
 * CÃ¡c hÃ m xá»­ lÃ½ dropdown sá»­a tráº¡ng thÃ¡i trá»±c tiáº¿p trong báº£ng chi tiáº¿t cá»§a nhÃ¢n viÃªn.
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

// Map value -> label hiá»ƒn thá»‹ trong badge
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

        // Láº¥y update URL tá»« data attribute (há»— trá»£ dynamic base_url cá»§a ERP), fallback vá» hardcode path
        const updateUrl = wrapper ? wrapper.getAttribute('data-update-url') : ('/attendance/update-status/' + attId);

        const res = await fetch(updateUrl, {
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

            // Flash xanh nháº¹ Ä‘á»ƒ xÃ¡c thá»±c trá»±c quan thÃ nh cÃ´ng
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