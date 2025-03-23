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

$thongbao = '';
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
    $phi_dich_vu = (int) get_field('service_tax', $group);

    # Nhập số tiền và người thanh toán vào hệ thống
    update_field('field_63bd1653129d7', $price, $order); # tong tien
    update_field('field_63f8894fe02a8', $payment_user, $order); # nguoi tra tien 
    # Chuyển trạng thái đơn hàng sang đã thanh toán
    update_field('field_63f7284f399ac', 'Thanh toán xong', $order); # trang thai don hang

    # redirect to order
    wp_redirect(get_permalink($order));
    exit;
}

// Get order info for display
$order_title = get_the_title($order);
$group_name = get_the_title($group);

get_header();
?>

<canvas id="starry-background"></canvas>

<div class="order-create-container starry-page">
    <?php if (!empty($thongbao)): ?>
        <div class="alert alert-info">
            <?php echo esc_html($thongbao); ?>
        </div>
    <?php endif; ?>

    <div class="order-create-form">
        <h1 class="order-create-title">Thanh toán đơn hàng</h1>
        <p class="order-create-subtitle">Đơn hàng: <?php echo esc_html($order_title); ?></p>
        <p class="order-create-subtitle">Nhóm: <?php echo esc_html($group_name); ?></p>
        
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield payment-user-field">
                <label for="payment_user">Người thanh toán</label>
                <select id="payment_user" name="payment_user" class="payment-select">
                    <?php 
                    if ($group_owner->ID) {
                        echo '<option value="' . esc_attr($group_owner->ID) . '">' . esc_html($group_owner->display_name) . '</option>';
                    }

                    if (!empty($list_user)) {
                        foreach ($list_user as $user) {
                            $user_obj = get_user_by('id', $user['thanh_vien']);
                            if ($user_obj && $user_obj->ID != $group_owner->ID) {
                                echo '<option value="' . esc_attr($user_obj->ID) . '">' . esc_html($user_obj->display_name) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="mui-textfield">
                <label for="price">Số tiền cần thanh toán</label>
                <input type="text" id="price" name="price" value="<?php echo esc_attr($total); ?>">
            </div>
            
            <?php wp_nonce_field('post_nonce', 'post_nonce_field'); ?>
            
            <div class="form-actions">
                <button type="submit" class="debt-details-button order-create-button">Thanh toán</button>
                <a href="<?php echo esc_url(get_permalink($order)); ?>" class="mui-btn mui-btn--flat">Quay lại đơn hàng</a>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>