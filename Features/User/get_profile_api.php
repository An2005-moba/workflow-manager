<?php
// C:/xampp/htdocs/Web_Project/Features/User/get_profile_api.php

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
    $response['message'] = 'Bạn chưa đăng nhập. Vui lòng đăng nhập để xem thông tin cá nhân.';
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

$userManager = new UserManager();

// 2. Lấy thông tin người dùng từ database
$userProfile = $userManager->getUserById($userId);

if ($userProfile) {
    $response['status'] = 'success';
    $response['message'] = 'Tải thông tin người dùng thành công.';
    $response['user'] = $userProfile; // Trả về các trường: id, name, email, role (không có password)
    http_response_code(200); // OK
} else {
    $response['message'] = 'Không tìm thấy thông tin người dùng.';
    http_response_code(404); // Not Found
}

echo json_encode($response);
exit();