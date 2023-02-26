<?php 
/* 
 Template Name: Tao order moi
*/
# Neu co id nhom thi tao don theo nhom, neu khong thi quay lai trang chu
if (isset($_GET['g']) && ($_GET['g'])) {
    $group = $_GET['g'];
}
if (
    is_user_logged_in() &&
    isset($_POST['post_nonce_field']) &&
    wp_verify_nonce($_POST['post_nonce_field'], 'post_nonce')
) {
    $group = $_POST['group'];
    $dateorder = new DateTime($_POST['dateorder']);

    if ($group) {
        # create order
        $title = get_the_title($group) . " " . $dateorder->format('d-m-Y');

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
        } else {
            $thongbao = "Lỗi rồi, kiểm tra lại nhé";
        }
    }
}

get_header();
echo $thongbao;
?>
        <h3>Thêm Order </h3>
        <form class="mui-form" method="POST" enctype="multipart/form-data">
            <div class="mui-textfield">
                <label for="html">Ngày tạo order</label>
                <input type="date" name="dateorder" value="">
            </div>
            <input type="hidden" name="group" value="<?php echo $group?$group:""; ?>">
            <?php
            wp_nonce_field('post_nonce', 'post_nonce_field');
            ?>
            <button type="submit" class="mui-btn mui-btn--raised mui-btn--primary">Tạo đơn</button>
        </form>
<?php
get_footer();