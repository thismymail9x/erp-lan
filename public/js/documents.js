/**
 * L.A.N ERP - Document Management interactions.
 */

function performSearch() {
    const filterForm = $('.search-filter-bar');
    const resultsContainer = $('#documents-table-results');
    const formData = filterForm.serialize();

    resultsContainer.css('opacity', '0.5');

    $.ajax({
        url: baseUrl + 'documents',
        type: 'GET',
        data: formData,
        success: function(response) {
            resultsContainer.html(response);
            resultsContainer.css('opacity', '1');
            bindTableEvents();
        },
        error: function() {
            resultsContainer.css('opacity', '1');
        }
    });
}

function bindTableEvents() {
    const selectAll = $('#selectAll');

    selectAll.off('change').on('change', function() {
        $('.doc-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkBar();
    });

    $(document).off('change', '.doc-checkbox').on('change', '.doc-checkbox', function() {
        updateBulkBar();
    });
}

function updateBulkBar() {
    const count = $('.doc-checkbox:checked').length;
    if (count > 0) {
        $('.selected-count').text(count);
        $('#bulkActionBar').fadeIn(200).css('display', 'flex');
    } else {
        $('#bulkActionBar').fadeOut(200);
    }
}

function openEditModal(id) {
    $.ajax({
        url: baseUrl + 'documents/edit/' + id,
        type: 'GET',
        success: function(doc) {
            if (doc.status === 'error') {
                alert(doc.message);
                return;
            }

            $('#formEditDocument').attr('action', baseUrl + 'documents/update/' + id);
            $('#edit_file_name').val(doc.file_name);
            $('#edit_category').val(doc.document_category);
            $('#edit_confidential').val(doc.is_confidential);
            $('#edit_description').val(doc.description);
            $('#edit_case_id').val(doc.case_id).trigger('change');
            $('#edit_customer_id').val(doc.customer_id).trigger('change');
            $('#edit_tags').val(doc.tag_names).trigger('change');

            $('#modalEdit').fadeIn(200);
        }
    });
}

function openShareModal(id, fileName) {
    $('#formShareDocument').attr('action', baseUrl + 'documents/share/' + id);
    $('#share_file_name').val(fileName);
    $('#modalShare').fadeIn(200);
}

function selectAllUsers() {
    const allIds = [];
    $('#share_user_ids option').each(function() {
        const val = $(this).val();
        if (val) allIds.push(val);
    });
    $('#share_user_ids').val(allIds).trigger('change');
}

$(document).ready(function() {
    let debounceTimer;
    const filterForm = $('.search-filter-bar');

    filterForm.find('input[name="keyword"]').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSearch, 500);
    });

    filterForm.find('select').not('.select2-basic').on('change', performSearch);

    $('.filter-select.select2-basic').select2({
        width: '100%',
        placeholder: 'Ch\u1ecdn m\u1ed9t m\u1ee5c'
    }).on('change', function() {
        performSearch();
    });

    $('.form-control-premium.select2-basic').select2({
        dropdownParent: $('#modalUpload'),
        width: '100%',
        placeholder: '-- Ch\u1ecdn m\u1ed9t m\u1ee5c --'
    });

    $('.select2-tags').select2({
        dropdownParent: $('#modalUpload'),
        width: '100%',
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: 'Nh\u1eadp \u0111\u1ec3 ch\u1ecdn ho\u1eb7c nh\u1eadp tag m\u1edbi...'
    });

    $('.select2-basic-edit').select2({
        dropdownParent: $('#modalEdit'),
        width: '100%'
    });

    $('.select2-tags-edit').select2({
        dropdownParent: $('#modalEdit'),
        width: '100%',
        tags: true
    });

    $('.select2-share').select2({
        dropdownParent: $('#modalShare'),
        width: '100%',
        placeholder: 'Ch\u1ecdn ng\u01b0\u1eddi nh\u1eadn...'
    });

    $('#formShareDocument').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const actionUrl = form.attr('action');

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                if (res.status === 'success') {
                    alert(res.message);
                    $('#modalShare').fadeOut(200);
                    form.trigger('reset');
                    $('#share_user_ids').val(null).trigger('change');
                } else {
                    alert(res.message);
                }
            }
        });
    });

    $('#dmsFileInput').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('label').find('span').html('\u0110\u00e3 ch\u1ecdn: <strong style="color:var(--apple-blue)">' + fileName + '</strong>');

            const nameInput = $('input[name="file_name"]');
            if (!nameInput.val()) {
                nameInput.val(fileName.split('.').shift());
            }
        }
    });

    window.cancelSelection = function() {
        $('.doc-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateBulkBar();
    };

    bindTableEvents();
});