<?php
get_header();
?>
        <div class="mui-row" id="main_content">
            <div class="mui-col-md-8 mui-col-md-offset-1">
                category
            <?php
            if (have_posts()) {
                while (have_posts()) {
                    the_post();

                    echo "<div class='newtheme_post post_" . get_the_ID() . "'>";
                        echo get_the_post_thumbnail();
                        echo "<h4><a href='" . get_the_permalink() . "'>" . get_the_title() . "</a></h4>";
                        echo "</div>";
                }
            }
            ?>        
        
            </div>
            <?php get_sidebar(); ?>
        </div>

<?php

get_footer();

