<?php
// File: /Features/Projects/handle_leave_project.php (Phiên bản tương thích)
session_start();
header('Content-Type: application/json');

require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/MemberManager.php';
require_once '../../Modules/Projects/ProjectManager.php';

// 1. Kiểm tra bảo mật
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

// 2. Lấy dữ liệu (Dùng isset() thay cho ??)
$projectId = isset($_POST['project_id']) ? $_POST['project_id'] : 0;
$userId = $_SESSION['user_id'];

if (!$projectId) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID của dự án.']);
    exit();
}

try {
    $db = getDbConnection();
    $projectManager = new ProjectManager($db);
    
    // 3. Kiểm tra quyền
    $projectDetails = $projectManager->getProjectById($projectId);
    if ($projectDetails['status'] === 'success' && $projectDetails['project']['created_by_user_id'] == $userId) {
        echo json_encode(['status' => 'error', 'message' => 'Bạn là người tạo dự án, không thể rời khỏi. Bạn chỉ có thể xóa dự án này.']);
        exit();
    }
    
    // 4. Thực hiện rời nhóm
    $memberManager = new MemberManager($db);
    $result = $memberManager->removeMember($projectId, $userId);
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Leave Project Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, không thể rời khỏi dự án.']);
}

exit();
?>