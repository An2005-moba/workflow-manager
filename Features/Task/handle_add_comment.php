<?php
session_start();
header('Content-Type: application/json');

require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$taskId = $_POST['task_id'] ?? 0;
$commentText = trim($_POST['comment_text'] ?? '');
$userId = $_SESSION['user_id'];

if (empty($taskId) || empty($commentText)) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

try {
    $db = getDbConnection();
    $taskManager = new TaskManager($db);
    $result = $taskManager->addComment($taskId, $userId, $commentText);
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Add Comment Handler Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi thêm bình luận.']);
}

exit();
?>