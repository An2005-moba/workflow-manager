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
$assigneeId = $_POST['assignee_id'] ?? 0;

if ($projectId && $taskId) {
    try {
        $dbConnection = getDbConnection();
        $taskManager = new TaskManager($dbConnection);
        
        $result = $taskManager->assignTask($taskId, $assigneeId);
        
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
header("Location: project_details.php?id=" . $projectId);
exit();
?>