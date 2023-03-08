<?php
get_header();
?>
<h2>Sự kiện đang hoạt động</h2>
<div class="order_listing">
    <?php 
    $paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
    $args = [
        'post_type' => 'don_hang',
    ];
    $args['meta_query'][] = array(
        array(
            'key'       => 'trang_thai',
            'value'     => "Đơn mới",
            'compare'   => '=',
        ),
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $nhom = get_field('nhom');
            echo "<a class='neworder' href='" . get_permalink() . "'>" . get_the_title($nhom) . "<br>" . get_field('ngay_thang') . "</a>";
            // echo "<br>";
        } wp_reset_postdata();
    }
    ?>
</div>
<?php

get_footer();

