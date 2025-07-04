<?php
session_start();
header('Content-Type: application/json');

require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

function send_json_response($data) {
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    send_json_response(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
}

$projectId = $_POST['project_id'] ?? 0; 
$taskId = $_POST['task_id'] ?? 0;

if ($taskId && $projectId) {
    try {
        $db = getDbConnection();
        $taskManager = new TaskManager($db);
        $result = $taskManager->deleteTask($taskId);
        
        // ---- BẮT ĐẦU THAY ĐỔI ----
        // Nếu xóa thành công, tính lại tiến độ và thêm vào kết quả trả về
        if ($result['status'] === 'success') {
            $projectProgress = $taskManager->getProjectProgress($projectId);
            $result['project_progress'] = $projectProgress;
        }
        // ---- KẾT THÚC THAY ĐỔI ----

        send_json_response($result);
    } catch (Exception $e) {
        error_log("Delete Task API Error: " . $e->getMessage());
        send_json_response(['status' => 'error', 'message' => 'Lỗi hệ thống khi xóa nhiệm vụ.']);
    }
} else {
    send_json_response(['status' => 'error', 'message' => 'ID nhiệm vụ hoặc dự án không hợp lệ.']);
}
?>