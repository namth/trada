<?php 
/* 
 Template Name: Them thanh vien moi tu danh sach co san
*/
$group_arr = [];
if (isset($_GET['g']) && ($_GET['g'])) {
    $group = $_GET['g'];
    $list_user = get_field('danh_sach_thanh_vien', $group);
    foreach ($list_user as $key => $value) {
        $group_arr[] = $value['thanh_vien'];
    }
}
if (
    is_user_logged_in() &&
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $users  = $_POST['user'];
    
    foreach ($users as $user) {
        if (!in_array($user, $group_arr)) {
            $row_update = array(
                'thanh_vien'   => $user,
            );
            add_row('field_63bd0da3f7281', $row_update, $group);
        }
    }
    wp_redirect( get_permalink($group) );
}

get_header();
echo $thongbao;
?>
        <h3>Thêm thành viên </h3>
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield">
                <label for="product">Thành viên sử dụng</label>
                <?php 
                $count_args  = array(
                    'number'    => 999999,
                );
                $query = new WP_User_Query($count_args);
                $users = $query->get_results();
                if (!empty($users)) {
                    foreach ($users as $user) {
                        echo '<div class="mui-checkbox">
                                <label>
                                <input type="checkbox" value="' . $user->ID . '" name="user[]">
                                    ' . $user->display_name . '
                                </label>
                            </div>';
                    }
                }
                ?>
            </div>
            <?php
            wp_nonce_field('post_nonce', 'post_nonce_field');
            ?>
            <button type="submit" class="mui-btn mui-btn--raised mui-btn--primary">Submit</button>
        </form>

        <a class='mui-btn mui-btn--raised' href="<?php echo get_permalink($group); ?>">Quay lại trang danh sách</a>
<?php
get_footer();