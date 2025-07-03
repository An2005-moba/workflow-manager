<?php
/**
 * API endpoint để xử lý yêu cầu tạo dự án.
 * PHIÊN BẢN SỬA LỖI: File này sẽ quản lý việc nạp các file phụ thuộc.
 */

// Bắt đầu session để lấy user_id
session_start();

// Luôn đặt header để trả về JSON
header('Content-Type: application/json');

// --- QUẢN LÝ VIỆC NẠP FILE TẬP TRUNG ---
// Giả định rằng cả 3 file (create_project.php, ProjectManager.php, db_connection.php)
// đều nằm trong cùng một thư mục để đơn giản hóa.
// Nếu chúng ở các thư mục khác nhau, bạn chỉ cần điều chỉnh đường dẫn ở đây.
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/ProjectManager.php';

function send_response($data) {
    echo json_encode($data);
    exit();
}

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    send_response(['status' => 'error', 'message' => 'Bạn phải đăng nhập để tạo dự án.']);
}

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(['status' => 'error', 'message' => 'Phương thức yêu cầu không hợp lệ.']);
}

// Lấy dữ liệu
$projectName = isset($_POST['project_name']) ? trim($_POST['project_name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$userId = $_SESSION['user_id']; 
try {
    // 1. Lấy kết nối từ file db_connection.php
    $connection = getDbConnection();

    // 2. Khởi tạo đối tượng ProjectManager
    $projectManager = new ProjectManager($connection);

    // 3. Gọi phương thức createProject để xử lý logic
    $result = $projectManager->createProject($projectName, $description, $userId);

    // 4. KIỂM TRA KẾT QUẢ VÀ CHUYỂN HƯỚNG
    if ($result['status'] === 'success') {
        // Nếu thành công, lưu thông báo và chuyển hướng đến list.php
        $_SESSION['flash'] = ['status' => 'success', 'message' => 'Dự án đã được tạo thành công!'];
        header("Location: list.php");
        exit(); // Dừng script ngay sau khi chuyển hướng
    } else {
        // Nếu thất bại, trả về lỗi dưới dạng JSON (để JavaScript xử lý)
        send_response($result);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    send_response(['status' => 'error', 'message' => 'Đã xảy ra lỗi hệ thống nghiêm trọng.']);
}
?>
