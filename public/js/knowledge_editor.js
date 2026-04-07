$(document).ready(function() {
    // 1. Tối ưu Module Tags với Select2
    if ($.fn.select2) {
        $('.select2-tags').select2({
            placeholder: "Chọn hoặc nhập Tag hệ thống...",
            allowClear: true,
            width: '100%'
        });
    }

    // 2. Khởi tạo Trình Soạn Thảo (QuillJS)
    var quill = null;
    var editorContainer = document.getElementById('editor-container');
    
    if (editorContainer) {
        quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Sửa đổi/Soạn thảo nội dung...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });
    }

    // 3. Hook đẩy dữ liệu HTML thực từ Quill sang hidden input khi user bấm Submit
    $('#knowledgeForm').on('submit', function(e) {
        if (quill) {
            var htmlContent = quill.root.innerHTML;
            if (quill.getText().trim().length === 0) {
                e.preventDefault();
                alert('Nội dung không được để trống!');
                return false;
            }
            $('#contentInput').val(htmlContent);
        }
    });
});
