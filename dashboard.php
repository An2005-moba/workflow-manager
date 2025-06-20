<?php
// Bắt đầu session để truy cập các biến session
session_start();

// Bật báo lỗi trong quá trình phát triển (có thể tắt khi triển khai thực tế)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Kiểm tra trạng thái đăng nhập ---
// Nếu người dùng chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email']) || !isset($_SESSION['user_name'])) {
    // Điều chỉnh đường dẫn nếu cần, dựa trên vị trí của dashboard.php so với login.html
    // Nếu dashboard.php nằm ở thư mục gốc của Web_Project và login.html nằm ở Features/Auth/
    header("Location: Features/Auth/login.html"); 
    exit();
}

// Lấy thông tin người dùng từ session
$user_id = htmlspecialchars($_SESSION['user_id']); // Luôn dùng htmlspecialchars để ngăn XSS
$user_email = htmlspecialchars($_SESSION['user_email']);
$user_full_name = htmlspecialchars($_SESSION['user_name']); // Tên đầy đủ từ database
$user_role = htmlspecialchars($_SESSION['user_role'] ?? 'user'); // Mặc định là 'user' nếu không có

// --- Xử lý tên người dùng để hiển thị và tạo initials cho avatar ---
$display_user_name = $user_full_name; // Mặc định là tên đầy đủ
$avatar_initials = '';

$name_parts = explode(' ', $user_full_name);

if (count($name_parts) >= 2) {
    $last_name_for_display = array_pop($name_parts); // Lấy phần tử cuối cùng (tên chính)
    $first_and_middle_names = implode(' ', $name_parts); // Các phần còn lại (họ và tên đệm)

    // Xử lý tên để hiển thị theo định dạng "Tên Họ Đệm"
    $display_user_name = $last_name_for_display . ' ' . $first_and_middle_names;

    // Lấy ký tự đầu cho avatar: ký tự đầu của Họ và ký tự đầu của Tên chính
    $avatar_initials .= strtoupper(mb_substr($first_and_middle_names, 0, 1, 'UTF-8')); // Ký tự đầu tiên của Họ/Tên đệm
    $avatar_initials .= strtoupper(mb_substr($last_name_for_display, 0, 1, 'UTF-8')); // Ký tự đầu tiên của Tên chính
} elseif (count($name_parts) == 1 && !empty($name_parts[0])) {
    // Trường hợp chỉ có một từ trong tên
    $avatar_initials = strtoupper(mb_substr($user_full_name, 0, 2, 'UTF-8')); // Lấy 2 ký tự đầu của tên đó
}
// Nếu $user_full_name rỗng hoặc không hợp lệ, $avatar_initials sẽ vẫn rỗng

// Sử dụng mb_substr và 'UTF-8' để đảm bảo xử lý đúng các ký tự tiếng Việt
// Nơi để debug: echo "Full Name: {$user_full_name}, Display Name: {$display_user_name}, Initials: {$avatar_initials}";

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkFlow - Dashboard</title>
    <link rel="stylesheet" href="Assets/styles/main.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <header class="dashboard-header">
        <div class="dashboard-container">
            <div class="dashboard-header-content">
                <div class="dashboard-nav-left">
                    <div class="dashboard-logo">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="3" width="7" height="7" rx="1" fill="#FFFFFF"/>
                            <rect x="14" y="3" width="7" height="7" rx="1" fill="#FFFFFF"/>
                            <rect x="3" y="14" width="7" height="7" rx="1" fill="#FFFFFF"/>
                            <rect x="14" y="14" width="7" height="7" rx="1" fill="#FFFFFF"/>
                        </svg>
                        <span class="dashboard-logo-text">WorkFlow</span>
                    </div>
                    
                    <nav class="dashboard-nav">
                        <a href="#" class="dashboard-nav-item active">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Trang chủ
                        </a>
                        <a href="#" class="dashboard-nav-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                                <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                                <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                                <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Dự án
                        </a>
                        <a href="#" class="dashboard-nav-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M23 21V19C23 18.1645 22.7155 17.3541 22.2094 16.6977C21.7033 16.0414 20.9999 15.5759 20.2 15.3726" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 3.37561C16.7999 3.57896 17.5033 4.04444 18.0094 4.70077C18.5155 5.35709 18.8 6.16754 18.8 7.00305C18.8 7.83856 18.5155 8.649 18.0094 9.30533C17.5033 9.96166 16.7999 10.4271 16 10.6305" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Nhóm
                        </a>
                    </nav>
                </div>

                <div class="dashboard-nav-right">
                    <div class="dashboard-search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                            <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input type="text" placeholder="Tìm kiếm...">
                    </div>
                    
                    <button class="dashboard-notification-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <div class="dashboard-user">
                        <div class="dashboard-user-avatar"><?php echo $avatar_initials; ?></div>
                        <span class="dashboard-user-name"><?php echo $display_user_name; ?></span>
                        <?php /* Đã xóa nút đăng xuất theo yêu cầu của bạn
                        <a href="Features/Auth/logout_api.php" class="dashboard-logout-btn" title="Đăng Xuất">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17 17L22 12L17 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        */ ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-container">
            <div class="dashboard-welcome">
                <div class="dashboard-welcome-content">
                    <h1 class="dashboard-welcome-title">Chào mừng đến với WorkFlow!</h1>
                    <p class="dashboard-welcome-description">
                        Bạn chưa có dự án nào. Hãy tạo dự án đầu tiên để bắt<br>
                        đầu quản lý công việc của mình.
                    </p>
                    <button class="dashboard-create-btn">Tạo dự án mới</button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>