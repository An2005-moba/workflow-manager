<?php
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../Auth/login.html"); exit();
}

$projectId = $_POST['project_id'] ?? 0;
$taskId = $_POST['task_id'] ?? 0;
$taskName = trim($_POST['task_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = trim($_POST['status'] ?? 'To Do');

if ($taskId && $taskName) {
    $db = getDbConnection();
    $taskManager = new TaskManager($db);
    $result = $taskManager->updateTask($taskId, $taskName, $description, $status);
    $_SESSION['flash'] = ['status' => $result['status'], 'message' => $result['message']];
} else {
    $_SESSION['flash'] = ['status' => 'error', 'message' => 'Dữ liệu cập nhật không hợp lệ.'];
}
header("Location: ../Projects/project_details.php?id=" . $projectId);
exit();
?>