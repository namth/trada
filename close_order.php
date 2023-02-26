<?php 
/* 
 Template Name: Thanh toan order
*/
# Neu co id nhom thi tao don theo nhom, neu khong thi quay lai trang chu
if (isset($_GET['o']) && ($_GET['o'])) {
    $order = $_GET['o'];
    $group = get_field('nhom', $order);
    $status = get_field('trang_thai', $order);
    $group_owner = get_user_by('id', get_field('chu_quy', $group));
    $list_user = get_field('danh_sach_thanh_vien', $group);

    $total = calculate_order_payment($order);

    # Neu trang thai khong phai la don moi thi redirect ve don hang
    if ($status != "Đơn mới") {
        wp_redirect(get_permalink($order));
    }
} else {
    wp_redirect( get_bloginfo('url') );
}

$current_user_id = get_current_user_id();
$verify = ($current_user_id == $group_owner->ID) || current_user_can('administrator');
if (
    is_user_logged_in() && $verify &&
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    # lay du lieu
    $payment_user = $_POST['payment_user'];
    $price  = $_POST['price'];

    # Nhập số tiền và người thanh toán vào hệ thống
    update_field('field_63bd1653129d7', $price, $order); # tong tien
    update_field('field_63f8894fe02a8', $payment_user, $order); # nguoi tra tien 
    # Chuyển trạng thái đơn hàng sang đã thanh toán
    update_field('field_63f7284f399ac', 'Thanh toán xong', $order); # trang thai don hang

    # redirect to order
    wp_redirect(get_permalink($order));
}

get_header();
echo $thongbao;
?>
        <h3>Thanh toán đơn hàng </h3>
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-select">
                <label for="product">Người thanh toán</label>
                <select name="payment_user">
                    <?php 
                        if ($group_owner->ID) {
                            echo '<option value="' . $group_owner->ID . '">' . $group_owner->display_name . '</option>';
                        }

                        foreach ($list_user as $user) {
                            $user_obj = get_user_by('id', $user['thanh_vien']);
                            if ($user_obj->ID != $group_owner->ID) {
                                echo '<option value="' . $user_obj->ID . '">' . $user_obj->display_name . '</option>';
                            }
                        }
                    ?>
                    
                </select>
            </div>
            <div class="mui-textfield product_price">
                <label for="product">Số tiền cần thanh toán</label>
                <input type="text" name="price" value="<?php echo $total; ?>">
            </div>
            <?php
            wp_nonce_field('post_nonce', 'post_nonce_field');
            ?>
            <button type="submit" class="mui-btn mui-btn--raised mui-btn--primary">Thanh toán</button>
            <a href="<?php echo get_permalink($order) ;?>" class="mui-btn mui-btn--raised">Quay lại order</a>
        </form>
<?php
get_footer();