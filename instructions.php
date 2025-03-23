<?php 
/* 
 Template Name: Hướng dẫn sử dụng
*/

get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}
?>

<canvas id="starry-background"></canvas>

<div class="container instructions-container starry-page">
    <h1 class="instructions-title">Hướng dẫn sử dụng</h1>
    
    <div class="instructions-layout">
        <!-- Left menu sidebar -->
        <div class="instructions-sidebar">
            <ul class="instructions-menu">
                <li><a href="#section-intro" class="active">Giới thiệu</a></li>
                <li><a href="#section-groups">Quản lý nhóm</a></li>
                <li><a href="#section-orders">Quản lý đơn hàng</a></li>
                <li><a href="#section-debts">Quản lý công nợ</a></li>
                <li><a href="#section-permissions">Quyền hạn của chủ nhóm</a></li>
                <li><a href="#section-account">Quản lý tài khoản</a></li>
            </ul>
            
            <div class="back-home-link">
                <a href="<?php echo home_url(); ?>" class="debt-details-button">Quay lại trang chủ</a>
            </div>
        </div>
        
        <!-- Main content area -->
        <div class="instructions-content">
            <div id="section-intro" class="instruction-section">
                <h2><i class="dashicons dashicons-welcome-learn-more"></i> Giới thiệu</h2>
                <p>Chào mừng bạn đến với <strong>Chia Tiền</strong> - ứng dụng giúp bạn dễ dàng quản lý, chia sẻ và theo dõi chi phí trong nhóm bạn bè, đồng nghiệp hoặc gia đình.</p>
                <p>Ứng dụng cho phép bạn:</p>
                <ul>
                    <li>Tạo và quản lý các nhóm chi tiêu</li>
                    <li>Thêm thành viên vào nhóm</li>
                    <li>Tạo đơn hàng và ghi nhận chi tiết chi tiêu</li>
                    <li>Theo dõi công nợ giữa các thành viên</li>
                    <li>Thanh toán và xoá nợ</li>
                </ul>
            </div>
            
            <div id="section-groups" class="instruction-section">
                <h2><i class="dashicons dashicons-groups"></i> Quản lý nhóm</h2>
                
                <div class="instruction-item">
                    <h3>Tạo nhóm mới</h3>
                    <ol>
                        <li>Từ màn hình chính, chọn "Danh sách nhóm"</li>
                        <li>Nhấn vào nút "Thêm nhóm mới"</li>
                        <li>Điền thông tin nhóm: tên nhóm, chủ quỹ, và phí dịch vụ (nếu có)</li>
                        <li>Nhấn "Tạo nhóm" để hoàn tất</li>
                    </ol>
                </div>
                
                <div class="instruction-item">
                    <h3>Thêm thành viên vào nhóm</h3>
                    <ol>
                        <li>Vào trang chi tiết nhóm</li>
                        <li>Nhấn nút "Tạo tài khoản thành viên" để thêm người mới, hoặc</li>
                        <li>Nhấn nút "Thêm thành viên" để thêm người dùng đã có tài khoản</li>
                        <li>Điền thông tin cần thiết và xác nhận</li>
                    </ol>
                    <p><strong>Lưu ý:</strong> Chỉ chủ nhóm hoặc quản trị viên mới có quyền thêm thành viên</p>
                </div>
                
                <div class="instruction-item">
                    <h3>Đổi chủ nhóm</h3>
                    <ol>
                        <li>Vào trang chi tiết nhóm</li>
                        <li>Trong bảng danh sách thành viên, tìm tên thành viên muốn chuyển quyền</li>
                        <li>Nhấn nút "Đặt làm chủ nhóm" và xác nhận</li>
                    </ol>
                </div>
            </div>
            
            <div id="section-orders" class="instruction-section">
                <h2><i class="dashicons dashicons-cart"></i> Quản lý đơn hàng</h2>
                
                <div class="instruction-item">
                    <h3>Tạo đơn hàng mới</h3>
                    <ol>
                        <li>Vào trang chi tiết nhóm</li>
                        <li>Nhấn nút "Thêm order"</li>
                        <li>Điền tiêu đề đơn hàng và ngày thực hiện</li>
                        <li>Nhấn "Tạo đơn hàng" để hoàn tất</li>
                    </ol>
                </div>
                
                <div class="instruction-item">
                    <h3>Thêm chi tiết vào đơn hàng</h3>
                    <ol>
                        <li>Mở đơn hàng đã tạo</li>
                        <li>Nhấn nút "Thêm đồ"</li>
                        <li>Nhập tên sản phẩm/dịch vụ</li>
                        <li>Nhập số tiền</li>
                        <li>Chọn các thành viên sử dụng</li>
                        <li>Nhấn "Thêm chi tiết" để hoàn tất</li>
                    </ol>
                    <p><strong>Lưu ý:</strong> Hệ thống sẽ tự động chia số tiền dựa trên số người sử dụng</p>
                </div>
                
                <div class="instruction-item">
                    <h3>Thanh toán đơn hàng</h3>
                    <ol>
                        <li>Mở đơn hàng cần thanh toán</li>
                        <li>Nhấn nút "Thanh toán"</li>
                        <li>Chọn người thanh toán từ danh sách</li>
                        <li>Xác nhận số tiền</li>
                        <li>Nhấn "Thanh toán" để hoàn tất</li>
                    </ol>
                    <p><strong>Lưu ý:</strong> Khi đơn hàng được thanh toán, công nợ sẽ được cập nhật tự động cho các thành viên</p>
                </div>
            </div>
            
            <div id="section-debts" class="instruction-section">
                <h2><i class="dashicons dashicons-money-alt"></i> Quản lý công nợ</h2>
                
                <div class="instruction-item">
                    <h3>Xem công nợ cá nhân</h3>
                    <ol>
                        <li>Nhấn vào nút "Công nợ của bạn" ở thanh menu dưới cùng</li>
                        <li>Xem danh sách các khoản nợ và cho nợ</li>
                        <li>Các khoản được đánh dấu (+) là tiền người khác nợ bạn</li>
                        <li>Các khoản được đánh dấu (-) là tiền bạn nợ người khác</li>
                    </ol>
                </div>
                
                <div class="instruction-item">
                    <h3>Kiểm tra công nợ nhóm</h3>
                    <ol>
                        <li>Vào trang chi tiết nhóm</li>
                        <li>Nhấn nút "Kiểm tra công nợ"</li>
                        <li>Xem chi tiết các khoản nợ giữa các thành viên</li>
                    </ol>
                </div>
                
                <div class="instruction-item">
                    <h3>Thanh toán công nợ</h3>
                    <ol>
                        <li>Vào trang kiểm tra công nợ nhóm</li>
                        <li>Tìm khoản nợ cần thanh toán</li>
                        <li>Nhấn nút "Thanh toán" bên cạnh khoản nợ đó</li>
                        <li>Hệ thống sẽ tự động cập nhật trạng thái thanh toán và công nợ</li>
                    </ol>
                    <p><strong>Lưu ý quan trọng:</strong> Chỉ chủ nhóm hoặc quản trị viên mới có quyền thanh toán công nợ cho nhóm. Các thành viên bình thường chỉ có thể xem các khoản nợ mà không thể thực hiện thao tác thanh toán.</p>
                </div>
            </div>
            
            <div id="section-permissions" class="instruction-section">
                <h2><i class="dashicons dashicons-lock"></i> Quyền hạn của chủ nhóm</h2>
                
                <div class="instruction-item">
                    <h3>Các quyền đặc biệt của chủ nhóm</h3>
                    <p>Trong ứng dụng Chia Tiền, chủ nhóm có những quyền hạn đặc biệt sau:</p>
                    <ul>
                        <li>Thêm thành viên vào nhóm</li>
                        <li>Xóa thành viên khỏi nhóm</li>
                        <li>Chuyển quyền chủ nhóm cho thành viên khác</li>
                        <li>Thanh toán đơn hàng</li>
                        <li>Xác nhận thanh toán các khoản nợ trong nhóm</li>
                        <li>Quản lý kiểm tra công nợ của nhóm</li>
                        <li>Sửa đổi thông tin nhóm</li>
                    </ul>
                    <p><strong>Lưu ý:</strong> Quản trị viên cũng có tất cả các quyền hạn trên</p>
                </div>
                
                <div class="instruction-item">
                    <h3>Quản lý thành viên</h3>
                    <p>Chỉ chủ nhóm mới có thể:</p>
                    <ol>
                        <li>Thêm thành viên mới vào nhóm qua nút "Tạo tài khoản thành viên" hoặc "Thêm thành viên"</li>
                        <li>Xóa thành viên khỏi nhóm bằng nút "Xóa thành viên" trong bảng danh sách thành viên</li>
                        <li>Chọn người khác làm chủ nhóm với nút "Đặt làm chủ nhóm" trong bảng danh sách thành viên</li>
                    </ol>
                </div>
                
                <div class="instruction-item">
                    <h3>Quản lý thanh toán</h3>
                    <p>Chỉ chủ nhóm mới có thể:</p>
                    <ol>
                        <li>Xác nhận thanh toán cho đơn hàng bằng nút "Thanh toán" trong trang chi tiết đơn hàng</li>
                        <li>Chọn người thanh toán cho đơn hàng</li>
                        <li>Xác nhận việc thanh toán nợ giữa các thành viên trong trang "Kiểm tra công nợ"</li>
                    </ol>
                    <p><strong>Lưu ý:</strong> Các thành viên thường chỉ có thể xem thông tin và tạo đồ trong đơn hàng, không thể xác nhận thanh toán</p>
                </div>
            </div>
            
            <div id="section-account" class="instruction-section">
                <h2><i class="dashicons dashicons-admin-users"></i> Quản lý tài khoản</h2>
                
                <div class="instruction-item">
                    <h3>Xem thông tin cá nhân</h3>
                    <ol>
                        <li>Nhấn vào "Tài khoản của bạn" ở thanh menu dưới cùng</li>
                        <li>Xem thông tin cá nhân và công nợ tổng quan</li>
                    </ol>
                </div>
                
                <div class="instruction-item">
                    <h3>Đổi mật khẩu</h3>
                    <ol>
                        <li>Vào trang tài khoản cá nhân</li>
                        <li>Tìm phần "Thay đổi mật khẩu"</li>
                        <li>Nhập mật khẩu mới và xác nhận</li>
                        <li>Nhấn "Cập nhật" để hoàn tất</li>
                    </ol>
                    <p><strong>Lưu ý:</strong> Mật khẩu mới cần có ít nhất 8 ký tự, bao gồm chữ hoa và ký tự đặc biệt</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>