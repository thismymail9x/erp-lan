/**
 * L.A.N ERP - Customer Wizard (Create)
 */
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 3;
    
    const steps = document.querySelectorAll('.wizard-step');
    const indicators = document.querySelectorAll('.step-indicator');
    const progressLine = document.getElementById('progress-line');
    
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const submitBtn = document.getElementById('submitBtn');

    const typeRadios = document.querySelectorAll('input[name="type"]');
    typeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'doanh_nghiep') {
                document.getElementById('individual_fields').style.display = 'none';
                document.getElementById('corporate_fields').style.display = 'block';
            } else {
                document.getElementById('individual_fields').style.display = 'block';
                document.getElementById('corporate_fields').style.display = 'none';
            }
        });
    });

    function validateCurrentStep() {
        const activeStep = steps[currentStep-1];
        let isValid = true;
        
        // 1. Kiểm tra các trường Bắt buộc (Bổ sung thêm sau nếu cần)
        const requiredFields = activeStep.querySelectorAll('input[required], select[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#ff3b30';
                isValid = false;
            } else {
                field.style.borderColor = '';
            }
        });

        // Đặc biệt: Bước 1 bắt buộc Tên và SĐT
        if (currentStep === 1) {
            const name = activeStep.querySelector('input[name="name"]');
            if (name && !name.value.trim()) {
                name.style.borderColor = '#ff3b30';
                isValid = false;
            }
            const phone = document.getElementById('phone_check');
            if (phone && !phone.value.trim()) {
                phone.style.borderColor = '#ff3b30';
                isValid = false;
            }
        }

        // 2. Kiểm tra các thông báo lỗi hiện hữu (Đỏ)
        const activeAlters = activeStep.querySelectorAll('.date-error-label, [id$="_alert"]');
        activeAlters.forEach(alert => {
            if (alert.style.display === 'block' && alert.innerText.trim() !== '') {
                isValid = false;
                // Làm nổi bật ô đang lỗi
                const input = alert.previousElementSibling;
                if(input) input.style.borderColor = '#ff3b30';
            }
        });

        if (!isValid) {
            alert("Vui lòng hoàn thành thông tin và sửa các lỗi đang báo đỏ trước khi đi tiếp.");
        }

        return isValid;
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (validateCurrentStep()) {
                if(currentStep < totalSteps) {
                    steps[currentStep-1].style.display = 'none';
                    currentStep++;
                    steps[currentStep-1].style.display = 'block';
                    updateWizard();
                }
            }
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if(currentStep > 1) {
                steps[currentStep-1].style.display = 'none';
                currentStep--;
                steps[currentStep-1].style.display = 'block';
                updateWizard();
            }
        });
    }

    function updateWizard() {
        indicators.forEach(ind => {
            const step = parseInt(ind.dataset.step);
            if(step <= currentStep) ind.classList.add('active');
            else ind.classList.remove('active');
            
            const dot = ind.querySelector('.step-dot');
            if(step < currentStep) {
                dot.style.background = '#0071e3';
                dot.style.color = '#fff';
                dot.innerHTML = '<i class="fas fa-check"></i>';
            } else if(step === currentStep) {
                dot.style.background = '#fff';
                dot.style.color = '#0071e3';
                dot.style.borderColor = '#0071e3';
                dot.innerHTML = step;
            } else {
                dot.style.background = '#fff';
                dot.style.color = '#d2d2d7';
                dot.style.borderColor = '#d2d2d7';
                dot.innerHTML = step;
            }
        });

        if (progressLine) {
            progressLine.style.width = ((currentStep - 1) / (totalSteps - 1) * 100) + '%';
        }

        if (prevBtn) prevBtn.style.display = (currentStep === 1) ? 'none' : 'block';
        if(currentStep === totalSteps) {
            if (nextBtn) nextBtn.style.display = 'none';
            if (submitBtn) submitBtn.style.display = 'block';
        } else {
            if (nextBtn) nextBtn.style.display = 'block';
            if (submitBtn) submitBtn.style.display = 'none';
        }
    }

    // --- HELPER: VALIDATE ĐỊNH DẠNG ---
    function isValidPhone(phone) {
        return /^0\d{9}$/.test(phone); // Bắt đầu bằng 0 và đủ 10 số
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    async function checkDuplicate(field, value, alertId) {
        const alertDiv = document.getElementById(alertId);
        if(!value) {
            alertDiv.style.display = 'none';
            return;
        }

        // Bước 1: Kiểm tra định dạng cục bộ trước khi gửi lên Server
        if (field === 'phone' && !isValidPhone(value)) {
            alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> SĐT không hợp lệ (Phải bắt đầu bằng 0 và đủ 10 chữ số).`;
            alertDiv.style.display = 'block';
            return;
        }
        if (field === 'email' && !isValidEmail(value)) {
            alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Định dạng Email không đúng.`;
            alertDiv.style.display = 'block';
            return;
        }
        
        // Bước 2: Kiểm tra trùng lặp trên Database
        try {
            const response = await fetch(`${baseUrl}/customers/check-duplicate?${field}=${value}`);
            const result = await response.json();
            
            if(result.exists) {
                const dup = result.duplicates[field];
                alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> Trùng lặp: <a href="${baseUrl}/customers/show/${dup.id}" target="_blank" style="color: inherit; text-decoration: underline;">${dup.code} - ${dup.name}</a>`;
                alertDiv.style.display = 'block';
            } else {
                alertDiv.style.display = 'none';
            }
        } catch(e) {
            console.error("Lỗi khi kiểm tra trùng lặp:", e);
        }
    }

    const phoneCheck = document.getElementById('phone_check');
    if (phoneCheck) {
        phoneCheck.addEventListener('blur', function() {
            checkDuplicate('phone', this.value, 'phone_alert');
        });
    }

    const idCheck = document.getElementById('id_check');
    if (idCheck) {
        idCheck.addEventListener('blur', function() {
            checkDuplicate('identity_number', this.value, 'id_alert');
        });
    }

    const emailCheck = document.getElementById('email_check');
    if (emailCheck) {
        emailCheck.addEventListener('blur', function() {
            checkDuplicate('email', this.value, 'email_alert');
        });
    }

    if ($('.select2-tags').length) {
        $('.select2-tags').select2({
            placeholder: "Chọn nhãn dán...",
            allowClear: true,
            width: 'resolve'
        });
    }

    // --- CHỐT CHẶN SUBMIT CUỐI CÙNG ---
    const form = document.getElementById('customerWizardForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateCurrentStep()) {
                e.preventDefault();
            }
        });
    }

    // --- VALIDATE NGÀY SINH (Cụ thể cho Form này) ---
    const dobInput = document.getElementById('date_of_birth');
    if (dobInput) {
        const handleDobError = function() {
            let errorLabel = dobInput.nextElementSibling;
            if (!errorLabel || !errorLabel.classList.contains('date-error-label')) {
                errorLabel = document.createElement('div');
                errorLabel.classList.add('date-error-label');
                errorLabel.style.color = '#ff3b30';
                errorLabel.style.fontSize = '12px';
                errorLabel.style.marginTop = '6px';
                errorLabel.style.fontWeight = '600';
                dobInput.parentNode.insertBefore(errorLabel, dobInput.nextSibling);
            }

            // badInput xảy ra khi người dùng gõ nội dung rác hoặc ngày không tồn tại
            if (!dobInput.validity.valid) {
                 dobInput.style.borderColor = '#ff3b30';
                 dobInput.style.backgroundColor = 'rgba(255, 59, 48, 0.05)';
                 errorLabel.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi: Ngày nhập vào không tồn tại hoặc sai định dạng.';
                 errorLabel.style.display = 'block';
            } else {
                 dobInput.style.borderColor = '';
                 dobInput.style.backgroundColor = '';
                 errorLabel.style.display = 'none';
            }
        };

        dobInput.addEventListener('input', handleDobError);
        dobInput.addEventListener('change', handleDobError);
        dobInput.addEventListener('blur', handleDobError);
    }
});
