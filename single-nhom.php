<?php

get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="container"><p>Bạn cần đăng nhập để xem thông tin nhóm.</p></div>';
    get_footer();
    exit;
}

// Handle group disbanding if requested
if (isset($_GET['disband_group']) && wp_verify_nonce($_GET['_wpnonce'], 'disband_group')) {
    $group_id = get_the_ID();
    $current_user_id = get_current_user_id();
    $group_owner_id = get_field('chu_quy', $group_id);
    
    // Only the group owner can disband the group
    if ($group_owner_id == $current_user_id) {
        // Check if there are any unpaid orders in the group
        $has_unpaid_orders = false;
        
        $args = [
            'post_type' => 'don_hang',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'nhom',
                    'value' => $group_id,
                    'compare' => '=',
                ],
                [
                    'key' => 'trang_thai',
                    'value' => 'Đơn mới',
                    'compare' => '=',
                ]
            ],
        ];
        
        $order_query = new WP_Query($args);
        $has_unpaid_orders = $order_query->have_posts();
        
        if ($has_unpaid_orders) {
            // Cannot disband group with unpaid orders
            $disband_error = 'Không thể giải tán nhóm khi còn đơn hàng chưa thanh toán.';
        } else {
            // Delete the group
            wp_delete_post($group_id, false);
            
            // Redirect to groups list
            wp_redirect(home_url('/danh-sach-nhom/'));
            exit;
        }
    } else {
        $disband_error = 'Chỉ chủ nhóm mới có quyền giải tán nhóm.';
    }
}

// Handle member removal if requested
if (isset($_GET['remove_member']) && wp_verify_nonce($_GET['_wpnonce'], 'remove_member')) {
    $member_id = intval($_GET['remove_member']);
    $group_id = get_the_ID();
    $current_user_id = get_current_user_id();
    $group_owner_id = get_field('chu_quy', $group_id);
    
    // Only admins and group owners can remove members
    if (current_user_can('administrator') || $group_owner_id == $current_user_id) {
        // Get current members
        $members = get_field('danh_sach_thanh_vien', $group_id);
        
        if (!empty($members)) {
            $updated_members = array();
            
            // Keep all members except the one being removed
            foreach ($members as $member) {
                if ($member['thanh_vien'] != $member_id) {
                    $updated_members[] = $member;
                }
            }
            
            // Update the field with new array
            update_field('danh_sach_thanh_vien', $updated_members, $group_id);
            
            // Set success message
            $removed_user = get_user_by('id', $member_id);
            $remove_message = 'Đã xóa thành viên ' . $removed_user->display_name . ' khỏi nhóm.';
        }
    }
}

// Handle setting new group owner if requested
if (isset($_GET['set_owner']) && wp_verify_nonce($_GET['_wpnonce'], 'set_owner')) {
    $new_owner_id = intval($_GET['set_owner']);
    $group_id = get_the_ID();
    $current_user_id = get_current_user_id();
    $current_owner_id = get_field('chu_quy', $group_id);
    
    // Only admins and current group owners can change ownership
    if (current_user_can('administrator') || $current_owner_id == $current_user_id) {
        // Make sure the user is a member of the group
        $members = get_field('danh_sach_thanh_vien', $group_id);
        $is_member = false;
        
        if (!empty($members)) {
            foreach ($members as $member) {
                if ($member['thanh_vien'] == $new_owner_id) {
                    $is_member = true;
                    break;
                }
            }
        }
        
        if ($is_member) {
            // Update the group owner field
            update_field('field_63bd0d21f7280', $new_owner_id, $group_id);
            
            // Set success message
            $new_owner = get_user_by('id', $new_owner_id);
            $owner_message = 'Đã đặt ' . $new_owner->display_name . ' làm chủ nhóm mới.';
        }
    }
}

if (have_posts()) {
    while (have_posts()) {
        the_post();

        $group = get_the_ID();
        $current_user_id = get_current_user_id();
        $group_owner_id = get_field('chu_quy', $group);
        $group_owner = get_user_by('id', $group_owner_id);
        
        // Check if user has access to this group:
        // - Admin can access all groups
        // - Group owner can access their group
        // - Members listed in danh_sach_thanh_vien can access the group
        $has_access = current_user_can('administrator') || $group_owner_id == $current_user_id;
        
        if (!$has_access) {
            $list_user = get_field('danh_sach_thanh_vien');
            
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
            echo '<div class="container"><p>Bạn không có quyền xem thông tin nhóm này vì bạn không phải là thành viên.</p></div>';
            get_footer();
            exit;
        }

        // Check if user is admin or group owner
        $is_manager = (current_user_can('administrator') || $current_user_id == $group_owner_id);
        $i = 0;
        $list_user = get_field('danh_sach_thanh_vien');
        $data = "";
        
        // Calculate debts similar to group_debt_check.php
        $phi_dich_vu = (int) get_field('service_tax', $group);
        $payment = array();
        
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
                        $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu));
                        
                        if (have_rows('danh_sach_nguoi_su_dung')) {
                            while (have_rows('danh_sach_nguoi_su_dung')) {
                                the_row();
                                
                                $user = get_sub_field('user');
                                $pay = get_sub_field('pay');
                                
                                // Fix: Check if $nguoi_thanh_toan is not null and has ID property before accessing it
                                if (!$pay && isset($nguoi_thanh_toan) && isset($nguoi_thanh_toan['ID']) && $nguoi_thanh_toan['ID'] != $user) {
                                    if (!isset($payment[$nguoi_thanh_toan['ID']][$user])) {
                                        $payment[$nguoi_thanh_toan['ID']][$user] = 0;
                                    }
                                    $payment[$nguoi_thanh_toan['ID']][$user] += $chiphi_motnguoi;
                                }
                            }
                        }
                    }
                    wp_reset_postdata();
                }
            }
            wp_reset_postdata();
        }
        
        // Calculate debt totals for each user
        $user_debts = array();
        
        foreach ($payment as $payment_user_id => $debts) {
            foreach ($debts as $debt_user_id => $value) {
                if (!isset($user_debts[$debt_user_id])) {
                    $user_debts[$debt_user_id] = 0;
                }
                $user_debts[$debt_user_id] += $value;
            }
        }
        
        // Generate user list HTML
        if (!empty($list_user)) {
            foreach ($list_user as $user) {
                $i++;
                $user_obj = get_user_by('id', $user['thanh_vien']);
                $amount = (isset($user['amount']) && is_numeric($user['amount'])) ? $user['amount'] : 0;
                
                // Add debt amount if exists
                $debt_amount = isset($user_debts[$user['thanh_vien']]) ? $user_debts[$user['thanh_vien']] : 0;
                
                $data .= "<tr>";
                $data .= "<td class='center'>" . $i . "</td>";
                
                // Link to author page with debt detail link for admins
                $data .= "<td>";
                $data .= "<div class='member-name-with-avatar'>";
                $data .= get_avatar($user_obj->ID, 24, '', '', array('class' => 'user-avatar-small'));
                $data .= "<a href='" . get_author_posts_url($user_obj->ID) . "'>" . $user_obj->display_name . "</a>";
                
                // Add debt detail link for administrators
                if (current_user_can('administrator')) {
                    $data .= " - <a href='" . home_url("/chi-tiet-cong-no/?u=" . $user_obj->ID) . "'><i><small>Công nợ</small></i></a>";
                }
                
                $data .= "</div>";
                $data .= "</td>";
                $data .= "<td>" . number_format($debt_amount) . " đ</td>";
                
                // Action column
                if ($user_obj->ID == $group_owner_id) {
                    // For the group owner, show a label instead of action buttons
                    $data .= "<td class='center'><span class='owner-label'>Chủ nhóm</span></td>";
                } else if ($is_manager) {
                    // For other members, add remove and set owner buttons for admins and group owners
                    $data .= "<td class='center'>";
                    
                    // Remove member button
                    $remove_url = add_query_arg(
                        array(
                            'remove_member' => $user_obj->ID,
                            '_wpnonce' => wp_create_nonce('remove_member')
                        ),
                        get_permalink()
                    );
                    $data .= "<a href='" . esc_url($remove_url) . "' class='remove-member-btn' onclick='return confirm(\"Bạn có chắc muốn xóa thành viên này?\");'>Xóa thành viên</a>";
                    
                    // Set as owner button
                    $set_owner_url = add_query_arg(
                        array(
                            'set_owner' => $user_obj->ID,
                            '_wpnonce' => wp_create_nonce('set_owner')
                        ),
                        get_permalink()
                    );
                    $data .= " <a href='" . esc_url($set_owner_url) . "' class='set-owner-btn' onclick='return confirm(\"Bạn có chắc muốn đặt " . esc_attr($user_obj->display_name) . " làm chủ nhóm?\");'>Đặt làm chủ nhóm</a>";
                    
                    $data .= "</td>";
                } else {
                    $data .= "<td></td>";
                }
                
                $data .= "</tr>";
            }
        }

        // Get 3 most recent orders for this group
        if ($is_manager) {
            // For admin and group owner, show 3 most recent orders of the group
            $recent_orders_args = [
                'post_type' => 'don_hang',
                'posts_per_page' => 3,
                'meta_query' => [
                    [
                        'key' => 'nhom',
                        'value' => $group,
                        'compare' => '=',
                    ]
                ],
                'orderby' => 'date',
                'order' => 'DESC',
            ];
            
            $recent_orders_query = new WP_Query($recent_orders_args);
            $has_recent_orders = $recent_orders_query->have_posts();
        } else {
            // For regular members, only show orders they're involved in
            // First get all orders for this group
            $user_orders = [];
            $all_orders_args = [
                'post_type' => 'don_hang',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'nhom',
                        'value' => $group,
                        'compare' => '=',
                    ]
                ],
                'orderby' => 'date',
                'order' => 'DESC',
            ];
            
            $all_orders_query = new WP_Query($all_orders_args);
            
            // Filter to only include orders where the current user is involved
            if ($all_orders_query->have_posts()) {
                while ($all_orders_query->have_posts()) {
                    $all_orders_query->the_post();
                    $order_id = get_the_ID();
                    $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
                    
                    // Check if user is the payer
                    $show_order = ($nguoi_thanh_toan && isset($nguoi_thanh_toan['ID']) && $nguoi_thanh_toan['ID'] == $current_user_id);
                    
                    if (!$show_order) {
                        // Check if user is a participant in any order item
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
                        $user_orders[] = $order_id;
                        
                        // Only collect the 3 most recent orders
                        if (count($user_orders) >= 3) {
                            break;
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            // Create a custom query with just these order IDs
            if (!empty($user_orders)) {
                $recent_orders_args = [
                    'post_type' => 'don_hang',
                    'post__in' => $user_orders,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'posts_per_page' => 3,
                ];
                
                $recent_orders_query = new WP_Query($recent_orders_args);
                $has_recent_orders = $recent_orders_query->have_posts();
            } else {
                $has_recent_orders = false;
            }
        }
?>

<div class="container">
    <?php if (isset($remove_message)): ?>
        <div class="alert alert-success">
            <?php echo esc_html($remove_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($owner_message)): ?>
        <div class="alert alert-success">
            <?php echo esc_html($owner_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($disband_error)): ?>
        <div class="alert alert-error">
            <?php echo esc_html($disband_error); ?>
        </div>
    <?php endif; ?>

    <div class="action-buttons">
        <a class="mui-btn" href="javascript:history.back()">« Quay lại</a>
    </div>

    <h2><?php echo get_the_title(); ?></h2>
    
    <?php if ($has_recent_orders): ?>
    <!-- Recent Orders Section -->
    <div class="recent-orders-section">
        <div class="section-header">
            <h3><?php echo $is_manager ? 'Đơn hàng gần đây' : 'Đơn hàng gần đây của bạn'; ?></h3>
            <a href="<?php echo get_bloginfo('url') . '/lich-su-hoat-dong/?g=' . $group; ?>" class="section-link">Xem tất cả</a>
        </div>
        
        <div class="order-boxes">
            <?php 
            while ($recent_orders_query->have_posts()) {
                $recent_orders_query->the_post();
                $order_id = get_the_ID();
                $order_date = get_field('ngay_thang', $order_id);
                $order_status = get_field('trang_thai', $order_id);
                $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
                
                // Calculate total cost and get members
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
                
                // Check if current user is the payer
                $user_is_payer = ($nguoi_thanh_toan && isset($nguoi_thanh_toan['ID']) && $nguoi_thanh_toan['ID'] == $current_user_id);
                
                echo '<div class="order-box ' . $box_class . '">';
                echo '<a href="' . get_permalink($order_id) . '">';
                echo '<div class="order-date">' . $order_date . '</div>';
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
            wp_reset_postdata();
            ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($is_manager): ?>
    <div class="action-buttons">
        <a class='debt-details-button' href="<?php echo get_bloginfo('url') . '/tao-order-moi/?g=' . get_the_ID(); ?>">Thêm order</a>
    </div>
    <?php endif; ?>
    
    <div class="col-12 box mb-20" id="listuser">
        <span>Chủ nhóm: <b><?php echo $group_owner->display_name; ?></b></span>
        <table class="debt-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tên thành viên</th>
                    <th>Số tiền nợ</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_user)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Không có dữ liệu thành viên</td>
                </tr>
                <?php else: ?>
                    <?php echo $data; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($is_manager): ?>
    <div class="action-buttons">
        <a class='debt-details-button' href="<?php echo get_bloginfo('url') . '/them-thanh-vien-moi/?g=' . $group; ?>">Tạo tài khoản thành viên</a>
        <a class='debt-details-button' href="<?php echo get_bloginfo('url') . '/them-thanh-vien-tu-danh-sach/?g=' . $group; ?>">Thêm thành viên</a>
        <a class='debt-details-button' href="<?php echo get_bloginfo('url') . '/kiem-tra-cong-no-nhom/?g=' . $group; ?>">Kiểm tra công nợ</a>
        <?php if ($current_user_id == $group_owner_id): ?>
        <?php 
            // Create a URL with nonce for group disbanding
            $disband_url = add_query_arg(
                array(
                    'disband_group' => 1,
                    '_wpnonce' => wp_create_nonce('disband_group')
                ),
                get_permalink()
            );
        ?>
        <a class='disband-group-btn' href="<?php echo esc_url($disband_url); ?>" onclick="return confirm('Bạn có chắc chắn muốn giải tán nhóm này? Hành động này sẽ xóa nhóm vĩnh viễn và không thể hoàn tác.');">Giải tán nhóm</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Add debt detail button for all members -->
    <?php 
    // Check if current user is a member of this group
    $is_member = false;
    $current_user_obj = get_user_by('id', $current_user_id);

    if (!empty($list_user)) {
        foreach ($list_user as $user) {
            if ($user['thanh_vien'] == $current_user_id) {
                $is_member = true;
                break;
            }
        }
    }

    // If current user is a member, show their debt detail button
    if ($is_member): 
    ?>
    <div class="member-debt-buttons">
        <h3>Thao tác cá nhân</h3>
        <div class="action-buttons">
            <a class='debt-details-button' href="<?php echo home_url("/chi-tiet-cong-no/?u=" . $current_user_id); ?>">
                Xem công nợ của bạn
            </a>
            <a class='debt-details-button' href="<?php echo get_bloginfo('url') . '/lich-su-hoat-dong/?g=' . $group; ?>">Lịch sử hoạt động</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php 
    }
}
get_footer();