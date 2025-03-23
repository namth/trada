<?php 
/* 
 Template Name: Danh sách nhóm
*/
get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="container"><p>Bạn cần đăng nhập để xem danh sách nhóm.</p></div>';
    get_footer();
    exit;
}

$current_user_id = get_current_user_id();
$is_admin = current_user_can('administrator');

// Set up query for groups
$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
$args = [
    'post_type' => 'nhom',
    'posts_per_page' => -1,
];

$query = new WP_Query($args);
?>
<div class="container">
    <h2>Danh sách nhóm</h2>
    <div class="col-12 box mb-20" id="listuser">
        <div class="group-boxes">
            <?php 
            $found_groups = false;
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $group_id = get_the_ID();
                    $group_owner_id = get_field('chu_quy', $group_id);
                    $list_user = get_field('danh_sach_thanh_vien', $group_id);
                    $member_count = !empty($list_user) ? count($list_user) : 0;
                    
                    // Determine if user should see this group:
                    // - Admin can see all groups
                    // - Group owner can see their group
                    // - Members listed in danh_sach_thanh_vien can see the group
                    $show_group = $is_admin || $group_owner_id == $current_user_id;
                    
                    if (!$show_group && !empty($list_user)) {
                        foreach ($list_user as $user) {
                            if ($user['thanh_vien'] == $current_user_id) {
                                $show_group = true;
                                break;
                            }
                        }
                    }
                    
                    if ($show_group) {
                        $found_groups = true;
                        echo '<div class="group-box">';
                        echo '<a href="' . get_permalink() . '" class="box">';
                        echo '<div class="group-name">' . get_the_title() . '</div>';
                        echo '<div class="member-count">' . $member_count . ' thành viên</div>';
                        echo '</a>';
                        
                        // Add the "Thêm order" button with improved markup
                        echo '<a href="' . get_bloginfo('url') . '/tao-order-moi/?group=' . $group_id . '" class="group-add-order" title="Thêm order mới">';
                        echo '<span class="dashicons dashicons-plus-alt"></span>';
                        echo '<span>Thêm</span>';
                        echo '</a>';
                        
                        echo '</div>';
                    }
                }
                wp_reset_postdata();
            }
            
            if (!$found_groups) {
                echo '<div class="no-data">Bạn không thuộc nhóm nào. Vui lòng liên hệ quản trị viên để được thêm vào nhóm.</div>';
            }
            ?>
        </div>
    </div>
    
    <a class='debt-details-button' href="<?php echo get_bloginfo('url') . '/them-thanh-vien-moi/?g='; ?>">Tạo tài khoản thành viên</a>
    <a class='debt-details-button' href="<?php echo get_bloginfo('url') . '/them-nhom-moi'; ?>">Thêm nhóm mới</a>
</div>
<?php 
get_footer();