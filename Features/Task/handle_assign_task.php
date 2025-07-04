<?php
session_start();

// --- Nhúng các file cần thiết ---
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';
require_once '../../Modules/Projects/ProjectManager.php';

/**
 * Hàm trợ giúp để gửi phản hồi và dừng kịch bản.
 */
function send_response_and_exit($status, $message, $projectId = null) {
    $_SESSION['flash'] = [
        'status' => $status,
        'message' => $message
    ];
    $location = $projectId ? "../Projects/project_details.php?id=" . $projectId : "../Projects/list.php";
    header("Location: " . $location);
    exit();
}

// --- 1. Kiểm tra yêu cầu cơ bản ---
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../Auth/login.html");
    exit();
}

// --- 2. Lấy và xác thực dữ liệu đầu vào ---
$projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$assigneeId = filter_input(INPUT_POST, 'assignee_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

if (!$projectId || !$taskId || $assigneeId === false) {
    send_response_and_exit('error', 'Dữ liệu không hợp lệ.', $projectId);
}

// --- 3. Logic chính: Kiểm tra quyền và thực thi ---
// --- 3. Logic chính: Kiểm tra quyền và thực thi ---
// --- 3. Logic chính: Kiểm tra quyền và thực thi ---
try {
    $dbConnection = getDbConnection();
    $projectManager = new ProjectManager($dbConnection);
    $projectResult = $projectManager->getProjectById($projectId);

    // --- BẮT ĐẦU MÃ GỠ LỖI MỚI ---
    $debug_data = [
        'debug_mode' => true,
        'message' => 'Đây là dữ liệu gỡ lỗi từ máy chủ.',
        'project_id' => $projectId,
        'creator_id' => $projectResult['project']['created_by_user_id'] ?? 'Lỗi: không tìm thấy',
        'current_user_id' => $_SESSION['user_id'] ?? 'Lỗi: không có trong Session'
    ];

    header('Content-Type: application/json');
    echo json_encode($debug_data);
    exit;
    // --- KẾT THÚC MÃ GỠ LỖI MỚI ---

} catch (Exception $e) {
    error_log("Assign Task Error: " . $e->getMessage());
    // Trả về lỗi dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi gỡ lỗi.']);
    exit();
}
?>