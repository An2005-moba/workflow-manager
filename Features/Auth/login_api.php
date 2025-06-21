<?php
// Bắt đầu session để lưu trữ thông tin người dùng sau khi đăng nhập
session_start();

// Đảm bảo chỉ cho phép truy cập qua phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
    exit();
}

// Bật báo lỗi trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập header để trả về JSON
header('Content-Type: application/json');

// Include lớp UserManager
require_once __DIR__ . '/../../Modules/Auth/UserManager.php';

// Lấy dữ liệu từ body của request
$input_data = json_decode(file_get_contents("php://input"), true);

$email = $input_data['email'] ?? '';
$password = $input_data['password'] ?? '';

// Xác thực đầu vào cơ bản
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ email và mật khẩu.']);
    exit();
}

// Khởi tạo UserManager
$userManager = new UserManager();

// Gọi hàm đăng nhập từ UserManager
// Chúng ta sẽ định nghĩa hàm loginUser() trong UserManager ở bước tiếp theo
$result = $userManager->loginUser($email, $password);

if ($result['status'] === 'success') {
    // Đăng nhập thành công, lưu thông tin người dùng vào session
    // (Giả sử $result['user'] chứa thông tin người dùng)
    $_SESSION['user_id'] = $result['user']['id'];
    $_SESSION['user_email'] = $result['user']['email'];
    $_SESSION['user_name'] = $result['user']['name'];
    $_SESSION['user_role'] = $result['user']['role'];
    // ... bạn có thể lưu thêm các thông tin khác
}

echo json_encode($result);

exit();
?>