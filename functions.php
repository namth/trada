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