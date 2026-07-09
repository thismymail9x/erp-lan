/**
 * Customer care module interactions.
 */

$(document).ready(function() {
    $('.segment-tab-btn').on('click', function() {
        const targetTab = $(this).data('target');

        $('.segment-tab-btn').removeClass('active');
        $(this).addClass('active');

        if ($('.segment-tab-pane').length) {
            $('.segment-tab-pane').hide();
            $(`#pane-${targetTab}`).fadeIn(150);
        } else if (targetTab === 'all') {
            $('.customer-care-item').fadeIn(200);
        } else {
            $('.customer-care-item').hide();
            $(`.customer-care-item[data-segment="${targetTab}"]`).fadeIn(200);
        }
    });

    let activeTaskId = null;
    let activeTaskElement = null;

    $('.task-checkbox').on('change', function() {
        const checkbox = $(this);
        const taskId = checkbox.data('id');
        const isChecked = checkbox.is(':checked');

        if (isChecked) {
            activeTaskId = taskId;
            activeTaskElement = checkbox.closest('.task-item');

            $('#taskNoteModal').css('display', 'flex');
            $('#taskNotesInput').val('').focus();
        } else {
            checkbox.prop('checked', true);
            alert('Quy tr\u00ecnh b\u1ea3o m\u1eadt: Thao t\u00e1c CSKH \u0111\u00e3 ghi nh\u1eadn kh\u00f4ng th\u1ec3 t\u1ef1 \u00fd r\u00fat l\u1ea1i. Vui l\u00f2ng li\u00ean h\u1ec7 Admin n\u1ebfu c\u00f3 sai s\u00f3t.');
        }
    });

    $('#btnConfirmTaskNote').on('click', function() {
        const notes = $('#taskNotesInput').val().trim();

        if (activeTaskId) {
            submitTaskCompletion(activeTaskId, notes, activeTaskElement);
        }

        closeTaskNoteModal();
    });

    $('#btnSkipTaskNote').on('click', function() {
        if (activeTaskId) {
            submitTaskCompletion(activeTaskId, '', activeTaskElement);
        }

        closeTaskNoteModal();
    });

    $('.close-modal, #btnCancelTaskNote').on('click', function() {
        if (activeTaskElement) {
            activeTaskElement.find('.task-checkbox').prop('checked', false);
        }
        closeTaskNoteModal();
    });

    function closeTaskNoteModal() {
        $('#taskNoteModal').hide();
        activeTaskId = null;
        activeTaskElement = null;
    }

    function submitTaskCompletion(taskId, notes, taskElement) {
        $.ajax({
            url: `${baseUrl}/customer-care/complete-task/${taskId}`,
            type: 'POST',
            data: {
                notes: notes,
                [csrfToken]: csrfHash
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    taskElement.addClass('completed');
                    taskElement.find('.task-title-text').css('text-decoration', 'line-through');
                    taskElement.find('.task-checkbox').prop('disabled', true);

                    location.reload();
                } else {
                    alert('L\u1ed7i: ' + response.message);
                    taskElement.find('.task-checkbox').prop('checked', false);
                }
            },
            error: function() {
                alert('Ph\u00e1t sinh l\u1ed7i k\u1ebft n\u1ed1i h\u1ec7 th\u1ed1ng. Vui l\u00f2ng th\u1eed l\u1ea1i sau.');
                taskElement.find('.task-checkbox').prop('checked', false);
            }
        });
    }

    $('.btn-copy-referral').on('click', function() {
        const referralCode = $(this).data('code');
        const tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(referralCode).select();
        document.execCommand('copy');
        tempInput.remove();

        const btn = $(this);
        const originalText = btn.html();
        btn.html('<i class="fas fa-check"></i> \u0110\u00e3 sao ch\u00e9p');
        btn.addClass('btn-success');

        setTimeout(function() {
            btn.html(originalText);
            btn.removeClass('btn-success');
        }, 2000);
    });

    initCustomerCareDashboardChart();
    initCustomerCareMonthlyReportCharts();
});

function initCustomerCareDashboardChart() {
    const chartEl = document.getElementById('segmentChart');
    const config = window.customerCareDashboardConfig;

    if (!chartEl || !config || typeof Chart === 'undefined') {
        return;
    }

    new Chart(chartEl.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: config.labels,
            datasets: [{
                data: config.segmentData,
                backgroundColor: ['#bf953f', '#0071e3', '#86868b'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: {
                            family: 'Inter',
                            size: 11,
                            weight: '500'
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

function initCustomerCareMonthlyReportCharts() {
    const config = window.customerCareMonthlyReportConfig;
    const segmentEl = document.getElementById('segmentReportChart');
    const trendEl = document.getElementById('monthlyTrendChart');

    if (!config || typeof Chart === 'undefined') {
        return;
    }

    if (segmentEl) {
        new Chart(segmentEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: config.segmentLabels,
                datasets: [{
                    data: config.segmentData,
                    backgroundColor: ['#bf953f', '#0071e3', '#86868b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: {
                                family: 'Inter',
                                size: 11,
                                weight: '500'
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    if (trendEl) {
        new Chart(trendEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: config.monthLabels,
                datasets: [{
                    label: config.trendLabel,
                    data: config.trendData,
                    backgroundColor: 'rgba(0, 113, 227, 0.85)',
                    hoverBackgroundColor: '#0071e3',
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                            font: {
                                family: 'Inter',
                                size: 10
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.04)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 10,
                                weight: '500'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}
