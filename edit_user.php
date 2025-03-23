<?php 
/* 
 Template Name: Chỉnh sửa thông tin
*/
get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="container"><p>Bạn cần đăng nhập để chỉnh sửa thông tin.</p></div>';
    get_footer();
    exit;
}

$current_user = wp_get_current_user();
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user->ID;

// Check permissions - only admins can edit other users
if ($user_id !== $current_user->ID && !current_user_can('administrator')) {
    echo '<div class="container"><p>Bạn không có quyền chỉnh sửa thông tin người dùng khác.</p></div>';
    get_footer();
    exit;
}

$user_data = get_userdata($user_id);
$thongbao = '';

if (
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'edit_user_nonce')
) {
    $display_name = sanitize_text_field($_POST['display_name']);
    $email = sanitize_email($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $error = false;
    
    // Validate password if provided
    if (!empty($password)) {
        // Check if passwords match
        if ($password !== $confirm_password) {
            $thongbao = "<div class='error-message'>Lỗi: Mật khẩu không khớp.</div>";
            $error = true;
        }
        
        // Check password complexity
        if (strlen($password) < 8) {
            $thongbao = "<div class='error-message'>Lỗi: Mật khẩu phải có ít nhất 8 ký tự.</div>";
            $error = true;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $thongbao = "<div class='error-message'>Lỗi: Mật khẩu phải có ít nhất 1 chữ in hoa.</div>";
            $error = true;
        }
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $thongbao = "<div class='error-message'>Lỗi: Mật khẩu phải có ít nhất 1 ký tự đặc biệt.</div>";
            $error = true;
        }
    }
    
    if (!$error) {
        $args = array(
            'ID'           => $user_id,
            'display_name' => $display_name,
            'user_email'   => $email,
        );
        
        // Only update password if a new one was provided
        if (!empty($password)) {
            $args['user_pass'] = $password;
        }
        
        $update_result = wp_update_user($args);
        
        if (is_wp_error($update_result)) {
            $thongbao = "<div class='error-message'>Lỗi: " . $update_result->get_error_message() . "</div>";
        } else {
            $thongbao = "<div class='success-message'>Cập nhật thông tin thành công!</div>";
            
            // Refresh user data after update
            $user_data = get_userdata($user_id);
        }
    }
}

// Get the refreshed user data
$display_name = $user_data->display_name;
$email = $user_data->user_email;
?>

<div class="container">
    <div class="edit-user-container">
        <div class="action-buttons">
            <a class="mui-btn mui-btn--primary" href="javascript:history.back()">« Quay lại</a>
        </div>
        
        <h2 class="center">Chỉnh sửa thông tin người dùng</h2>
        
        <?php echo $thongbao; ?>
        
        <div class="edit-user-avatar center">
            <?php echo get_avatar($user_id, 120); ?>
        </div>
        
        <form class="mui-form edit-user-form" method="POST" id="edit-user-form">
            <div class="mui-textfield">
                <label for="display_name">Tên hiển thị</label>
                <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($display_name); ?>" required>
            </div>
            
            <div class="mui-textfield">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" required>
            </div>
            
            <div class="mui-textfield">
                <label for="password">Mật khẩu mới (để trống nếu không thay đổi)</label>
                <input type="password" id="password" name="password">
                <p class="password-hint">Mật khẩu cần có ít nhất 8 ký tự, 1 chữ in hoa và 1 ký tự đặc biệt</p>
            </div>
            
            <div class="mui-textfield">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input type="password" id="confirm_password" name="confirm_password">
                <p class="password-error" id="password-error" style="display: none; color: red; font-size: 14px;"></p>
            </div>
            
            <?php wp_nonce_field('edit_user_nonce', 'post_nonce_field'); ?>
            
            <div class="form-actions center"></div>
                <button type="submit" class="mui-btn mui-btn--raised mui-btn--primary">Cập nhật thông tin</button>
            </div>
        </form>
    </div>
</div>

<style>
.error-message {
    color: #d32f2f;
    background-color: #fbe9e7;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #d32f2f;
}

.success-message {
    color: #388e3c;
    background-color: #e8f5e9;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #388e3c;
}

.password-hint {
    color: #666;
    font-size: 13px;
    margin-top: 5px;
    font-style: italic;
}

.edit-user-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}
</style>

<?php get_footer(); ?>
