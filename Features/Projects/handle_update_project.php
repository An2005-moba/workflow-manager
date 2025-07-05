<?php
// File: handle_update_project.php (File xử lý việc cập nhật)
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/ProjectManager.php';

// 1. Kiểm tra phương thức và đăng nhập
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['user_id'])) {
    // Nếu không phải POST request hoặc chưa đăng nhập, chuyển về trang danh sách
    header("Location: list.php");
    exit();
}

// 2. Lấy dữ liệu từ form
$projectId = trim($_POST['project_id'] ?? '');
$projectName = trim($_POST['project_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$userId = $_SESSION['user_id'];

// 3. Xác thực dữ liệu cơ bản
if (empty($projectId) || empty($projectName)) {
    // Nếu thiếu ID hoặc tên dự án, tạo thông báo lỗi và quay lại
    $_SESSION['flash'] = ['status' => 'error', 'message' => 'Tên dự án và ID không được để trống.'];
    // Quay lại trang sửa với ID cũ nếu có thể
    $redirectUrl = $projectId ? "edit_project.php?id=$projectId" : "list.php";
    header("Location: $redirectUrl");
    exit();
}

try {
    $db = getDbConnection();
    $pm = new ProjectManager($db);

    // 4. (Khuyến nghị) Kiểm tra quyền sở hữu trước khi cập nhật
    $projectDetails = $pm->getProjectById($projectId);
    if ($projectDetails['status'] !== 'success' || $projectDetails['project']['created_by_user_id'] != $userId) {
        $_SESSION['flash'] = ['status' => 'error', 'message' => 'Bạn không có quyền cập nhật dự án này.'];
        header("Location: list.php");
        exit();
    }
    
    // 5. Gọi hàm cập nhật
    $result = $pm->updateProject($projectId, $projectName, $description);

    // 6. Lưu kết quả vào session flash message
    $_SESSION['flash'] = $result;

} catch (Exception $e) {
    // Nếu có lỗi hệ thống
    $_SESSION['flash'] = ['status' => 'error', 'message' => 'Lỗi hệ thống, không thể cập nhật dự án.'];
    error_log("Update Project Error: " . $e->getMessage());
}

// 7. Chuyển hướng về trang danh sách để hiển thị thông báo
header("Location: list.php");
exit();

?>