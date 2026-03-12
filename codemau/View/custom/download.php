<?php
$base_model->add_css(THEMEPATH . 'css/post_node.css', [
    'cdn' => CDN_BASE_URL,
]);
$base_model->add_css(THEMEPATH . 'css/custom.css', [
    'cdn' => CDN_BASE_URL,
]);
$base_model->add_js(THEMEPATH . 'js/custom.js', [
    'cdn' => CDN_BASE_URL,
]);
$customPostModel = new \App\Models\CustomPost();
$in_cache_dataMore = $term_model->key_cache('in_cache_dataMore'.$category_slug);
$dataMore = $base_model->scache($in_cache_dataMore);
if (empty($dataMore)) {
    $dataMore = $post_model->get_posts_by([

    ], [
            'where'=>[
                'category_primary_slug' => $category_slug
            ],
        'limit' => 50,
        'order_by' => [
            'post_viewed' =>'DESC'
        ]
        //'offset' => 0,
    ]);
    shuffle($dataMore);
    //
    $base_model->scache($in_cache_dataMore, $dataMore, 300);
} else {
    shuffle($dataMore);
}
?>
<div class="w90">
    <div class="widget-box ng-main-content" id="myApp">
        <div class="row">
            <div class="col-12">
                <h2>⭐⭐⭐ Luật Ánh Ngọc tin rằng sự chia sẻ là sức mạnh!</h2>
                <br>
                <?php if (!empty($post)){ ?>
                <div class="waiting">
                    <p> Vui lòng chờ trong giây lát để tải về tài liệu và khám phá thế giới pháp luật trong tầm tay của bạn.</p>
                    <div class="waiting-content">
                        Thời gian đợi: <span id="countdown">60</span> giây
                    </div>
                </div>
                <div class="download">
                    <p><i class="d-block">Bấm vào liên kết để tải về:</i></p>
                    <div class="download-content">
                        <a href="<?= base_url() ?>c/goDownload/<?= $post['ID'] ?>">
                            <span class="d-block text-primary"><?= $post['file_name'] ?></span>
                            <span class="d-block text-secondary"><?= $post['file_size'] ?></span>
                        </a>
                    </div>
                </div>
                <?php } else { ?>
                    <p class="text-danger">File không tồn tại</p>
                <?php } ?>
                <hr>
                <h3>Tham khảo thêm:</h3>
                <hr>

                <div id="term_main" class="category__main">
                    <?php
                    foreach ($dataMore as $k => $v) {
                        if ($k >5) {
                            break;
                        }
                        $customPostModel->the_node_custom($v, [
                            //'taxonomy_post_size' => $taxonomy_post_size,
                        ]);
                        ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

