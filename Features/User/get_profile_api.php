<?php
// C:/xampp/htdocs/Web_Project/Features/User/get_profile_api.php
session_start(); // Bắt đầu session để truy cập thông tin người dùng

// Bật báo lỗi để dễ debug trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập header cho JSON response
header('Content-Type: application/json');

// Bao gồm các file cần thiết
require_once __DIR__ . '/../../Modules/Auth/UserManager.php';
require_once __DIR__ . '/../../Modules/Auth/SessionManager.php'; // Để kiểm tra session

$response = ['status' => 'error', 'message' => ''];

$sessionManager = new SessionManager();

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!$sessionManager->isLoggedIn()) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'Bạn chưa đăng nhập. Vui lòng đăng nhập để xem thông tin cá nhân.';
    echo json_encode($response);
    exit();
}

// Lấy user ID từ session
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Không tìm thấy ID người dùng trong phiên đăng nhập.';
    // Có thể cần hủy session nếu ID không hợp lệ
    $sessionManager->logout(); 
    echo json_encode($response);
    exit();
}

$userManager = new UserManager();

// Lấy thông tin người dùng bằng ID
// Hàm getUserById đã được cập nhật để trả về các trường mới
$user = $userManager->getUserById($userId);

if ($user) {
    $response['status'] = 'success';
    $response['message'] = 'Tải thông tin người dùng thành công.';
    
    // Đảm bảo không gửi mật khẩu hoặc các thông tin nhạy cảm khác
    unset($user['password']); // Mặc dù UserManager::getUserById không chọn password, đây là biện pháp an toàn
    unset($user['username']); // Hoặc bất kỳ trường nào không cần thiết cho frontend

    // Format date_of_birth nếu nó tồn tại và không phải là NULL
    if (isset($user['date_of_birth']) && $user['date_of_birth'] === '0000-00-00') {
        $user['date_of_birth'] = ''; // Trả về chuỗi rỗng nếu là giá trị mặc định của MySQL
    }


    $response['user'] = $user;
} else {
    http_response_code(404); // Not Found
    $response['message'] = 'Không tìm thấy thông tin người dùng.';
}

echo json_encode($response);
exit();
?>