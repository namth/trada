<?php

get_header();

if (isset($_GET['del']) && ($_GET['del']) && is_user_logged_in()) {
    $del_id = $_GET['del'];
    $thongbao = "Đã xoá " . get_the_title($del_id);
    wp_delete_post($del_id);
}

echo $thongbao;
?>
        <div class="mui-row" id="main_content">
            <div class="mui-col-md-12">
            
                <?php 
                    if (have_posts()) {
                        while (have_posts()){
                            the_post();

                            echo "<h2>" . get_the_title() . "</h2>";

                            $order_id = get_the_ID();
                            # Chi tiet don hang 
                            $tong_tien  = get_field('tong_tien');
                            $ngay_thang = get_field('ngay_thang');
                            $nhom       = get_field('nhom');

                            # content
                            $args   = array(
                                'post_type'     => 'chi_tiet_don_hang',
                                'posts_per_page' => 999,
                            );
                            $args['meta_query'][] = array(
                                array(
                                    'key'       => 'don_hang',
                                    'value'     => $order_id,
                                    'compare'   => '=',
                                ),
                            );

                            $query = new WP_Query($args);

                            $total = 0;
                            if ($query->have_posts()) {
                                while ($query->have_posts()) {
                                    $query->the_post();

                                    $detail_id = get_the_ID();
                                    $product_id = get_field('san_pham_dich_vu');

                                    $list_user = [];
                                    $users = get_field('danh_sach_nguoi_su_dung');
                                    // print_r($users);
                                    foreach ($users as $user) {
                                        $user_obj = get_user_by('id', $user['user']);
                                        $list_user[] = $user_obj->display_name;
                                    }
                                    $so_tien = get_field('so_tien');

                                    $data .= "<tr>
                                                <td>" . get_the_title($product_id) . "</td>
                                                <td>" . implode(', ', $list_user) . "</td>
                                                <td>" . number_format($so_tien) . " đ</td>
                                                <td>
                                                    <a href='?del=" . $detail_id . "'>Xoá</a>
                                                </td>
                                            </tr>";

                                    $total += $so_tien;
                                } wp_reset_postdata();
                                
                            }

                            echo "Tổng tiền: <b style='font-size: 24px; color: red;'>" . number_format($total) . ' đ</b>';
                            echo "<br>Ngày: <b>" . $ngay_thang . "</b>";
                            echo "<br>Nhóm: <b>" . get_the_title($nhom) . "</b><br>";

                            ?>
                            <h3>Chi tiết đơn hàng</h3>
                            <div class="col-12 box mb-20" id="listuser">
                                <table>
                                    <tr>
                                        <th>Tên sản phẩm</th>
                                        <th>Người sử dụng</th>
                                        <th>Giá tiền</th>
                                        <th>Thao tác</th>
                                    </tr>
                                    <?php 
                                    echo $data;
                                    ?>
                                </table>
                            </div>
                        <?php
            }
                    }
                
                ?>
            </div>
            <a class='mui-btn mui-btn--primary' href="<?php echo get_bloginfo('url') . '/them-chi-tiet-don-hang/?o=' . $order_id; ?>">Thêm đồ</a>
        </div>

<?php
get_footer();