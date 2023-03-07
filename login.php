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

            // print_r($user);

            $userID = $user->ID;

            wp_set_current_user( $userID, $username );
            wp_set_auth_cookie( $userID, true, false );
            do_action( 'wp_login', $username );
            
            // redirect sang trang chủ
            wp_redirect( get_bloginfo('url') );
            exit;
        }
    }
    // redirect sang trang chủ
}
?>
<!doctype html>
<html class="no-js" lang="en">

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="//cdn.muicss.com/mui-0.10.3/css/mui.min.css" rel="stylesheet" type="text/css" />
    <script src="//cdn.muicss.com/mui-0.10.3/js/mui.min.js"></script>

    <?php wp_head(); ?>
</head>
<body>
    <div class="mui-container-fluid">
        <div class="mui-row" id="header">
            <div class="mui-col-md-12">
                <?php
                    wp_nav_menu(array(
                        'menu' => "Main menu"
                    ));
                ?>
            </div>
        </div>

        <div class="alert alert-secondary" role="alert">
            <?php 
                if ($error_user) {
                    echo $error_user;
                } else if ($error_password) {
                    echo $error_password;
                }
            ?>
        </div>

        <div class="login-register-form">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <?php 
                        $username = $_POST["username"]?$_POST["username"]:"";
                    ?>
                    <div class="mui-textfield product_price">
                        <label for="username">User ID / Email</label>
                        <input type="text" name="username" value="">
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
                    <button type="submit" class="mui-btn mui-btn--raised mui-btn--primary">Đăng nhập</button>                    
                </div>
            </form>
        </div>
                       
    </div>
    <?php wp_footer(); ?>
</body>

</html>