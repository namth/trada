<?php 
/* 
 Template Name: Them thanh vien moi
*/
if (isset($_GET['g']) && ($_GET['g'])) {
    $group = $_GET['g'];
}
if (
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $display_name   = $_POST['display_name'];
    $username       = $_POST['username'];
    $password       = $_POST['password'];
    $email          = $_POST['email'];

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
        $row_update = array(
            'thanh_vien'   => $new_partner,
        );
        add_row('field_63bd0da3f7281', $row_update, $group);

        # chuyen trang
        wp_redirect( get_permalink($group) );
        exit;
    } else {
        $thongbao = "Lỗi, có thể user đã đăng ký.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
<?php
echo $thongbao;
?>
        <h3>Thêm thành viên </h3>
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield">
                <label for="html">Tên thành viên</label>
                <input type="text" name="display_name" value="">
            </div>
            <div class="mui-textfield">
                <label for="html">Username</label>
                <input type="text" name="username">
            </div>
            <div class="mui-textfield">
                <label for="password">Mật khẩu </label>
                <input type="password" name="password">
            </div>
            <div class="mui-textfield">
                <label for="email">Email</label>
                <input type="text" name="email">
            </div>
            <?php
            wp_nonce_field('post_nonce', 'post_nonce_field');
            ?>
            <button type="submit" class="mui-btn mui-btn--raised mui-btn--primary">Submit</button>
        </form>

        <a class='mui-btn mui-btn--raised' href="<?php echo get_permalink($group); ?>">Quay lại trang danh sách</a>
<?php
get_footer();