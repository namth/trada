<?php
/* 
    Template Name: Hiển thị QR Code
*/

get_header();

// Process payment form submission
if (isset($_POST['marked_as_paid'])) {
    $qr_description = sanitize_text_field($_POST['qr_description']);
    $payer_id = intval($_POST['payer_id']);
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $detail_ids = sanitize_text_field($_POST['detail_ids']);
    $qr_url = esc_url_raw($_POST['qr_url']);
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    
    // Save as pending transaction
    $transaction_data = array(
        'transaction_id' => 'MANUAL_' . time(),
        'payer_id' => $payer_id,
        'debtor_id' => $user_id,
        'amount' => $amount,
        'detail_ids' => $detail_ids,
        'qr_code_url' => $qr_url,
        'content' => $qr_description,
        'status' => 'pending'
    );
    
    $result = save_transaction($transaction_data);
    
    if (is_numeric($result) && $result > 0) {
        echo '<div class="container"><div class="alert success">Đã ghi nhận thanh toán của bạn. Vui lòng chờ người nhận xác nhận.</div></div>';
        echo '<script>setTimeout(function() { window.location = "' . home_url() . '"; }, 3000);</script>';
        get_footer();
        exit;
    } else {
        echo '<div class="container"><div class="alert error">Có lỗi xảy ra. Vui lòng thử lại.</div></div>';
        if (is_array($result) && isset($result['error'])) {
            echo '<pre>Error: ' . esc_html($result['error']) . '</pre>';
        }
    }
}

// Get parameters
$user_id = isset($_GET['u']) ? $_GET['u'] : get_current_user_id();
$payer_id = isset($_GET['pay']) ? $_GET['pay'] : null;
$group_id = isset($_GET['g']) ? $_GET['g'] : null;

// Initialize variables
$debt_data = array();
$detail_ids = array();
$total_debt = 0;
$user_info = get_user_by('id', $user_id);
$payer_info = $payer_id ? get_user_by('id', $payer_id) : null;

// Get bank account information of payer
$bank_account = get_user_meta($payer_id, 'bank_account', true);
$bank_name = get_user_meta($payer_id, 'bank_name', true);

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

if ($order_query->have_posts() && $payer_id) {
    while ($order_query->have_posts()) {
        $order_query->the_post();
        
        $order_id = get_the_ID();
        $order_title = get_the_title();
        $ngay_thang = get_field('ngay_thang');
        $group = get_field('nhom');
        $phi_dich_vu = $group ? (int) get_field('service_tax', $group) : 0;
        $nguoi_thanh_toan = get_field('nguoi_thanh_toan');

        // Only process if this order is from the specified payer
        if ($nguoi_thanh_toan['ID'] != $payer_id) {
            continue;
        }

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
                
                $detail_id = get_the_ID();
                $so_tien = (int) get_field('so_tien');
                $users = get_field('danh_sach_nguoi_su_dung');
                
                if (!$users || empty($users)) continue;
                
                $chiphi_motnguoi = round(($so_tien / count($users)) * (1 + $phi_dich_vu));
                
                if (have_rows('danh_sach_nguoi_su_dung')) {
                    while (have_rows('danh_sach_nguoi_su_dung')) {
                        the_row();
                        
                        $user = get_sub_field('user');
                        $pay = get_sub_field('pay');
                        
                        // Check if this user is the debtor and hasn't paid yet
                        if (!$pay && $user == $user_id && $nguoi_thanh_toan['ID'] == $payer_id) {
                            $detail_ids[] = $detail_id;
                            $total_debt += $chiphi_motnguoi;
                        }
                    }
                }
            }
            wp_reset_postdata();
        }
    }
    wp_reset_postdata();
}

// Create QR description with encrypted detail IDs including payer_id and user_id
$payment_data = array(
    $payer_id,
    $user_id
);

// Add detail IDs to payment data
if (!empty($detail_ids)) {
    $payment_data = array_merge($payment_data, $detail_ids);
}

$qr_description = !empty($detail_ids) ? process_order_data(implode(',', $payment_data), 'encrypt') : "No details";

// Create QR code URL
$qr_url = "";
if (!empty($bank_account) && !empty($bank_name) && $total_debt > 0) {
    $qr_url = "https://qr.sepay.vn/img?acc=" . urlencode($bank_account) . 
              "&bank=" . urlencode($bank_name) . 
              "&amount=" . $total_debt . 
              "&des=" . urlencode($qr_description);
}
?>

<canvas id="starry-background"></canvas>

<div class="container">
    <div class="action-buttons">
        <a class="mui-btn" href="javascript:history.back()">« Quay lại</a>
    </div>
    
    <!-- <h3>Thanh toán cho: <?php echo $payer_info ? $payer_info->display_name : 'Không xác định'; ?></h3> -->
    
    <?php if ($group_id): ?>
    <p>Nhóm: <?php echo $group_title; ?></p>
    <?php endif; ?>
    
    <div class="qr-container">
        <?php if ($payer_id && $total_debt > 0 && !empty($qr_url)): ?>
            <div class="qr-info">
                <p>Người thanh toán: <strong><?php echo $payer_info->display_name; ?></strong></p>
                <p>Tài khoản: <strong><?php echo esc_html($bank_account); ?></strong></p>
                <p>Ngân hàng: <strong><?php echo esc_html($bank_name); ?></strong></p>
                <p>Số tiền: <strong><?php echo number_format($total_debt); ?> đ</strong></p>
                <p>Nội dung chuyển khoản: <strong><?php echo esc_html($qr_description); ?></strong></p>
            </div>
            
            <div class="qr-image">
                <img src="<?php echo esc_url($qr_url); ?>" alt="QR Code thanh toán" />
            </div>
            
            <div class="qr-instructions">
                <p>Quét mã QR bằng ứng dụng ngân hàng để thanh toán.</p>
            </div>
        <?php elseif ($total_debt <= 0): ?>
            <div class="alert">
                <p>Bạn không còn khoản nợ nào với người dùng này.</p>
            </div>
        <?php elseif (empty($bank_account) || empty($bank_name)): ?>
            <div class="alert">
                <p>Người nhận chưa cập nhật thông tin tài khoản ngân hàng.</p>
            </div>
        <?php else: ?>
            <div class="alert">
                <p>Không thể tạo mã QR. Vui lòng kiểm tra lại thông tin.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="back-buttons">
        <?php if ($payer_id && $total_debt > 0 && !empty($qr_url)): ?>
            <form method="post" action="" class="payment-form">
                <input type="hidden" name="qr_description" value="<?php echo esc_attr($qr_description); ?>">
                <input type="hidden" name="payer_id" value="<?php echo esc_attr($payer_id); ?>">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" name="amount" value="<?php echo esc_attr($total_debt); ?>">
                <input type="hidden" name="detail_ids" value="<?php echo esc_attr(implode(',', $detail_ids)); ?>">
                <input type="hidden" name="qr_url" value="<?php echo esc_attr($qr_url); ?>">
                <?php if ($group_id): ?>
                <input type="hidden" name="group_id" value="<?php echo esc_attr($group_id); ?>">
                <?php endif; ?>
                <button type="submit" class="debt-details-button" name="marked_as_paid">Đã thanh toán</button>
            </form>
        <?php else: ?>
            <a class='debt-details-button' href="<?php echo home_url("/chi-tiet-cong-no/?u={$user_id}&pay={$payer_id}"); ?>">
                Xem chi tiết công nợ
            </a>
        <?php endif; ?>
        
        <?php if ($group_id): ?>
        <a class='debt-details-button' href="<?php echo get_permalink($group_id); ?>">
            Quay lại nhóm
        </a>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
