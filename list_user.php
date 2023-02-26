<?php 
/* 
 Template Name: Danh sách thành viên 
*/
get_header();
$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
$count_args  = array(
    'number'    => 999999,
);
$query = new WP_User_Query($count_args);
$users = $query->get_results();
?>
<div class="col-12 box mb-20" id="listuser">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Tên</th>
                <th>Username</th>
                <th><?php _e('Email', 'qlcv'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 0;
            if (!empty($users)) {
                foreach ($users as $user) {
                    $i++;
                    echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td><a href='" . get_author_posts_url($user->ID) . "'>" . $user->display_name . "</a></td>";
                    echo "<td>" . $user->user_login . "</td>";
                    echo "<td>" . $user->user_email . "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>
<a class='mui-btn mui-btn--primary' href="<?php echo get_bloginfo('url') ?>/them-thanh-vien-moi/">Thêm thành viên</a>
<?php 
get_footer();