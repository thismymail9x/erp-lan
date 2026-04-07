<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<!-- Tích hợp QuillJS Editor Core -->
<link href="<?= base_url('vendor/quill/quill.snow.css') ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/knowledge.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="create-container-lg">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Soạn Cẩm Nang Tri Thức</h2>
            <p class="content-subtitle">Đúc kết tri thức và nghiệp vụ để chia sẻ cho cộng đồng nhân sự L.A.N.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('knowledge') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('knowledge/store') ?>" method="POST" id="knowledgeForm" class="premium-form">
            <?= csrf_field() ?>
            
            <?php if (session()->getFlashdata('errors')) : ?>
                <div class="lan-status-box lan-status-error m-b-24">
                    <i class="fas fa-exclamation-circle lan-box-icon"></i>
                    <p class="m-0">Vui lòng kiểm tra lại: <?= array_shift(session()->getFlashdata('errors')) ?></p>
                </div>
            <?php endif; ?>

            <div class="k-form-row">
                <div class="k-main-col">
                    <div class="form-group-premium m-b-24">
                        <label class="label-premium">Tiêu đề đúc kết <span style="color: #ff3b30;">*</span></label>
                        <input type="text" name="title" class="form-control-premium k-title-input" 
                               value="<?= old('title') ?>" 
                               placeholder="Ví dụ: Kỹ năng tranh tụng thu hồi nợ, Kinh nghiệm đàm phán..." 
                               required>
                    </div>

                    <div class="form-group-premium">
                        <label class="label-premium">Nội dung chi tiết <span style="color: #ff3b30;">*</span></label>
                        <div class="editor-wrapper">
                            <div id="editor-container" style="min-height: 480px;"><?= old('content') ?></div>
                        </div>
                        <input type="hidden" name="content" id="contentInput" value="<?= esc(old('content') ?? '') ?>">
                    </div>
                </div>

                <div class="k-side-col">
                    <div class="k-settings-panel">
                        <h5 class="m-b-24 color-primary font-weight-bold">
                            <i class="fas fa-info-circle m-r-8"></i> Thông tin bổ trợ
                        </h5>

                        <div class="form-group-premium m-b-24">
                            <label class="label-premium">Danh mục bài viết</label>
                            <select name="category" class="form-control-premium select2-enable" required>
                                <option value="general" <?= old('category') == 'general' ? 'selected' : '' ?>>Chia sẻ chung</option>
                                <option value="case_study" <?= (old('category') == 'case_study' || isset($caseInfo)) ? 'selected' : '' ?>>Thực chiến (Case Study)</option>
                                <option value="skill" <?= old('category') == 'skill' ? 'selected' : '' ?>>Kỹ năng chuyên môn</option>
                                <option value="legal_update" <?= old('category') == 'legal_update' ? 'selected' : '' ?>>Kiến thức Pháp lý mới</option>
                            </select>
                        </div>

                        <div class="form-group-premium m-b-24">
                            <label class="label-premium">Vụ việc liên quan</label>
                            <?php if (isset($caseInfo) && $caseInfo) : ?>
                                <input type="hidden" name="case_id" value="<?= $caseInfo['id'] ?>">
                                <div class="case-link-box-active">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-link color-primary m-r-12"></i>
                                        <div>
                                            <div class="font-weight-bold text-sm"><?= esc($caseInfo['code']) ?></div>
                                            <div class="text-xs color-muted line-clamp-1"><?= esc($caseInfo['title']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="case-link-box-empty">
                                    <i class="fas fa-unlink color-muted m-r-8"></i>
                                    <span class="color-muted text-xs">Bản độc lập (Không gắn case)</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group-premium m-b-32">
                            <label class="label-premium">Nhãn dán (Smart Tags)</label>
                            <select name="tags[]" class="form-control-premium select2-enable" multiple="multiple">
                                <?php foreach ($availableTags as $tag) : ?>
                                    <option value="<?= $tag['id'] ?>"><?= esc($tag['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-help-text m-t-12">Dùng nhãn để liên kết kinh nghiệm này với các hồ sơ khác.</p>
                        </div>

                        <button type="submit" class="btn-premium btn-submit-premium w-100">
                            <i class="fas fa-paper-plane m-r-8"></i> Đăng bài viết
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('vendor/quill/quill.min.js') ?>"></script>
<script src="<?= base_url('js/knowledge_editor.js') ?>"></script>
<script>
$(document).ready(function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2-enable').select2({
            width: '100%',
            placeholder: "-- Vui lòng chọn --"
        });
    }
});
</script>
<style>
/* Module-specific refinements */
.case-link-box-active {
    background: #fff;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #0071e333;
    box-shadow: 0 4px 10px rgba(0, 113, 227, 0.05);
}
.case-link-box-empty {
    background: #f5f5f7;
    padding: 16px;
    border-radius: 12px;
    border: 1px dashed #d2d2d7;
    text-align: center;
}
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
<?= $this->endSection() ?>
