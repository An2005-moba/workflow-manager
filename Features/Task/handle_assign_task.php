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
$assigneeId = $_POST['assignee_id'] ?? 0; // ID của một người dùng được chọn

if ($projectId && $taskId) {
    try {
        $dbConnection = getDbConnection();
        $taskManager = new TaskManager($dbConnection);
        
        // SỬA LỖI: Gọi hàm reassignTask thay vì assignTask
        // và truyền ID người dùng vào trong một mảng.
        // Nếu assigneeId = 0 (tức là chọn "Bỏ gán"), chúng ta truyền vào một mảng rỗng.
        $assigneeIdsArray = ($assigneeId > 0) ? [$assigneeId] : [];
        $result = $taskManager->reassignTask($taskId, $assigneeIdsArray);
        
        $_SESSION['flash'] = [
            'message' => $result['message'],
            'status' => $result['status']
        ];

    } catch (Exception $e) {
        $_SESSION['flash'] = [
            'message' => "Lỗi hệ thống: " . $e->getMessage(),
            'status' => 'error'
        ];
    }
} else {
     $_SESSION['flash'] = [
        'message' => "Dữ liệu không hợp lệ.",
        'status' => 'error'
    ];
}

// Chuyển hướng người dùng trở lại trang chi tiết dự án
header("Location: ../Projects/project_details.php?id=" . $projectId);
exit();
?>