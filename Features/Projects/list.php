<?php
// Bắt đầu session để truy cập thông tin người dùng
session_start();

// Bật báo lỗi để dễ dàng debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Kiểm tra đăng nhập ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Auth/login.html"); // Chuyển hướng nếu chưa đăng nhập
    exit();
}

// --- Lấy dữ liệu dự án ---
// Nhúng các file cần thiết: kết nối DB và lớp quản lý dự án
// Sử dụng đường dẫn tương đối từ vị trí file `list.php`
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/ProjectManager.php';

try {
    // Tạo kết nối CSDL
    $dbConnection = getDbConnection();
    // Khởi tạo ProjectManager với kết nối CSDL
    $projectManager = new ProjectManager($dbConnection);
    $userId = $_SESSION['user_id'];
    // Lấy tất cả các dự án
    // Sẽ cần truyền tham số tìm kiếm vào đây nếu bạn muốn tìm kiếm server-side
    // Hiện tại, chúng ta sẽ lấy tất cả và lọc bằng JS (client-side)
    $result = $projectManager->getProjectsForUser($userId);

    if ($result['status'] === 'success') {
        $projects = $result['projects'];
    } else {
        // Nếu có lỗi, gán mảng rỗng và có thể hiển thị lỗi
        $projects = [];
        $errorMessage = $result['message'];
    }
} catch (Exception $e) {
    $projects = [];
    $errorMessage = "Lỗi hệ thống: " . $e->getMessage();
}

// --- Lấy thông tin người dùng từ session để hiển thị trên header ---
$user_full_name = htmlspecialchars($_SESSION['user_name']);
$display_user_name = $user_full_name;
$avatar_initials = '';
$name_parts = explode(' ', $user_full_name);

if (count($name_parts) >= 2) {
    $last_name_part = array_pop($name_parts);
    $first_and_middle_parts = implode(' ', $name_parts);
    $avatar_initials = strtoupper(mb_substr($first_and_middle_parts, 0, 1, 'UTF-8')) . strtoupper(mb_substr($last_name_part, 0, 1, 'UTF-8'));
    $display_user_name = $last_name_part . ' ' . $first_and_middle_parts;
} elseif (!empty($user_full_name)) {
    $avatar_initials = strtoupper(mb_substr($user_full_name, 0, 2, 'UTF-8'));
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách dự án - WorkFlow</title>
    <link rel="stylesheet" href="../../Assets/styles/features/projects/list.css?v=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<div id="action-mode-bar" class="action-mode-bar" style="display: none;">
    <div class="dashboard-container">
        <span id="action-mode-message"></span>
        <button id="cancel-action-btn" class="cancel-action-btn">Hủy bỏ</button>
    </div>
</div>



<body class="dashboard-body">
    <header class="dashboard-header">
        <div class="dashboard-container">
            <div class="dashboard-header-content dashboard-header-content-with-search">
                <div class="dashboard-nav-left">
                    <a href="../../dashboard.php" class="dashboard-logo-link">
                        <div class="dashboard-logo">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" rx="1" fill="#FFFFFF" />
                                <rect x="14" y="3" width="7" height="7" rx="1" fill="#FFFFFF" />
                                <rect x="3" y="14" width="7" height="7" rx="1" fill="#FFFFFF" />
                                <rect x="14" y="14" width="7" height="7" rx="1" fill="#FFFFFF" />
                            </svg>
                            <span class="dashboard-logo-text">WorkFlow</span>
                        </div>
                    </a>
                    <nav class="dashboard-nav">
                        <a href="../../dashboard.php" class="dashboard-nav-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Trang chủ
                        </a>
                        <a href="#" class="dashboard-nav-item active">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                                <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                                <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                                <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                            </svg>
                            Dự án
                        </a>
                    </nav>
                </div>


                <div class="dashboard-nav-right">
                    <div class="search-bar">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M21 21L16.65 16.65" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <input type="text" id="projectSearchInput" class="search-input" placeholder="Tìm kiếm dự án...">
                    </div>


                    <button id="userDropdownToggle" class="dashboard-user" aria-haspopup="true" aria-expanded="false">
                        <div class="dashboard-user-avatar"><?php echo $avatar_initials; ?></div>
                        <span class="dashboard-user-name"><?php echo $display_user_name; ?></span>
                        <svg class="dashboard-user-dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div class="dashboard-user-dropdown" id="userDropdownMenu">
                        <a href="../../Features/User/profile.html" class="dashboard-dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" />
                            </svg>
                            Thông tin cá nhân
                        </a>
                        <a href="#" class="dashboard-dropdown-item" data-action="edit-profile">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Sửa dự án
                        </a>
                       

                        <a href="#" class="dashboard-dropdown-item dashboard-delete-item" data-action="delete-project">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Xóa dự án
                        </a>
                         <a href="#" class="dashboard-dropdown-item dashboard-leave-item" data-action="leave-project">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 3H7C5.89543 3 5 3.89543 5 5V19C5 20.1046 5.89543 21 7 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M19 12H9M19 12L16 15M19 12L16 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Rời nhóm dự án
                        </a>
                        <a href="../../Features/User/Settings/account_settings.html" class="dashboard-dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12 2V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12 20V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M4 12H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M22 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M4.93 4.93L6.34 6.34" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M17.66 17.66L19.07 19.07" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M4.93 19.07L6.34 17.66" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M17.66 6.34L19.07 4.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Cài đặt tài khoản
                        </a>
                        <div class="dashboard-dropdown-divider"></div>
                        <a href="../../Features/Auth/logout_api.php" class="dashboard-dropdown-item dashboard-logout-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M17 17L22 12L17 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M22 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-container">
            <div class="project-list-container">
                <?php if (isset($errorMessage)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php elseif (empty($projects)): ?>
                    <div class="empty-state" id="emptyState">
                        <h2>Bạn chưa có dự án nào.</h2>
                        <p>Hãy bắt đầu bằng cách tạo dự án đầu tiên của bạn.</p>
                        <a href="create_project.html" class="create-project-btn-empty">Tạo dự án ngay</a>
                    </div>
                <?php else: ?>
                    <div class="project-grid">
              <?php foreach ($projects as $project): ?>
    <a href="project_details.php?id=<?php echo $project['id']; ?>" class="project-link-card">
        <div class="project-card"
             data-project-id="<?php echo htmlspecialchars($project['id'] ?? 0); ?>"
             data-created-by="<?php echo htmlspecialchars($project['created_by_user_id'] ?? 0); ?>"
             data-project-name="<?php echo htmlspecialchars(mb_strtolower($project['project_name'] ?? 'Không tên', 'UTF-8')); ?>"
             data-project-description="<?php echo htmlspecialchars(mb_strtolower($project['description'] ?? '', 'UTF-8')); ?>">

            <div class="project-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>

            <div class="project-card-content">
                <div class="project-card-header">
                    <h3 class="project-card-title"><?php echo htmlspecialchars($project['project_name'] ?? 'Dự án không tên'); ?></h3>
                </div>
                <div class="project-card-body">
                    <p class="project-card-description">
                        <?php echo !empty($project['description']) ? htmlspecialchars($project['description']) : '<em>Không có mô tả.</em>'; ?>
                    </p>
                </div>
                <div class="project-card-footer">
                    <span class="project-card-creator">Người tạo: <?php echo htmlspecialchars($project['created_by_name'] ?? 'N/A'); ?></span>
                    <span class="project-card-date">
                        <?php
                            // Bọc trong if để đảm bảo 'creation_date' tồn tại và hợp lệ
                            if (!empty($project['creation_date'])) {
                                try {
                                    $date = new DateTime($project['creation_date']);
                                    echo $date->format('d/m/Y');
                                } catch (Exception $e) {
                                    echo 'N/A';
                                }
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </span>
                </div>
            </div>

        </div>
    </a>
<?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
    const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
</script>
   <footer class="dashboard-footer-enhanced">
        <div class="dashboard-container">
            <div class="dashboard-footer-grid">
                <div class="footer-column about-column">
                    <div class="dashboard-logo">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="3" width="7" height="7" rx="1" fill="#4F46E5"/>
                            <rect x="14" y="3" width="7" height="7" rx="1" fill="#4F46E5"/>
                            <rect x="3" y="14" width="7" height="7" rx="1" fill="#4F46E5"/>
                            <rect x="14" y="14" width="7" height="7" rx="1" fill="#4F46E5"/>
                        </svg>
                        <span class="dashboard-logo-text-footer">WorkFlow</span>
                    </div>
                    <p class="footer-tagline">
                        Nền tảng quản lý dự án giúp bạn và đội nhóm làm việc hiệu quả hơn.
                    </p>
                    <p class="footer-copyright">
                        &copy; <?php echo date("Y"); ?> WorkFlow. All Rights Reserved.
                    </p>
                </div>

                <div class="footer-column">
                    <h4 class="footer-column-title">Sản phẩm</h4>
                    <ul class="footer-links">
                        <li><a href="#">Tính năng</a></li>
                        <li><a href="#">Bảng giá</a></li>
                        <li><a href="#">Demo</a></li>
                        <li><a href="#">Tích hợp</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4 class="footer-column-title">Công ty</h4>
                    <ul class="footer-links">
                        <li><a href="#">Về chúng tôi</a></li>
                        <li><a href="#">Tuyển dụng</a></li>
                        <li><a href="#">Báo chí</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>

                <div class="footer-column">
    <h4 class="footer-column-title">Thông tin liên hệ</h4>
    
    <ul class="footer-contact-list">
        <li>
            <i class="fas fa-envelope icon-contact"></i>
            <a href="mailto:your-email@gmail.com">SupportWorkflow@gmail.com</a>
        </li>
        <li>
            <i class="fas fa-phone icon-contact"></i>
            <span>083 3812 3456</span>
        </li>
        <li>
            <i class="fas fa-location-dot icon-contact"></i>
            <span>12 Đường Ngô Quyền, Quận 1, TP. Hồ Chí Minh</span>
        </li>
        <li>
            <i class="fab fa-github icon-contact"></i>
            <a href="https://an2005-moba.github.io/GiuaKi_Nhom2/" target="_blank">github.com/nhom2DuAn</a>
        </li>
        <li>
            <i class="fab fa-facebook icon-contact"></i>
            <a href="https://an2005-moba.github.io/GiuaKi_Nhom2" target="_blank">facebook.com/nhom2DuAn</a>
        </li>
    </ul>
</div>
            </div>
        </div>
    </footer>
   
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" integrity="sha512-fD9DI5bZwQxOi7MhYWnnNPlvXdp/2Pj3XSTRrFs5FQa4mizyGLnJcN6tuvUS6LbmgN1ut+XGSABKvjN0H6Aoow==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="../../Features/Projects/projects.js"></script>
</html>