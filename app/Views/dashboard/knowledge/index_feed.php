<?php if (empty($articles)) { ?>
    <div class="empty-state-container text-center p-40">
        <i class="fas fa-wind text-muted k-empty-icon"></i>
        <h4 class="text-muted-dark">Chưa tìm thấy kết quả phù hợp</h4>
        <p class="text-muted">Thử thay đổi từ khóa hoặc bộ lọc khác xem sao nhé.</p>
    </div>
<?php } else { ?>
    <?php foreach ($articles as $article) { ?>
        <div class="knowledge-card <?= $article['is_pinned'] ? 'pinned' : '' ?>">
            <div class="knowledge-meta-top">
                <div class="author-info">
                    <div class="avatar-sm"><?= mb_strtoupper(mb_substr($article['author_name'], 0, 1)) ?></div>
                    <div class="author-text">
                        <div class="author-name"><?= esc($article['author_name']) ?></div>
                        <div class="author-meta">
                            <span class="badge-category-pill badge-<?= esc($article['category']) ?>">
                                <?php
                                    $catLabels = ['case_study' => 'Thực chiến', 'skill' => 'Kỹ năng', 'legal_update' => 'Luật mới', 'general' => 'Chia sẻ'];
                                    echo $catLabels[$article['category']] ?? 'Khác';
                                ?>
                            </span>
                            <span class="m-l-8"><?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
                <div class="meta-right">
                    <?php if ($article['is_pinned']) { ?>
                        <span class="pinned-tag"><i class="fas fa-thumbtack"></i></span>
                    <?php } ?>
                </div>
            </div>
            
            <a href="<?= base_url('knowledge/show/' . $article['id']) ?>" class="knowledge-title-main">
                <?= esc($article['title']) ?>
            </a>

            <div class="knowledge-summary-text">
                <?= !empty($article['summary']) ? esc($article['summary']) : mb_substr(strip_tags($article['content']), 0, 180) . '...' ?>
            </div>

            <?php if ($article['case_id']) { ?>
            <div class="m-t-16">
                <a href="<?= base_url('cases/show/' . $article['case_id']) ?>" class="k-case-tag">
                    <i class="fas fa-link"></i> #<?= esc($article['case_code']) ?>: <?= esc(mb_substr($article['case_title'], 0, 40)) ?>...
                </a>
            </div>
            <?php } ?>

            <div class="knowledge-footer-main">
                <div class="tags-row">
                    <?php if(!empty($article['tags'])) { foreach($article['tags'] as $tag) { ?>
                        <span class="tag-pill">#<?= esc($tag['name']) ?></span>
                    <?php } } ?>
                </div>
                <div class="stats-row">
                    <span class="stat-pill" title="Hữu ích">
                        <i class="fas fa-lightbulb"></i> <?= $article['helpful_count'] ?>
                    </span>
                    <span class="stat-pill" title="Xem">
                        <i class="far fa-eye"></i> <?= $article['view_count'] ?>
                    </span>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- Pagination -->
    <div class="d-flex justify-content-center m-t-24" id="knowledge-pagination">
        <?= $pager->links() ?>
    </div>
<?php } ?>
