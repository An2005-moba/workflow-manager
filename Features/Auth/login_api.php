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
require_once __DIR__ . '/../../Modules/Auth/SessionManager.php'; // <-- Đảm bảo dòng này TỒN TẠI

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
    $sessionManager = new SessionManager(); // <-- THÊM DÒNG NÀY
    $sessionManager->login($result['user']); // <-- THAY THẾ CÁC GÁN $_SESSION BẰNG DÒNG NÀY

    // Chuyển hướng trình duyệt sang dashboard.php
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