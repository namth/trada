<?php 
/* 
 Template Name: Danh sách nhóm
*/
get_header();
$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
$args = [
    'post_type' => 'nhom',
];
$query = new WP_Query($args);
?>
<h2>Danh sách nhóm</h2>
<div class="col-12 box mb-20" id="listuser">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Tên nhóm</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 0;
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $i++;
                    echo "<tr>";
                    echo "<td>" . $i . "</td>";
                    echo "<td><a href='" . get_permalink() . "'>" . get_the_title() . "</a></td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>
<a class='mui-btn mui-btn--primary' href="<?php echo get_bloginfo('url') . '/them-thanh-vien-moi/?g='; ?>">Tạo tài khoản thành viên</a>
<?php 
get_footer();