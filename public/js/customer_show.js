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
    .then(response => response.json())
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
        alert('Lỗi kết nối mạng: ' + err.message);
        location.reload();
    });
}
