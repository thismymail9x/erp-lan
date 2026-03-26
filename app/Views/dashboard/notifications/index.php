<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/notifications.css') ?>">
<?= $this->endSection() ?><?= $this->section('content') ?>
<div class="notifications-container">
    <div class="dashboard-header-wrapper notif-header m-b-24">
        <h2 class="content-title">Tất cả thông báo</h2>
        <button id="markAllReadPage" class="btn-secondary-sm">
            <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
        </button>
    </div>

    <div class="premium-card p-0">
        <?php if (empty($notifications)) { ?>
            <div class="empty-state-container p-40 text-center text-muted-dark">
                <i class="fas fa-bell-slash notif-empty-icon"></i>
                <p>Bạn không có thông báo nào.</p>
            </div>
        <?php } else { ?>
            <div class="notification-list">
                <?php foreach ($notifications as $n) { ?>
                    <?php 
                        $iconClass = $n['type'] === 'approval' ? 'fa-check-circle' : 'fa-info-circle';
                        $typeClass = $n['type'] === 'approval' ? 'approval' : 'info';
                        $readClass = $n['is_read'] ? 'read' : 'unread';
                    ?>
                    <div class="notif-item-page <?= $readClass ?>">
                        <div class="notif-icon-wrapper <?= $typeClass ?>">
                            <i class="fas <?= $iconClass ?>"></i>
                        </div>
                        <div class="notif-content-wrapper">
                            <div class="notif-title <?= $readClass ?>">
                                <?= esc($n['title']) ?>
                            </div>
                            <div class="notif-message">
                                <?= esc($n['message']) ?>
                            </div>
                            <div class="notif-time">
                                <i class="far fa-clock"></i> <?= date('H:i d/m/Y', strtotime($n['created_at'])) ?>
                            </div>
                        </div>
                        <div class="notif-actions">
                            <?php if ($n['link']) { ?>
                                <a href="<?= esc($n['link']) ?>" class="btn-secondary-sm text-xs">Xem chi tiết</a>
                            <?php } ?>
                            <?php if (!$n['is_read']) { ?>
                                <button class="btn-mark-read text-xs" data-id="<?= $n['id'] ?>">Đánh dấu đã đọc</button>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
            
            <div class="pagination-wrapper notif-pagination">
                <?= $pager->links() ?>
            </div>
        <?php } ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
/**
 * L.A.N ERP - Quản lý Thông báo
 * Xử lý các tương tác: Đánh dấu đã đọc đơn lẻ và Đánh dấu tất cả.
 */
$(document).ready(function() {
    
    // 1. Đánh dấu một thông báo là đã đọc (Xử lý AJAX)
    $('.btn-mark-read').click(function() {
        const btn = $(this);
        const id = btn.data('id'); // Lấy ID thông báo từ thuộc tính data-id

        // Gửi yêu cầu POST lên server qua jQuery AJAX
        $.post('<?= base_url("notifications/read/") ?>' + id, function() {
            // Sau khi server xử lý thành công: Cập nhật giao diện người dùng ngay lập tức
            const row = btn.closest('.notif-item-page');
            row.removeClass('unread').addClass('read'); // Đổi màu nền (bỏ highlight)
            row.find('.notif-title').removeClass('unread').addClass('read'); // Đổi màu chữ tiêu đề
            btn.remove(); // Xóa nút bấm vì đã đọc xong
            
            // Tùy chọn: Cập nhật số lượng thông báo trên chuông thông báo (Navbar) nếu cần
        });
    });

    // 2. Đánh dấu tất cả thông báo là đã đọc
    $('#markAllReadPage').click(function() {
        if (confirm('Bạn có muốn đánh dấu tất cả thông báo hiện tại là đã đọc không?')) {
            $.post('<?= base_url("notifications/read-all") ?>', function() {
                // Tải lại trang để áp dụng thay đổi cho toàn bộ danh sách và phân trang
                location.reload();
            });
        }
    });
});
</script>
<?= $this->endSection() ?>
