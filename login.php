<?php 
/*
    Template Name: Login
*/
if(is_user_logged_in()) {
    // redirect sang trang chủ
    wp_redirect( get_bloginfo('url') );
    exit;
} else {
    // check form
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && 
        isset( $_POST['post_nonce_field'] ) && 
        wp_verify_nonce( $_POST['post_nonce_field'], 'post_nonce' ) ) {
        
        if (isset($_POST)) {
            $error = false;
            
            if ( isset($_POST['username']) && ($_POST['username'] != "") ) {
                $username = $_POST['username'];
            } else {
                $error = true;
                $error_user = __('Mời bạn nhập User ID / Email.', 'qlcv');
            }

            if ( isset($_POST['password']) && ($_POST['password'] != "") ) {
                $password = $_POST['password'];
            } else {
                $error = true;
                $error_password = __('Mời bạn nhập mật khẩu.', 'qlcv');
            }

            if ( isset($_POST['remember']) && ($_POST['remember'] == "on") ) {
                $remember = true;
            } else {
                $remember = false;
            }

        } else $error = true;
        
        if (!$error) {
            // dùng wp_signon() để đăng nhập
            $user = wp_signon( array(
                'user_login'    => $_POST['username'],
                'user_password' => $_POST['password'],
                'remember'      => $remember,
            ), false );

            // Check if login was successful
            if (is_wp_error($user)) {
                $error = true;
                $error_login = $user->get_error_message();
            } else {
                $userID = $user->ID;

                wp_set_current_user( $userID, $username );
                wp_set_auth_cookie( $userID, true, false );
                do_action( 'wp_login', $username, $user );

                // redirect sang trang chủ
                wp_redirect( get_bloginfo('url') );
                exit;
            }
        }
    }
    // redirect sang trang chủ
}

// Get the header
get_header();
?>

<canvas id="starry-background"></canvas>

<div class="login-container">
    <?php if (isset($error_user) || isset($error_password) || isset($error_login)): ?>
    <div class="alert alert-secondary" role="alert">
        <?php 
            if (isset($error_login)) {
                echo $error_login;
            } elseif (isset($error_user)) {
                echo $error_user;
            } elseif (isset($error_password)) {
                echo $error_password;
            }
        ?>
    </div>
    <?php endif; ?>

    <div class="login-register-form">
        <h1 class="login-title">Chia tiền platform</h1>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row">
                <?php 
                    $username = isset($_POST["username"]) ? $_POST["username"] : "";
                ?>
                <div class="mui-textfield product_price">
                    <label for="username">User ID / Email</label>
                    <input type="text" name="username" value="<?php echo esc_attr($username); ?>">
                </div>
                <div class="mui-textfield product_price">
                    <label for="password">Password</label>
                    <input type="password" name="password" value="">
                </div>
                <div class="mui-textfield">
                    <label for="product">Ghi nhớ</label>
                    <div class="mui-checkbox">
                        <label>
                        <input type="checkbox" value="" name="remember"> <?php _e('Lưu session.', 'qlcv'); ?>
                        </label>
                    </div>
                </div>
                <?php 
                    wp_nonce_field( 'post_nonce', 'post_nonce_field' );
                ?>
                <button type="submit" class="debt-details-button login-button">Đăng nhập</button>                    
            </div>
        </form>
        <div class="register-link">
            <a href="<?php echo home_url('/them-thanh-vien-moi'); ?>">Đăng ký tài khoản mới</a>
        </div>
    </div>
</div>
                   
</div>
<?php wp_footer(); ?>
</body>
</html>