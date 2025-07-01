<?php
session_start();
// Nhúng các file cần thiết
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/MemberManager.php';

// Kiểm tra xem người dùng đã đăng nhập và yêu cầu có phải là POST không
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../Auth/login.html");
    exit();
}

// Lấy projectId và email từ form
$projectId = $_POST['project_id'] ?? 0;
$email = trim($_POST['email'] ?? '');

// Kiểm tra dữ liệu đầu vào
if ($projectId && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $dbConnection = getDbConnection();
        $memberManager = new MemberManager($dbConnection);
        
        // Gọi hàm để thêm thành viên bằng email
        $result = $memberManager->addMemberByEmail($projectId, $email);
        
        // Lưu thông báo kết quả vào session để hiển thị
        $_SESSION['flash_message'] = $result['message'];

    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Lỗi hệ thống: " . $e->getMessage();
    }
} else {
    $_SESSION['flash_message'] = "Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.";
}

// Chuyển hướng người dùng trở lại trang chi tiết dự án
header("Location: project_details.php?id=" . $projectId);
exit();
?>