/**
     * L.A.N ERP - Trình khởi tạo Quy trình nghiệp vụ
     * Điều khiển việc thêm, xóa, sắp xếp và lưu trữ các giai đoạn trong một quy trình mẫu.
     */
    let stepCount = parseInt(document.querySelector('.workflow-steps-container').dataset.stepCount || '0', 10);

    /**
     * Thêm một giai đoạn mới vào cuối danh sách.
     * Tạo cấu trúc HTML động và khởi tạo Select2 cho các trường chọn mới.
     */
    function addNewStep() {
        const container = document.getElementById('steps-container');
        
        // Ẩn thông báo "Trống" nếu đây là bước đầu tiên
        const emptyState = container.querySelector('.empty-steps-state');
        if (emptyState) emptyState.remove();

        const index = stepCount;
        const stepHtml = `
            <div class="step-card premium-card m-b-15" data-index="${index}">
                <div class="step-card-header">
                    <div class="step-number">#${index + 1}</div>
                    <input type="text" name="steps[${index}][step_name]" class="step-name-input" value="" placeholder="Tên bước mới..." required>
                    <button type="button" class="btn-remove-step" onclick="removeStep(this)">
                        <i class="far fa-trash-alt"></i>
                    </button>
                </div>
                <div class="step-card-body">
                    <div class="form-row-steps">
                        <div class="form-group-mini">
                            <label>Số ngày định mức</label>
                            <input type="number" name="steps[${index}][duration_days]" value="3" min="1" required title="Số ngày dự kiến hoàn thành bước này">
                        </div>
                        <div class="form-group-mini">
                            <label>Thưởng hoàn thành (KPI)</label>
                            <div class="currency-input-wrapper">
                                <input type="text" name="steps[${index}][kpi_reward]" class="input-currency" value="0" title="Thưởng hoàn thành (VND)">
                                <span class="currency-label">VND</span>
                            </div>
                        </div>
                        <div class="form-group-mini">
                            <label>Phân quyền/Người phụ trách</label>
                            <select class="select2-multiple" name="steps[${index}][responsible_role][]" multiple="multiple" style="width: 100%;">
                                ${document.getElementById("workflowRoleOptionsTemplate").innerHTML}
                            </select>
                        </div>
                        <div class="form-group-mini flex-2">
                            <label>Tài liệu cần có (Phân tách bằng dấu phẩy)</label>
                            <input type="text" name="steps[${index}][required_documents_raw]" value="" placeholder="Ví dụ: Đơn khởi kiện, CCCD, Bản án...">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Chèn HTML mới vào container
        container.insertAdjacentHTML('beforeend', stepHtml);
        
        // Khởi tạo thư viện Select2 cho các element vừa tạo động
        $(`.step-card[data-index="${index}"] .select2-multiple`).select2({
            placeholder: "Chọn đối tượng xử lý...",
            allowClear: true
        });

        stepCount++;
        reorderSteps(); // Cập nhật lại số hiệu #1, #2...
    }

    /**
     * Xóa một giai đoạn khỏi quy trình.
     * @param {HTMLElement} btn - Nút xóa được nhấn.
     */
    function removeStep(btn) {
        if (confirm('Xác nhận xóa giai đoạn này? Thứ tự các bước sau nó sẽ được cập nhật lại.')) {
            // Xóa khối cha gần nhất có class .step-card
            const card = btn.closest('.step-card');
            if (card) {
                card.remove();
                reorderSteps();
            }
        }
    }

    /**
     * Cập nhật lại số hiệu và name attribute của toàn bộ các bước.
     * Đảm bảo tính nhất quán khi người dùng thêm/xóa/sắp xếp lại.
     */
    function reorderSteps() {
        const cards = document.querySelectorAll('.step-card');
        cards.forEach((card, index) => {
            // Cập nhật số hiệu hiển thị (#1, #2...)
            card.querySelector('.step-number').innerText = `#${index + 1}`;
            card.dataset.index = index;
            
            // Đồng bộ lại attribute 'name' của các input để backend nhận đúng mảng tuần tự
            const inputs = card.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/steps\[\d+\]/, `steps[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        stepCount = cards.length;
    }

    /**
     * Thu thập dữ liệu và gửi lên máy chủ.
     * Chuyển các input 'raw' (như tài liệu cách nhau bằng dấu phẩy) thành cấu trúc mảng chuẩn.
     */
    function saveWorkflowSteps() {
        const form = document.getElementById('workflow-steps-form');
        const container = document.getElementById('hidden-inputs-container');
        container.innerHTML = ''; // Clear previous hidden inputs

        const cards = document.querySelectorAll('.step-card');
        if (cards.length === 0) {
            alert('Quy trình phải có ít nhất một bước thực hiện.');
            return;
        }

        cards.forEach((card, index) => {
            const stepName = card.querySelector('input[name*="[step_name]"]').value;
            const duration = card.querySelector('input[name*="[duration_days]"]').value;
            
            // Xử lý Responsible Roles (mảng từ Select2)
            const roles = $(card).find('select[name*="[responsible_role]"]').val() || [];
            
            // Xử lý Required Documents (chuyển string comma-separated sang mảng)
            const docsRaw = card.querySelector('input[name*="[required_documents_raw]"]').value;
            const docsArray = docsRaw.split(',').map(d => d.trim()).filter(d => d !== "");

            // KPI Reward - Clean formatting before saving
            const kpiInput = card.querySelector('input[name*="[kpi_reward]"]');
            const kpiReward = kpiInput.value.replace(/,/g, '') || 0;

            // Gắn vào form ẩn thông qua hidden inputs
            addHiddenInput(container, `steps[${index}][step_name]`, stepName);
            addHiddenInput(container, `steps[${index}][duration_days]`, duration);
            addHiddenInput(container, `steps[${index}][kpi_reward]`, kpiReward);
            
            roles.forEach((role, rIdx) => {
                addHiddenInput(container, `steps[${index}][responsible_role][${rIdx}]`, role);
            });
            
            docsArray.forEach((doc, dIdx) => {
                addHiddenInput(container, `steps[${index}][required_documents][${dIdx}]`, doc);
            });
        });

        // Submit form
        form.submit();
    }

    /**
     * Helper tạo hidden input động.
     */
    function addHiddenInput(container, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        container.appendChild(input);
    }

    // Khởi tạo Select2 cho các bước có sẵn khi load trang
    $(document).ready(function() {
        $('.select2-multiple').select2({
            placeholder: "Chọn đối tượng xử lý...",
            allowClear: true
        });

        // Xử lý định dạng tiền tệ khi nhập liệu
        $(document).on('input', '.input-currency', function() {
            let val = $(this).val().replace(/\D/g, "");
            if (val === "") {
                $(this).val("0");
                return;
            }
            // Loại bỏ số 0 ở đầu nếu có nhiều chữ số
            val = parseInt(val, 10).toString();
            $(this).val(val.replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        });

        // Click để focus toàn bộ text (tiện lợi khi sửa giá trị)
        $(document).on('focus', '.input-currency', function() {
            $(this).select();
        });
    });
