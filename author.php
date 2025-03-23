<?php
get_header();
$this_user = get_queried_object(); 
$current_user_id = $this_user->ID;
$logged_in_user_id = get_current_user_id();
$is_own_profile = ($current_user_id == $logged_in_user_id);

// Initialize debt data array
$debt_data = array();
$total_debt = 0;

// Set up query for orders
$orders_args = [
    'post_type' => 'don_hang',
    'posts_per_page' => 999,
    'meta_query'    => array(
        array(
            'key'       => 'trang_thai',
            'value'     => "Thanh toán xong",
            'compare'   => '=',
        )
    ),
    'meta_key' => 'ngay_thang',
    'orderby' => 'meta_value',
    'order' => 'DESC'
];

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
                
                $so_tien = (int) get_field('so_tien');
                $users = get_field('danh_sach_nguoi_su_dung');
                
                if (!$users || empty($users)) continue;
                
                $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu / 100));
                
                if (have_rows('danh_sach_nguoi_su_dung')) {
                    while (have_rows('danh_sach_nguoi_su_dung')) {
                        the_row();
                        
                        $user = get_sub_field('user');
                        $pay = get_sub_field('pay');
                        
                        // Là người nợ tiền (người sử dụng chưa thanh toán và không phải người thanh toán)
                        if (!$pay && $user == $current_user_id && $nguoi_thanh_toan['ID'] != $current_user_id) {
                            // Initialize the array elements if they don't exist
                            if (!isset($debt_data[$nguoi_thanh_toan['ID']])) {
                                $debt_data[$nguoi_thanh_toan['ID']] = [
                                    'display_name' => $nguoi_thanh_toan['display_name'],
                                    'amount' => 0
                                ];
                            }
                            
                            $debt_data[$nguoi_thanh_toan['ID']]['amount'] += $chiphi_motnguoi;
                            $total_debt += $chiphi_motnguoi;
                        }
                        
                        // Là người thanh toán (người khác nợ mình)
                        if (!$pay && $nguoi_thanh_toan['ID'] == $current_user_id && $user != $current_user_id) {
                            $debtor = get_user_by('id', $user);
                            if ($debtor) {
                                // Initialize the array elements if they don't exist
                                if (!isset($debt_data[$user])) {
                                    $debt_data[$user] = [
                                        'display_name' => $debtor->display_name,
                                        'amount' => 0
                                    ];
                                }
                                
                                $debt_data[$user]['amount'] -= $chiphi_motnguoi;
                                $total_debt -= $chiphi_motnguoi;
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
?>

<div class="container">
    <div class="author-profile">
        <div class="author-avatar">
            <?php echo get_avatar($current_user_id, 120); ?>
        </div>
        
        <h2 class="author-name"><?php echo $this_user->display_name; ?></h2>
        
        <?php if ($is_own_profile): ?>
        <div class="author-actions">
            <a href="<?php echo home_url("/chinh-sua-thong-tin"); ?>" class="debt-details-button">Chỉnh sửa thông tin</a>
            <a href="<?php echo home_url("/thay-doi-avatar"); ?>" class="debt-details-button">Thay đổi avatar</a>
            <a href="<?php echo home_url("/huong-dan-su-dung"); ?>" class="debt-details-button">Hướng dẫn sử dụng</a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="debt-details-button">Đăng xuất</a>
        </div>
        <?php endif; ?>
        
        <div class="author-debt">
            <h3>Chi tiết công nợ</h3>
            
            <table class="debt-table">
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($debt_data)): ?>
                    <tr>
                        <td colspan="2" class="center">Không có dữ liệu công nợ</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($debt_data as $user_id => $data): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_bloginfo('url'); ?>/chi-tiet-cong-no/?u=<?php echo $current_user_id; ?>&p=<?php echo $user_id; ?>">
                                    <?php echo $data['display_name']; ?>
                                </a>
                            </td>
                            <td class="<?php echo $data['amount'] < 0 ? 'negative' : 'positive'; ?>">
                                <?php echo number_format(abs($data['amount'])); ?> đ
                                <?php echo $data['amount'] < 0 ? '(+)' : '(-)'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td style="text-align: right;"><strong>Tổng cộng:</strong></td>
                        <td class="<?php echo $total_debt < 0 ? 'negative' : 'positive'; ?>">
                            <strong>
                                <?php echo number_format(abs($total_debt)); ?> đ
                                <?php echo $total_debt < 0 ? '(+)' : '(-)'; ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php get_footer(); ?>