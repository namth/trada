<?php
/* 
    Template Name: Chi tiết công nợ
*/

get_header();

// Get parameters
$user_id = isset($_GET['u']) ? $_GET['u'] : get_current_user_id();
$group_id = isset($_GET['g']) ? $_GET['g'] : null;
$payer_id = isset($_GET['pay']) ? $_GET['pay'] : null; // Add payer ID parameter
$current_user_id = get_current_user_id();

// Initialize variables
$debt_data = array();
$total_debt = 0;
$user_info = get_user_by('id', $user_id);
$payer_info = $payer_id ? get_user_by('id', $payer_id) : null;

// Set up query for orders
$orders_args = [
    'post_type'         => 'don_hang',
    'posts_per_page'    => 999,
    'meta_query'        => array(
        array(
            'key'       => 'trang_thai',
            'value'     => "Thanh toán xong",
            'compare'   => '=',
        )
    ),
    'meta_key'  => 'ngay_thang',
    'orderby'   => 'meta_value',
    'order'     => 'DESC'
];

// Filter by group if specified
if ($group_id) {
    $orders_args['meta_query'][] = array(
        'key'       => 'nhom',
        'value'     => $group_id,
        'compare'   => '=',
    );
    $group_title = get_the_title($group_id);
}

$order_query = new WP_Query($orders_args);

if ($order_query->have_posts()) {
    while ($order_query->have_posts()) {
        $order_query->the_post();
        
        $order_id = get_the_ID();
        $order_title = get_the_title();
        $ngay_thang = get_field('ngay_thang');
        $group = get_field('nhom');
        $group_name = $group ? get_the_title($group) : 'Không có nhóm';
        $phi_dich_vu = $group ? (int) get_field('service_tax', $group) : 0;
        $nguoi_thanh_toan = get_field('nguoi_thanh_toan');

        // Query for order details with unpaid status
        $detail_args = array(
            'post_type'     => 'chi_tiet_don_hang',
            'posts_per_page' => 999,
            'meta_query'    => array(
                array(
                    'key'       => 'don_hang',
                    'value'     => $order_id,
                    'compare'   => '=',
                ),
                array(
                    'key'       => 'trang_thai',
                    'value'     => "Chưa thanh toán",
                    'compare'   => '=',
                )
            )
        );
        
        $detail_query = new WP_Query($detail_args);
        
        if ($detail_query->have_posts()) {
            while ($detail_query->have_posts()) {
                $detail_query->the_post();
                
                // echo get_the_title() . '<br>';
                $so_tien = (int) get_field('so_tien');
                $users = get_field('danh_sach_nguoi_su_dung');
                
                if (!$users || empty($users)) continue;
                
                $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu));
                
                if (have_rows('danh_sach_nguoi_su_dung')) {
                    while (have_rows('danh_sach_nguoi_su_dung')) {
                        the_row();
                        
                        $user = get_sub_field('user');
                        $pay = get_sub_field('pay');
                        
                        // Là người nợ tiền (người sử dụng chưa thanh toán và không phải người thanh toán)
                        if (!$pay && $user == $user_id && $nguoi_thanh_toan['ID'] != $user_id && 
                            ($payer_id === null || $payer_id == $nguoi_thanh_toan['ID'])) {
                            $debt_data[] = array(
                                'date'          => $ngay_thang,
                                'payer'         => $nguoi_thanh_toan['display_name'],
                                'payer_id'      => $nguoi_thanh_toan['ID'],
                                'debtor'        => $user_info->display_name,
                                'debtor_id'     => $user_id, // Add debtor_id
                                'order_title'   => $order_title,
                                'group_name'    => $group_name,
                                'group_id'      => $group,
                                'amount'        => $chiphi_motnguoi,
                                'status'        => $pay ? 'Đã thanh toán' : 'Chưa thanh toán'
                            );
                            $total_debt += $chiphi_motnguoi;
                        }
                        
                        // Là người thanh toán (người khác nợ mình)
                        if (!$pay && $nguoi_thanh_toan['ID'] == $user_id && $user != $user_id && 
                            ($payer_id === null || $payer_id == $user_id)) {
                            $debtor = get_user_by('id', $user);
                            $debt_data[] = array(
                                'date' => $ngay_thang,
                                'payer' => $user_info->display_name,
                                'payer_id' => $user_id,
                                'debtor' => $debtor->display_name,
                                'debtor_id' => $user, // Add debtor_id
                                'order_title' => $order_title,
                                'group_name' => $group_name,
                                'group_id' => $group,
                                'amount' => -$chiphi_motnguoi, // Negative because they owe you
                                'status' => $pay ? 'Đã thanh toán' : 'Chưa thanh toán'
                            );
                            $total_debt -= $chiphi_motnguoi;
                        }
                    }
                }
            }
            wp_reset_postdata();
        }
    }
    wp_reset_postdata();
}

// Sort by date (newest first)
usort($debt_data, fn($a, $b) => strtotime(str_replace('/', '-', $b['date'])) - strtotime(str_replace('/', '-', $a['date'])));

?>

<div class="container">
    <div class="action-buttons">
        <a class="mui-btn" href="javascript:history.back()">« Quay lại</a>
    </div>
    
    <h3>Chi tiết công nợ của <?php echo $user_info->display_name; ?></h3>
    
    <?php if ($group_id): ?>
    <p>Nhóm: <?php echo $group_title; ?></p>
    <?php endif; ?>
    
    <div class="col-12 box mb-20" id="listdebt">
        <table class="debt-table">
            <thead>
                <tr>
                    <th>Ngày tháng</th>
                    <th>Người thanh toán</th>
                    <th>Người nợ</th>
                    <th>Tên đơn hàng</th>
                    <?php if (!$group_id): ?>
                    <th>Tên nhóm</th>
                    <?php endif; ?>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($debt_data)): ?>
                <tr>
                    <td colspan="<?php echo $group_id ? 6 : 7; ?>" style="text-align: center;">Không có dữ liệu công nợ</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($debt_data as $debt): ?>
                    <tr>
                        <td class="center"><?php echo $debt['date']; ?></td>
                        <td class="center">
                            <div class="member-name-with-avatar">
                                <?php echo get_avatar($debt['payer_id'], 24, '', '', array('class' => 'user-avatar-small')); ?>
                                <?php echo $debt['payer']; ?>
                            </div>
                        </td>
                        <td class="center">
                            <div class="member-name-with-avatar">
                                <?php echo get_avatar($debt['debtor_id'], 24, '', '', array('class' => 'user-avatar-small')); ?>
                                <?php echo $debt['debtor']; ?>
                            </div>
                        </td>
                        <td><?php echo $debt['order_title']; ?></td>
                        <?php if (!$group_id): ?>
                        <td>
                            <a href="?u=<?php echo $user_id; ?>&g=<?php echo $debt['group_id']; ?>">
                                <?php echo $debt['group_name']; ?>
                            </a>
                        </td>
                        <?php endif; ?>
                        <td class="<?php echo $debt['amount'] < 0 ? 'negative' : 'positive'; ?>">
                            <?php echo number_format(abs($debt['amount'])); ?> đ
                            <?php echo $debt['amount'] < 0 ? '(+)' : '(-)'; ?>
                        </td>
                        <td><?php echo $debt['status']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="<?php echo $group_id ? 4 : 5; ?>" style="text-align: right;"><strong>Tổng cộng:</strong></td>
                    <td colspan="2" class="<?php echo $total_debt < 0 ? 'negative' : 'positive'; ?>">
                        <strong>
                            <?php echo number_format(abs($total_debt)); ?> đ
                            <?php echo $total_debt < 0 ? '(+)' : '(-)'; ?>
                        </strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php if ($group_id): ?>
    <a class='debt-details-button' href="<?php echo get_permalink($group_id); ?>">Quay lại nhóm</a>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
