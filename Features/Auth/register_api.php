<?php
// C:/xampp/htdocs/Web_Project/Features/Auth/register_api.php

// Đảm bảo chỉ cho phép truy cập qua phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
    exit();
}

// Bật báo lỗi trong quá trình phát triển.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập header để trả về JSON
header('Content-Type: application/json');

// Include lớp UserManager
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
// Lấy thêm các trường mới
$phone_number = $input_data['phone_number'] ?? null; // Có thể là null nếu không gửi
$date_of_birth = $input_data['date_of_birth'] ?? null; // Có thể là null nếu không gửi
$address = $input_data['address'] ?? null; // Có thể là null nếu không gửi

// Xác thực đầu vào CƠ BẢN cho các trường bắt buộc
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ các trường bắt buộc (Họ tên, Email, Mật khẩu).']);
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

// Xác thực bổ sung cho các trường mới (tùy chọn, nhưng nên có)
if (!empty($phone_number) && !preg_match('/^\d{10,11}$/', $phone_number)) { // Kiểm tra 10 hoặc 11 chữ số
    echo json_encode(['status' => 'error', 'message' => 'Số điện thoại không hợp lệ (chỉ chấp nhận 10 hoặc 11 chữ số).']);
    exit();
}

if (!empty($date_of_birth)) {
    // Simple date validation: check if it's a valid date format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth) || !strtotime($date_of_birth)) {
        echo json_encode(['status' => 'error', 'message' => 'Ngày sinh không hợp lệ (định dạng YYYY-MM-DD).']);
        exit();
    }
    $today = new DateTime();
    $birthDate = new DateTime($date_of_birth);
    if ($birthDate > $today) {
        echo json_encode(['status' => 'error', 'message' => 'Ngày sinh không thể ở tương lai.']);
        exit();
    }
}

if (!empty($address) && strlen($address) > 512) {
    echo json_encode(['status' => 'error', 'message' => 'Địa chỉ quá dài (tối đa 512 ký tự).']);
    exit();
}


// --- Gọi hàm đăng ký từ UserManager ---
// Hàm registerUser đã được sửa đổi để lưu mật khẩu plaintext VÀ các trường mới
$result = $userManager->registerUser($name, $email, $password, $phone_number, $date_of_birth, $address);

// --- Trả về phản hồi cho frontend ---
echo json_encode($result);

?>