<?php 
function newtheme_init(){
    add_theme_support( 'post-thumbnails' );    
    register_nav_menus(
        array(
            'menu-1' => __( 'Primary', 'twentynineteen' ),
            'footer' => __( 'Footer Menu', 'twentynineteen' ),
            'social' => __( 'Social Links Menu', 'twentynineteen' ),
        )
    );
}
add_action( 'after_setup_theme', 'newtheme_init' );

function load_css_script() {
    wp_enqueue_style( 'newtheme_style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui', 'https://code.jquery.com/ui/1.13.2/jquery-ui.js', array('jquery'), '1.13.2', true);
    wp_enqueue_script('custom', get_template_directory_uri() . '/js/custom.js', array('jquery', 'jquery-ui'), '1.0', true);
    wp_localize_script('custom', 'AJAX', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));

}
add_action( 'wp_enqueue_scripts', 'load_css_script' );


add_action('wp_ajax_autocomplete_action', 'autocomplete_action');
function autocomplete_action(){
    $suggestions= [];
    $s  = strtolower($_REQUEST['term']);
    $args = array(
        'post_type' => 'san_pham',
        's'         => $s
    );
    $query = new WP_Query( $args );
    if( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $suggestions[] = [
                'id' => get_the_ID(), 
                'product'   => get_the_title(),
                'price'     => get_field('gia_tien')
            ];
        }
    }
    $response = $_GET["callback"] . "(" . json_encode($suggestions) . ")";
    echo $response;

    exit;
}

add_action('wp_ajax_calculate_price', 'calculate_price');
function calculate_price(){
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];

    echo $quantity * $price;
    exit;
}

function calculate_order_payment ($order_id) {
    $total = 0;
    $args   = array(
        'post_type'     => 'chi_tiet_don_hang',
        'posts_per_page' => 999,
    );
    $args['meta_query'][] = array(
        array(
            'key'       => 'don_hang',
            'value'     => $order_id,
            'compare'   => '=',
        ),
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $so_tien = get_field('so_tien');
            $total += $so_tien;
        } wp_reset_postdata();
    }

    return $total;
}


// filter sub field in acf repeater
function my_posts_where( $where ) {
    
    $where = str_replace("meta_key = 'danh_sach_nguoi_su_dung_$", "meta_key LIKE 'danh_sach_nguoi_su_dung_%", $where);

    return $where;
}

add_filter('posts_where', 'my_posts_where');

/**
 * Custom avatar functionality
 * Add this to your theme's functions.php file
 */
function custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $user_id = 0;
    
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        $user_id = $user ? $user->ID : 0;
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        } elseif (!empty($id_or_email->comment_author_email)) {
            $user = get_user_by('email', $id_or_email->comment_author_email);
            $user_id = $user ? $user->ID : 0;
        }
    }
    
    if ($user_id) {
        $custom_avatar = get_user_meta($user_id, 'custom_avatar', true);
        
        if ($custom_avatar) {
            $upload_dir = wp_upload_dir();
            $avatar_url = $upload_dir['baseurl'] . '/' . $custom_avatar;
            
            $avatar = "<img alt='{$alt}' src='{$avatar_url}' class='avatar avatar-{$size} photo user-avatar-small' height='{$size}' width='{$size}' />";
        }
    }
    
    return $avatar;
}
add_filter('get_avatar', 'custom_avatar', 10, 5);

/**
 * Process order data - encrypt or decrypt with shorter output
 *
 * @param mixed $data The data to process
 * @param string $mode 'encrypt' or 'decrypt'
 * @return mixed The processed data or false on failure
 */
function process_order_data($data, $mode = 'encrypt') {
    // Get a key using WordPress authentication unique keys
    $key = defined('AUTH_KEY') ? substr(AUTH_KEY, 0, 32) : 'default-key-please-change-this-32';
    
    if ($mode === 'encrypt') {
        // Convert array to string if needed
        if (is_array($data)) {
            $data = implode(',', $data);
        }
        
        // Create a shorter IV (8 bytes instead of 16)
        $iv = substr(md5(uniqid()), 0, 8);
        
        // Encrypt with shorter algorithm
        $encrypted = openssl_encrypt($data, 'BF-CBC', $key, 0, $iv);
        if ($encrypted === false) return false;
        
        // Combine IV and ciphertext and encode with Base64
        $result = base64_encode($iv . $encrypted);
        
        // Make it URL safe by replacing certain characters
        $result = str_replace(['+', '/', '='], ['-', '_', ''], $result);
        
        return 'Q' . $result; // Shorter prefix
    } 
    else if ($mode === 'decrypt') {
        // Check if it has our prefix
        if (substr($data, 0, 1) !== 'Q') {
            return false;
        }
        
        // Remove the prefix
        $data = substr($data, 1);
        
        // Restore Base64 standard characters
        $data = str_replace(['-', '_'], ['+', '/'], $data);
        $data = base64_decode($data);
        
        if ($data === false) return false;
        
        // Extract the IV (first 8 bytes)
        $iv = substr($data, 0, 8);
        $encrypted = substr($data, 8);
        
        // Decrypt the data
        return openssl_decrypt($encrypted, 'BF-CBC', $key, 0, $iv);
    }
    
    return false;
}

/**
 * Create transactions table on theme activation
 */
function create_transactions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'payment_transactions';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL,
            payer_id bigint(20) NOT NULL,
            debtor_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            detail_ids text NOT NULL,
            gateway varchar(100) DEFAULT NULL,
            transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
            content text,
            reference_code varchar(255),
            qr_code_url text,
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    } else {
        // Check if qr_code_url column exists and add it if not
        $column = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s
            AND COLUMN_NAME = 'qr_code_url'",
            DB_NAME, $table_name
        ));
        
        if (empty($column)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN qr_code_url text AFTER reference_code");
        }
    }
}

/**
 * Insert transaction data into database with better error handling
 * 
 * @param array $data Transaction data
 * @return int|false|array The ID of the inserted record, or error information on failure
 */
function save_transaction($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'payment_transactions';
    
    // Validate required fields
    $required_fields = ['transaction_id', 'payer_id', 'debtor_id', 'amount', 'detail_ids', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            error_log("Missing required field: $field in transaction data");
            return ['error' => "Missing required field: $field", 'data' => $data];
        }
    }
    
    // Make sure the table exists
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // If table doesn't exist, try to create it
        create_transactions_table();
        
        // Check again
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("Transaction table does not exist: $table_name");
            return ['error' => 'Transaction table does not exist', 'table' => $table_name];
        }
    }
    
    // For debugging: check table structure
    $table_structure = $wpdb->get_results("DESCRIBE $table_name");
    error_log("Table structure: " . print_r($table_structure, true));
    
    // For debugging: log the data being inserted
    error_log("Inserting transaction data: " . print_r($data, true));
    
    // Ensure amount is numeric
    if (isset($data['amount'])) {
        $data['amount'] = floatval($data['amount']);
    }
    
    // Ensure IDs are integers
    if (isset($data['payer_id'])) {
        $data['payer_id'] = intval($data['payer_id']);
    }
    if (isset($data['debtor_id'])) {
        $data['debtor_id'] = intval($data['debtor_id']);
    }
    
    // Try to insert the data
    $result = $wpdb->insert($table_name, $data);
    
    if ($result === false) {
        // Get the database error
        $db_error = $wpdb->last_error;
        error_log("Database error in save_transaction: $db_error");
        return ['error' => $db_error, 'data' => $data];
    }
    
    return $wpdb->insert_id;
}

// Update the create_transactions_table function to run on both theme activation and init
function initialize_transactions_table() {
    create_transactions_table();
}
add_action('init', 'initialize_transactions_table');

