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