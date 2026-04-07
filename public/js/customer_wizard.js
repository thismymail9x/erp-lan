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

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if(currentStep < totalSteps) {
                steps[currentStep-1].style.display = 'none';
                currentStep++;
                steps[currentStep-1].style.display = 'block';
                updateWizard();
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

    async function checkDuplicate(field, value, alertId) {
        if(!value) return;
        const alertDiv = document.getElementById(alertId);
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
});
