function openModal() {
        $('#replyId').val('');
        $('#replyForm')[0].reset();
        $('#modalTitle').text('Thêm mẫu câu trả lời');
        $('#replyModal').css('display', 'flex');
    }

    function closeModal() {
        $('#replyModal').hide();
    }

    function editReply(data) {
        $('#replyId').val(data.id);
        $('#replyTitle').val(data.title);
        $('#replyContent').val(data.content);
        $('#modalTitle').text('Chỉnh sửa mẫu câu');
        $('#replyModal').css('display', 'flex');
    }

    $(document).ready(function() {
        $('#replyForm').on('submit', function(e) {
            e.preventDefault();
            const data = $(this).serialize();
            
            $.post(baseUrl + 'zalo/quick-replies/store', data, function(res) {
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert(res.message);
                }
            });
        });
    });