/**
 * L.A.N ERP - Hồ sơ khách hàng 360 độ (Show)
 */
$(document).ready(function() {
    const tabs = document.querySelectorAll('#customerModuleTabs .tab-btn');
    const panes = document.querySelectorAll('.tab-pane');

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

    if (typeof Quill !== 'undefined' && document.getElementById('editor-container')) {
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Nhập nội dung chi tiết cuộc trao đổi, có hỗ trợ hình ảnh, link và định dạng...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        const btnSave = document.getElementById('btnSaveInteraction');
        const contentInput = document.getElementById('detailed_content_input');

        if (btnSave) {
            btnSave.addEventListener('click', function(e) {
                const htmlValue = quill.getSemanticHTML();
                contentInput.value = htmlValue;
            });
        }
    }

    const uploadInput = $('#customerDmsFileInput');
    const uploadZone = $('.customer-upload-zone');
    const uploadLabelText = uploadInput.next('label').find('span');
    const selectedFiles = $('#customerDmsSelectedFiles');
    let selectedUploadFiles = [];

    function fileKey(file) {
        return [file.name, file.size, file.lastModified].join('|');
    }

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
        return bytes + ' B';
    }

    function syncUploadInput() {
        if (!uploadInput.length) return;

        const transfer = new DataTransfer();
        selectedUploadFiles.forEach(function(file) {
            transfer.items.add(file);
        });
        uploadInput[0].files = transfer.files;
    }

    function renderUploadFiles() {
        if (!selectedFiles.length) return;

        selectedFiles.empty();

        if (!selectedUploadFiles.length) {
            uploadLabelText.text('Click để chọn một hoặc nhiều tệp');
            uploadZone.removeClass('has-files');
            return;
        }

        uploadZone.addClass('has-files');
        uploadLabelText.empty()
            .append(document.createTextNode('Đã chọn: '))
            .append($('<strong></strong>').text(selectedUploadFiles.length + ' tệp'));

        selectedUploadFiles.forEach(function(file, index) {
            const item = $('<div class="dms-selected-file"></div>');
            const name = $('<span class="dms-selected-file-name"></span>').text(file.name);
            const size = $('<span class="dms-selected-file-size"></span>').text(formatBytes(file.size));
            const remove = $('<button type="button" class="dms-selected-file-remove" title="Bỏ tệp này"><i class="fas fa-times"></i></button>');

            remove.on('click', function() {
                selectedUploadFiles.splice(index, 1);
                syncUploadInput();
                renderUploadFiles();
            });

            item.append(name).append(size).append(remove);
            selectedFiles.append(item);
        });

        const nameInput = $('#formCustomerUploadDocument input[name="file_name"]');
        if (selectedUploadFiles.length === 1 && !nameInput.val()) {
            const firstFileName = selectedUploadFiles[0].name;
            nameInput.val(firstFileName.split('.').slice(0, -1).join('.') || firstFileName);
        }
    }

    function addUploadFiles(files) {
        const existingKeys = new Set(selectedUploadFiles.map(fileKey));
        Array.from(files || []).forEach(function(file) {
            const key = fileKey(file);
            if (!existingKeys.has(key)) {
                selectedUploadFiles.push(file);
                existingKeys.add(key);
            }
        });

        syncUploadInput();
        renderUploadFiles();
    }

    if (uploadInput.length) {
        uploadInput.on('change', function() {
            addUploadFiles(this.files);
            this.value = '';
            syncUploadInput();
        });

        uploadZone.on('dragover', function(e) {
            e.preventDefault();
            uploadZone.addClass('is-dragover');
        });

        uploadZone.on('dragleave drop', function(e) {
            e.preventDefault();
            uploadZone.removeClass('is-dragover');
        });

        uploadZone.on('drop', function(e) {
            const files = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : [];
            addUploadFiles(files);
        });

        $('#formCustomerUploadDocument').on('submit', function(e) {
            if (!selectedUploadFiles.length) {
                e.preventDefault();
                alert('Vui lòng chọn ít nhất một tệp tin để tải lên.');
            }
        });
    }
});

let selectedVaultDocId = null;

function openVaultModal() {
    const modal = document.getElementById('vaultModal');
    if (modal) {
        modal.style.display = 'flex';
        selectedVaultDocId = null;
        if (document.getElementById('btnConfirmImport')) {
            document.getElementById('btnConfirmImport').disabled = true;
        }

        fetch(baseUrl + "/documents/vault-list?category=internal")
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('vaultTableBody');
                if (tbody) {
                    tbody.innerHTML = '';
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-20">Kho tài liệu hiện tại đang trống.</td></tr>';
                        return;
                    }
                    data.forEach(doc => {
                        const tr = document.createElement('tr');
                        tr.style.cursor = 'pointer';
                        tr.onclick = () => selectVaultDoc(doc.id, tr);
                        tr.innerHTML = `
                            <td><input type="radio" name="vault_doc" value="${doc.id}"></td>
                            <td><strong>${doc.file_name}</strong></td>
                            <td><span class="badge-secondary-minimal text-xs">${doc.document_category}</span></td>
                            <td class="text-sm">${new Date(doc.created_at).toLocaleDateString('vi-VN')}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            });
    }
}

function selectVaultDoc(id, row) {
    selectedVaultDocId = id;
    document.querySelectorAll('#vaultTableBody tr').forEach(r => r.style.background = 'white');
    row.style.background = 'rgba(0, 113, 227, 0.05)';
    row.querySelector('input[type="radio"]').checked = true;
    if (document.getElementById('btnConfirmImport')) {
        document.getElementById('btnConfirmImport').disabled = false;
    }
}

function confirmImport(entityId) {
    if (!selectedVaultDocId) return;

    const formData = new FormData();
    formData.append('document_id', selectedVaultDocId);
    formData.append(csrfToken, csrfHash);

    fetch(baseUrl + "/customers/import-doc/" + entityId, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert('Đã thêm tài liệu thành công.');
            location.reload();
        } else {
            alert('Có lỗi xảy ra: ' + result.message);
        }
    });
}

function filterVault() {
    let input = document.getElementById('vaultSearch');
    if (input) {
        let filter = input.value.toUpperCase();
        let tr = document.querySelectorAll('#vaultTableBody tr');
        tr.forEach(row => {
            let text = row.textContent || row.innerText;
            row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        });
    }
}

/**
 * API: Chuyển đổi trạng thái tư vấn và SLA của khách hàng qua AJAX (Không cần tải lại thủ công)
 */
function transitionCustomerStatus(customerId, statusKey) {
    if (!statusKey) return;
    
    if (!confirm('Bạn có chắc chắn muốn chuyển trạng thái tư vấn/chăm sóc của khách hàng này sang bước tiếp theo?')) {
        location.reload();
        return;
    }
    
    const formData = new FormData();
    formData.append('status_key', statusKey);
    // Sử dụng csrfToken và csrfHash từ layout chung của hệ thống
    if (typeof csrfToken !== 'undefined' && typeof csrfHash !== 'undefined') {
        formData.append(csrfToken, csrfHash);
    }
    
    fetch(baseUrl + "/customers/transition-status/" + customerId, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const rawResponse = await response.text();
        let result;
        try {
            result = JSON.parse(rawResponse);
        } catch (parseError) {
            throw new Error(response.ok ? 'May chu tra ve du lieu khong hop le.' : 'May chu tra loi HTTP ' + response.status + '.');
        }

        if (!response.ok) {
            throw new Error(result.message || ('May chu tra loi HTTP ' + response.status + '.'));
        }

        return result;
    })
    .then(result => {
        if (result.status === 'success') {
            // Hiển thị thông báo thành công và reload lại trang để đồng bộ
            alert(result.message);
            location.reload();
        } else {
            alert('Có lỗi xảy ra: ' + result.message);
            location.reload();
        }
    })
    .catch(err => {
        alert('Khong the cap nhat trang thai tu van: ' + err.message);
        location.reload();
    });
}
