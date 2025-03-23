<div class="mui-row" id="footer">
            <div class="mui-col-md-12">
                <div class="mui-row">
                    <div class="mui-col-xs-3"></div>
                    <div class="mui-col-xs-3"></div>
                    <div class="mui-col-xs-3"></div>
                    <div class="mui-col-xs-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fixed Quick Actions Bar -->
    <?php if(is_user_logged_in()): ?>
    <div class="fixed-quick-actions">
        <div class="quick-actions-container">
            <a href="<?php echo home_url('/'); ?>" class="quick-action-btn">
                <i class="dashicons dashicons-admin-home"></i>
                <span>Trang chủ</span>
            </a>
            <a href="<?php echo home_url('/danh-sach-nhom'); ?>" class="quick-action-btn">
                <i class="dashicons dashicons-groups"></i>
                <span>Nhóm của bạn</span>
            </a>
            <a href="<?php echo home_url('/chi-tiet-cong-no/?u=' . get_current_user_id()); ?>" class="quick-action-btn">
                <i class="dashicons dashicons-money-alt"></i>
                <span>Công nợ của bạn</span>
            </a>
            <a href="<?php echo get_author_posts_url(get_current_user_id()); ?>" class="quick-action-btn">
                <i class="dashicons dashicons-admin-users"></i>
                <span>Tài khoản của bạn</span>
            </a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="quick-action-btn">
                <i class="dashicons dashicons-exit"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php wp_footer(); ?>
</body>
</html>