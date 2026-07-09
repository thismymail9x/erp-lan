/*
 * L.A.N ERP - Chi tiết vụ việc.
 * Tách JS khỏi view để điều khiển tab, modal, preview ảnh và nhập tài liệu từ kho.
 */
(function () {
    let selectedVaultDocId = null;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('is-open');
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('is-open');
        }
    }

    function switchTab(tabName) {
        qsa('.tab-content').forEach(function (tab) {
            tab.classList.remove('active');
        });

        qsa('.nav-tab-item').forEach(function (item) {
            item.classList.remove('active');
        });

        const targetTab = document.getElementById('tab-' + tabName);
        if (targetTab) {
            targetTab.classList.add('active');
        }

        const targetItem = qs('.nav-tab-item[data-tab="' + tabName + '"]');
        if (targetItem) {
            targetItem.classList.add('active');
        }
    }

    function initSelect2(modalId) {
        if (!window.jQuery || !jQuery.fn.select2) {
            return;
        }

        const modal = jQuery('#' + modalId);
        jQuery('.select2-multi', modal).select2({
            dropdownParent: modal,
            width: '100%',
            placeholder: '-- Chọn nhân sự --'
        });

        jQuery('.select2-single', modal).select2({
            dropdownParent: modal,
            width: '100%'
        });
    }

    function openUploadStep(stepId, docName) {
        const stepInput = document.getElementById('modal_step_id');
        const fileNameInput = document.getElementById('modal_file_name');

        if (stepInput) {
            stepInput.value = stepId;
        }

        if (fileNameInput) {
            fileNameInput.value = docName;
        }

        openModal('uploadModal');
    }

    function previewImage(url, title) {
        const img = document.getElementById('previewImgElement');
        const titleEl = document.getElementById('previewImgTitle');

        if (img) {
            img.src = url;
        }

        if (titleEl) {
            titleEl.innerText = title || '';
        }

        openModal('imagePreviewModal');
    }

    function selectVaultDoc(id, row) {
        selectedVaultDocId = id;
        qsa('#vaultTableBody tr').forEach(function (item) {
            item.classList.remove('is-selected');
        });

        row.classList.add('is-selected');
        const radio = qs('input[type="radio"]', row);
        if (radio) {
            radio.checked = true;
        }

        const confirmButton = document.getElementById('btnConfirmImport');
        if (confirmButton) {
            confirmButton.disabled = false;
        }
    }

    function openVaultModal() {
        const page = qs('.case-detail-container');
        const tbody = document.getElementById('vaultTableBody');
        const confirmButton = document.getElementById('btnConfirmImport');

        openModal('vaultModal');
        selectedVaultDocId = null;

        if (confirmButton) {
            confirmButton.disabled = true;
        }

        if (!page || !tbody) {
            return;
        }

        fetch(page.dataset.vaultUrl)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                tbody.innerHTML = '';

                if (!data.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center p-20">Kho tài liệu hiện tại đang trống.</td></tr>';
                    return;
                }

                data.forEach(function (doc) {
                    const row = document.createElement('tr');
                    row.className = 'vault-row';
                    row.innerHTML = [
                        '<td><input type="radio" name="vault_doc" value="' + doc.id + '"></td>',
                        '<td><strong>' + doc.file_name + '</strong></td>',
                        '<td><span class="badge-secondary-minimal text-xs">' + doc.document_category + '</span></td>',
                        '<td class="text-sm">' + new Date(doc.created_at).toLocaleDateString('vi-VN') + '</td>'
                    ].join('');
                    row.addEventListener('click', function () {
                        selectVaultDoc(doc.id, row);
                    });
                    tbody.appendChild(row);
                });
            });
    }

    function confirmImport() {
        const page = qs('.case-detail-container');
        if (!selectedVaultDocId || !page) {
            return;
        }

        const formData = new FormData();
        formData.append('document_id', selectedVaultDocId);
        formData.append(page.dataset.csrfName, page.dataset.csrfHash);

        fetch(page.dataset.importDocUrl, {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (result.status === 'success') {
                    alert('Đã nhập tài liệu thành công.');
                    location.reload();
                    return;
                }

                alert('Có lỗi xảy ra: ' + result.message);
            });
    }

    function filterVault() {
        const input = document.getElementById('vaultSearch');
        if (!input) {
            return;
        }

        const filter = input.value.toUpperCase();
        qsa('#vaultTableBody tr').forEach(function (row) {
            const text = row.textContent || row.innerText;
            row.style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
        });
    }

    function applyDynamicTagColors() {
        qsa('[data-tag-color]').forEach(function (tag) {
            const color = tag.dataset.tagColor;
            tag.style.backgroundColor = color + '15';
            tag.style.color = color;
            tag.style.borderColor = color + '30';
        });
    }

    function formatMoneyInput(input) {
        input.value = input.value
            .replace(/[^\d]/g, '')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    document.addEventListener('DOMContentLoaded', function () {
        qsa('[data-progress-percent]').forEach(function (bar) {
            const percent = Number.parseInt(bar.dataset.progressPercent || '0', 10);
            bar.style.width = Math.max(0, Math.min(100, percent)) + '%';
        });

        applyDynamicTagColors();
        initSelect2('assignMembersModal');

        qsa('[data-tab]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                switchTab(tab.dataset.tab);
            });
        });

        qsa('[data-modal-open]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                const target = trigger.dataset.modalOpen;
                openModal(target);

                if (target === 'assignMembersModal' || target === 'reminderModal') {
                    initSelect2(target);
                }
            });
        });

        qsa('[data-modal-close]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                closeModal(trigger.dataset.modalClose);
            });
        });

        qsa('[data-confirm]:not(form)').forEach(function (element) {
            element.addEventListener('click', function (event) {
                if (!confirm(element.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });

        qsa('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });

        qsa('[data-preview-url]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                previewImage(trigger.dataset.previewUrl, trigger.dataset.previewTitle);
            });
        });

        qsa('[data-upload-step]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                openUploadStep(trigger.dataset.uploadStep, trigger.dataset.docName || '');
            });
        });

        qsa('[data-vault-open]').forEach(function (trigger) {
            trigger.addEventListener('click', openVaultModal);
        });

        qsa('[data-navigate-url]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                window.location.href = trigger.dataset.navigateUrl;
            });
        });

        const confirmImportButton = document.getElementById('btnConfirmImport');
        if (confirmImportButton) {
            confirmImportButton.addEventListener('click', confirmImport);
        }

        const vaultSearch = document.getElementById('vaultSearch');
        if (vaultSearch) {
            vaultSearch.addEventListener('keyup', filterVault);
        }

        qsa('[data-money-format]').forEach(function (input) {
            input.addEventListener('keyup', function () {
                formatMoneyInput(input);
            });
        });

        qsa('.case-modal').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal && modal.classList.contains('image-preview-modal')) {
                    closeModal(modal.id);
                }
            });
        });
    });
})();
