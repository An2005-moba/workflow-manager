<?php
session_start();
// Thêm dòng này để đảm bảo client biết đây là phản hồi JSON
header('Content-Type: application/json');

require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

// Hàm trợ giúp để gửi phản hồi JSON và thoát
function send_json_response($data) {
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    send_json_response(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
}

// Giữ nguyên project_id để có thể dùng trong tương lai nếu cần
$projectId = $_POST['project_id'] ?? 0; 
$taskId = $_POST['task_id'] ?? 0;

if ($taskId) {
    try {
        $db = getDbConnection();
        $taskManager = new TaskManager($db);
        $result = $taskManager->deleteTask($taskId);
        // Gửi kết quả dưới dạng JSON
        send_json_response($result);
    } catch (Exception $e) {
        error_log("Delete Task API Error: " . $e->getMessage());
        send_json_response(['status' => 'error', 'message' => 'Lỗi hệ thống khi xóa nhiệm vụ.']);
    }
} else {
    // Gửi lỗi dưới dạng JSON
    send_json_response(['status' => 'error', 'message' => 'ID nhiệm vụ không hợp lệ.']);
}

// Xóa các dòng header("Location: ...") và $_SESSION['flash'] cũ
?>