$(document).ready(function() {
    // 1. Tối ưu Module Tags với Select2
    if ($.fn.select2) {
        $('.select2-tags').select2({
            placeholder: "Chọn hoặc nhập Tag hệ thống...",
            allowClear: true,
            width: '100%'
        });
    }

    // 2. Khởi tạo các trình soạn thảo theo cấu trúc mới
    function initQuill(id, inputId, placeholder) {
        var container = document.getElementById(id);
        if (!container) return null;
        
        var q = new Quill('#' + id, {
            theme: 'snow',
            placeholder: placeholder,
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['clean']
                ]
            }
        });
        
        return q;
    }

    var problemQuill = initQuill('problem-editor', 'problemInput', 'Mô tả vấn đề cụ thể (nên dùng bullet points)...');
    var solutionQuill = initQuill('solution-editor', 'solutionInput', 'Các bước xử lý hoặc giải pháp đã áp dụng...');
    var redflagsQuill = initQuill('redflags-editor', 'redflagsInput', 'Những điều cần đặc biệt lưu ý hoặc rủi ro tiềm ẩn...');

    // 3. Hook đẩy dữ liệu HTML thực từ các editor sang hidden inputs khi user bấm Submit
    $('#knowledgeForm').on('submit', function(e) {
        if (problemQuill) $('#problemInput').val(problemQuill.root.innerHTML);
        if (solutionQuill) $('#solutionInput').val(solutionQuill.root.innerHTML);
        if (redflagsQuill) $('#redflagsInput').val(redflagsQuill.root.innerHTML);
        
        // Kiểm tra tối thiểu
        if (problemQuill && problemQuill.getText().trim().length < 5) {
            e.preventDefault();
            alert('Vui lòng mô tả chi tiết vấn đề!');
            return false;
        }
        if (solutionQuill && solutionQuill.getText().trim().length < 5) {
            e.preventDefault();
            alert('Vui lòng cung cấp cách giải quyết!');
            return false;
        }
    });
});
