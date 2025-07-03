<?php
session_start();
header('Content-Type: application/json');

require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

// Hàm trợ giúp để gửi phản hồi và thoát
function send_json_response($data) {
    echo json_encode($data);
    exit();
}

// Kiểm tra yêu cầu hợp lệ
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    send_json_response(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
}

// Lấy dữ liệu từ POST
$taskId = $_POST['task_id'] ?? 0;
$taskName = trim($_POST['task_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? 'Cần làm';
$assigneeIds = $_POST['assignee_ids'] ?? [];

// Kiểm tra dữ liệu đầu vào
if (empty($taskId) || empty($taskName)) {
    send_json_response(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ. ID và tên nhiệm vụ là bắt buộc.']);
}

try {
    $dbConnection = getDbConnection();
    $taskManager = new TaskManager($dbConnection);
    
    // --- PHIÊN BẢN ĐÚNG: KIỂM TRA KẾT QUẢ TỪNG BƯỚC ---

    // Bước 1: Cập nhật thông tin chính của task VÀ KIỂM TRA KẾT QUẢ
    $updateResult = $taskManager->updateTask($taskId, $taskName, $description, $status);
    if ($updateResult['status'] !== 'success') {
        // Nếu thất bại, gửi ngay lập tức phản hồi lỗi và dừng lại
        send_json_response($updateResult);
    }
    
    // Bước 2: Cập nhật lại danh sách người được giao VÀ KIỂM TRA KẾT QUẢ
    $reassignResult = $taskManager->reassignTask($taskId, $assigneeIds);
    if ($reassignResult['status'] !== 'success') {
        // Nếu thất bại, gửi ngay lập tức phản hồi lỗi và dừng lại
        send_json_response($reassignResult);
    }
    
    // Bước 3: Nếu tất cả các bước trên đều thành công, lấy thông tin mới nhất của task
    $updatedTask = $taskManager->getTaskById($taskId);
    if (!$updatedTask) {
        send_json_response(['status' => 'error', 'message' => 'Không thể lấy dữ liệu nhiệm vụ sau khi cập nhật.']);
    }

    // Bước 4: Gửi phản hồi thành công cuối cùng về cho client
    send_json_response([
        'status' => 'success', 
        'message' => 'Đã cập nhật nhiệm vụ thành công.',
        'updated_task' => $updatedTask // Gửi kèm dữ liệu mới để cập nhật UI
    ]);

} catch (Exception $e) {
    error_log("Update Task API Error: " . $e->getMessage());
    send_json_response(['status' => 'error', 'message' => 'Lỗi hệ thống khi cập nhật.']);
}
?>