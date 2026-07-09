<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content-header flex-row justify-between align-center">
    <div>
        <h2 class="content-title">Quản lý Câu trả lời nhanh</h2>
        <p class="text-muted" style="font-size: 0.85rem;">Thiết lập các mẫu câu hỗ trợ khách hàng nhanh chóng trên nhiều kênh.</p>
    </div>
    <button class="btn-premium" onclick="openModal()">
        <i class="fas fa-plus"></i> Thêm mẫu mới
    </button>
</div>

<div class="card-premium">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 200px;">Tiêu đề mẫu</th>
                    <th>Nội dung câu trả lời</th>
                    <th style="width: 150px;">Ngày tạo</th>
                    <th style="width: 100px; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quickReplies)) { ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-ghost" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                            Chưa có mẫu câu nào được cài đặt.
                        </td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($quickReplies as $qr) { ?>
                        <tr>
                            <td><?= $qr['id'] ?></td>
                            <td><strong><?= esc($qr['title']) ?></strong></td>
                            <td class="text-limit-2" title="<?= esc($qr['content']) ?>">
                                <?= esc($qr['content']) ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= date('d/m/Y H:i', strtotime($qr['created_at'])) ?>
                            </td>
                            <td class="text-center">
                                <div class="flex-row justify-center gap-10">
                                    <button class="btn-action-edit" onclick="editReply(<?= htmlspecialchars(json_encode($qr)) ?>)" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="<?= base_url('zalo/quick-replies/delete/' . $qr['id']) ?>" 
                                       class="btn-action-delete" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa mẫu câu này?')" 
                                       title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm/Sửa -->
<div id="replyModal" class="modal-overlay" style="display: none;">
    <div class="modal-content-premium" style="width: 500px;">
        <div class="flex-row justify-between align-center m-b-20">
            <h3 id="modalTitle" class="section-header-title">Thêm mẫu câu trả lời</h3>
            <span class="close-btn-minimal" onclick="closeModal()">&times;</span>
        </div>
        
        <form id="replyForm">
            <input type="hidden" name="id" id="replyId">
            <div class="form-group m-b-15">
                <label class="form-label-minimal">Tiêu đề gợi nhớ</label>
                <input type="text" name="title" id="replyTitle" class="form-control-minimal" placeholder="Ví dụ: Chào buổi sáng, Báo giá..." required>
            </div>
            
            <div class="form-group m-b-20">
                <label class="form-label-minimal">Nội dung chi tiết</label>
                <textarea name="content" id="replyContent" class="form-control-minimal" rows="5" placeholder="Nội dung sẽ được gửi cho khách hàng..." required></textarea>
                <p class="text-muted m-t-5" style="font-size: 0.75rem;">Mẹo: Nội dung nên ngắn gọn, súc tích và chuyên nghiệp.</p>
            </div>

            <div class="form-actions-row">
                <button type="button" class="btn-filter-secondary" onclick="closeModal()">Hủy</button>
                <button type="submit" class="btn-premium">Lưu cấu hình</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/zalo_quick_replies.js') ?>"></script>
<?= $this->endSection() ?>
