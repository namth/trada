<?php
/* 
    Template Name: Kiểm tra công nợ theo nhóm
*/

get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="container"><p>Bạn cần đăng nhập để xem thông tin công nợ.</p></div>';
    get_footer();
    exit;
}

// Initialize variables
$group = isset($_GET['g']) ? intval($_GET['g']) : 0;
$pu = isset($_GET['pu']) ? intval($_GET['pu']) : 0;
$du = isset($_GET['du']) ? intval($_GET['du']) : 0;
$data = "";
$payment = array();
$tongtienthanhtoan = 0;
$function = "";

if (!$group) {
    echo '<div class="container"><p>Không tìm thấy nhóm.</p></div>';
    get_footer();
    exit;
}

$phi_dich_vu = (int) get_field('service_tax', $group);
$current_user_id = get_current_user_id();
$group_owner_id = get_field('chu_quy', $group);
$verify = ($current_user_id == $group_owner_id) || current_user_can('administrator');
$group_title = get_the_title($group);

// Process payment if requested
if ($pu && $du) {
    $args = [
        'post_type' => 'don_hang',
        'posts_per_page' => 999,
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
            
            $ngay_thang = get_field('ngay_thang');
            
            $args = array(
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
                    $so_tien = (int) get_field('so_tien');
                    $users = get_field('danh_sach_nguoi_su_dung');
                    $done_order = true;
                    
                    if (!empty($users)) {
                        $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu / 100));
                        
                        if (have_rows('danh_sach_nguoi_su_dung')) {
                            while (have_rows('danh_sach_nguoi_su_dung')) {
                                the_row();
                                
                                $row = get_row_index();
                                $user = get_sub_field('user');
                                $pay = get_sub_field('pay');
                                
                                if (!$pay) {
                                    if (($nguoi_thanh_toan['ID'] == $pu) && ($user == $du)) {
                                        # nếu người thanh toán và người trả tiền trùng khớp thì update thanh toán
                                        update_sub_field('field_63f97e0b6cf3b', true);
                                        $tongtienthanhtoan += $chiphi_motnguoi;
                                    } else {
                                        if ($nguoi_thanh_toan['ID'] != $user) {
                                            if (!isset($payment[$nguoi_thanh_toan['ID']][$user])) {
                                                $payment[$nguoi_thanh_toan['ID']][$user] = 0;
                                            }
                                            $payment[$nguoi_thanh_toan['ID']][$user] += $chiphi_motnguoi;
                                            $done_order = false;
                                        } else {
                                            update_sub_field('field_63f97e0b6cf3b', true);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($done_order) {
                        update_field('field_63f9e870f1437', "Đã thanh toán");
                    }
                }
                wp_reset_postdata();
            }
        }
        wp_reset_postdata(); 
    }
    
    # Cap nhat cong no cho chu nhom
    if (have_rows('danh_sach_thanh_vien', $group)) {
        while (have_rows('danh_sach_thanh_vien', $group)) {
            the_row();
            
            $thanh_vien = get_sub_field('thanh_vien');
            $amount = (int) get_sub_field('amount');
            $row = get_row_index();
            
            # Nếu người thanh toán là chủ nhóm thì lấy số tiền của người trả tiền trong nhóm trừ đi
            if ($thanh_vien == $du) {
                $row_update = array(
                    'thanh_vien' => $thanh_vien,
                    'amount' => $amount - $tongtienthanhtoan,
                );
                update_row('field_63bd0da3f7281', $row, $row_update, $group);
            }
            
            # nếu người thanh toán là thành viên thì nạp thêm tiền cho người thanh toán vào nhóm đó
            # và trừ tiền của user trong group
            if (($pu != $group_owner_id) && ($thanh_vien == $pu)) {
                $row_update = array(
                    'thanh_vien' => $pu,
                    'amount' => $amount + $tongtienthanhtoan,
                );
                update_row('field_63bd0da3f7281', $row, $row_update, $group);
            }
        }
        reset_rows();
    }
}

// Calculate all debt relationships
$args = [
    'post_type' => 'don_hang',
    'posts_per_page' => 999,
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
        
        $args = array(
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
                $so_tien = (int) get_field('so_tien');
                $users = get_field('danh_sach_nguoi_su_dung');
                
                if (!empty($users)) {
                    $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu / 100));
                    
                    if (have_rows('danh_sach_nguoi_su_dung')) {
                        while (have_rows('danh_sach_nguoi_su_dung')) {
                            the_row();
                            
                            $user = get_sub_field('user');
                            $pay = get_sub_field('pay');
                            
                            if (!$pay && $nguoi_thanh_toan['ID'] != $user) {
                                if (!isset($payment[$nguoi_thanh_toan['ID']][$user])) {
                                    $payment[$nguoi_thanh_toan['ID']][$user] = 0;
                                }
                                $payment[$nguoi_thanh_toan['ID']][$user] += $chiphi_motnguoi;
                            }
                        }
                    }
                }
            }
            wp_reset_postdata();
        }
    }
    wp_reset_postdata();
}

// Generate payment table rows
foreach ($payment as $payment_user_id => $debts) {
    foreach ($debts as $debt_user_id => $value) {
        $payment_user = get_user_by('id', $payment_user_id);
        $debt_user = get_user_by('id', $debt_user_id);
        
        if ($verify) {
            $function = "<td class='center'><a class='debt-details-button' href='?g=" . $group . "&pu=" . $payment_user_id . "&du=" . $debt_user_id . "'>Thanh toán</a></td>";
        } else {
            $function = "<td></td>";
        }
        
        $data .= "<tr>
                    <td class='center'>
                        <div class='member-name-with-avatar'>
                            " . get_avatar($payment_user_id, 24, '', '', array('class' => 'user-avatar-small')) . "
                            " . ($payment_user ? $payment_user->display_name : 'Người dùng không tồn tại') . "
                        </div>
                    </td>
                    <td class='center'>
                        <div class='member-name-with-avatar'>
                            " . get_avatar($debt_user_id, 24, '', '', array('class' => 'user-avatar-small')) . "
                            " . ($debt_user ? $debt_user->display_name : 'Người dùng không tồn tại') . "
                        </div>
                    </td>
                    <td class='center'>" . number_format($value) . " đ</td>
                    " . $function . "
                </tr>";
    }
}
?>

<div class="container">
    <div class="action-buttons">
        <a class="mui-btn" href="<?php echo get_permalink($group); ?>">« Quay lại nhóm</a>
    </div>

    <h3>Chi tiết công nợ - <?php echo esc_html($group_title); ?></h3>
    
    <div class="col-12 box mb-20" id="listdebt">
        <table class="debt-table">
            <thead>
                <tr>
                    <th>Người thanh toán</th>
                    <th>Người nợ</th>
                    <th>Số tiền nợ</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Không có dữ liệu công nợ</td>
                </tr>
                <?php else: ?>
                    <?php echo $data; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="action-buttons">
        <a class='debt-details-button' href="<?php echo get_permalink($group); ?>">Quay lại nhóm</a>
    </div>
</div>

<?php get_footer(); ?>