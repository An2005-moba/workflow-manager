<?php
// C:/xampp/htdocs/Web_Project/Features/User/update_profile_api.php
session_start(); // Bắt đầu session để truy cập thông tin người dùng

// Đảm bảo chỉ cho phép truy cập qua phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
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
require_once __DIR__ . '/../../Modules/Auth/UserManager.php';
require_once __DIR__ . '/../../Modules/Auth/SessionManager.php'; // Để kiểm tra session

$response = ['status' => 'error', 'message' => ''];

$sessionManager = new SessionManager();

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!$sessionManager->isLoggedIn()) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'Bạn chưa đăng nhập. Vui lòng đăng nhập để cập nhật thông tin.';
    echo json_encode($response);
    exit();
}

// Lấy user ID từ session
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Không tìm thấy ID người dùng trong phiên đăng nhập.';
    $sessionManager->logout(); 
    echo json_encode($response);
    exit();
}

// Lấy dữ liệu từ body của request (JSON)
$input_data = json_decode(file_get_contents("php://input"), true);

// Khởi tạo UserManager
$userManager = new UserManager();

// --- Lấy và xác thực dữ liệu từ input_data ---
$updatedData = [];

// Name (có thể cập nhật nhưng thường ít thay đổi qua API này)
if (isset($input_data['name']) && $input_data['name'] !== '') {
    $updatedData['name'] = trim($input_data['name']);
    if (strlen($updatedData['name']) > 255) {
        $response['message'] = 'Tên không được quá 255 ký tự.';
        echo json_encode($response);
        exit();
    }
}

// Email (có thể cập nhật nhưng cần kiểm tra trùng lặp và định dạng)
if (isset($input_data['email']) && $input_data['email'] !== '') {
    $updatedData['email'] = trim($input_data['email']);
    if (!filter_var($updatedData['email'], FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Địa chỉ email không hợp lệ.';
        echo json_encode($response);
        exit();
    }
    // UserManager đã có logic kiểm tra email trùng lặp của người khác, nên không cần check ở đây nữa
}

// Phone Number (kiểm tra định dạng, có thể để trống)
if (array_key_exists('phone_number', $input_data)) { // Use array_key_exists to allow empty string
    $phone_number = trim($input_data['phone_number']);
    if (!empty($phone_number) && !preg_match('/^\d{10,11}$/', $phone_number)) { // Kiểm tra 10 hoặc 11 chữ số
        $response['message'] = 'Số điện thoại không hợp lệ (chỉ chấp nhận 10 hoặc 11 chữ số).';
        echo json_encode($response);
        exit();
    }
    $updatedData['phone_number'] = $phone_number;
}

// Date of Birth (kiểm tra định dạng, có thể để trống)
if (array_key_exists('date_of_birth', $input_data)) {
    $date_of_birth = trim($input_data['date_of_birth']);
    if (!empty($date_of_birth)) {
        // Simple date validation: check if it's a valid date format YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth) || !strtotime($date_of_birth)) {
            $response['message'] = 'Ngày sinh không hợp lệ (định dạng YYYY-MM-DD).';
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

// Address (có thể để trống)
if (array_key_exists('address', $input_data)) {
    $address = trim($input_data['address']);
    if (strlen($address) > 512) { // Kích thước cột address là VARCHAR(512)
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

// Gọi hàm cập nhật từ UserManager
// updateUserProfile sẽ trả về mảng ['status' => 'success/error', 'message' => '...']
$result = $userManager->updateUserProfile($userId, $updatedData);

if ($result['status'] === 'success') {
    // Nếu email được cập nhật, có thể cần cập nhật lại session email
    // Tùy thuộc vào cách bạn quản lý session, có thể không cần thiết nếu chỉ dựa vào user_id
    if (isset($updatedData['email'])) {
        $_SESSION['user_email'] = $updatedData['email'];
        $_SESSION['username'] = $updatedData['email']; // Nếu username cũng là email
    }
    // Cập nhật tên trong session nếu có
    if (isset($updatedData['name'])) {
        $_SESSION['user_name'] = $updatedData['name'];
    }

    // Sau khi cập nhật thành công, có thể trả về thông tin user mới nhất
    // Hoặc chỉ trả về thông báo thành công
    $response['status'] = 'success';
    $response['message'] = $result['message'];
    // Tùy chọn: Gửi lại thông tin user mới nhất để frontend cập nhật ngay lập tức
    // $updated_user = $userManager->getUserById($userId);
    // unset($updated_user['password']);
    // $response['user'] = $updated_user;
} else {
    $response['message'] = $result['message'];
}

echo json_encode($response);
exit();
?>