<?php
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../Auth/login.html"); exit();
}

$projectId = $_POST['project_id'] ?? 0;
$taskName = trim($_POST['task_name'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($projectId && $taskName) {
    $db = getDbConnection();
    $taskManager = new TaskManager($db);
    $result = $taskManager->createTask($projectId, $taskName, $description);
    $_SESSION['flash'] = ['status' => $result['status'], 'message' => $result['message']];
} else {
    $_SESSION['flash'] = ['status' => 'error', 'message' => 'Tên nhiệm vụ không được để trống.'];
}
header("Location: ../Projects/project_details.php?id=" . $projectId);
exit();
?>