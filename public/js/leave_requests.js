/**
 * L.A.N ERP - Leave requests interactions.
 */

$(document).ready(function() {
    initLeaveFilters();
    initLeaveFormValidation();
    initLeaveApprovalActions();
});

function initLeaveFilters() {
    const filterForm = $('#leave-filter-form');
    const resultsContainer = $('#leave-table-container');

    if (!filterForm.length || !resultsContainer.length) {
        return;
    }

    let debounceTimer;

    function performSearch(url = window.location.pathname) {
        const formData = filterForm.serialize();
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            data: url === window.location.pathname ? formData : null,
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
            },
            error: function() {
                resultsContainer.css('opacity', '1');
                console.error('L\u1ed7i khi t\u1ea3i k\u1ebft qu\u1ea3 l\u1ecdc.');
            }
        });
    }

    filterForm.find('input[name="search"]').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => performSearch(), 500);
    });

    filterForm.find('select, input[name="month"]').on('change', function() {
        performSearch();
    });

    $(document).on('click', '#leave-pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
                $('html, body').animate({ scrollTop: $('.dashboard-header-wrapper').offset().top - 20 }, 500);
            }
        });
    });
}

function initLeaveFormValidation() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const totalDaysSpan = document.getElementById('totalDays');
    const calcSummary = document.getElementById('calcSummary');
    const noticeWarning = document.getElementById('noticeWarning');
    const warningMessage = document.getElementById('warningMessage');
    const submitBtn = document.querySelector('.btn-premium[type="submit"]');

    if (!startDate || !endDate || !totalDaysSpan || !calcSummary || !noticeWarning || !warningMessage || !submitBtn) {
        return;
    }

    const isEmergency = document.getElementById('is_emergency');
    const isEditForm = !!document.getElementById('status');

    function setSubmitState(enabled) {
        submitBtn.disabled = !enabled;
        submitBtn.style.opacity = enabled ? '1' : '0.5';
        submitBtn.style.cursor = enabled ? 'pointer' : 'not-allowed';
    }

    function checkValidation() {
        const startVal = startDate.value;
        const endDateGroup = document.getElementById('end_date_group');
        const durationRadios = document.querySelectorAll('input[name="leave_duration"]');
        let isHalfDay = false;

        durationRadios.forEach(radio => {
            if (radio.checked && radio.value !== 'full_day') {
                isHalfDay = true;
            }
        });

        if (startVal) {
            endDate.setAttribute('min', startVal);
        }

        if (isHalfDay) {
            if (endDateGroup) endDateGroup.style.display = 'none';
            if (startVal) endDate.value = startVal;
        } else if (endDateGroup) {
            endDateGroup.style.display = 'block';
        }

        const endValActual = endDate.value;
        const start = new Date(startVal);
        const end = new Date(endValActual);

        if (endValActual && startVal && end < start) {
            endDate.style.borderColor = '#ff3b30';
            endDate.style.background = 'rgba(255, 59, 48, 0.05)';
            warningMessage.innerText = 'L\u1ed7i: Ng\u00e0y k\u1ebft th\u00fac kh\u00f4ng th\u1ec3 s\u1edbm h\u01a1n ng\u00e0y b\u1eaft \u0111\u1ea7u!';
            noticeWarning.style.display = 'flex';
            noticeWarning.className = 'lan-status-box lan-status-error';
            setSubmitState(false);
            return;
        }

        endDate.style.borderColor = '';
        endDate.style.background = '';
        noticeWarning.className = 'lan-status-box lan-status-warning';
        setSubmitState(true);

        const diffTime = Math.abs(end - start);
        let daysToLeave = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        if (isHalfDay) {
            daysToLeave = 0.5;
        }

        if (!isNaN(daysToLeave) && endValActual && startVal) {
            totalDaysSpan.innerText = daysToLeave;
            calcSummary.style.display = 'block';
        } else {
            calcSummary.style.display = 'none';
        }

        if (isEditForm) {
            noticeWarning.style.display = 'none';
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const noticeDays = Math.floor((start - today) / (1000 * 60 * 60 * 24));
        let errMsg = '';

        if (startVal && endValActual && !(isEmergency && isEmergency.checked)) {
            if (daysToLeave === 1 && noticeDays < 1) {
                errMsg = 'Ngh\u1ec9 1 ng\u00e0y c\u1ea7n b\u00e1o tr\u01b0\u1edbc \u00edt nh\u1ea5t 1 ng\u00e0y l\u00e0m vi\u1ec7c.';
            } else if (daysToLeave >= 2 && daysToLeave < 5 && noticeDays < 3) {
                errMsg = 'Ngh\u1ec9 t\u1eeb 2-4 ng\u00e0y c\u1ea7n b\u00e1o tr\u01b0\u1edbc \u00edt nh\u1ea5t 3 ng\u00e0y l\u00e0m vi\u1ec7c.';
            } else if (daysToLeave >= 5 && noticeDays < 7) {
                errMsg = 'Ngh\u1ec9 t\u1eeb 5 ng\u00e0y tr\u1edf l\u00ean c\u1ea7n b\u00e1o tr\u01b0\u1edbc \u00edt nh\u1ea5t 7 ng\u00e0y l\u00e0m vi\u1ec7c.';
            }
        }

        if (errMsg) {
            warningMessage.innerText = errMsg;
            noticeWarning.style.display = 'flex';
            setSubmitState(false);
        } else {
            noticeWarning.style.display = 'none';
            setSubmitState(true);
        }
    }

    startDate.addEventListener('change', checkValidation);
    endDate.addEventListener('change', checkValidation);
    if (isEmergency) {
        isEmergency.addEventListener('change', checkValidation);
    }

    document.querySelectorAll('input[name="leave_duration"]').forEach(radio => {
        radio.addEventListener('change', checkValidation);
    });

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2-enable').select2({ width: '100%' });
    }

    checkValidation();
}

function initLeaveApprovalActions() {
    const modal = document.getElementById('leaveModal');
    if (!modal) {
        return;
    }

    window.currentId = null;

    window.closeModal = function() {
        modal.style.display = 'none';
    };

    window.formatDate = function(dateStr) {
        if (!dateStr) return '...';
        const d = new Date(dateStr);
        return d.toLocaleDateString('vi-VN');
    };

    window.handleApproval = function(id, action) {
        const note = (action === 'approved') ? '\u0110\u00e3 \u0111\u1ed3ng \u00fd cho ngh\u1ec9.' : 'Kh\u00f4ng \u0111\u01b0\u1ee3c ph\u00ea duy\u1ec7t.';
        if (!confirm('B\u1ea1n c\u00f3 ch\u1eafc mu\u1ed1n th\u1ef1c hi\u1ec7n h\u00e0nh \u0111\u1ed9ng n\u00e0y?')) return;
        submitApproval(id, action, note);
    };

    $('#btnApprove').on('click', function() {
        submitApproval(window.currentId, 'approved', $('#approvalNote').val());
    });

    $('#btnReject').on('click', function() {
        submitApproval(window.currentId, 'rejected', $('#approvalNote').val());
    });

    function submitApproval(id, action, note) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('note', note);
        if (typeof csrfToken !== 'undefined' && typeof csrfHash !== 'undefined') {
            fd.append(csrfToken, csrfHash);
        }

        fetch(baseUrl + 'leave-requests/approve/' + id, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            window.closeModal();
        }
    });
}