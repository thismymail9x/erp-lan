/**
 * L.A.N ERP - Notifications page interactions.
 */

$(document).ready(function() {
    initNotificationCreateForm();
    initNotificationList();
});

function initNotificationCreateForm() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2-enable').select2({
            placeholder: '-- Vui l\u00f2ng ch\u1ecdn --',
            allowClear: true,
            width: '100%'
        });
    }
}

function toggleRecipientFields(type) {
    if (type === 'individual') {
        $('#individualField').show();
        $('#departmentField').hide();
        $('.select2-enable[name="user_ids[]"]').attr('required', true);
    } else if (type === 'department') {
        $('#individualField').hide();
        $('#departmentField').show();
        $('.select2-enable[name="user_ids[]"]').attr('required', false);
    } else {
        $('#individualField').hide();
        $('#departmentField').hide();
        $('.select2-enable[name="user_ids[]"]').attr('required', false);
    }
}

function initNotificationList() {
    const listContainer = $('#notif-list-container');
    const filterForm = $('#notif-filter-form');

    if (!listContainer.length || !filterForm.length) {
        return;
    }

    let searchTimeout = null;

    $(document).on('change', '.ajax-filter', triggerFilter);

    $(document).on('input', '.ajax-filter-search', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(triggerFilter, 500);
    });

    function triggerFilter() {
        const formData = filterForm.serialize();
        const finalUrl = filterForm.attr('action') + '?' + formData;

        fetchUpdate(finalUrl);
        window.history.pushState({ path: finalUrl }, '', finalUrl);
    }

    async function fetchUpdate(url) {
        listContainer.css('opacity', '0.5');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            listContainer.html(html);
        } catch (err) {
            console.error('L\u1ed7i filter th\u00f4ng b\u00e1o AJAX:', err);
        } finally {
            listContainer.css('opacity', '1');
        }
    }

    $(document).on('click', '.btn-mark-read', function() {
        const btn = $(this);
        const id = btn.data('id');

        $.post(baseUrl + 'notifications/read/' + id, function() {
            const row = btn.closest('.notif-item-page');
            row.removeClass('unread').addClass('read');
            row.find('.notif-title').removeClass('unread').addClass('read');
            btn.remove();
        });
    });

    $(document).on('click', '.js-notif-open', function(e) {
        if ($(e.target).closest('a, button, input, label, .notif-actions').length) {
            return;
        }

        const href = ($(this).data('href') || '').toString().trim();
        if (href) {
            window.location.href = href;
        }
    });

    $('#markAllReadPage').on('click', function() {
        if (confirm('\u0110\u00e1nh d\u1ea5u t\u1ea5t c\u1ea3 l\u00e0 \u0111\u00e3 \u0111\u1ecdc?')) {
            $.post(baseUrl + 'notifications/read-all', function() {
                location.reload();
            });
        }
    });
}
