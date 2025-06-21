<?php
// Đảm bảo chỉ cho phép truy cập qua phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
    exit();
}

// Bật báo lỗi trong quá trình phát triển.
// CÓ THỂ TẮT HOẶC ĐẶT MỨC ĐỘ THẤP HƠN KHI TRIỂN KHAI THỰC TẾ TRÊN SERVER PRODUCTION.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập header để trả về JSON
header('Content-Type: application/json');

// Include lớp UserManager
// Đảm bảo bạn đã đổi tên file UserManager_temp.php thành UserManager.php
require_once __DIR__ . '/../../Modules/Auth/UserManager.php';

// Lấy dữ liệu từ body của request (khi gửi bằng fetch API hoặc Axios với JSON)
$input_data = json_decode(file_get_contents("php://input"), true);

// Khởi tạo UserManager
$userManager = new UserManager();

// --- Lấy dữ liệu từ input_data và xác thực cơ bản ---
$name = $input_data['name'] ?? '';
$email = $input_data['email'] ?? '';
$password = $input_data['password'] ?? '';
$confirm_password = $input_data['confirm_password'] ?? '';

// Xác thực đầu vào
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ các trường.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Địa chỉ email không hợp lệ.']);
    exit();
}

if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu và xác nhận mật khẩu không khớp.']);
    exit();
}

if (strlen($password) < 6) { // Ví dụ: yêu cầu mật khẩu ít nhất 6 ký tự
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
    exit();
}

// --- Gọi hàm đăng ký từ UserManager ---
$result = $userManager->registerUser($name, $email, $password);

// --- Trả về phản hồi cho frontend ---
echo json_encode($result);

// Đóng kết nối database (không bắt buộc vì PDO tự động đóng khi script kết thúc)
// $userManager = null; // Không cần thiết vì PHP tự động dọn dẹp khi script kết thúc
?>