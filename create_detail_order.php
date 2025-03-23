<?php 
/* 
 Template Name: Tao chi tiet order moi
*/
# Neu co id nhom thi tao don theo nhom, neu khong thi quay lai trang chu
if (isset($_GET['o']) && ($_GET['o'])) {
    $order = $_GET['o'];
    $group = get_field('nhom', $order);
    $date = get_field('ngay_thang', $order);
} else {
    wp_redirect( get_bloginfo('url') );
    exit;
}

$thongbao = '';

if (
    is_user_logged_in() &&
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $product = $_POST['product'];
    $sanpham = $_POST['sanpham'];
    $user = $_POST['user'];
    $price = $_POST['price'];

    if ($product) {
        $new_product = $product;
    } else {
        # check san pham trung lap truoc
        # ...
        # create product if not exists
        $new_product = wp_insert_post(array(
            'post_title'    => $sanpham,
            'post_status'   => 'publish',
            'post_type'     => 'san_pham',
        ));

        # update tam gia tien cho san pham
        update_field('field_63bbf02c7b2e9', $price, $new_product);
    }
        
    # create order detail 
    if ($new_product) {
        $order_detail = wp_insert_post(array(
            'post_title'    => get_the_title($product) . ' ngày: ' . $date,
            'post_status'   => 'publish',
            'post_type'     => 'chi_tiet_don_hang',
        ));
        # get product id and add to order detail
        if ($order_detail) {
            # update chi tiet order
            update_field('field_63bd131405595', $new_product, $order_detail);
            update_field('field_63bd134205596', $price, $order_detail);
            update_field('field_63bd139205599', $order, $order_detail);
            update_field('field_63f9e870f1437', "Chưa thanh toán", $order_detail);
            foreach ($user as $u) {
                $user_obj = get_user_by('id', $u);
                $row_update = array(
                    'user'   => $user_obj->ID,
                );
                add_row('field_63bd134e05597', $row_update, $order_detail);
            }

            $thongbao = 'Tạo đơn hàng thành công';
            wp_redirect( get_permalink($order) );
            exit;
        } else {
            $thongbao = "Lỗi rồi, kiểm tra lại nhé";
        }
    } else {
        $thongbao = "Lỗi rồi, kiểm tra lại nhé";
    }
}

get_header();

// Get order info for display
$order_title = get_the_title($order);
$group_name = get_the_title($group);
?>

<canvas id="starry-background"></canvas>

<div class="order-create-container starry-page">
    <?php if (!empty($thongbao)): ?>
        <div class="alert alert-info">
            <?php echo esc_html($thongbao); ?>
        </div>
    <?php endif; ?>

    <div class="order-create-form">
        <h1 class="order-create-title">Thêm Chi tiết đơn hàng</h1>
        <p class="order-create-subtitle">Đơn hàng: <?php echo esc_html($order_title); ?></p>
        <p class="order-create-subtitle">Nhóm: <?php echo esc_html($group_name); ?></p>
        
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield">
                <label for="autocomplete_product">Tên sản phẩm / dịch vụ</label>
                <p class='product_label' style="display: none;"></p>
                <input type="text" id="autocomplete_product" name="sanpham">
                <input type="hidden" name="product">
            </div>
            
            <div class="mui-textfield">
                <label for="price">Số tiền</label>
                <input type="text" name="price" id="price" value="">
                <input type="hidden" name="single_price">
            </div>
            
            <div class="mui-textfield">
                <label>Thành viên sử dụng</label>
                <div class="member-checkboxes">
                    <?php 
                    $list_user = get_field('danh_sach_thanh_vien', $group);

                    if (!empty($list_user)) {
                        foreach ($list_user as $user) {
                            $user_obj = get_user_by('id', $user['thanh_vien']);
                            if ($user_obj) {
                                echo '<div class="mui-checkbox">
                                        <label>
                                        <input type="checkbox" value="' . $user_obj->ID . '" name="user[]">
                                            ' . $user_obj->display_name . '
                                        </label>
                                    </div>';
                            }
                        }
                    } else {
                        echo '<p>Không có thành viên trong nhóm này</p>';
                    }
                    ?>
                </div>
            </div>
            
            <input type="hidden" name="group" value="<?php echo $group ? $group : ""; ?>">
            
            <?php wp_nonce_field('post_nonce', 'post_nonce_field'); ?>
            
            <div class="form-actions">
                <button type="submit" class="debt-details-button order-create-button">Thêm chi tiết</button>
                <a href="<?php echo get_permalink($order); ?>" class="mui-btn mui-btn--flat">Quay lại đơn hàng</a>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>