<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/knowledge.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="knowledge-feed-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Cẩm nang Nội bộ</h2>
            <p class="content-subtitle">Chia sẻ, học hỏi và đóng góp kinh nghiệm nghiệp vụ Luật.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('knowledge/create') ?>" class="btn-premium">
                <i class="fas fa-pen-nib"></i> Thêm
            </a>
        </div>
    </div>

    <!-- Search/Filter Bar (Apple Style Row) -->
    <form action="<?= base_url('knowledge') ?>" method="GET" class="search-filter-bar filter-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Tìm kiếm kinh nghiệm..." value="<?= esc(request()->getGet('search')) ?>">
        </div>
        
        <select name="category" class="filter-select">
            <option value="">Tất cả danh mục</option>
            <option value="case_study" <?= request()->getGet('category') == 'case_study' ? 'selected' : '' ?>>Case Study (Thực chiến)</option>
            <option value="skill" <?= request()->getGet('category') == 'skill' ? 'selected' : '' ?>>Kỹ năng chuyên môn</option>
            <option value="legal_update" <?= request()->getGet('category') == 'legal_update' ? 'selected' : '' ?>>Cập nhật Văn bản Pháp lý</option>
            <option value="general" <?= request()->getGet('category') == 'general' ? 'selected' : '' ?>>Chia sẻ chung</option>
        </select>

        <select name="tag_id" class="filter-select">
            <option value="">Theo Nhãn dán</option>
            <?php foreach ($availableTags as $t) { ?>
                <option value="<?= $t['id'] ?>" <?= request()->getGet('tag_id') == $t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?></option>
            <?php } ?>
        </select>
        
<!--        <button type="submit" class="btn-filter-submit">Tìm</button>-->
        <a href="<?= base_url('knowledge') ?>" class="btn-filter-secondary">Xóa</a>
    </form>

    <!-- Articles Feed & Leaderboard Layout -->
    <div class="k-main-layout">
        <div class="k-feed-side">
            <div id="knowledge-feed-results">
                <?= view('dashboard/knowledge/index_feed', ['articles' => $articles, 'pager' => $pager]) ?>
            </div>
        </div>

        <div class="k-sidebar-side">
            <!-- Vinh danh Chuyên gia -->
            <div class="premium-card leaderboard-card">
                <div class="card-header-premium">
                    <h5 class="m-0"><i class="fas fa-trophy color-warning m-r-8"></i> Chuyên gia của tháng</h5>
                </div>
                <div class="card-body-premium p-0">
                    <?php if (empty($leaderboard)) : ?>
                        <div class="p-24 text-center text-muted text-xs">Chưa có dữ liệu tháng này. Hãy là người đầu chia sẻ!</div>
                    <?php else : ?>
                        <div class="leaderboard-list">
                            <?php foreach ($leaderboard as $index => $expert) : ?>
                                <div class="leaderboard-item">
                                    <div class="expert-rank rank-<?= $index + 1 ?>"><?= $index + 1 ?></div>
                                    <div class="expert-info">
                                        <div class="expert-name"><?= esc($expert['full_name']) ?></div>
                                        <div class="expert-pos"><?= esc($expert['position']) ?></div>
                                    </div>
                                    <div class="expert-score">
                                        <span class="score-val"><?= number_format($expert['total_helpful']) ?></span>
                                        <i class="fas fa-lightbulb text-warning"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="premium-card m-t-24">
                <div class="card-body-premium">
                    <h6 class="font-weight-bold m-b-16">Tại sao nên chia sẻ?</h6>
                    <ul class="benefit-list">
                        <li><i class="fas fa-check-circle text-success font-12"></i> Được vinh danh Chuyên gia.</li>
                        <li><i class="fas fa-check-circle text-success font-12"></i> Tích lũy điểm KPI nghiệp vụ.</li>
                        <li><i class="fas fa-check-circle text-success font-12"></i> Xây dựng kho tri thức chung.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/knowledge.js') ?>"></script>
<?= $this->endSection() ?>
