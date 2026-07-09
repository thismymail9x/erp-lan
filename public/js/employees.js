/**
 * L.A.N ERP - Employee module interactions.
 */

$(document).ready(function() {
    initEmployeeListAjax();
    initEmployeeProbationControls();
});

function initEmployeeListAjax() {
    const searchInput = document.getElementById('employee-search');
    const tableContainer = document.getElementById('employees-table-container');

    if (!searchInput || !tableContainer) {
        return;
    }

    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', this.value);
            url.searchParams.set('page', 1);
            fetchEmployeesByUrl(url, searchInput, tableContainer);
        }, 300);
    });

    tableContainer.addEventListener('click', function(e) {
        const link = e.target.closest('.pagination a, .sort-link');
        if (link) {
            e.preventDefault();
            const url = new URL(link.href);
            fetchEmployeesByUrl(url, searchInput, tableContainer);
        }
    });
}

async function fetchEmployeesByUrl(url, searchInput, tableContainer) {
    try {
        tableContainer.style.opacity = '0.5';

        if (!url.searchParams.has('search')) {
            url.searchParams.set('search', searchInput.value);
        }

        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await response.text();

        tableContainer.innerHTML = html;
        tableContainer.style.opacity = '1';
        window.history.pushState(null, '', url);
    } catch (err) {
        console.error('L\u1ed7i khi t\u1ea3i d\u1eef li\u1ec7u nh\u00e2n s\u1ef1:', err);
        tableContainer.style.opacity = '1';
    }
}

function initEmployeeProbationControls() {
    const presetButtons = document.querySelectorAll('.probation-preset-btn');
    const endDateEl = document.getElementById('probation_end_date');

    if (!presetButtons.length && !endDateEl) {
        return;
    }

    function toggleNewRateGroup() {
        const endDate = document.getElementById('probation_end_date');
        const group = document.getElementById('new-rate-after-group');
        if (!endDate || !group) return;

        if (endDate.value) {
            group.classList.remove('hidden');
        } else {
            group.classList.add('hidden');
        }
    }

    presetButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const rate = this.getAttribute('data-rate');
            const rateInput = document.getElementById('probation_rate');
            if (rateInput) {
                rateInput.value = rate;
                toggleNewRateGroup();
            }
        });
    });

    if (endDateEl) {
        endDateEl.addEventListener('change', toggleNewRateGroup);
        toggleNewRateGroup();
    }
}
