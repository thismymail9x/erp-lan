<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/knowledge.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper">
    <div class="header-title-container">
        <div class="m-b-8">
            <a href="<?= base_url('knowledge') ?>" class="text-muted text-decoration-none text-sm">
                <i class="fas fa-chevron-left text-xs"></i> Quay lại Cẩm nang
            </a>
        </div>
        <h2 class="content-title">Chi tiết Bài viết</h2>
    </div>
    <div class="header-controls">
        <?php if ($article['author_id'] == session()->get('employee_id') || has_permission('sys.admin')) : ?>
            <a href="<?= base_url('knowledge/edit/' . $article['id']) ?>" class="btn-secondary-sm">
                <i class="fas fa-edit"></i> Chỉnh sửa
            </a>
            <a href="<?= base_url('knowledge/delete/' . $article['id']) ?>" class="btn-secondary-sm text-red" onclick="return confirm('Xác nhận gỡ bài viết này?');">
                <i class="fas fa-trash"></i> Gỡ Bài
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="knowledge-feed-container">
    <div class="article-layer">
        
        <!-- Headers & Meta -->
        <div class="text-center m-b-32">
            <div class="d-flex justify-content-center m-b-16">
                <span class="badge-category badge-<?= esc($article['category']) ?>">
                    <?php
                        $catLabels = ['case_study' => 'Case Study Thực Chiến', 'skill' => 'Kỹ năng chuyên môn', 'legal_update' => 'Kiến thức Pháp lý mới', 'general' => 'Chia sẻ chung'];
                        echo $catLabels[$article['category']] ?? 'Khác';
                    ?>
                </span>
            </div>
            <h1 class="k-show-title">
                <?= esc($article['title']) ?>
            </h1>
            
            <div class="d-flex justify-content-center align-items-center gap-3 text-muted">
                <div class="avatar-sm shadow-sm k-author-avatar">
                    <?= mb_strtoupper(mb_substr($author['full_name'], 0, 1)) ?>
                </div>
                <div class="text-start">
                    <strong class="d-block text-dark"><?= esc($author['full_name']) ?></strong>
                    <span class="k-author-pos"><?= esc($author['position']) ?> • Đăng ngày <?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <?php if ($caseInfo) { ?>
            <!-- Card Ngữ cảnh Vụ Việc -->
            <div class="bg-light rounded-3 p-20 m-b-32 border border-info border-opacity-25 k-case-link-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="k-case-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-primary font-weight-bold">Bài học này được rút ra từ Hồ sơ pháp lý:</h6>
                        <a href="<?= base_url('cases/show/' . $caseInfo['id']) ?>" class="text-decoration-none">
                            <strong class="text-dark hover-primary"><?= esc($caseInfo['code']) ?> - <?= esc($caseInfo['title']) ?></strong>
                        </a>
                        <p class="mb-0 mt-1 text-muted k-case-subtitle">Khuyến nghị nên bám sát Timeline của vụ việc này để hiểu rõ ngữ cảnh xử lý trên thực tế.</p>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Bài viết chính -->
        <div class="article-content-main">
            <!-- Summary Area -->
            <?php if (!empty($article['summary'])) : ?>
                <div class="knowledge-quick-summary m-b-32">
                    <i class="fas fa-quote-left color-primary m-r-8"></i>
                    <strong>Tóm tắt nhanh:</strong> <?= esc($article['summary']) ?>
                </div>
            <?php endif; ?>

            <div class="knowledge-section m-b-40">
                <h4 class="section-title"><i class="fas fa-question-circle m-r-8 color-primary"></i> 1. Vấn đề (Problem)</h4>
                <div class="section-body ql-editor">
                    <?= $article['problem'] ?? '<p class="text-muted italic">Đang cập nhật...</p>' ?>
                </div>
            </div>

            <div class="knowledge-section m-b-40">
                <h4 class="section-title"><i class="fas fa-check-circle m-r-8 color-success"></i> 2. Cách giải quyết (Solution)</h4>
                <div class="section-body ql-editor">
                    <?= $article['solution'] ?? '<p class="text-muted italic">Đang cập nhật...</p>' ?>
                </div>
            </div>

            <?php if (!empty($article['red_flags'])) : ?>
                <div class="red-flag-section m-b-40">
                    <div class="red-flag-title">
                        <i class="fas fa-exclamation-triangle"></i> LƯU Ý QUAN TRỌNG (RED FLAGS)
                    </div>
                    <div class="section-body ql-editor">
                        <?= $article['red_flags'] ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer / Tags / Analytics -->
        <div class="m-t-40 p-t-24 k-footer-divider">
            
            <div class="m-b-32">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <strong>Nhãn dán liên kết:</strong> 
                        <div class="tags-group mt-2">
                            <?php if(!empty($tags)) { foreach($tags as $tag) { ?>
                                <span class="badge bg-light text-dark border rounded-pill fw-normal px-3 py-2"><i class="fas fa-tag"></i> <?= esc($tag['name']) ?></span>
                            <?php } } else { ?>
                                <span class="text-muted italic">Không có nhãn dán nào</span>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn-premium-sm btn-copy-link" data-link="<?= base_url('knowledge/show/' . $article['id']) ?>">
                            <i class="fas fa-link"></i> Copy Link nhanh
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center bg-light rounded-pill p-x-24 p-y-16">
                <div class="text-muted font-weight-bold">
                    <i class="fas fa-eye m-r-8"></i> <?= $article['view_count'] ?> lượt xem
                </div>
                
                <!-- Khối chức năng Vote Hữu Ích qua POST Form -->
                <form action="<?= base_url('knowledge/vote/' . $article['id']) ?>" method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm">
                        <i class="fas fa-lightbulb"></i> Bài này cực hữu ích (<?= $article['helpful_count'] ?>)
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.btn-copy-link').click(function() {
        const link = $(this).data('link');
        const el = document.createElement('textarea');
        el.value = link;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        
        const originalHtml = $(this).html();
        $(this).html('<i class="fas fa-check"></i> Đã Copy!');
        $(this).addClass('bg-success text-white');
        
        setTimeout(() => {
            $(this).html(originalHtml);
            $(this).removeClass('bg-success text-white');
        }, 2000);
    });
});
</script>

<style>
.section-title { font-weight: 700; font-size: 1.2rem; color: #1d1d1f; border-bottom: 2px solid #f2f2f7; padding-bottom: 12px; margin-bottom: 20px; }
.knowledge-quick-summary { background: #f5f5f7; padding: 20px; border-radius: 12px; border-left: 4px solid #0071e3; font-style: italic; color: #48484a; }
.btn-copy-link { background: #fff; border: 1px solid #d2d2d7; color: #1d1d1f; padding: 10px 20px; border-radius: 20px; font-weight: 600; font-size: 12px; transition: all 0.2s; }
.btn-copy-link:hover { background: #f5f5f7; border-color: #0071e3; color: #0071e3; }
</style>
<?= $this->endSection() ?>
