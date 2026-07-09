/*
 * JS cho màn hình tạo chiến dịch ZNS.
 * URL và dữ liệu PHP được truyền qua data-* để view không chứa script inline.
 */
document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.zns-campaign-create-page');
    if (!page) {
        return;
    }

    const selectTemplate = document.getElementById('select-zns-template');
    const mappingContainer = document.getElementById('mapping-container');
    const mappingRows = document.getElementById('mapping-rows');
    const form = document.getElementById('create-campaign-form');
    const btnSave = document.getElementById('btn-save-campaign');
    const saveUrl = page.dataset.saveUrl || '';

    let customerFields = {};
    try {
        customerFields = JSON.parse(page.dataset.customerFields || '{}');
    } catch (error) {
        console.error('Không đọc được danh sách trường khách hàng:', error);
    }

    function setElementHidden(element, hidden) {
        if (element) {
            element.classList.toggle('zns-hidden', hidden);
        }
    }

    function buildEmptyMappingState() {
        const empty = document.createElement('div');
        empty.className = 'zns-empty-inline';
        empty.innerText = 'Mẫu tin này không chứa biến số nào.';
        return empty;
    }

    function buildMappingRow(param, mappings) {
        const row = document.createElement('div');
        row.className = 'mapping-row';

        const labelCol = document.createElement('div');
        labelCol.className = 'mapping-param-label';
        labelCol.innerText = param;
        row.appendChild(labelCol);

        const typeCol = document.createElement('div');
        const selectType = document.createElement('select');
        selectType.className = 'form-control-custom no-select2';
        selectType.innerHTML = '<option value="field">Trường khách hàng</option><option value="static">Nhập giá trị tĩnh</option>';
        typeCol.appendChild(selectType);
        row.appendChild(typeCol);

        const valueCol = document.createElement('div');
        const containerValue = document.createElement('div');
        containerValue.className = 'mapping-value-cell';

        const selectField = document.createElement('select');
        selectField.className = 'form-control-custom no-select2';
        selectField.name = `mapping[${param}]`;
        selectField.required = true;

        let fieldOptions = '<option value="">-- Chọn trường dữ liệu --</option>';
        const currentVal = mappings[param] || '';
        const isStaticValue = currentVal.startsWith('#');

        Object.entries(customerFields).forEach(function ([key, label]) {
            const normalizedParam = param.toLowerCase();
            const isSelected = (currentVal && !isStaticValue && currentVal === key)
                || (!currentVal && normalizedParam.includes('name') && key === 'name')
                || (!currentVal && normalizedParam.includes('phone') && key === 'phone')
                || (!currentVal && normalizedParam.includes('code') && key === 'code');
            fieldOptions += `<option value="${key}" ${isSelected ? 'selected' : ''}>${label}</option>`;
        });
        selectField.innerHTML = fieldOptions;
        containerValue.appendChild(selectField);

        const inputStatic = document.createElement('input');
        inputStatic.type = 'text';
        inputStatic.className = 'form-control-custom zns-hidden';
        inputStatic.placeholder = 'Nhập chuỗi tĩnh...';

        if (isStaticValue) {
            selectType.value = 'static';
            setElementHidden(selectField, true);
            selectField.name = '';
            selectField.required = false;

            setElementHidden(inputStatic, false);
            inputStatic.name = `mapping[${param}]`;
            inputStatic.required = true;
            inputStatic.value = currentVal.substring(1);
        }

        selectType.addEventListener('change', function () {
            const isField = this.value === 'field';
            setElementHidden(selectField, !isField);
            selectField.name = isField ? `mapping[${param}]` : '';
            selectField.required = isField;

            setElementHidden(inputStatic, isField);
            inputStatic.name = isField ? '' : `mapping[${param}]`;
            inputStatic.required = !isField;

            if (!isField) {
                inputStatic.focus();
            }
        });

        containerValue.appendChild(inputStatic);
        valueCol.appendChild(containerValue);
        row.appendChild(valueCol);

        return row;
    }

    selectTemplate.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        if (!selectedOption.value) {
            setElementHidden(mappingContainer, true);
            mappingRows.innerHTML = '';
            return;
        }

        let params = [];
        let mappings = {};
        try {
            params = JSON.parse(selectedOption.dataset.params || '[]');
            mappings = JSON.parse(selectedOption.dataset.mappings || '{}');
        } catch (error) {
            console.error('Lỗi parse params:', error);
        }

        mappingRows.innerHTML = '';
        if (params.length === 0) {
            mappingRows.appendChild(buildEmptyMappingState());
        } else {
            params.forEach(function (param) {
                mappingRows.appendChild(buildMappingRow(param, mappings));
            });
        }

        setElementHidden(mappingContainer, false);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo...';

        const formData = new FormData(form);
        mappingRows.querySelectorAll('.mapping-row').forEach(function (row) {
            const selectType = row.querySelector('select:first-of-type');
            if (selectType && selectType.value === 'static') {
                const paramName = row.querySelector('.mapping-param-label').innerText;
                const inputStatic = row.querySelector('input');
                let value = inputStatic.value.trim();
                if (value && !value.startsWith('#')) {
                    value = `#${value}`;
                }
                formData.set(`mapping[${paramName}]`, value);
            }
        });

        fetch(saveUrl, {
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
                    window.location.href = data.redirect;
                    return;
                }

                alert(data.message || 'Lỗi lưu chiến dịch nháp.');
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save"></i> Lưu Chiến dịch (Nháp)';
            })
            .catch(function (error) {
                console.error(error);
                alert('Có lỗi mạng xảy ra. Vui lòng thử lại.');
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save"></i> Lưu Chiến dịch (Nháp)';
            });
    });
});
