<?php
/* 
    Template Name: Kiểm tra công nợ
*/
get_header();
// $current_user_id = get_current_user_id();

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

$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();

        $order_id = get_field('don_hang');
        $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
        $so_tien = get_field('so_tien');
        $users = get_field('danh_sach_nguoi_su_dung');

        foreach ($users as $user) {
            $payment[$nguoi_thanh_toan['ID']][$user['user']] += $so_tien / count($users);
            if (!$user['pay']) {
            }
        }
        
    }
}

foreach ($payment as $payment_user_id => $debts) {
    foreach ($debts as $debt_user_id => $value) {
        $payment_user = get_user_by('id', $payment_user_id);
        $debt_user = get_user_by('id', $debt_user_id);

        $data .= "<tr>
                    <td>" . $payment_user->display_name . "</td>
                    <td>" . $debt_user->display_name . "</td>
                    <td>" . number_format($value) . " đ</td>
                </tr>";
    }
}
?>
<h3>Chi tiết công nợ</h3>
<div class="col-12 box mb-20" id="listuser">
    <table class='table table-hover'>
        <tr>
            <th>Người thanh toán</th>
            <th>Con nợ</th>
            <th>Giá tiền phải trả</th>
        </tr>
        <?php 
        echo $data;
        ?>
    </table>
</div>
<?php
get_footer();