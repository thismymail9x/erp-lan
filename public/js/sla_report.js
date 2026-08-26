/**
 * L.A.N ERP - Customer care SLA report and configuration.
 */
$(document).ready(function() {
    enhanceSlaTables();

    const tabs = document.querySelectorAll('.sla-tab-btn');
    const panes = document.querySelectorAll('.sla-tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));

            tab.classList.add('active');
            const targetPane = document.getElementById(target);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });

    const formSetting = document.getElementById('formSlaSetting');
    if (formSetting) {
        formSetting.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(formSetting);
            if (typeof csrfToken !== 'undefined' && typeof csrfHash !== 'undefined') {
                formData.append(csrfToken, csrfHash);
            }

            fetch(baseUrl + '/customer-care/save-sla-setting', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert(result.message);
                    closeSlaModal();
                    location.reload();
                } else {
                    alert('L\u1ed7i: ' + result.message);
                }
            })
            .catch(err => {
                alert('L\u1ed7i k\u1ebft n\u1ed1i m\u1ea1ng: ' + err.message);
            });
        });
    }

    const formMonitoringSetting = document.getElementById('formMonitoringSetting');
    if (formMonitoringSetting) {
        formMonitoringSetting.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(formMonitoringSetting);
            if (typeof csrfToken !== 'undefined' && typeof csrfHash !== 'undefined') {
                formData.append(csrfToken, csrfHash);
            }

            fetch(baseUrl + '/customer-care/save-monitoring-status-setting', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert(result.message);
                    closeMonitoringModal();
                    location.reload();
                } else {
                    alert('L\u1ed7i: ' + result.message);
                }
            })
            .catch(err => {
                alert('L\u1ed7i k\u1ebft n\u1ed1i m\u1ea1ng: ' + err.message);
            });
        });
    }
});

function enhanceSlaTables() {
    $('.customer-care-shell table').each(function() {
        const headers = [];

        $(this).find('thead th').each(function() {
            headers.push($(this).text().replace(/\s+/g, ' ').trim());
        });

        if (!headers.length) {
            return;
        }

        $(this).find('tbody tr').each(function() {
            $(this).children('td').each(function(index) {
                if (!$(this).attr('data-label')) {
                    $(this).attr('data-label', headers[index] || '');
                }
            });
        });
    });
}

function openAddSlaModal() {
    const modal = document.getElementById('modalSlaSetting');
    const form = document.getElementById('formSlaSetting');
    if (modal && form) {
        form.reset();
        document.getElementById('setting_id').value = '';
        document.getElementById('modalSlaTitle').innerText = 'Th\u00eam c\u1ea5u h\u00ecnh tr\u1ea1ng th\u00e1i Ch\u0103m s\u00f3c';
        document.getElementById('setting_status_key').readOnly = false;
        modal.style.display = 'flex';
    }
}

function openEditSlaModal(btn) {
    const modal = document.getElementById('modalSlaSetting');
    const form = document.getElementById('formSlaSetting');
    if (modal && form) {
        form.reset();

        const id = btn.getAttribute('data-id');
        const key = btn.getAttribute('data-key');
        const name = btn.getAttribute('data-name');
        const hours = btn.getAttribute('data-hours');
        const color = btn.getAttribute('data-color');
        const sort = btn.getAttribute('data-sort');
        const active = btn.getAttribute('data-active');

        document.getElementById('setting_id').value = id;
        document.getElementById('setting_status_key').value = key;
        document.getElementById('setting_status_key').readOnly = true;
        document.getElementById('setting_status_name').value = name;
        document.getElementById('setting_sla_hours').value = hours;
        document.getElementById('setting_color').value = color;
        document.getElementById('color_picker').value = color;
        document.getElementById('setting_sort_order').value = sort;
        document.getElementById('setting_is_active').value = active;

        document.getElementById('modalSlaTitle').innerText = 'Ch\u1ec9nh s\u1eeda c\u1ea5u h\u00ecnh tr\u1ea1ng th\u00e1i Ch\u0103m s\u00f3c';
        modal.style.display = 'flex';
    }
}

function closeSlaModal() {
    const modal = document.getElementById('modalSlaSetting');
    if (modal) {
        modal.style.display = 'none';
    }
}

function deleteSlaSetting(id, name) {
    const message = `B\u1ea1n c\u00f3 ch\u1eafc ch\u1eafn mu\u1ed1n x\u00f3a c\u1ea5u h\u00ecnh tr\u1ea1ng th\u00e1i '${name}'? \u0110i\u1ec1u n\u00e0y s\u1ebd kh\u00f4ng x\u00f3a c\u00e1c d\u1eef li\u1ec7u l\u1ecbch s\u1eed c\u0169.`;
    if (!confirm(message)) {
        return;
    }

    const formData = new FormData();
    if (typeof csrfToken !== 'undefined' && typeof csrfHash !== 'undefined') {
        formData.append(csrfToken, csrfHash);
    }

    fetch(baseUrl + '/customer-care/delete-sla-setting/' + id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert(result.message);
            location.reload();
        } else {
            alert('L\u1ed7i: ' + result.message);
        }
    })
    .catch(err => {
        alert('L\u1ed7i k\u1ebft n\u1ed1i m\u1ea1ng: ' + err.message);
    });
}

function openAddMonitoringModal() {
    const modal = document.getElementById('modalMonitoringSetting');
    const form = document.getElementById('formMonitoringSetting');
    if (modal && form) {
        form.reset();
        document.getElementById('monitoring_setting_id').value = '';
        document.getElementById('modalMonitoringTitle').innerText = 'Th\u00eam c\u1ea5u h\u00ecnh tr\u1ea1ng th\u00e1i gi\u00e1m s\u00e1t';
        document.getElementById('monitoring_status_key').readOnly = false;
        document.getElementById('monitoring_color').value = '#ff3b30';
        document.getElementById('monitoring_color_picker').value = '#ff3b30';
        modal.style.display = 'flex';
    }
}

function openEditMonitoringModal(btn) {
    const modal = document.getElementById('modalMonitoringSetting');
    const form = document.getElementById('formMonitoringSetting');
    if (modal && form) {
        form.reset();

        document.getElementById('monitoring_setting_id').value = btn.getAttribute('data-id');
        document.getElementById('monitoring_status_key').value = btn.getAttribute('data-key');
        document.getElementById('monitoring_status_key').readOnly = true;
        document.getElementById('monitoring_status_name').value = btn.getAttribute('data-name');
        document.getElementById('monitoring_color').value = btn.getAttribute('data-color');
        document.getElementById('monitoring_color_picker').value = btn.getAttribute('data-color');
        document.getElementById('monitoring_sort_order').value = btn.getAttribute('data-sort');
        document.getElementById('monitoring_is_active').value = btn.getAttribute('data-active');

        document.getElementById('modalMonitoringTitle').innerText = 'Ch\u1ec9nh s\u1eeda c\u1ea5u h\u00ecnh tr\u1ea1ng th\u00e1i gi\u00e1m s\u00e1t';
        modal.style.display = 'flex';
    }
}

function closeMonitoringModal() {
    const modal = document.getElementById('modalMonitoringSetting');
    if (modal) {
        modal.style.display = 'none';
    }
}

function deleteMonitoringSetting(id, name) {
    const message = `B\u1ea1n c\u00f3 ch\u1eafc ch\u1eafn mu\u1ed1n x\u00f3a tr\u1ea1ng th\u00e1i gi\u00e1m s\u00e1t '${name}'?`;
    if (!confirm(message)) {
        return;
    }

    const formData = new FormData();
    if (typeof csrfToken !== 'undefined' && typeof csrfHash !== 'undefined') {
        formData.append(csrfToken, csrfHash);
    }

    fetch(baseUrl + '/customer-care/delete-monitoring-status-setting/' + id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert(result.message);
            location.reload();
        } else {
            alert('L\u1ed7i: ' + result.message);
        }
    })
    .catch(err => {
        alert('L\u1ed7i k\u1ebft n\u1ed1i m\u1ea1ng: ' + err.message);
    });
}
