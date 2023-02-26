<?php
/* 
    Template Name: Kiểm tra công nợ theo nhóm
*/
if (isset($_GET['g']) && ($_GET['g'])) {
    $group = $_GET['g'];
    $pu = $_GET['pu'];
    $du = $_GET['du'];
    $phi_dich_vu = get_field('service_tax', $group);
}

get_header();
$current_user_id = get_current_user_id();
$group_owner_id = get_field('chu_quy', $group);
$verify = ($current_user_id == $group_owner_id) || current_user_can('administrator');

$args = [
    'post_type' => 'don_hang',
];
$args['meta_query'][] = array(
    array(
        'key'       => 'nhom',
        'value'     => $group,
        'compare'   => '=',
    ),
);
$order_query = new WP_Query($args);
if ($order_query->have_posts()) {
    while ($order_query->have_posts()) {
        $order_query->the_post();

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
        $args['meta_query'][] = array(
            array(
                'key'       => 'don_hang',
                'value'     => get_the_ID(),
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
                // print_r($users);
                $done_order = true;
                
                if (have_rows('danh_sach_nguoi_su_dung')) {
                    while (have_rows('danh_sach_nguoi_su_dung')) {
                        the_row();

                        $row    = get_row_index();
                        $user   = get_sub_field('user');
                        $pay    = get_sub_field('pay');
                        if (!$pay) {
                            if (($nguoi_thanh_toan['ID'] == $pu) && ($user == $du)) {
                                # nếu người thanh toán và người trả tiền trùng khớp thì update thanh toán
                                update_sub_field('field_63f97e0b6cf3b', true);
                                
                            } else {
                                if ($nguoi_thanh_toan['ID'] != $user) {
                                    $payment[$nguoi_thanh_toan['ID']][$user] += round(($so_tien / count($users)) * (1 + $phi_dich_vu));

                                    $done_order = false;
                                }
                            }
                        }
                    }
                }
                if ($done_order) {
                    update_field('field_63f9e870f1437', "Đã thanh toán");
                }
            } wp_reset_postdata();
        }
    } wp_reset_postdata(); 
}    

foreach ($payment as $payment_user_id => $debts) {
    foreach ($debts as $debt_user_id => $value) {
        $payment_user = get_user_by('id', $payment_user_id);
        $debt_user = get_user_by('id', $debt_user_id);
        if ($verify) {
            $function = "<td><a class='mui-btn' href='?g=" . $group . "&pu=" . $payment_user_id . "&du=" . $debt_user_id . "'>Thanh toán</a></td>";
        }

        $data .= "<tr>
                    <td>" . $payment_user->display_name . "</td>
                    <td>" . $debt_user->display_name . "</td>
                    <td>" . number_format($value) . " đ</td>
                    " . $function . "
                </tr>";
    }
}
?>
<h3>Chi tiết công nợ</h3>
<div class="col-12 box mb-20" id="listuser">
    <table>
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
<a class='mui-btn mui-btn--primary' href="<?php echo get_permalink($group); ?>">Quay lại nhóm</a>
<?php
get_footer();