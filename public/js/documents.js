/**
 * L.A.N ERP - Document Management interactions.
 */

function performSearch(url) {
    const filterForm = $('.search-filter-bar');
    const resultsContainer = $('#documents-table-results');
    const formData = filterForm.serialize();
    const hasUrl = typeof url === 'string' && url.length > 0;
    const requestUrl = hasUrl ? url : (baseUrl + 'documents');

    resultsContainer.css('opacity', '0.5');

    $.ajax({
        url: requestUrl,
        type: 'GET',
        data: hasUrl ? {} : formData,
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

    $(document).off('click', '#documents-table-results .pagination a').on('click', '#documents-table-results .pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (href) {
            performSearch(href);
        }
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
        debounceTimer = setTimeout(function() {
            performSearch();
        }, 500);
    });

    filterForm.find('select').not('.select2-basic').on('change', function() {
        performSearch();
    });

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

    const uploadInput = $('#dmsFileInput');
    const uploadZone = $('.dms-upload-zone');
    const uploadLabelText = uploadInput.next('label').find('span');
    const selectedFiles = $('#dmsSelectedFiles');
    let selectedUploadFiles = [];

    function fileKey(file) {
        return [file.name, file.size, file.lastModified].join('|');
    }

    function syncUploadInput() {
        const transfer = new DataTransfer();
        selectedUploadFiles.forEach(function(file) {
            transfer.items.add(file);
        });
        uploadInput[0].files = transfer.files;
    }

    function renderUploadFiles() {
        selectedFiles.empty();

        if (!selectedUploadFiles.length) {
            uploadLabelText.text('Click \u0111\u1ec3 ch\u1ecdn m\u1ed9t ho\u1eb7c nhi\u1ec1u t\u1ec7p');
            uploadZone.removeClass('has-files');
            return;
        }

        uploadZone.addClass('has-files');
        uploadLabelText.empty()
            .append(document.createTextNode('\u0110\u00e3 ch\u1ecdn: '))
            .append($('<strong></strong>').text(selectedUploadFiles.length + ' t\u1ec7p'));

        selectedUploadFiles.forEach(function(file, index) {
            const item = $('<div class="dms-selected-file"></div>');
            const name = $('<span class="dms-selected-file-name"></span>').text(file.name);
            const size = $('<span class="dms-selected-file-size"></span>').text(formatBytes(file.size));
            const remove = $('<button type="button" class="dms-selected-file-remove" title="B\u1ecf t\u1ec7p n\u00e0y"><i class="fas fa-times"></i></button>');

            remove.on('click', function() {
                selectedUploadFiles.splice(index, 1);
                syncUploadInput();
                renderUploadFiles();
            });

            item.append(name).append(size).append(remove);
            selectedFiles.append(item);
        });

        const nameInput = $('#formUploadDocument input[name="file_name"]');
        if (selectedUploadFiles.length === 1 && !nameInput.val()) {
            const firstFileName = selectedUploadFiles[0].name;
            nameInput.val(firstFileName.split('.').slice(0, -1).join('.') || firstFileName);
        }
    }

    function addUploadFiles(files) {
        const existingKeys = new Set(selectedUploadFiles.map(fileKey));
        Array.from(files || []).forEach(function(file) {
            const key = fileKey(file);
            if (!existingKeys.has(key)) {
                selectedUploadFiles.push(file);
                existingKeys.add(key);
            }
        });

        syncUploadInput();
        renderUploadFiles();
    }

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
        return bytes + ' B';
    }

    uploadInput.on('change', function() {
        addUploadFiles(this.files);
        this.value = '';
        syncUploadInput();
    });

    uploadZone.on('dragover', function(e) {
        e.preventDefault();
        uploadZone.addClass('is-dragover');
    });

    uploadZone.on('dragleave drop', function(e) {
        e.preventDefault();
        uploadZone.removeClass('is-dragover');
    });

    uploadZone.on('drop', function(e) {
        const files = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : [];
        addUploadFiles(files);
    });

    $('#formUploadDocument').on('submit', function(e) {
        if (!selectedUploadFiles.length) {
            e.preventDefault();
            alert('Vui l\u00f2ng ch\u1ecdn \u00edt nh\u1ea5t m\u1ed9t t\u1ec7p tin \u0111\u1ec3 t\u1ea3i l\u00ean.');
        }
    });

    window.cancelSelection = function() {
        $('.doc-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateBulkBar();
    };

    bindTableEvents();
});
