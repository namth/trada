<?php 
/* 
 Template Name: Them nhom moi
*/

// Make sure user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$thongbao = '';
$current_user_id = get_current_user_id();

if (
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $group_name = sanitize_text_field($_POST['group_name']);
    $group_owner = $current_user_id; // Automatically set current user as group owner
    $group_fund = 0; // Default to 0
    $service_fee = 0; // Default to 0
    
    if (!empty($group_name)) {
        // Create group post
        $group_id = wp_insert_post(array(
            'post_title'    => $group_name,
            'post_status'   => 'publish',
            'post_type'     => 'nhom',
        ));
        
        if (!is_wp_error($group_id)) {
            // Set group owner
            update_field('field_63bd0d21f7280', $group_owner, $group_id);
            
            // Set fund and service fee with default values
            update_field('fund', $group_fund, $group_id);
            update_field('service_tax', $service_fee, $group_id);
            
            // Add group owner to members list
            $row_owner = array(
                'thanh_vien' => $group_owner,
            );
            add_row('field_63bd0da3f7281', $row_owner, $group_id);
            
            $thongbao = "Đã tạo nhóm thành công: " . $group_name;
            
            // Redirect to the new group page
            wp_redirect(get_permalink($group_id));
            exit;
        } else {
            $thongbao = "Lỗi khi tạo nhóm. Vui lòng thử lại.";
        }
    } else {
        $thongbao = "Vui lòng điền tên nhóm";
    }
}

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
        <h1 class="order-create-title">Tạo nhóm mới</h1>
        
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield mui-textfield--float-label">
                <input type="text" id="group_name" name="group_name" required>
                <label for="group_name">Tên nhóm</label>
            </div>
            
            <?php /* Hidden group fund and service fee fields
            <div class="mui-textfield mui-textfield--float-label">
                <input type="number" id="group_fund" name="group_fund" min="0" step="1000">
                <label for="group_fund">Quỹ nhóm ban đầu (đồng)</label>
            </div>
            
            <div class="mui-textfield mui-textfield--float-label">
                <input type="number" id="service_fee" name="service_fee" min="0" max="30" step="0.1" value="0">
                <label for="service_fee">Phí dịch vụ (%)</label>
                <small>Nhập giá trị từ 0% đến 30%</small>
            </div>
            */ ?>
            
            <?php wp_nonce_field('post_nonce', 'post_nonce_field'); ?>
            
            <div class="form-actions">
                <button type="submit" class="debt-details-button order-create-button">Tạo nhóm</button>
                <a href="<?php echo esc_url(home_url('/danh-sach-nhom')); ?>" class="mui-btn mui-btn--flat">Quay lại</a>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>
