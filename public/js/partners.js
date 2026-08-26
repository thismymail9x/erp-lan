document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-partner-money').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = input.value.toString().replace(/[^\d]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('.partner-select2').select2({
            width: '100%',
            allowClear: true
        });
    }
});
