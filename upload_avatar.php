<?php 
/* 
 Template Name: Thay đổi avatar
*/

// Make sure user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;
$thongbao = '';
$error = false;

// Process form submission
if (isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
    // Check nonce for security
    if (!isset($_POST['avatar_nonce']) || !wp_verify_nonce($_POST['avatar_nonce'], 'upload_avatar')) {
        $thongbao = 'Lỗi bảo mật. Vui lòng thử lại.';
        $error = true;
    } else {
        // Get the file
        $avatar = $_FILES['avatar'];
        
        // Check for upload errors
        if ($avatar['error'] !== UPLOAD_ERR_OK) {
            $thongbao = 'Lỗi khi tải lên file. Vui lòng thử lại.';
            $error = true;
        } else {
            // Check file type
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
            $file_type = $avatar['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $thongbao = 'Chỉ chấp nhận file hình ảnh (JPG, PNG, GIF).';
                $error = true;
            } else {
                // Check file size (max 2MB)
                if ($avatar['size'] > 2 * 1024 * 1024) {
                    $thongbao = 'Kích thước file không được vượt quá 2MB.';
                    $error = true;
                } else {
                    // Create upload directory if it doesn't exist
                    $upload_dir = wp_upload_dir();
                    $user_dir = $upload_dir['basedir'] . '/user-avatars';
                    
                    if (!file_exists($user_dir)) {
                        wp_mkdir_p($user_dir);
                    }
                    
                    // Create a unique filename
                    $file_ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
                    $filename = 'user-' . $current_user_id . '-' . time() . '.' . $file_ext;
                    $file_path = $user_dir . '/' . $filename;
                    
                    // Move the file
                    if (move_uploaded_file($avatar['tmp_name'], $file_path)) {
                        // Get relative path for storage
                        $relative_path = 'user-avatars/' . $filename;
                        
                        // Delete old avatar if exists
                        $old_avatar = get_user_meta($current_user_id, 'custom_avatar', true);
                        if ($old_avatar) {
                            $old_file_path = $upload_dir['basedir'] . '/' . $old_avatar;
                            if (file_exists($old_file_path)) {
                                @unlink($old_file_path);
                            }
                        }
                        
                        // Save avatar path to user meta
                        update_user_meta($current_user_id, 'custom_avatar', $relative_path);
                        
                        $thongbao = 'Avatar đã được cập nhật thành công!';
                        
                        // Redirect after 2 seconds
                        echo '<script>
                            setTimeout(function() {
                                window.location.href = "' . get_author_posts_url($current_user_id) . '";
                            }, 2000);
                        </script>';
                    } else {
                        $thongbao = 'Lỗi khi lưu file. Vui lòng thử lại.';
                        $error = true;
                    }
                }
            }
        }
    }
}

get_header();
?>

<canvas id="starry-background"></canvas>

<div class="order-create-container starry-page">
    <?php if (!empty($thongbao)): ?>
        <div class="alert <?php echo $error ? 'alert-error' : 'alert-success'; ?>">
            <?php echo esc_html($thongbao); ?>
        </div>
    <?php endif; ?>

    <div class="order-create-form">
        <h1 class="order-create-title">Thay đổi avatar</h1>
        
        <div class="avatar-preview">
            <?php echo get_avatar($current_user_id, 150); ?>
        </div>
        
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield">
                <label for="avatar">Chọn ảnh avatar mới</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif" required style="height: 50px;">
                <small>Chấp nhận định dạng: JPG, PNG, GIF. Kích thước tối đa: 2MB.</small>
            </div>
            
            <?php wp_nonce_field('upload_avatar', 'avatar_nonce'); ?>
            
            <div class="form-actions">
                <button type="submit" name="upload_avatar" class="debt-details-button order-create-button">Cập nhật avatar</button>
                <a href="<?php echo get_author_posts_url($current_user_id); ?>" class="mui-btn mui-btn--flat">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>
