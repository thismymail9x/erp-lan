/*
 * JS cho quản lý template ZNS.
 * Các style động dùng class CSS, view chỉ truyền endpoint qua data-*.
 */
document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.zns-templates-page');
    if (!page) {
        return;
    }

    const endpoints = {
        sync: page.dataset.syncUrl || '',
        saveMappings: page.dataset.saveMappingsUrl || '',
        deleteTemplate: page.dataset.deleteUrl || ''
    };

    const customerFieldsForZns = {
        name: 'Tên khách hàng (name)',
        code: 'Mã khách hàng (code)',
        phone: 'Số điện thoại chính (phone)',
        zalo_phone: 'Số điện thoại Zalo (zalo_phone)',
        email: 'Email chính (email)',
        company: 'Tên công ty (company)',
        address: 'Địa chỉ (address)',
        care_status: 'Trạng thái tư vấn (care_status)',
        customer_segment: 'Phân khúc khách hàng (customer_segment)'
    };

    function setHidden(element, hidden) {
        if (element) {
            element.classList.toggle('zns-hidden', hidden);
        }
    }

    function openModal(modal) {
        modal.classList.add('open');
    }

    function closeModal(modal) {
        modal.classList.remove('open');
    }

    const syncModal = document.getElementById('sync-modal');
    const btnOpenSync = document.getElementById('btn-open-sync-modal');
    const btnCloseSync = document.getElementById('btn-close-sync-modal');
    const btnCancelSync = document.getElementById('btn-cancel-sync');
    const syncForm = document.getElementById('sync-template-form');
    const btnSubmitSync = document.getElementById('btn-submit-sync');

    btnOpenSync.addEventListener('click', function () {
        openModal(syncModal);
        document.getElementById('input-template-id').focus();
    });

    function closeSyncModal() {
        closeModal(syncModal);
        syncForm.reset();
    }

    btnCloseSync.addEventListener('click', closeSyncModal);
    btnCancelSync.addEventListener('click', closeSyncModal);

    syncForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const templateId = document.getElementById('input-template-id').value.trim();
        const templateName = document.getElementById('input-template-name').value.trim();

        if (!templateId || !templateName) {
            alert('Vui lòng điền đầy đủ thông tin.');
            return;
        }

        btnSubmitSync.disabled = true;
        btnSubmitSync.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải từ Zalo...';

        const formData = new FormData();
        formData.append('template_id', templateId);
        formData.append('template_name', templateName);

        fetch(endpoints.sync, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.status === 'success') {
                    alert(data.message);
                    closeSyncModal();
                    window.location.reload();
                    return;
                }

                alert(data.message || 'Lỗi đồng bộ mẫu tin. Vui lòng kiểm tra lại Access Token hoặc ID mẫu tin.');
                btnSubmitSync.disabled = false;
                btnSubmitSync.innerHTML = '<i class="fas fa-cloud-download-alt"></i> Đồng bộ ngay';
            })
            .catch(function (error) {
                console.error(error);
                alert('Có lỗi mạng xảy ra khi gọi API. Vui lòng kiểm tra lại.');
                btnSubmitSync.disabled = false;
                btnSubmitSync.innerHTML = '<i class="fas fa-cloud-download-alt"></i> Đồng bộ ngay';
            });
    });

    const mappingModal = document.getElementById('mapping-modal');
    const btnCloseMapping = document.getElementById('btn-close-mapping-modal');
    const btnCancelMapping = document.getElementById('btn-cancel-mapping');
    const mappingForm = document.getElementById('save-mappings-form');
    const btnSubmitMapping = document.getElementById('btn-submit-mapping');
    const mappingFieldsContainer = document.getElementById('mapping-fields-container');
    const mappingTemplateName = document.getElementById('mapping-template-name');
    const mappingTemplateId = document.getElementById('mapping-template-id');

    function closeMappingModal() {
        closeModal(mappingModal);
        mappingFieldsContainer.innerHTML = '';
        mappingForm.reset();
    }

    btnCloseMapping.addEventListener('click', closeMappingModal);
    btnCancelMapping.addEventListener('click', closeMappingModal);

    function buildMappingEmptyState() {
        const empty = document.createElement('div');
        empty.className = 'zns-empty-inline zns-empty-inline-center';
        empty.innerText = 'Mẫu tin này không chứa biến số nào.';
        return empty;
    }

    function buildMappingFieldRow(param, mappings) {
        const row = document.createElement('div');
        row.className = 'zns-template-mapping-row';

        const label = document.createElement('div');
        label.className = 'zns-template-mapping-label';
        label.innerText = param;
        row.appendChild(label);

        const selectType = document.createElement('select');
        selectType.className = 'form-input-custom no-select2 zns-template-mapping-control';
        selectType.innerHTML = '<option value="field">Trường khách hàng</option><option value="static">Nhập giá trị tĩnh</option>';
        row.appendChild(selectType);

        const containerValue = document.createElement('div');
        containerValue.className = 'mapping-value-cell';

        const selectField = document.createElement('select');
        selectField.className = 'form-input-custom no-select2 zns-template-mapping-control';
        selectField.name = `mapping[${param}]`;

        let fieldOptions = '<option value="">-- Chọn trường dữ liệu --</option>';
        const currentVal = mappings[param] || '';
        const isStaticValue = currentVal.startsWith('#');

        Object.entries(customerFieldsForZns).forEach(function ([key, fieldLabel]) {
            const normalizedParam = param.toLowerCase();
            const isSelected = (!isStaticValue && currentVal === key)
                || (!currentVal && normalizedParam.includes('name') && key === 'name')
                || (!currentVal && normalizedParam.includes('phone') && key === 'phone')
                || (!currentVal && normalizedParam.includes('code') && key === 'code');
            fieldOptions += `<option value="${key}" ${isSelected ? 'selected' : ''}>${fieldLabel}</option>`;
        });
        selectField.innerHTML = fieldOptions;
        containerValue.appendChild(selectField);

        const inputStatic = document.createElement('input');
        inputStatic.type = 'text';
        inputStatic.className = 'form-input-custom zns-template-mapping-control zns-hidden';
        inputStatic.placeholder = 'Nhập chuỗi tĩnh (VD: L.A.N)...';

        if (isStaticValue) {
            selectType.value = 'static';
            setHidden(selectField, true);
            selectField.name = '';

            setHidden(inputStatic, false);
            inputStatic.name = `mapping[${param}]`;
            inputStatic.value = currentVal.substring(1);
        }

        selectType.addEventListener('change', function () {
            const isField = this.value === 'field';
            setHidden(selectField, !isField);
            selectField.name = isField ? `mapping[${param}]` : '';
            selectField.required = isField;

            setHidden(inputStatic, isField);
            inputStatic.name = isField ? '' : `mapping[${param}]`;
            inputStatic.required = !isField;

            if (!isField) {
                inputStatic.focus();
            }
        });

        containerValue.appendChild(inputStatic);
        row.appendChild(containerValue);

        return row;
    }

    document.querySelectorAll('.btn-open-mapping-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            let params = [];
            let mappings = {};

            try {
                params = JSON.parse(this.dataset.params || '[]');
                mappings = JSON.parse(this.dataset.mappings || '{}');
            } catch (error) {
                console.error(error);
            }

            mappingTemplateId.value = id;
            mappingTemplateName.innerText = name;
            mappingFieldsContainer.innerHTML = '';

            if (params.length === 0) {
                mappingFieldsContainer.appendChild(buildMappingEmptyState());
            } else {
                params.forEach(function (param) {
                    mappingFieldsContainer.appendChild(buildMappingFieldRow(param, mappings));
                });
            }

            openModal(mappingModal);
        });
    });

    mappingForm.addEventListener('submit', function (event) {
        event.preventDefault();

        btnSubmitMapping.disabled = true;
        btnSubmitMapping.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

        const formData = new FormData(mappingForm);
        mappingFieldsContainer.querySelectorAll('.zns-template-mapping-row').forEach(function (row) {
            const selectType = row.querySelector('select');
            if (selectType && selectType.value === 'static') {
                const paramName = row.querySelector('.zns-template-mapping-label').innerText;
                const inputStatic = row.querySelector('input');
                let value = inputStatic.value.trim();
                if (value && !value.startsWith('#')) {
                    value = `#${value}`;
                }
                formData.set(`mapping[${paramName}]`, value);
            }
        });

        fetch(endpoints.saveMappings, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.status === 'success') {
                    alert(data.message);
                    closeMappingModal();
                    window.location.reload();
                    return;
                }

                alert(data.message || 'Lỗi lưu cấu hình.');
                btnSubmitMapping.disabled = false;
                btnSubmitMapping.innerHTML = '<i class="fas fa-save"></i> Lưu cấu hình';
            })
            .catch(function (error) {
                console.error(error);
                alert('Có lỗi mạng khi lưu cấu hình.');
                btnSubmitMapping.disabled = false;
                btnSubmitMapping.innerHTML = '<i class="fas fa-save"></i> Lưu cấu hình';
            });
    });

    document.querySelectorAll('.btn-delete-template').forEach(function (button) {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;

            if (!confirm(`Bạn có chắc chắn muốn xóa mẫu tin "${name}"? Thao tác này sẽ gỡ mẫu tin khỏi hệ thống ERP.`)) {
                return;
            }

            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`${endpoints.deleteTemplate}${id}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                        return;
                    }

                    alert(data.message || 'Có lỗi xảy ra khi xóa mẫu tin.');
                    button.disabled = false;
                    button.innerHTML = originalText;
                })
                .catch(function (error) {
                    console.error(error);
                    alert('Có lỗi mạng xảy ra. Vui lòng thử lại.');
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
        });
    });
});
