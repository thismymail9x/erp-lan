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
                    <?= strtoupper(substr($author['full_name'], 0, 1)) ?>
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
        <div class="article-content ql-editor">
            <!-- Render raw HTML từ QuillJS Editor (Đã lọc XSS từ tầng Controller nếu cần) -->
            <?= $article['content'] ?>
        </div>

        <!-- Footer / Tags / Analytics -->
        <div class="m-t-40 p-t-24 k-footer-divider">
            
            <div class="m-b-24">
                <strong>Nhãn dán liên kết:</strong> 
                <div class="tags-group mt-2">
                    <?php if(!empty($tags)) { foreach($tags as $tag) { ?>
                        <span class="badge bg-secondary rounded-pill fw-normal px-3 py-2"><i class="fas fa-tag"></i> <?= esc($tag['name']) ?></span>
                    <?php } } else { ?>
                        <span class="text-muted italic">Không có nhãn dán nào</span>
                    <?php } ?>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center bg-light rounded-pill p-x-24 p-y-16">
                <div class="text-muted font-weight-bold">
                    <i class="fas fa-eye m-r-8"></i> Đã tiếp cận <?= $article['view_count'] ?> lượt đọc
                </div>
                
                <!-- Khối chức năng Vote Hữu Ích qua POST Form -->
                <form action="<?= base_url('knowledge/vote/' . $article['id']) ?>" method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm">
                        <i class="fas fa-lightbulb"></i> Bài viết siêu Hữu Ích (<?= $article['helpful_count'] ?>)
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</div>
<?= $this->endSection() ?>
