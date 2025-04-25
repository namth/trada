<?php
/**
 * Template Name: Payment Callback Handler
 * 
 * A template for handling payment callbacks from payment gateway
 * Create a page with this template and set its URL in your payment gateway configuration
 */

// Disable direct output to browser - we'll handle the response manually
define('DOING_AJAX', true);

// Start output buffering to prevent any accidental output
ob_start();

// Log incoming requests for debugging
function log_callback($data) {
    $log_file = get_template_directory() . '/payment_log.txt';
    $log_data = date('[Y-m-d H:i:s]') . ' ' . json_encode($data) . "\n";
    file_put_contents($log_file, $log_data, FILE_APPEND);
}

// Get input data (from POST or raw input)
$input = file_get_contents('php://input');
$callback_data = json_decode($input, true);

// If no JSON input, try POST data
if (empty($callback_data) && !empty($_POST)) {
    $callback_data = $_POST;
}

// Log incoming data
log_callback(['raw_input' => $input, 'processed' => $callback_data]);

// Validate required fields
if (empty($callback_data) || !isset($callback_data['content']) || 
    !isset($callback_data['transferAmount']) || !isset($callback_data['transferType']) || 
    $callback_data['transferType'] !== 'in') {
    log_callback(['error' => 'Invalid data or not an incoming transfer']);
    status_header(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    ob_end_flush();
    exit;
}

// Extract QR description from the transfer content
$encrypted_data = $callback_data['content'];

// Attempt to decrypt the data
$payment_data = process_order_data($encrypted_data, 'decrypt');

// If decryption fails, exit
if ($payment_data === false) {
    log_callback(['error' => 'Failed to decrypt: ' . $encrypted_data]);
    status_header(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment reference']);
    ob_end_flush();
    exit;
}

// Parse the payment data
$payment_data = explode(',', $payment_data);

// Extract payer_id, user_id, and detail_ids
if (count($payment_data) < 3) {
    log_callback(['error' => 'Invalid payment data format']);
    status_header(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment data format']);
    ob_end_flush();
    exit;
}

$payer_id = (int)$payment_data[0];
$user_id = (int)$payment_data[1];
$detail_ids = array_slice($payment_data, 2);

// Verify the payment amount
$total_debt = 0;
$processed_orders = [];

// Process each order detail
foreach ($detail_ids as $detail_id) {
    $detail_id = (int)$detail_id;
    
    // Get the order detail
    $order_detail = get_post($detail_id);
    if (!$order_detail || $order_detail->post_type !== 'chi_tiet_don_hang') {
        continue;
    }
    
    // Get order and compute user share
    $order_id = get_field('don_hang', $detail_id);
    $nguoi_thanh_toan = get_field('nguoi_thanh_toan', $order_id);
    
    // Skip if not matching the expected payer
    if ($nguoi_thanh_toan['ID'] != $payer_id) {
        continue;
    }
    
    $so_tien = (int)get_field('so_tien', $detail_id);
    $users = get_field('danh_sach_nguoi_su_dung', $detail_id);
    
    if (!$users || empty($users)) continue;
    
    // Get the group info for service tax
    $group = get_field('nhom', $order_id);
    $phi_dich_vu = $group ? (int)get_field('service_tax', $group) : 0;
    
    $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu/100));
    $order_updated = false;
    
    // Update user payment status
    if (have_rows('danh_sach_nguoi_su_dung', $detail_id)) {
        while (have_rows('danh_sach_nguoi_su_dung', $detail_id)) {
            the_row();
            
            $row_user = get_sub_field('user');
            $pay = get_sub_field('pay');
            
            // If this is the user who is paying and hasn't paid yet
            if (!$pay && $row_user == $user_id) {
                update_sub_field('field_63f97e0b6cf3b', true); // Mark as paid
                $total_debt += $chiphi_motnguoi;
                $order_updated = true;
            }
        }
    }
    
    // Check if all users have paid and update order status
    $all_paid = true;
    if (have_rows('danh_sach_nguoi_su_dung', $detail_id)) {
        while (have_rows('danh_sach_nguoi_su_dung', $detail_id)) {
            the_row();
            $pay = get_sub_field('pay');
            if (!$pay) {
                $all_paid = false;
                break;
            }
        }
    }
    
    if ($all_paid) {
        update_field('field_63f9e870f1437', "Đã thanh toán", $detail_id);
    }
    
    if ($order_updated) {
        $processed_orders[] = $detail_id;
    }
}

// If the transfer amount doesn't match, log but still process
if (abs($total_debt - $callback_data['transferAmount']) > 1000) { // Allow small difference
    log_callback(['warning' => "Amount mismatch: expected {$total_debt}, got {$callback_data['transferAmount']}"]);
}

// If group exists, update group member balances
if (!empty($processed_orders)) {
    $order_detail = get_post($processed_orders[0]);
    $order_id = get_field('don_hang', $processed_orders[0]);
    $group_id = get_field('nhom', $order_id);
    
    if ($group_id) {
        // Update group member balances as in group_debt_check.php
        $group_owner_id = get_field('chu_quy', $group_id);
        
        if (have_rows('danh_sach_thanh_vien', $group_id)) {
            while (have_rows('danh_sach_thanh_vien', $group_id)) {
                the_row();
                
                $thanh_vien = get_sub_field('thanh_vien');
                $amount = (int)get_sub_field('amount');
                $row = get_row_index();
                
                // If this is the debtor, reduce their balance
                if ($thanh_vien == $user_id) {
                    $row_update = array(
                        'thanh_vien' => $thanh_vien,
                        'amount' => $amount - $total_debt,
                    );
                    update_row('field_63bd0da3f7281', $row, $row_update, $group_id);
                }
                
                // If payer is a member, increase their balance 
                if (($payer_id != $group_owner_id) && ($thanh_vien == $payer_id)) {
                    $row_update = array(
                        'thanh_vien' => $payer_id,
                        'amount' => $amount + $total_debt,
                    );
                    update_row('field_63bd0da3f7281', $row, $row_update, $group_id);
                }
            }
        }
    }
    
    // Save transaction record
    $transaction_data = array(
        'transaction_id' => $callback_data['id'],
        'payer_id' => $payer_id,
        'debtor_id' => $user_id,
        'amount' => $callback_data['transferAmount'],
        'detail_ids' => implode(',', $processed_orders),
        'gateway' => $callback_data['gateway'],
        'transaction_date' => $callback_data['transactionDate'],
        'content' => $callback_data['content'],
        'reference_code' => $callback_data['referenceCode'],
        'status' => 'completed'
    );
    
    save_transaction($transaction_data);
    
    log_callback(['success' => "Payment processed for user {$user_id} to {$payer_id}. Amount: {$total_debt}"]);
    echo json_encode(['status' => 'success', 'message' => 'Payment processed successfully']);
} else {
    log_callback(['error' => 'No orders processed']);
    status_header(400);
    echo json_encode(['status' => 'error', 'message' => 'No valid orders found']);
}

// End output buffering and send response
ob_end_flush();
exit;
