<?php
/**
 * Test script for payment callback
 * This simulates a payment gateway sending callback data
 */

// Load WordPress environment
define('WP_USE_THEMES', false);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Generate a valid QR description based on real data
function generate_test_qr_data() {
    // Get a real user and payer for testing
    $users = get_users(['number' => 2]);
    
    if (count($users) < 2) {
        die("Need at least 2 users for testing");
    }
    
    $payer_id = $users[0]->ID;
    $user_id = $users[1]->ID;
    
    echo "Using payer_id: {$payer_id}, debtor_id: {$user_id}\n";
    
    // Find real order details that are unpaid
    $args = array(
        'post_type'     => 'chi_tiet_don_hang',
        'posts_per_page' => 3,
        'meta_query'    => array(
            array(
                'key'   => 'trang_thai',
                'value' => "Chưa thanh toán",
                'compare' => '=',
            )
        )
    );
    
    $query = new WP_Query($args);
    $detail_ids = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $detail_ids[] = get_the_ID();
        }
        wp_reset_postdata();
    }
    
    if (empty($detail_ids)) {
        // If no real unpaid orders, use some dummy IDs for testing
        $detail_ids = [1, 2, 3]; // These won't update real data
        echo "Warning: Using dummy detail IDs. No real data will be updated.\n";
    } else {
        echo "Using real detail IDs: " . implode(', ', $detail_ids) . "\n";
    }
    
    // Create and encrypt the payment data
    $payment_data = array_merge([$payer_id, $user_id], $detail_ids);
    $encrypted = process_order_data(implode(',', $payment_data), 'encrypt');
    
    echo "Generated encrypted QR: {$encrypted}\n";
    return [
        'encrypted' => $encrypted,
        'payer_id' => $payer_id,
        'user_id' => $user_id,
        'detail_ids' => $detail_ids
    ];
}

// Get QR data and calculate appropriate amount
$test_data = generate_test_qr_data();
$amount = 71000; // Generate a plausible amount

// Create mock callback data
$callback_data = [
    'id' => rand(10000, 99999),
    'gateway' => 'Vietcombank',
    'transactionDate' => date('Y-m-d H:i:s'),
    'accountNumber' => '0123499999',
    'code' => null,
    'content' => 'QMWZlZGEwMDA2UVg1SkpIWTc0YVNweUU5WEVoM2lRPT0',
    'transferType' => 'in',
    'transferAmount' => $amount,
    'accumulated' => $amount + 1000000,
    'subAccount' => null,
    'referenceCode' => 'MBVCB.' . rand(1000000000, 9999999999),
    'description' => 'Mock test payment'
];

// Determine which callback handler to test
$callback_url = '';

// Option 1: Test the standalone callback.php file directly
$callback_url = get_template_directory_uri() . '/callback.php';

// Option 2: If using the page template, get its URL
// $callback_page = get_page_by_path('payment-callback');
// if ($callback_page) {
//     $callback_url = get_permalink($callback_page->ID);
// }

if (empty($callback_url)) {
    die("Callback URL not found. Please set up the callback handler first.");
}

echo "Sending test callback to: {$callback_url}\n";
echo "Callback data: " . json_encode($callback_data, JSON_PRETTY_PRINT) . "\n\n";

// Make the curl request
$ch = curl_init($callback_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($callback_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Display results
echo "Response HTTP code: {$http_code}\n";
echo "Response body: {$response}\n\n";
echo "Check payment_log.txt for detailed results.\n";

// Instructions for validating the test
echo "\n----------------------------\n";
echo "To validate the test results:\n";
echo "1. Check payment_log.txt for detailed logs\n";
echo "2. Check if the order detail statuses were updated\n";
echo "3. Verify group member balances if using a group\n";
echo "4. Check the payment_transactions table in the database\n";
