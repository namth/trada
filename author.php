<?php
get_header();
$this_user = get_queried_object(); 
$current_user_id = $this_user->ID;

$args   = array(
    'post_type'     => 'chi_tiet_don_hang',
    'posts_per_page' => 999,
);
$args['meta_query'][] = array(
    array(
        'key'       => 'trang_thai',
        'value'     => "Chưa thanh toán",
        'compare'   => '=',
    ),
);
# using hook in this function: my_posts_where (functions.php)
# to filter data in sub field of acf repeater
$args['meta_query'][] = array(
    array(
        'key'       => 'danh_sach_nguoi_su_dung_$_user',
        'value'     => $current_user_id,
        'compare'   => '=',
    ),
);

$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();

        $detail_id = get_the_ID();
        $product_id = get_field('san_pham_dich_vu');
        $order_id = get_field('don_hang');
        $ngay_thang = get_field('ngay_thang', $order_id);
        $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
        $so_tien = get_field('so_tien');
        $users = get_field('danh_sach_nguoi_su_dung');

        foreach ($users as $user) {
            if (!$user['pay']) {
                $payment[$nguoi_thanh_toan['ID']][$user['user']] += $so_tien / count($users);
            }
        }
    }
}
// print_r($payment);
foreach ($payment as $payment_user_id => $debts) {
    foreach ($debts as $debt_user_id => $value) {
        if ($debt_user_id == $current_user_id) {
            $payment_user = get_user_by('id', $payment_user_id);
            $debt_user = get_user_by('id', $debt_user_id);
    
            $data .= "<tr>
                        <td>" . $payment_user->display_name . "</td>
                        <td>" . number_format($value) . " đ</td>
                    </tr>";
        }
    }
}
?>
<h3>Chi tiết công nợ của <?php echo $this_user->display_name; ?></h3>
<div class="col-12 box mb-20" id="listuser">
    <table>
        <tr>
            <th>Chủ nợ</th>
            <th>Giá tiền phải trả</th>
        </tr>
        <?php 
        echo $data;
        ?>
    </table>
</div>
<?php
get_footer();