<?php
// C:/xampp/htdocs/Web_Project/Features/User/update_profile_api.php

// Đảm bảo chỉ cho phép truy cập qua phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
    exit();
}

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
    http_response_code(401);
    $response['message'] = 'Bạn chưa đăng nhập. Vui lòng đăng nhập để cập nhật thông tin.';
    echo json_encode($response);
    exit();
}

// Lấy user ID từ session
$userId = $sessionManager->getUserId();

if (!$userId) {
    http_response_code(400);
    $response['message'] = 'Không tìm thấy ID người dùng trong phiên đăng nhập.';
    $sessionManager->logout(); 
    echo json_encode($response);
    exit();
}

// Lấy dữ liệu từ body của request (JSON)
$input_data = json_decode(file_get_contents("php://input"), true);

// Kiểm tra nếu dữ liệu JSON không hợp lệ
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['message'] = 'Dữ liệu gửi lên không hợp lệ (không phải JSON).';
    echo json_encode($response);
    exit();
}

// --- Lấy và xác thực dữ liệu từ input_data ---
$updatedData = [];

// Name
if (isset($input_data['name'])) {
    $name = trim($input_data['name']);
    if (strlen($name) > 255) {
        $response['message'] = 'Tên không được quá 255 ký tự.';
        echo json_encode($response);
        exit();
    }
    $updatedData['name'] = $name;
}

// Email
if (isset($input_data['email'])) {
    $email = trim($input_data['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Địa chỉ email không hợp lệ.';
        echo json_encode($response);
        exit();
    }
    $updatedData['email'] = $email;
}

// Phone Number
if (array_key_exists('phone_number', $input_data)) {
    $phone_number = trim($input_data['phone_number']);
    if (!empty($phone_number) && !preg_match('/^\d{10,11}$/', $phone_number)) {
        $response['message'] = 'Số điện thoại không hợp lệ (chỉ chấp nhận 10 hoặc 11 chữ số).';
        echo json_encode($response);
        exit();
    }
    $updatedData['phone_number'] = $phone_number;
}

// Date of Birth
if (array_key_exists('date_of_birth', $input_data)) {
    $date_of_birth = trim($input_data['date_of_birth']);
    if (!empty($date_of_birth)) {
        $date_parts = explode('-', $date_of_birth);
        if (count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
             $response['message'] = 'Ngày sinh không hợp lệ (định dạng YYYY-MM-DD và phải là ngày có thật).';
             echo json_encode($response);
             exit();
        }

        $today = new DateTime();
        $birthDate = new DateTime($date_of_birth);
        if ($birthDate > $today) {
            $response['message'] = 'Ngày sinh không thể ở tương lai.';
            echo json_encode($response);
            exit();
        }
    }
    $updatedData['date_of_birth'] = $date_of_birth;
}

// Address
if (array_key_exists('address', $input_data)) {
    $address = trim($input_data['address']);
    if (strlen($address) > 512) {
        $response['message'] = 'Địa chỉ quá dài (tối đa 512 ký tự).';
        echo json_encode($response);
        exit();
    }
    $updatedData['address'] = $address;
}

// Kiểm tra xem có dữ liệu nào để cập nhật không
if (empty($updatedData)) {
    $response['status'] = 'info';
    $response['message'] = 'Không có dữ liệu nào được gửi để cập nhật.';
    echo json_encode($response);
    exit();
}

// Gọi hàm cập nhật từ ProfileManager
$result = $profileManager->updateUserProfile($userId, $updatedData);

if ($result['status'] === 'success') {
    if (isset($updatedData['email'])) {
        $_SESSION['user_email'] = $updatedData['email'];
        if (isset($_SESSION['username'])) { 
            $_SESSION['username'] = $updatedData['email'];
        }
    }
    if (isset($updatedData['name'])) {
        $_SESSION['user_name'] = $updatedData['name'];
    }

    $response['status'] = 'success';
    $response['message'] = $result['message'];
    
    $updated_user = $profileManager->getUserById($userId);
    if ($updated_user) {
        unset($updated_user['password']);
        unset($updated_user['username']);
        if (isset($updated_user['date_of_birth']) && ($updated_user['date_of_birth'] === '0000-00-00' || $updated_user['date_of_birth'] === null)) {
            $updated_user['date_of_birth'] = '';
        }
        if (!isset($updated_user['phone_number']) || $updated_user['phone_number'] === null) {
            $updated_user['phone_number'] = '';
        }
        if (!isset($updated_user['address']) || $updated_user['address'] === null) {
            $updated_user['address'] = '';
        }
        $response['user'] = $updated_user;
    }

} else {
    $response['message'] = $result['message'];
    if ($result['message'] === 'Email mới đã được sử dụng bởi tài khoản khác.') {
        http_response_code(409);
    } else {
        http_response_code(500);
    }
}

echo json_encode($response);
exit();
?>