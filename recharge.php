<?php 
/*
    Template Name: Nạp tiền
*/
if (isset($_GET['g']) && ($_GET['g'])) {
    $group = $_GET['g'];
    $list_user = get_field('danh_sach_thanh_vien', $group);
} else {
    wp_redirect( get_bloginfo('url') );
}

$current_user_id = get_current_user_id();
if (
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $payment_user = $_POST['payment_user'];
    $price  = $_POST['price'];

    # đọc user id và lấy danh sách tiền nạp của user của các nhóm 

    # tìm kiếm nhóm trong danh sách tiền nạp
    if (have_rows('danh_sach_thanh_vien', $group)) {
        while (have_rows('danh_sach_thanh_vien', $group)) {
            the_row();
            
            $thanh_vien = get_sub_field('thanh_vien');
            $amount     = get_sub_field('amount');
            $row        = get_row_index();
            
            if ($thanh_vien == $payment_user) {
                $row_update = array(
                    'thanh_vien'    => $thanh_vien,
                    'amount'        => $amount + $price,
                );
                                        
                update_row('field_63bd0da3f7281', $row, $row_update, $group);
                break;
            }
        }
        reset_rows();
    }
    # Nếu có nhóm thì đọc số tiền hiện tại và cập nhật tiền mới thêm vào nhóm
    # Nếu không có nhóm thì tạo row mới gồm nhóm và số tiền mới vào nhóm 
}

get_header();
?>
    <h3>Nạp tiền</h3>
    <form class="mui-form" method="POST" enctype="multipart/form-data">
        <div class="mui-select">
            <label for="product">Người nạp tiền</label>
            <select name="payment_user">
                <?php 
                    foreach ($list_user as $user) {
                        $user_obj = get_user_by('id', $user['thanh_vien']);
                        echo '<option value="' . $user_obj->ID . '">' . $user_obj->display_name . '</option>';
                    }
                ?>
                
            </select>
        </div>
        <div class="mui-textfield product_price">
            <label for="product">Số tiền</label>
            <input type="text" name="price" value="<?php echo $total; ?>">
        </div>
        <?php
        wp_nonce_field('post_nonce', 'post_nonce_field');
        ?>
        <button type="submit" class="mui-btn mui-btn--raised mui-btn--primary">Thanh toán</button>
        <a href="<?php echo get_permalink($order) ;?>" class="mui-btn mui-btn--raised">Quay lại order</a>
    </form>
<?php
get_footer();

