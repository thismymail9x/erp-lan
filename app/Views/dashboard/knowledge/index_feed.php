<?php if (empty($articles)) { ?>
    <div class="empty-state-container text-center p-40">
        <i class="fas fa-wind text-muted k-empty-icon"></i>
        <h4 class="text-muted-dark">Chưa tìm thấy kết quả phù hợp</h4>
        <p class="text-muted">Thử thay đổi từ khóa hoặc bộ lọc khác xem sao nhé.</p>
    </div>
<?php } else { ?>
    <?php foreach ($articles as $article) { ?>
        <div class="knowledge-card <?= $article['is_pinned'] ? 'pinned' : '' ?>">
            <div class="knowledge-meta">
                <div class="avatar-sm"><?= strtoupper(substr($article['author_name'], 0, 1)) ?></div>
                <strong><?= esc($article['author_name']) ?></strong>
                <span class="text-muted">• <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?></span>
                <?php if ($article['is_pinned']) { ?>
                    <span class="badge bg-warning text-dark"><i class="fas fa-thumbtack"></i> Đã ghim</span>
                <?php } ?>
                <span class="badge-category badge-<?= esc($article['category']) ?>">
                    <?php
                        $catLabels = ['case_study' => 'Case Study', 'skill' => 'Kỹ năng', 'legal_update' => 'Luật mới', 'general' => 'Chia sẻ chung'];
                        echo $catLabels[$article['category']] ?? 'Khác';
                    ?>
                </span>
            </div>
            
            <a href="<?= base_url('knowledge/show/' . $article['id']) ?>" class="knowledge-title">
                <?= esc($article['title']) ?>
            </a>

            <?php if ($article['case_id']) { ?>
            <div class="m-t-8 m-b-12">
                <a href="<?= base_url('cases/show/' . $article['case_id']) ?>" class="btn-secondary-sm k-case-link">
                    <i class="fas fa-briefcase"></i> Link Vụ việc: <?= esc($article['case_code']) ?>
                </a>
            </div>
            <?php } ?>

            <div class="knowledge-excerpt m-t-12">
                <?= esc(mb_substr(strip_tags($article['content']), 0, 250)) ?>...
            </div>

            <div class="knowledge-footer">
                <div class="tags-group">
                    <?php if(!empty($article['tags'])) { foreach($article['tags'] as $tag) { ?>
                        <span class="badge bg-light text-dark border"><i class="fas fa-tag"></i> <?= esc($tag['name']) ?></span>
                    <?php } } ?>
                </div>
                <div class="knowledge-stats">
                    <span title="Lượt đọc"><i class="far fa-eye stat-icon"></i> <?= $article['view_count'] ?></span>
                    <span title="Hữu ích"><i class="far fa-lightbulb stat-icon text-warning"></i> <?= $article['helpful_count'] ?></span>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- Pagination -->
    <div class="d-flex justify-content-center m-t-24" id="knowledge-pagination">
        <?= $pager->links() ?>
    </div>
<?php } ?>
