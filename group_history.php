<?php 
/* 
 Template Name: Lịch sử hoạt động nhóm
*/
get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="container"><p>Bạn cần đăng nhập để xem lịch sử hoạt động.</p></div>';
    get_footer();
    exit;
}

// Get group ID from URL parameter
$group_id = isset($_GET['g']) ? intval($_GET['g']) : 0;

if (!$group_id) {
    echo '<div class="container"><p>Không tìm thấy nhóm.</p></div>';
    get_footer();
    exit;
}

$current_user_id = get_current_user_id();
$group_owner_id = get_field('chu_quy', $group_id);
$is_admin = current_user_can('administrator');
$is_group_owner = ($group_owner_id == $current_user_id);

// Check if user has access to this group
$has_access = $is_admin || $is_group_owner;

if (!$has_access) {
    $list_user = get_field('danh_sach_thanh_vien', $group_id);
    
    if (!empty($list_user)) {
        foreach ($list_user as $user) {
            if ($user['thanh_vien'] == $current_user_id) {
                $has_access = true;
                break;
            }
        }
    }
}

// If no access, show error and exit
if (!$has_access) {
    echo '<div class="container"><p>Bạn không có quyền xem lịch sử hoạt động của nhóm này vì bạn không phải là thành viên.</p></div>';
    get_footer();
    exit;
}

// Get group information
$group_title = get_the_title($group_id);
$group_owner = get_user_by('id', $group_owner_id);

// Query orders for this group
$args = [
    'post_type' => 'don_hang',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'nhom',
            'value' => $group_id,
            'compare' => '=',
        ]
    ],
    'orderby' => 'date',
    'order' => 'DESC',
];

$query = new WP_Query($args);
?>

<div class="container">
    <h2>Lịch sử hoạt động - <?php echo esc_html($group_title); ?></h2>
    
    <?php if ($group_owner): ?>
        <p>Chủ nhóm: <strong><?php echo esc_html($group_owner->display_name); ?></strong></p>
    <?php endif; ?>
    
    <div class="col-12 box mb-20" id="listuser">
        <div class="order-boxes">
            <?php 
            $found_orders = false;
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $order_id = get_the_ID();
                    $order_date = get_field('ngay_thang', $order_id);
                    $order_status = get_field('trang_thai', $order_id);
                    $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
                    
                    // Admins and group owners can see all orders
                    $show_order = $is_admin || $is_group_owner;
                    
                    // For regular members, check if they're part of the order
                    if (!$show_order) {
                        // Check if user is the one who paid
                        if ($nguoi_thanh_toan && isset($nguoi_thanh_toan['ID']) && $nguoi_thanh_toan['ID'] == $current_user_id) {
                            $show_order = true;
                        } else {
                            // Check if user is in any order item's danh_sach_nguoi_su_dung
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
                                    
                                    // Check if user is in danh_sach_nguoi_su_dung
                                    if (have_rows('danh_sach_nguoi_su_dung')) {
                                        while (have_rows('danh_sach_nguoi_su_dung')) {
                                            the_row();
                                            $user_id = get_sub_field('user');
                                            
                                            if ($user_id == $current_user_id) {
                                                $show_order = true;
                                                break 2; // Break both loops
                                            }
                                        }
                                    }
                                }
                                wp_reset_postdata();
                            }
                        }
                    }
                    
                    // Skip displaying this order if user doesn't have access
                    if (!$show_order) {
                        continue;
                    }
                    
                    $found_orders = true;
                    
                    // Calculate total cost and get members
                    $total_cost = 0;
                    $members = [];
                    
                    // Get order details to calculate total and get members
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
                            
                            // Get users from this order item
                            $users = get_field('danh_sach_nguoi_su_dung');
                            if ($users) {
                                foreach ($users as $user_item) {
                                    $user_id = $user_item['user'];
                                    $user_obj = get_user_by('id', $user_id);
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
                    if ($order_status == "Đơn mới") {
                        $box_class = 'order-box-new';
                    } elseif ($order_status == "Thanh toán xong") {
                        $box_class = 'order-box-completed';
                    }
                    
                    echo '<div class="order-box ' . $box_class . '">';
                    echo '<a href="' . get_permalink($order_id) . '">';
                    echo '<div class="order-date">' . $order_date . '</div>';
                    echo '<div class="order-cost">' . number_format($total_cost) . ' đ</div>';
                    echo '<div class="order-status">' . $order_status . '</div>';
                    echo '<div class="order-members">' . (empty($members) ? 'Không có thành viên' : implode(', ', $members)) . '</div>';
                    echo '</a>';
                    echo '</div>';
                }
                wp_reset_postdata();
            }
            
            if (!$found_orders) {
                echo '<div class="no-data">Không có đơn hàng nào cho bạn</div>';
            }
            ?>
        </div>
    </div>
    
    <div class="actions">
        <a class="debt-details-button" href="<?php echo get_permalink($group_id); ?>">Quay lại nhóm</a>
        <?php if (current_user_can('administrator')): ?>
            <a class="debt-details-button" href="<?php echo get_bloginfo('url') . '/tao-order-moi/?g=' . $group_id; ?>">Thêm đơn hàng</a>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
