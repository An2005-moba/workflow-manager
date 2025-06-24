<?php
// C:/xampp/htdocs/Web_Project/Features/User/update_profile_api.php

// Bật báo lỗi để dễ dàng debug trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Đảm bảo rằng bạn đã include các file cần thiết
require_once realpath(__DIR__ . '/../../Modules/Auth/SessionManager.php');
require_once realpath(__DIR__ . '/../../Modules/Auth/UserManager.php');

// Đặt header Content-Type để báo cho trình duyệt biết đây là JSON
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

$sessionManager = new SessionManager();

// 1. Kiểm tra xem người dùng đã đăng nhập chưa
if (!$sessionManager->isLoggedIn()) {
    $response['message'] = 'Bạn chưa đăng nhập. Vui lòng đăng nhập để cập nhật thông tin.';
    http_response_code(401); // Unauthorized
    echo json_encode($response);
    exit();
}

$userId = $sessionManager->getUserId();

if (!$userId) {
    $response['message'] = 'Không thể xác định ID người dùng.';
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit();
}

// 2. Đọc dữ liệu JSON từ request body
$input = file_get_contents('php://input');
$data = json_decode($input, true); // Chuyển đổi JSON thành mảng PHP

// Kiểm tra xem dữ liệu có hợp lệ không
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Dữ liệu không hợp lệ.';
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit();
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');

// 3. Xác thực dữ liệu đầu vào
$updateData = [];
if (!empty($name)) {
    $updateData['name'] = $name;
} else {
    $response['message'] = 'Tên không được để trống.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

if (!empty($email)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Địa chỉ email không hợp lệ.';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    $updateData['email'] = $email;
} else {
    $response['message'] = 'Email không được để trống.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// Nếu không có dữ liệu để cập nhật (ví dụ: client gửi rỗng)
if (empty($updateData)) {
    $response['message'] = 'Không có dữ liệu nào được gửi để cập nhật.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

$userManager = new UserManager();

// 4. Gọi hàm cập nhật từ UserManager
$updateResult = $userManager->updateUserProfile($userId, $updateData);

if ($updateResult['status'] === 'success') {
    $response['status'] = 'success';
    $response['message'] = $updateResult['message'];

    // Cập nhật session nếu email hoặc tên thay đổi
    // Điều này quan trọng để thông tin hiển thị trên các trang khác được đồng bộ
    if (isset($updateData['name'])) {
        $sessionManager->login(['id' => $userId, 'name' => $updateData['name'], 'email' => $sessionManager->getUserEmail(), 'role' => $sessionManager->getUserRole()]);
    }
    if (isset($updateData['email'])) {
        $sessionManager->login(['id' => $userId, 'name' => $sessionManager->getUserName(), 'email' => $updateData['email'], 'role' => $sessionManager->getUserRole()]);
    }

    http_response_code(200);
} else {
    $response['message'] = $updateResult['message'];
    http_response_code(400); // Bad Request hoặc 500 Internal Server Error tùy lỗi cụ thể
}

echo json_encode($response);
exit();