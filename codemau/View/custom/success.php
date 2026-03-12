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
<!--                <h2>⭐⭐⭐ Đặt liên hệ với Luật Ánh Ngọc!</h2>-->
<!--                <br>-->
<!--                <hr>-->
                <p>Tham khảo bài viết:</p>
                <hr>

                <div id="term_main" class="category__main">
                    <?php
                    foreach ($dataMore as $k => $v) {
                        if ($k >5) {
                            break;
                        }
                        $post_model->the_node($v, [
                            //'taxonomy_post_size' => $taxonomy_post_size,
                        ]);
                        ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

