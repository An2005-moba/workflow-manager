<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra các yêu cầu cơ bản
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

// Nhúng các tệp cần thiết
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

// Lấy dữ liệu từ yêu cầu POST
$commentId = $_POST['comment_id'] ?? 0;
$userId = $_SESSION['user_id'];

if (empty($commentId)) {
    echo json_encode(['status' => 'error', 'message' => 'ID bình luận không được để trống.']);
    exit();
}

try {
    $db = getDbConnection();
    $taskManager = new TaskManager($db);

    // Gọi hàm xóa bình luận, hàm này đã có sẵn logic kiểm tra quyền sở hữu
    $result = $taskManager->deleteComment($commentId, $userId);

    // Trả kết quả về cho JavaScript
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Delete Comment Handler Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi xóa bình luận.']);
}

exit();
?>