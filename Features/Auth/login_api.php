<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Không cần header('Content-Type: application/json'); nếu bạn chuyển hướng bằng PHP
// header('Content-Type: application/json'); 

require_once __DIR__ . '/../../Modules/Auth/UserManager.php';
require_once __DIR__ . '/../../Modules/Auth/SessionManager.php'; // Đảm bảo dòng này TỒN TẠI

$input_data = json_decode(file_get_contents("php://input"), true);

$email = $input_data['email'] ?? '';
$password = $input_data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ email và mật khẩu.']);
    exit();
}

$userManager = new UserManager();

// Gọi hàm loginUser đã được sửa đổi để so sánh mật khẩu plaintext
$result = $userManager->loginUser($email, $password);

if ($result['status'] === 'success') {
    // Đăng nhập thành công, sử dụng SessionManager để lưu thông tin người dùng vào session
    $sessionManager = new SessionManager(); 
    $sessionManager->login($result['user']); 

    // Chuyển hướng trình duyệt sang dashboard.php
    // Đường dẫn này cần CHÍNH XÁC từ thư mục hiện tại của login_api.php
    // login_api.php nằm trong Web_Project/Features/Auth/
    // dashboard.php nằm trong Web_Project/
    // Vậy đường dẫn là ../../dashboard.php
    header('Location: ../../dashboard.php'); 
    exit(); // RẤT QUAN TRỌNG: Dừng script sau khi chuyển hướng
} else {
    // Nếu đăng nhập thất bại, trả về JSON để frontend xử lý thông báo lỗi
    // Lúc này thì header Content-Type mới cần
    header('Content-Type: application/json'); 
    echo json_encode($result);
    exit();
}
?>