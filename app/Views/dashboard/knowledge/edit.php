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
            <h2 class="content-title">Chỉnh sửa Cẩm Nang</h2>
            <p class="content-subtitle">Cập nhật và hoàn thiện tri thức nghiệp vụ cho hệ thống.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('knowledge/show/' . $article['id']) ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại bài viết
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('knowledge/update/' . $article['id']) ?>" method="POST" id="knowledgeForm" class="premium-form">
            <?= csrf_field() ?>
            
            <?php if ($errors = session()->getFlashdata('errors')) : ?>
                <div class="lan-status-box lan-status-error m-b-24">
                    <i class="fas fa-exclamation-circle lan-box-icon"></i>
                    <p class="m-0">Vui lòng kiểm tra lại: <?= array_shift($errors) ?></p>
                </div>
            <?php endif; ?>

            <div class="k-form-row">
                <div class="k-main-col">
                    <div class="form-group-premium m-b-24">
                        <label class="label-premium">Vấn đề đúc kết <span style="color: #ff3b30;">*</span></label>
                        <input type="text" name="title" class="form-control-premium k-title-input" 
                               value="<?= old('title', $article['title']) ?>" 
                               placeholder="Vấn đề" 
                               required>
                    </div>

                    <div class="form-group-premium m-b-24">
                        <label class="label-premium">Tóm tắt nhanh (Quick Summary)</label>
                        <input type="text" name="summary" class="form-control-premium" 
                               value="<?= old('summary', $article['summary']) ?>" 
                               placeholder="Tóm tắt nhanh trong 1 câu...">
                    </div>

                    <div class="form-group-premium m-b-28">
                        <label class="label-premium">1. Chi tiết Vấn đề (Dạng Bullet points) <span style="color: #ff3b30;">*</span></label>
                        <div class="editor-wrapper">
                            <div id="problem-editor" style="height: 150px;"><?= old('problem', $article['problem']) ?></div>
                        </div>
                        <input type="hidden" name="problem" id="problemInput" value="<?= esc(old('problem', $article['problem']) ?? '') ?>">
                    </div>

                    <div class="form-group-premium m-b-28">
                        <label class="label-premium">2. Cách giải quyết <span style="color: #ff3b30;">*</span></label>
                        <div class="editor-wrapper">
                            <div id="solution-editor" style="height: 150px;"><?= old('solution', $article['solution']) ?></div>
                        </div>
                        <input type="hidden" name="solution" id="solutionInput" value="<?= esc(old('solution', $article['solution']) ?? '') ?>">
                    </div>

                    <div class="form-group-premium m-b-28">
                        <label class="label-premium">3. Lưu ý quan trọng (Red flags) <i class="fas fa-flag color-danger m-l-4"></i></label>
                        <div class="editor-wrapper red-flag-wrapper">
                            <div id="redflags-editor" style="height: 150px;"><?= old('red_flags', $article['red_flags']) ?></div>
                        </div>
                        <input type="hidden" name="red_flags" id="redflagsInput" value="<?= esc(old('red_flags', $article['red_flags']) ?? '') ?>">
                    </div>
                </div>

                <div class="k-side-col">
                    <div class="k-settings-panel">
                        <h5 class="m-b-24 color-primary font-weight-bold">
                            <i class="fas fa-cogs m-r-8"></i> Cấu hình & Chỉ số
                        </h5>

                        <div class="form-group-premium m-b-24">
                            <label class="label-premium">Danh mục bài viết</label>
                            <select name="category" class="form-control-premium select2-enable" required>
                                <option value="general" <?= old('category', $article['category']) == 'general' ? 'selected' : '' ?>>Chia sẻ chung</option>
                                <option value="case_study" <?= old('category', $article['category']) == 'case_study' ? 'selected' : '' ?>>Thực chiến (Case Study)</option>
                                <option value="skill" <?= old('category', $article['category']) == 'skill' ? 'selected' : '' ?>>Kỹ năng chuyên môn</option>
                                <option value="legal_update" <?= old('category', $article['category']) == 'legal_update' ? 'selected' : '' ?>>Kiến thức Pháp lý mới</option>
                            </select>
                        </div>

                        <div class="form-group-premium m-b-24">
                            <label class="label-premium">Vụ việc liên quan</label>
                            <?php if ($caseInfo) : ?>
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
                                    <span class="color-muted text-xs">Bản độc lập</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group-premium m-b-24">
                            <label class="label-premium">Nhãn dán (Smart Tags)</label>
                            <select name="tags[]" class="form-control-premium select2-enable" multiple="multiple">
                                <?php foreach ($availableTags as $tag) : ?>
                                    <option value="<?= $tag['id'] ?>" <?= in_array($tag['id'], $currentTags) ? 'selected' : '' ?>><?= esc($tag['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="stats-overview-box m-b-24">
                            <div class="d-flex justify-content-between">
                                <div class="stat-item">
                                    <div class="text-xs color-muted">Lượt xem</div>
                                    <div class="font-weight-bold"><?= number_format($article['view_count']) ?></div>
                                </div>
                                <div class="stat-item text-right">
                                    <div class="text-xs color-muted">Hữu ích</div>
                                    <div class="font-weight-bold color-success"><?= number_format($article['helpful_count']) ?></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-premium btn-submit-premium w-100">
                            <i class="fas fa-save m-r-8"></i> Lưu thay đổi
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
.red-flag-wrapper { border: 1px solid #ff3b3033; background: #fffafa; border-radius: 8px; }
.red-flag-wrapper .ql-toolbar { background: #fff5f5; border-bottom: 1px solid #ff3b301a; }
.case-link-box-active { background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #0071e333; }
.case-link-box-empty { background: #f5f5f7; padding: 16px; border-radius: 12px; border: 1px dashed #d2d2d7; text-align: center; }
.line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.stats-overview-box { background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #ebebed; }
</style>
<?= $this->endSection() ?>
