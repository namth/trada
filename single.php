<?php

get_header();
?>
        <div class="mui-row" id="main_content">
            <div class="mui-col-md-8 mui-col-md-offset-1">
            
                <?php 
                    if (have_posts()) {
                        while (have_posts()){
                            the_post();

                            echo "<h2>" . get_the_title() . "</h2>";
                            echo get_the_post_thumbnail();

                            echo get_the_content();


                        }
                    }
                
                ?>
            </div>
            <?php get_sidebar(); ?>
        </div>

<?php
get_footer();