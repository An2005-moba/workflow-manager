<?php
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

// Kiểm tra đăng nhập và phương thức POST
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../Auth/login.html");
    exit();
}

// Lấy dữ liệu từ form
$projectId = $_POST['project_id'] ?? 0;
$taskId = $_POST['task_id'] ?? 0;
$taskName = trim($_POST['task_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? 'To Do';
$assigneeIds = $_POST['assignee_ids'] ?? [];

$final_message = '';
$final_status = 'error'; // Mặc định là lỗi

if ($projectId && $taskId && $taskName) {
    try {
        $dbConnection = getDbConnection();
        $taskManager = new TaskManager($dbConnection);
        
        // --- Cập nhật logic xử lý ---
        // 1. Cập nhật thông tin cơ bản của task
        $updateResult = $taskManager->updateTask($taskId, $taskName, $description, $status);
        
        // 2. Cập nhật lại danh sách người được gán
        $reassignResult = $taskManager->reassignTask($taskId, $assigneeIds);
        
        // 3. Tổng hợp kết quả
        if ($updateResult['status'] === 'success' && $reassignResult['status'] === 'success') {
            $final_status = 'success';
            $final_message = 'Đã cập nhật nhiệm vụ và phân công thành công.';
        } else {
            // Nối các thông báo lỗi nếu có
            $final_message = "Có lỗi xảy ra. " . ($updateResult['message'] ?? '') . " " . ($reassignResult['message'] ?? '');
        }

    } catch (Exception $e) {
        $final_message = "Lỗi hệ thống: " . $e->getMessage();
    }
} else {
    $final_message = "Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.";
}

// Lưu thông báo vào session
$_SESSION['flash'] = [
    'message' => $final_message,
    'status' => $final_status
];

// Chuyển hướng người dùng trở lại trang chi tiết dự án
header("Location: ../Projects/project_details.php?id=" . $projectId);
exit();
?>