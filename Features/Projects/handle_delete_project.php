<?php
session_start();
// Thiết lập header để cho trình duyệt biết đây là phản hồi JSON
header('Content-Type: application/json');

// Đường dẫn an toàn đến các file cần thiết
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/ProjectManager.php';

// Kiểm tra bảo mật cơ bản
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    // Trả về lỗi và dừng thực thi
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ hoặc chưa đăng nhập.']);
    exit();
}

// Lấy project_id từ dữ liệu POST
$projectId = $_POST['project_id'] ?? 0;

// Nếu không có project_id, trả về lỗi
if (!$projectId) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID của dự án.']);
    exit();
}

try {
    $db = getDbConnection();
    $pm = new ProjectManager($db);
    
    // -- (Tùy chọn nhưng khuyến nghị) Kiểm tra xem người dùng có quyền xóa dự án này không --
    // Ví dụ: chỉ người tạo mới được xóa
    $projectDetails = $pm->getProjectById($projectId);
    if ($projectDetails['status'] === 'success' && $projectDetails['project']['created_by_user_id'] != $_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xóa dự án này.']);
        exit();
    }
    
    // Thực hiện xóa dự án
    $result = $pm->deleteProject($projectId);
    
    // Trả về kết quả dưới dạng JSON
    echo json_encode($result);

} catch (Exception $e) {
    // Bắt các lỗi ngoại lệ khác và trả về lỗi
    error_log("Handle Delete Project Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, không thể xóa dự án.']);
}

exit();
?>