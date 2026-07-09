let userSearchTimeout;
let userCheckAll;
let userRecordChecks;
let userBulkBar;
let userSelectedCount;

function initBulkElements() {
    userCheckAll = document.getElementById('check-all');
    userRecordChecks = document.querySelectorAll('.record-check');
    userBulkBar = document.getElementById('bulk-bar');
    userSelectedCount = document.getElementById('selected-count');
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.record-check:checked');

    if (!userBulkBar) {
        return;
    }

    if (checked.length > 0) {
        userBulkBar.style.display = 'flex';
        if (userSelectedCount) {
            userSelectedCount.innerText = checked.length + ' mục đã chọn';
        }
        return;
    }

    userBulkBar.style.display = 'none';
}

function rebindBulkActions() {
    initBulkElements();

    if (userCheckAll) {
        userCheckAll.addEventListener('change', function () {
            userRecordChecks.forEach(function (checkbox) {
                checkbox.checked = userCheckAll.checked;
            });
            updateBulkBar();
        });
    }

    userRecordChecks.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateBulkBar);
    });

    updateBulkBar();
}

async function fetchUsersByUrl(url) {
    const searchInput = document.getElementById('user-search');
    const tableContainer = document.getElementById('users-table-container');

    if (!searchInput || !tableContainer) {
        return;
    }

    try {
        tableContainer.style.opacity = '0.5';

        if (!url.searchParams.has('search')) {
            url.searchParams.set('search', searchInput.value);
        }
        if (!url.searchParams.has('status')) {
            const currentUrl = new URL(window.location.href);
            url.searchParams.set('status', currentUrl.searchParams.get('status') || 'active');
        }

        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await response.text();

        tableContainer.innerHTML = html;
        tableContainer.style.opacity = '1';
        window.history.pushState(null, '', url);
        rebindBulkActions();
    } catch (err) {
        console.error('Lỗi khi tải dữ liệu người dùng:', err);
        tableContainer.style.opacity = '1';
    }
}

async function applyBulkDelete() {
    const ids = Array.from(document.querySelectorAll('.record-check:checked')).map(function (checkbox) {
        return checkbox.value;
    });

    if (!confirm('Hệ thống sẽ xóa vĩnh viễn ' + ids.length + ' tài khoản. Tiếp tục?')) {
        return;
    }

    try {
        const formData = new FormData();
        ids.forEach(function (id) {
            formData.append('ids[]', id);
        });

        const response = await fetch(baseUrl + 'users/bulk-delete', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();

        if (res.code === 0) {
            location.reload();
        } else {
            alert('Lỗi: ' + res.error);
        }
    } catch (err) {
        alert('Lỗi kết nối máy chủ khi thực hiện Xóa chọn');
    }
}

function openPermissionModal(userId) {
    document.getElementById('permissionModal').style.display = 'flex';
    document.getElementById('permissionMatrixContainer').innerHTML = '<div class="text-center p-20 text-muted-dark"><i class="fas fa-spinner fa-spin m-r-5"></i> Đang tải dữ liệu bộ máy quyền...</div>';

    fetch(baseUrl + 'users/permissions/matrix/' + userId)
        .then(function (res) {
            if (!res.ok) {
                throw new Error('Network');
            }
            return res.text();
        })
        .then(function (html) {
            try {
                const json = JSON.parse(html);
                if (json.status === 'error') {
                    alert(json.message);
                    closePermissionModal();
                    return;
                }
            } catch (e) {
                // HTML response is expected for successful matrix loads.
            }

            document.getElementById('permissionMatrixContainer').innerHTML = html;
        })
        .catch(function () {
            alert('Có lỗi xảy ra khi tải bảng cấu hình phân quyền.');
            closePermissionModal();
        });
}

function closePermissionModal() {
    document.getElementById('permissionModal').style.display = 'none';
}

async function savePermissions(e, userId) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    btn.disabled = true;

    try {
        const response = await fetch(baseUrl + 'users/permissions/save/' + userId, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();

        if (res.status === 'success') {
            closePermissionModal();
            alert(res.message);
            if (typeof showToast === 'function') {
                showToast(res.message, 'success');
            }
        } else {
            alert(res.message);
        }
    } catch (err) {
        alert('Lỗi kết nối máy chủ khi lưu phân quyền');
    } finally {
        btn.innerHTML = '<i class="fas fa-save m-r-8"></i> Áp dụng Phân Quyền';
        btn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('user-search');
    const tableContainer = document.getElementById('users-table-container');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(userSearchTimeout);
            userSearchTimeout = setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('search', this.value);
                url.searchParams.set('page', 1);
                fetchUsersByUrl(url);
            }, 300);
        });
    }

    if (tableContainer) {
        tableContainer.addEventListener('click', function (e) {
            const link = e.target.closest('.pagination a, .sort-link');
            if (link) {
                e.preventDefault();
                fetchUsersByUrl(new URL(link.href));
            }
        });
    }

    rebindBulkActions();
});
