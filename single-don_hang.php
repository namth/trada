<?php
get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="order-container center"><p>Bạn cần đăng nhập để xem đơn hàng.</p></div>';
    get_footer();
    exit;
}

// Initialize variables to prevent undefined warnings
$thongbao = '';
$data = '';
$thao_tac = '';
$trang_thai = '';
$nhom = 0;
$total = 0;

$current_user_id = get_current_user_id();
$order_id = get_the_ID();
$has_access = current_user_can('administrator');

// If not admin, check if user is part of the group
if (!$has_access && have_posts()) {
    // Get the group ID associated with this order
    $nhom = get_field('nhom', $order_id);
    
    if ($nhom) {
        // Get the list of users in this group
        $list_user = get_field('danh_sach_thanh_vien', $nhom);
        
        // Check if current user is a member of the group
        if (!empty($list_user)) {
            foreach ($list_user as $user) {
                if ($user['thanh_vien'] == $current_user_id) {
                    $has_access = true;
                    break;
                }
            }
        }
    }
}

// If no access, show error and exit
if (!$has_access) {
    echo '<div class="order-container center"><p>Bạn không có quyền xem đơn hàng này vì bạn không phải là thành viên của nhóm.</p></div>';
    get_footer();
    exit;
}

// Handle deletion if requested
if (isset($_GET['del']) && ($_GET['del']) && is_user_logged_in()) {
    $del_id = $_GET['del'];
    $thongbao = "Đã xoá " . get_the_title($del_id);
    wp_delete_post($del_id);
}
?>

<div class="order-container">
    <?php if (!empty($thongbao)): ?>
        <div class="alert alert-success">
            <?php echo esc_html($thongbao); ?>
        </div>
    <?php endif; ?>
    
    <div class="action-buttons">
        <?php 
        // Add back button to return to the group
        $nhom = get_field('nhom');
        if ($nhom): 
        ?>
            <a class="mui-btn" href="<?php echo get_permalink($nhom); ?>">« Quay lại nhóm</a>
        <?php else: ?>
            <a class="mui-btn" href="javascript:history.back()">« Quay lại</a>
        <?php endif; ?>
    </div>
    
    <div class="order-content">
        <?php 
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                
                // Get order details
                $order_id = get_the_ID();
                $tong_tien = get_field('tong_tien');
                $ngay_thang = get_field('ngay_thang');
                $nhom = get_field('nhom');
                $trang_thai = get_field('trang_thai');
                $nguoi_thanh_toan = get_field('nguoi_thanh_toan');
                
                // Get order title 
                $order_title = get_the_title();
        ?>
                <div class="order-header">
                    <h1><?php echo esc_html($order_title); ?></h1>
                </div>
                
                <div class="order-summary">
                    <div class="order-info-item">
                        <span class="order-info-label">Nhóm:</span>
                        <span class="order-info-value"><?php echo esc_html(get_the_title($nhom)); ?></span>
                    </div>
                    
                    <div class="order-info-item">
                        <span class="order-info-label">Ngày:</span>
                        <span class="order-info-value"><?php echo esc_html($ngay_thang); ?></span>
                    </div>
                    
                    <div class="order-info-item">
                        <span class="order-info-label">Trạng thái:</span>
                        <span class="order-info-value <?php echo $trang_thai === 'Đơn mới' ? 'order-status-new' : 'order-status-completed'; ?>">
                            <?php echo esc_html($trang_thai); ?>
                        </span>
                    </div>
                    
                    <?php if ($nguoi_thanh_toan): ?>
                    <div class="order-info-item">
                        <span class="order-info-label">Người thanh toán:</span>
                        <span class="order-info-value"><?php echo esc_html($nguoi_thanh_toan['display_name']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="order-details">
                    <h2>Chi tiết đơn hàng</h2>
                    
                    <?php
                    // Get order items
                    $args = array(
                        'post_type'     => 'chi_tiet_don_hang',
                        'posts_per_page' => 999,
                        'meta_query' => array(
                            array(
                                'key'     => 'don_hang',
                                'value'   => $order_id,
                                'compare' => '=',
                            ),
                        ),
                    );
                    
                    $query = new WP_Query($args);
                    $total = 0;
                    
                    if ($query->have_posts()): 
                    ?>
                        <div class="order-items-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tên sản phẩm</th>
                                        <th>Người sử dụng</th>
                                        <th>Giá tiền</th>
                                        <?php if ($trang_thai == "Đơn mới"): ?>
                                            <th>Thao tác</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    while ($query->have_posts()): 
                                        $query->the_post();
                                        
                                        $detail_id = get_the_ID();
                                        $product_id = get_field('san_pham_dich_vu');
                                        $so_tien = get_field('so_tien');
                                        
                                        // Get list of users
                                        $list_user = [];
                                        $users = get_field('danh_sach_nguoi_su_dung');
                                        
                                        if (!empty($users) && is_array($users)):
                                            foreach ($users as $user):
                                                if (isset($user['user'])):
                                                    $user_obj = get_user_by('id', $user['user']);
                                                    if ($user_obj):
                                                        $list_user[] = $user_obj->display_name;
                                                    endif;
                                                endif;
                                            endforeach;
                                        endif;
                                        
                                        $total += $so_tien;
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                            <td><?php echo esc_html(implode(', ', $list_user)); ?></td>
                                            <td><?php echo number_format($so_tien) . ' đ'; ?></td>
                                            <?php if ($trang_thai == "Đơn mới"): ?>
                                                <td>
                                                    <a href="?del=<?php echo esc_attr($detail_id); ?>" class="delete-item-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa?');">Xoá</a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="<?php echo $trang_thai == 'Đơn mới' ? '2' : '1'; ?>"><strong>Tổng cộng</strong></td>
                                        <td colspan="<?php echo $trang_thai == 'Đơn mới' ? '2' : '2'; ?>" class="total-amount"><?php echo number_format($total) . ' đ'; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php 
                    else: 
                    ?>
                        <div class="no-data">Chưa có chi tiết đơn hàng nào.</div>
                    <?php 
                    endif; 
                    wp_reset_postdata();
                    ?>
                </div>
                
                <div class="order-actions">
                    <?php if ($trang_thai == "Đơn mới"): ?>
                        <a class="debt-details-button" href="<?php echo esc_url(get_bloginfo('url') . '/them-chi-tiet-don-hang/?o=' . $order_id); ?>">Thêm đồ</a>
                        
                        <?php if (($current_user_id == get_field('chu_quy', $nhom)) || current_user_can('administrator')): ?>
                            <a class="debt-details-button" href="<?php echo esc_url(get_bloginfo('url') . '/chot-don-thanh-toan/?o=' . $order_id); ?>">Thanh toán</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($nhom): ?>
                    <div class="group-debt-link">
                        <a href="<?php echo esc_url(get_bloginfo('url') . '/chi-tiet-cong-no/?g=' . $nhom); ?>" class="debt-details-button">
                            Xem chi tiết công nợ của nhóm này
                        </a>
                    </div>
                <?php endif; ?>
        <?php
            }
        } else {
            echo '<div class="no-data">Không tìm thấy đơn hàng.</div>';
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>