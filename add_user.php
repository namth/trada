<?php 
/* 
 Template Name: Them thanh vien moi
*/
if (isset($_GET['g']) && ($_GET['g'])) {
    $group = $_GET['g'];
}

$thongbao = '';

if (
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $display_name   = $_POST['display_name'];
    $username       = $_POST['username'];
    $password       = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email          = $_POST['email'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $thongbao = "Mật khẩu không khớp. Vui lòng kiểm tra lại.";
    } else {
        $args = array(
            'user_login'    => $username,
            'user_email'    => $email,
            'user_pass'     => $password,
            'display_name'  => $display_name,
        );

        $new_partner = wp_insert_user($args);

        if (!is_wp_error($new_partner)) {
            $thongbao = "Đăng ký thành công cho user: " . $display_name;

            # add user to group
            if (isset($group) && $group) {
                $row_update = array(
                    'thanh_vien'   => $new_partner,
                );
                add_row('field_63bd0da3f7281', $row_update, $group);
            }

            # chuyen trang
            if (isset($group) && $group) {
                wp_redirect(get_permalink($group));
            } else {
                wp_redirect(home_url());
            }
            exit;
        } else {
            $thongbao = "Lỗi, có thể user đã đăng ký.";
        }
    }
}

get_header();

// Get group name for display
$group_name = '';
if (isset($group) && $group) {
    $group_name = get_the_title($group);
}
?>

<canvas id="starry-background"></canvas>

<div class="order-create-container starry-page">
    <?php if (!empty($thongbao)): ?>
        <div class="alert alert-info">
            <?php echo esc_html($thongbao); ?>
        </div>
    <?php endif; ?>

    <div class="order-create-form">
        <h1 class="order-create-title">Thêm thành viên mới</h1>
        <?php if (!empty($group_name)): ?>
            <p class="order-create-subtitle">Nhóm: <?php echo esc_html($group_name); ?></p>
        <?php endif; ?>
        
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield mui-textfield--float-label">
                <input type="text" id="display_name" name="display_name" required>
                <label for="display_name">Tên thành viên</label>
            </div>
            
            <div class="mui-textfield mui-textfield--float-label">
                <input type="text" id="username" name="username" required>
                <label for="username">Username</label>
            </div>
            
            <div class="mui-textfield mui-textfield--float-label">
                <input type="password" id="password" name="password" required>
                <label for="password">Mật khẩu</label>
            </div>
            
            <div class="mui-textfield mui-textfield--float-label">
                <input type="password" id="confirm_password" name="confirm_password" required>
                <label for="confirm_password">Xác nhận mật khẩu</label>
            </div>
            
            <div class="mui-textfield mui-textfield--float-label">
                <input type="email" id="email" name="email" required>
                <label for="email">Email</label>
            </div>
            
            <?php wp_nonce_field('post_nonce', 'post_nonce_field'); ?>
            
            <div class="form-actions">
                <button type="submit" class="debt-details-button order-create-button">Tạo thành viên</button>
                <?php if (isset($group) && $group): ?>
                    <a href="<?php echo esc_url(get_permalink($group)); ?>" class="mui-btn mui-btn--flat">Quay lại</a>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url()); ?>" class="mui-btn mui-btn--flat">Quay lại trang chủ</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>