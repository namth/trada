<?php
/**
 * Template Name: Xác nhận đã thanh toán
 * 
 * Template for confirming manual payments
 */

get_header();

// Process confirmation if requested
if (isset($_GET['confirm']) && isset($_GET['id'])) {
    $transaction_id = intval($_GET['id']);
    $confirm = ($_GET['confirm'] === 'yes');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'payment_transactions';
    
    // Get transaction details
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND status = 'pending'",
        $transaction_id
    ));
    
    if (!$transaction) {
        echo '<div class="container"><div class="alert error">Không tìm thấy giao dịch hoặc giao dịch đã được xử lý.</div></div>';
    } else {
        if ($confirm) {
            // Process the payment like in group_debt_check.php
            $payer_id = $transaction->payer_id;
            $user_id = $transaction->debtor_id;
            $detail_ids = explode(',', $transaction->detail_ids);
            $tongtienthanhtoan = 0;
            $group_id = 0;
            
            // Process each order detail
            foreach ($detail_ids as $detail_id) {
                $detail_id = intval($detail_id);
                $order_id = get_field('don_hang', $detail_id);
                
                if (!$order_id) continue;
                
                $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
                $so_tien = (int) get_field('so_tien', $detail_id);
                $users = get_field('danh_sach_nguoi_su_dung', $detail_id);
                $done_order = true;
                
                // Get group information if not already set
                if (!$group_id) {
                    $group_id = get_field('nhom', $order_id);
                }
                
                // Calculate user's share
                if (!empty($users)) {
                    $phi_dich_vu = $group_id ? (int) get_field('service_tax', $group_id) : 0;
                    $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu / 100));
                    
                    if (have_rows('danh_sach_nguoi_su_dung', $detail_id)) {
                        while (have_rows('danh_sach_nguoi_su_dung', $detail_id)) {
                            the_row();
                            
                            $row = get_row_index();
                            $user = get_sub_field('user');
                            $pay = get_sub_field('pay');
                            
                            if (!$pay) {
                                if (($nguoi_thanh_toan['ID'] == $payer_id) && ($user == $user_id)) {
                                    // Mark as paid
                                    update_sub_field('field_63f97e0b6cf3b', true);
                                    $tongtienthanhtoan += $chiphi_motnguoi;
                                } else {
                                    // User hasn't paid yet
                                    $done_order = false;
                                }
                            }
                        }
                    }
                }
                
                // Update order status if all users have paid
                if ($done_order) {
                    update_field('field_63f9e870f1437', "Đã thanh toán", $detail_id);
                }
            }
            
            // Update group member balances if needed
            if ($group_id && $tongtienthanhtoan > 0) {
                $group_owner_id = get_field('chu_quy', $group_id);
                
                if (have_rows('danh_sach_thanh_vien', $group_id)) {
                    while (have_rows('danh_sach_thanh_vien', $group_id)) {
                        the_row();
                        
                        $thanh_vien = get_sub_field('thanh_vien');
                        $amount = (int) get_sub_field('amount');
                        $row = get_row_index();
                        
                        // If this is the debtor, reduce their balance
                        if ($thanh_vien == $user_id) {
                            $row_update = array(
                                'thanh_vien' => $thanh_vien,
                                'amount' => $amount - $tongtienthanhtoan,
                            );
                            update_row('field_63bd0da3f7281', $row, $row_update, $group_id);
                        }
                        
                        // If payer is a member, increase their balance 
                        if (($payer_id != $group_owner_id) && ($thanh_vien == $payer_id)) {
                            $row_update = array(
                                'thanh_vien' => $payer_id,
                                'amount' => $amount + $tongtienthanhtoan,
                            );
                            update_row('field_63bd0da3f7281', $row, $row_update, $group_id);
                        }
                    }
                }
            }
            
            // Update transaction status
            $wpdb->update(
                $table_name,
                array('status' => 'completed'),
                array('id' => $transaction_id)
            );
            
            echo '<div class="container"><div class="alert success">Thanh toán đã được xác nhận thành công.</div></div>';
        } else {
            // Reject the transaction
            $wpdb->update(
                $table_name,
                array('status' => 'rejected'),
                array('id' => $transaction_id)
            );
            
            echo '<div class="container"><div class="alert">Thanh toán đã bị từ chối.</div></div>';
        }
    }
}

// Current user can only see payments where they are the payer
$current_user_id = get_current_user_id();

if (!$current_user_id) {
    echo '<div class="container"><p>Vui lòng đăng nhập để xem danh sách thanh toán chờ xác nhận.</p></div>';
    get_footer();
    exit;
}

// Get pending payments for current user to confirm
global $wpdb;
$table_name = $wpdb->prefix . 'payment_transactions';

$pending_payments = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name WHERE payer_id = %d AND status = 'pending' ORDER BY created_at DESC",
    $current_user_id
));
?>

<div class="container">
    <h2>Xác nhận thanh toán</h2>
    
    <?php if (empty($pending_payments)): ?>
        <p>Không có giao dịch nào đang chờ xác nhận.</p>
    <?php else: ?>
        <div class="pending-payments">
            <table class="debt-table">
                <thead>
                    <tr>
                        <th>Người thanh toán</th>
                        <th>Số tiền</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_payments as $payment): ?>
                        <?php 
                        $debtor = get_user_by('id', $payment->debtor_id);
                        $debtor_name = $debtor ? $debtor->display_name : 'Người dùng không tồn tại';
                        ?>
                        <tr>
                            <td>
                                <div class="member-name-with-avatar">
                                    <?php echo get_avatar($payment->debtor_id, 24, '', '', array('class' => 'user-avatar-small')); ?>
                                    <?php echo $debtor_name; ?>
                                </div>
                            </td>
                            <td><?php echo number_format($payment->amount); ?> đ</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($payment->created_at)); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?confirm=yes&id=<?php echo $payment->id; ?>" class="confirm-btn">Xác nhận</a>
                                    <a href="?confirm=no&id=<?php echo $payment->id; ?>" class="reject-btn">Từ chối</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="action-buttons mt-20">
        <a class="mui-btn" href="<?php echo home_url(); ?>">« Quay lại trang chủ</a>
    </div>
</div>

<?php get_footer(); ?>
