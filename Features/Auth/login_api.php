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

// KHÔNG CẦN header('Content-Type: application/json'); NẾU BẠN CHUYỂN HƯỚNG BẰNG PHP
// header('Content-Type: application/json'); // <-- COMMENT HOẶC XÓA DÒNG NÀY

require_once __DIR__ . '/../../Modules/Auth/UserManager.php';

$input_data = json_decode(file_get_contents("php://input"), true);

$email = $input_data['email'] ?? '';
$password = $input_data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ email và mật khẩu.']);
    exit();
}

$userManager = new UserManager();

$result = $userManager->loginUser($email, $password);

// --- ĐOẠN CODE CẦN CHỈNH SỬA TẠI ĐÂY ---
if ($result['status'] === 'success') {
    // Đăng nhập thành công, lưu thông tin người dùng vào session
    $_SESSION['user_id'] = $result['user']['id'];
    $_SESSION['user_email'] = $result['user']['email'];
    $_SESSION['user_name'] = $result['user']['name'];
    $_SESSION['user_role'] = $result['user']['role']; // Đảm bảo có cột 'role' trong bảng users

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