<?php 
/* 
 Template Name: Trang chủ
*/
get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;
$is_admin = current_user_can('administrator');

// Initialize debt data array - similar to author.php approach
$debt_data = array();
$total_debt = 0;
$total_owed_to_me = 0;
$total_i_owe = 0;

// ========================================
// SECTION 1: USER GROUPS (from list_group.php)
// ========================================
$groups_args = [
    'post_type' => 'nhom',
    'posts_per_page' => -1,
];

$user_groups = [];
$groups_count = 0;

$group_query = new WP_Query($groups_args);

if ($group_query->have_posts()) {
    while ($group_query->have_posts()) {
        $group_query->the_post();
        $group_id = get_the_ID();
        $group_owner_id = get_field('chu_quy', $group_id);
        $list_user = get_field('danh_sach_thanh_vien', $group_id);
        $member_count = !empty($list_user) ? count($list_user) : 0;
        
        // Determine if user should see this group (copied from list_group.php)
        $show_group = $is_admin || $group_owner_id == $current_user_id;
        
        if (!$show_group && !empty($list_user)) {
            foreach ($list_user as $user) {
                if ($user['thanh_vien'] == $current_user_id) {
                    $show_group = true;
                    break;
                }
            }
        }
        
        if ($show_group) {
            $groups_count++;
            
            // Count orders for this group (similar to single-nhom.php)
            $group_orders_args = [
                'post_type' => 'don_hang',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'nhom',
                        'value' => $group_id,
                        'compare' => '=',
                    ]
                ],
            ];
            
            $group_orders_query = new WP_Query($group_orders_args);
            $orders_count = $group_orders_query->found_posts;
            
            // Only store first 3 groups for display
            if (count($user_groups) < 3) {
                $user_groups[] = [
                    'id' => $group_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'member_count' => $member_count,
                    'orders_count' => $orders_count,
                    'is_owner' => ($group_owner_id == $current_user_id)
                ];
            }
        }
    }
    wp_reset_postdata();
}

// ========================================
// SECTION 2: ORDERS (from group_history.php)
// ========================================
// Get all orders the user is involved in
$user_orders = [];
$orders_count = 0;

$orders_args = [
    'post_type' => 'don_hang',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
];

$orders_query = new WP_Query($orders_args);

if ($orders_query->have_posts()) {
    while ($orders_query->have_posts()) {
        $orders_query->the_post();
        $order_id = get_the_ID();
        $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
        
        // Check if user is the payer (as in group_history.php)
        $show_order = $is_admin || ($nguoi_thanh_toan && $nguoi_thanh_toan['ID'] == $current_user_id);
        
        if (!$show_order) {
            // Check if user is a participant (as in group_history.php)
            $detail_args = [
                'post_type' => 'chi_tiet_don_hang',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'don_hang',
                        'value' => $order_id,
                        'compare' => '=',
                    ]
                ],
            ];
            
            $detail_query = new WP_Query($detail_args);
            
            if ($detail_query->have_posts()) {
                while ($detail_query->have_posts()) {
                    $detail_query->the_post();
                    
                    if (have_rows('danh_sach_nguoi_su_dung')) {
                        while (have_rows('danh_sach_nguoi_su_dung')) {
                            the_row();
                            $user = get_sub_field('user');
                            
                            if ($user == $current_user_id) {
                                $show_order = true;
                                break 2; // Break both loops
                            }
                        }
                    }
                }
                wp_reset_postdata();
            }
        }
        
        if ($show_order) {
            $orders_count++;
            
            // Only collect the 3 most recent orders
            if (count($user_orders) < 3) {
                $user_orders[] = $order_id;
            }
        }
    }
    wp_reset_postdata();
}

// ========================================
// SECTION 3: DEBT CALCULATION (from author.php and detail_debt.php)
// ========================================
// Set up query for orders with completed payment status
$debt_orders_args = [
    'post_type' => 'don_hang',
    'posts_per_page' => 999,
    'meta_query' => [
        [
            'key' => 'trang_thai',
            'value' => "Thanh toán xong",
            'compare' => '=',
        ]
    ],
    'meta_key' => 'ngay_thang',
    'orderby' => 'meta_value',
    'order' => 'DESC'
];

$debt_query = new WP_Query($debt_orders_args);

if ($debt_query->have_posts()) {
    while ($debt_query->have_posts()) {
        $debt_query->the_post();
        
        $order_id = get_the_ID();
        $nguoi_thanh_toan = get_field('nguoi_thanh_toan');
        $group = get_field('nhom');
        $phi_dich_vu = $group ? (int) get_field('service_tax', $group) : 0;

        // Query for order details with unpaid status - copied from detail_debt.php
        $detail_args = [
            'post_type' => 'chi_tiet_don_hang',
            'posts_per_page' => 999,
            'meta_query' => [
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
            ]
        ];

        $detail_query = new WP_Query($detail_args);
        
        if ($detail_query->have_posts()) {
            while ($detail_query->have_posts()) {
                $detail_query->the_post();
                
                $so_tien = (int) get_field('so_tien');
                $users = get_field('danh_sach_nguoi_su_dung');
                
                if (!$users || empty($users)) continue;

                $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu / 100));

                if ($users && !empty($users)) {
                    foreach ($users as $u) {
                        
                        $user = $u['user'];
                        $pay = $u['pay'] ?? false;

                        // I owe money to payer (same as in author.php)
                        if (!$pay && $user == $current_user_id && $nguoi_thanh_toan['ID'] != $current_user_id) {
                            if (!isset($debt_data[$nguoi_thanh_toan['ID']])) {
                                $debt_data[$nguoi_thanh_toan['ID']] = [
                                    'display_name' => $nguoi_thanh_toan['display_name'],
                                    'amount' => 0
                                ];
                            }

                            $debt_data[$nguoi_thanh_toan['ID']]['amount'] += $chiphi_motnguoi;
                            $total_debt += $chiphi_motnguoi;
                            $total_i_owe += $chiphi_motnguoi;
                        }
                        
                        // Others owe money to me (same as in author.php)
                        if (!$pay && $nguoi_thanh_toan['ID'] == $current_user_id && $user != $current_user_id) {
                            $debtor = get_user_by('id', $user);
                            if ($debtor) {
                                if (!isset($debt_data[$user])) {
                                    $debt_data[$user] = [
                                        'display_name' => $debtor->display_name,
                                        'amount' => 0
                                    ];
                                }
                                
                                $debt_data[$user]['amount'] -= $chiphi_motnguoi;
                                $total_debt -= $chiphi_motnguoi;
                                $total_owed_to_me += $chiphi_motnguoi;
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

// Sort debt by amount (highest first) - same as in home.php
if (!empty($debt_data)) {
    usort($debt_data, function($a, $b) {
        return abs($b['amount']) - abs($a['amount']);
    });
}
?>

<div class="container">
    <div class="home-welcome">
        <h1>
            <span class="welcome-avatar">
                <?php echo get_avatar($current_user_id, 32, '', '', array('class' => 'user-avatar-small')); ?>
            </span>
            Xin chào, <?php echo $current_user->display_name; ?>
        </h1>
        <p class="home-date"><?php echo date_i18n('l, j F Y'); ?></p>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-icon">
                <i class="dashicons dashicons-groups"></i>
            </div>
            <div class="summary-card-content">
                <div class="summary-card-value"><?php echo $groups_count; ?></div>
                <div class="summary-card-label">Nhóm của bạn</div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card-icon">
                <i class="dashicons dashicons-cart"></i>
            </div>
            <div class="summary-card-content">
                <div class="summary-card-value"><?php echo $orders_count; ?></div>
                <div class="summary-card-label">Đơn hàng</div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card-icon">
                <i class="dashicons dashicons-money-alt"></i>
            </div>
            <div class="summary-card-content">
                <div class="summary-card-value positive">
                    <?php echo number_format($total_i_owe); ?> đ
                </div>
                <div class="summary-card-label">Tôi nợ người khác</div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card-icon">
                <i class="dashicons dashicons-money-alt"></i>
            </div>
            <div class="summary-card-content">
                <div class="summary-card-value negative">
                    <?php echo number_format($total_owed_to_me); ?> đ
                </div>
                <div class="summary-card-label">Người khác nợ tôi</div>
            </div>
        </div>
    </div>
    
    <div class="home-sections">
        <!-- User's Groups Section - Based on list_group.php -->
        <div class="home-section">
            <div class="section-header">
                <h2>Nhóm của bạn</h2>
                <a href="<?php echo home_url('/danh-sach-nhom'); ?>" class="section-link">Xem tất cả</a>
            </div>
            
            <?php if (!empty($user_groups)): ?>
            <div class="group-boxes">
                <?php foreach ($user_groups as $group): ?>
                <div class="group-box">
                    <a href="<?php echo $group['permalink']; ?>">
                        <div class="group-name"><?php echo $group['title']; ?></div>
                        <div class="group-stats">
                            <span class="member-count"><?php echo $group['member_count']; ?> thành viên</span>
                            <span class="order-count"><?php echo $group['orders_count']; ?> đơn hàng</span>
                        </div>
                        <?php if ($group['is_owner']): ?>
                        <div class="owner-badge">Quản lý</div>
                        <?php endif; ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                Bạn chưa tham gia nhóm nào. Liên hệ quản trị viên để được thêm vào nhóm.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Orders Section - Based on group_history.php -->
        <div class="home-section">
            <div class="section-header">
                <h2>Đơn hàng gần đây</h2>
                <a href="<?php echo home_url('/don-hang'); ?>" class="section-link">Xem tất cả</a>
            </div>
            
            <?php if (!empty($user_orders)): ?>
            <div class="order-boxes">
                <?php
                // Display the orders using the format from group_history.php
                foreach ($user_orders as $order_id) {
                    $order_date = get_field('ngay_thang', $order_id);
                    $order_status = get_field('trang_thai', $order_id);
                    $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
                    $group = get_field('nhom', $order_id);
                    $group_name = $group ? get_the_title($group) : 'Không có nhóm';
                    
                    // Calculate total cost and members
                    $total_cost = 0;
                    $members = [];
                    
                    $detail_args = [
                        'post_type' => 'chi_tiet_don_hang',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'don_hang',
                                'value' => $order_id,
                                'compare' => '=',
                            ]
                        ],
                    ];
                    
                    $detail_query = new WP_Query($detail_args);
                    
                    if ($detail_query->have_posts()) {
                        while ($detail_query->have_posts()) {
                            $detail_query->the_post();
                            $total_cost += (int)get_field('so_tien');
                            $users = get_field('danh_sach_nguoi_su_dung');
                            
                            // Get users from this order item
                            if ($users && !empty($users)) {
                                foreach ($users as $user) {
                                    
                                    $userid = $user['user'];
                                    $user_obj = get_user_by('id', $userid);
                                    if ($user_obj && !in_array($user_obj->display_name, $members)) {
                                        $members[] = $user_obj->display_name;
                                    }
                                }
                            }
                        }
                        wp_reset_postdata();
                    }
                    
                    // Determine CSS class based on order status
                    $box_class = '';
                    if ($order_status == "Đơn mới" || $order_status == "Chưa thanh toán") {
                        $box_class = 'order-box-new';
                    } elseif ($order_status == "Thanh toán xong") {
                        $box_class = 'order-box-completed';
                    }
                    
                    // Check if current user is the payer
                    $user_is_payer = ($nguoi_thanh_toan && isset($nguoi_thanh_toan['ID']) && $nguoi_thanh_toan['ID'] == $current_user_id);
                    
                    echo '<div class="order-box ' . $box_class . '">';
                    echo '<a href="' . get_permalink($order_id) . '">';
                    echo '<div class="order-date">' . $order_date . '</div>';
                    echo '<div class="order-group"><strong>Nhóm:</strong> ' . $group_name . '</div>';
                    echo '<div class="order-cost">' . number_format($total_cost) . ' đ</div>';
                    echo '<div class="order-status">' . $order_status;
                    if ($user_is_payer) {
                        echo ' <span class="payer-badge">Người thanh toán</span>';
                    }
                    echo '</div>';
                    echo '<div class="order-members"><strong>Thành viên:</strong> ' . (empty($members) ? 'Không có thành viên' : implode(', ', $members)) . '</div>';
                    echo '</a>';
                    echo '</div>';
                }
                ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                Không có đơn hàng gần đây.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Debt Summary Section - Based on detail_debt.php logic -->
        <div class="home-section">
            <div class="section-header">
                <h2>Tóm tắt công nợ</h2>
                <a href="<?php echo get_author_posts_url($current_user_id); ?>" class="section-link">Xem chi tiết</a>
            </div>
            
            <?php if (!empty($debt_data)): ?>
            <div class="debt-summary">
                <table class="debt-table">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($debt_data, 0, 5) as $user_id => $data): ?>
                        <tr>
                            <td>
                                <div class="member-name-with-avatar">
                                    <?php echo get_avatar($user_id, 24, '', '', array('class' => 'user-avatar-small')); ?>
                                    <a href="<?php echo get_author_posts_url($user_id); ?>">
                                        <?php echo $data['display_name']; ?>
                                    </a>
                                </div>
                            </td>
                            <td class="<?php echo $data['amount'] < 0 ? 'negative' : 'positive'; ?>">
                                <?php echo number_format(abs($data['amount'])); ?> đ
                            </td>
                            <td>
                                <?php if ($data['amount'] < 0): ?>
                                <span class="debt-status debt-status-positive">Nợ bạn</span>
                                <?php else: ?>
                                <span class="debt-status debt-status-negative">Bạn nợ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                Không có dữ liệu công nợ.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
