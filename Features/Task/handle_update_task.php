<?php
session_start();
header('Content-Type: application/json');

// Nhúng các file cần thiết
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';
require_once '../../Modules/Projects/ProjectManager.php';

function send_json_response($data)
{
    echo json_encode($data);
    exit();
}

// Kiểm tra yêu cầu cơ bản
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    send_json_response(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
}

// --- Lấy và xác thực dữ liệu ---
$taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$taskName = trim($_POST['task_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? 'Cần làm';
$newAssigneeIds = array_map('intval', $_POST['assignee_ids'] ?? []);

// --- THÊM 1: Lấy dữ liệu deadline ---
$deadline = $_POST['deadline'] ?? null;

if (empty($taskId) || empty($taskName) || empty($projectId)) {
    send_json_response(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ. ID và tên nhiệm vụ là bắt buộc.']);
}

try {
    $dbConnection = getDbConnection();
    $taskManager = new TaskManager($dbConnection);

    // Lấy thông tin gốc của nhiệm vụ để kiểm tra thay đổi
    $originalTask = $taskManager->getTaskById($taskId);
    if (!$originalTask) {
        send_json_response(['status' => 'error', 'message' => 'Không tìm thấy nhiệm vụ.']);
    }
    $originalStatus = $originalTask['status'];
    $originalAssigneeIds = $taskManager->getAssigneeIdsForTask($taskId);

    sort($originalAssigneeIds);
    sort($newAssigneeIds);
    $assignmentChanged = ($originalAssigneeIds !== $newAssigneeIds);
    $statusChangedToApproved = ($status === 'Đã duyệt' && $originalStatus !== 'Đã duyệt');

    // --- LOGIC PHÂN QUYỀN (GIỮ NGUYÊN) ---
    if ($assignmentChanged || $statusChangedToApproved) {
        $projectManager = new ProjectManager($dbConnection);
        $projectResult = $projectManager->getProjectById($projectId);

        if ($projectResult['status'] !== 'success' || !isset($projectResult['project'])) {
            send_json_response(['status' => 'error', 'message' => 'Dự án không tồn tại.']);
        }

        $projectCreatorId = $projectResult['project']['created_by_user_id'];
        $currentUserId = $_SESSION['user_id'];

        if ($projectCreatorId != $currentUserId) {
            if ($assignmentChanged) {
                send_json_response(['status' => 'error', 'message' => 'Thao tác thất bại: Chỉ người tạo dự án mới có quyền thay đổi phân công.']);
            }
            if ($statusChangedToApproved) {
                send_json_response(['status' => 'error', 'message' => 'Thao tác thất bại: Chỉ người tạo dự án mới có quyền duyệt nhiệm vụ.']);
            }
        }
    }
    // --- KẾT THÚC LOGIC PHÂN QUYỀN ---

    // Luôn cho phép cập nhật các thông tin cơ bản của task
    // --- THÊM 2: Truyền biến $deadline vào hàm updateTask ---
    $updateResult = $taskManager->updateTask($taskId, $taskName, $description, $status, $deadline);
    if ($updateResult['status'] !== 'success') {
        send_json_response($updateResult);
    }

    // Nếu có sự thay đổi phân công, thực hiện gán lại
    if ($assignmentChanged) {
        $reassignResult = $taskManager->reassignTask($taskId, $newAssigneeIds);
        if ($reassignResult['status'] !== 'success') {
            send_json_response($reassignResult);
        }
    }

    // Lấy lại thông tin task đã cập nhật để gửi về giao diện
    $updatedTask = $taskManager->getTaskById($taskId);
    if (!$updatedTask) {
        send_json_response(['status' => 'error', 'message' => 'Không thể lấy dữ liệu nhiệm vụ sau khi cập nhật.']);
    }
    
    // --- THÊM 3: Định dạng thông tin deadline trước khi gửi về ---
    $updatedTask['deadline_info'] = get_deadline_info($updatedTask['deadline']);

    // Gửi về phản hồi thành công
    send_json_response([
        'status' => 'success',
        'message' => 'Đã cập nhật nhiệm vụ thành công.',
        'updated_task' => $updatedTask
    ]);

} catch (Exception $e) {
    error_log("Update Task API Error: " . $e->getMessage());
    send_json_response(['status' => 'error', 'message' => 'Lỗi hệ thống khi cập nhật.']);
}
?>