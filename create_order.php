<?php 
/* 
 Template Name: Tao order moi
*/
# Neu co id nhom thi tao don theo nhom, neu khong thi quay lai trang chu
if (isset($_GET['group']) && ($_GET['group'])) {
    $group = $_GET['group'];
} elseif (isset($_GET['g']) && ($_GET['g'])) {
    $group = $_GET['g'];
}

if (!isset($group) || !$group) {
    wp_redirect(home_url());
    exit;
}

$thongbao = '';

if (
    is_user_logged_in() &&
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $group = $_POST['group'];
    // Fix: Use DateTime::createFromFormat to properly parse the date in DD/MM/YYYY format
    $dateorder = DateTime::createFromFormat('d/m/Y', $_POST['dateorder']);
    
    // Add fallback in case date parsing fails
    if ($dateorder === false) {
        $dateorder = new DateTime(); // Use current date as fallback
    }

    if ($group) {
        # create order
        $title = $_POST['order_title'] ?: (get_the_title($group) . " " . $dateorder->format('d-m-Y'));

        $inserted = wp_insert_post(array(
            'post_title'    => $title,
            'post_status'   => 'publish',
            'post_type'     => 'don_hang',
        ));

        update_field('field_63bd1693129d9', $group, $inserted); # group
        update_field('field_63bd1678129d8', $dateorder->format('Ymd'), $inserted); # date
        update_field('field_63f7284f399ac', 'Đơn mới', $inserted); # date

        if ($inserted) {
            $thongbao = 'Tạo đơn hàng thành công';
            wp_redirect( get_permalink($inserted) );
            exit;
        } else {
            $thongbao = "Lỗi rồi, kiểm tra lại nhé";
        }
    }
}

$group_name = isset($group) && $group ? get_the_title($group) : "";
$default_title = $group_name ? $group_name . " " . date('d-m-Y') : "";

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
        <h1 class="order-create-title">Tạo đơn hàng mới</h1>
        <p class="order-create-subtitle">Nhóm: <?php echo esc_html($group_name); ?></p>
        
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield">
                <input type="text" id="order_title" name="order_title" value="<?php echo esc_attr($default_title); ?>" data-manually-changed="false" required>
                <label for="order_title">Tiêu đề đơn hàng</label>
            </div>
            
            <div class="mui-textfield">
                <input type="text" id="datepicker" name="dateorder" value="<?php echo date('d/m/Y'); ?>" autocomplete="off" required>
                <label for="datepicker">Ngày tạo order</label>
            </div>
            
            <input type="hidden" name="group" value="<?php echo esc_attr($group); ?>">
            <input type="hidden" id="group_name" value="<?php echo esc_attr($group_name); ?>">
            
            <?php wp_nonce_field('post_nonce', 'post_nonce_field'); ?>
            
            <div class="form-actions">
                <button type="submit" class="debt-details-button order-create-button">Tạo đơn hàng</button>
                <a href="<?php echo esc_url(get_permalink($group)); ?>" class="mui-btn mui-btn--flat">Quay lại</a>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>