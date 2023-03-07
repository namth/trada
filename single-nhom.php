<?php

get_header();

if (have_posts()) {
    while (have_posts()) {
        the_post();

        $group = get_the_ID();
        $current_user_id = get_current_user_id();
        $group_owner_id = get_field('chu_quy', $group);
        $verify = ($current_user_id == $group_owner_id) || current_user_can('administrator');

        $i = 0;
        $list_user = get_field('danh_sach_thanh_vien');
        foreach ($list_user as $user) {
            $i++;
            $user_obj = get_user_by('id', $user['thanh_vien']);

            $data .= "<tr>";
            $data .= "<td>" . $i . "</td>";
            $data .= "<td><a href='" . get_author_posts_url($user_obj->ID) . "'>" . $user_obj->display_name . "</a></td>";
            $data .= "</tr>";
        }
?>
<h2>Lịch sử hoạt động của nhóm</h2>
<div class="order_listing">
    <?php 
    $paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
    $args = [
        'post_type' => 'don_hang',
    ];
    $args['meta_query'][] = array(
        array(
            'key'       => 'nhom',
            'value'     => $group,
            'compare'   => '=',
        ),
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $trang_thai = get_field('trang_thai');
            if ($trang_thai == "Đơn mới") {
                $class = 'neworder';
            } else if ($trang_thai == "Thanh toán xong") {
                $class = 'doneorder';
            } else $class = '';

            echo "<a class='" . $class . "' href='" . get_permalink() . "'>" . get_field('ngay_thang') . "</a>";
            // echo "<br>";
        } wp_reset_postdata();
    }
    ?>
</div>
<a class='mui-btn mui-btn--primary' href="<?php echo get_bloginfo('url') . '/tao-order-moi/?g=' . get_the_ID(); ?>">Thêm order</a>

<h2><?php echo get_the_title(); ?></h2>
<div class="col-12 box mb-20" id="listuser">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Tên thành viên</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                echo $data;
            ?>
        </tbody>
    </table>
</div>
<a class='mui-btn mui-btn--primary' href="<?php echo get_bloginfo('url') . '/them-thanh-vien-moi/?g=' . $group; ?>">Tạo tài khoản thành viên</a>
<a class='mui-btn mui-btn--primary' href="<?php echo get_bloginfo('url') . '/them-thanh-vien-tu-danh-sach/?g=' . $group; ?>">Thêm thành viên</a>
<a class='mui-btn mui-btn--danger' href="<?php echo get_bloginfo('url') . '/kiem-tra-cong-no-nhom/?g=' . $group; ?>">Kiểm tra công nợ</a>

<?php 
    }
}
get_footer();