<?php
// C:/xampp/htdocs/Web_Project/Features/User/get_profile_api.php

// Bật báo lỗi để dễ debug trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập header cho JSON response
header('Content-Type: application/json');

// Bao gồm các file cần thiết
require_once realpath(__DIR__ . '/../../Modules/Auth/SessionManager.php');
require_once realpath(__DIR__ . '/../../Modules/User/ProfileManager.php');

$response = ['status' => 'error', 'message' => ''];

$sessionManager = new SessionManager(); // Session sẽ được tự động bắt đầu trong constructor của SessionManager
$profileManager = new ProfileManager();

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!$sessionManager->isLoggedIn()) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'Bạn chưa đăng nhập. Vui lòng đăng nhập để xem thông tin cá nhân.';
    echo json_encode($response);
    exit();
}

// Lấy user ID từ session
$userId = $sessionManager->getUserId();

if (!$userId) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Không tìm thấy ID người dùng trong phiên đăng nhập.';
    $sessionManager->logout(); 
    echo json_encode($response);
    exit();
}

// Lấy thông tin người dùng bằng ProfileManager
$user = $profileManager->getUserById($userId);

if ($user) {
    $response['status'] = 'success';
    $response['message'] = 'Tải thông tin người dùng thành công.';
    
    unset($user['password']);
    unset($user['username']);

    if (isset($user['date_of_birth'])) {
        if ($user['date_of_birth'] === '0000-00-00' || $user['date_of_birth'] === null) {
            $user['date_of_birth'] = '';
        }
    } else {
        $user['date_of_birth'] = '';
    }
    
    if (!isset($user['phone_number']) || $user['phone_number'] === null) {
        $user['phone_number'] = '';
    }
    if (!isset($user['address']) || $user['address'] === null) {
        $user['address'] = '';
    }

    $response['user'] = $user;
} else {
    http_response_code(404);
    $response['message'] = 'Không tìm thấy thông tin người dùng hoặc có lỗi xảy ra.';
}

echo json_encode($response);
exit();
?>