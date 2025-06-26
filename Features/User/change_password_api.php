<?php
// C:/xampp/htdocs/Web_Project/Features/User/change_password_api.php

require_once realpath(__DIR__ . '/../../Modules/Auth/SessionManager.php');
require_once realpath(__DIR__ . '/../../Modules/Auth/UserManager.php'); // Sử dụng UserManager để thay đổi mật khẩu

header('Content-Type: application/json');

$sessionManager = new SessionManager();
$userManager = new UserManager();

// --- LƯU Ý QUAN TRỌNG: Đảm bảo không gọi $sessionManager->startSession() ở đây nếu nó đã được xử lý trong constructor của SessionManager hoặc ở một nơi khác.
// Nếu bạn gặp lỗi "Call to undefined method SessionManager::startSession()", hãy đảm bảo dòng này đã được xóa hoặc comment out.
// Ví dụ:
// if (!$sessionManager->startSession()) { // Dòng này có thể đã được xử lý tự động bởi SessionManager constructor
//     echo json_encode(['status' => 'error', 'message' => 'Không thể bắt đầu phiên làm việc.']);
//     exit();
// }

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!$sessionManager->isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập. Vui lòng đăng nhập để thay đổi mật khẩu.', 'redirect' => '../../Features/Auth/login.html']);
    exit();
}

$userId = $sessionManager->getUserId();

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy ID người dùng trong phiên.', 'redirect' => '../../Features/Auth/login.html']);
    exit();
}

// Nhận dữ liệu JSON từ request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';
$confirmNewPassword = $data['confirm_new_password'] ?? '';

// === VALIDATION ===
if (empty($currentPassword) || empty($newPassword) || empty($confirmNewPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ tất cả các trường.']);
    exit();
}

if ($newPassword !== $confirmNewPassword) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới và xác nhận mật khẩu không khớp.']);
    exit();
}

// Thêm các quy tắc phức tạp cho mật khẩu mới nếu cần (ví dụ: độ dài, ký tự đặc biệt)
// Bạn đã có logic này trong JS, nhưng nên có ở cả backend để bảo mật hơn.
if (strlen($newPassword) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự.']);
    exit();
}
// Thêm kiểm tra ký tự đặc biệt nếu cần (giống như trong JS của bạn)
// if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
//     echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới phải bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.']);
//     exit();
// }


// Lấy thông tin người dùng để xác minh mật khẩu hiện tại
// Đảm bảo getUserById trong UserManager.php có select cả cột 'password'
$user = $userManager->getUserById($userId); 

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin người dùng.']);
    exit();
}

// Xác minh mật khẩu hiện tại
// PHƯƠNG PHÁP NÀY CHỈ DÙNG KHI MẬT KHẨU LƯU TRONG DB LÀ PLAINTEXT (không băm)
// Bạn đã xác nhận đây là lựa chọn cho báo cáo của bạn.
if ($currentPassword !== $user['password']) { 
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu hiện tại không đúng.']);
    exit();
}

// Kiểm tra mật khẩu mới có trùng với mật khẩu hiện tại không
if ($newPassword === $currentPassword) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại.']);
    exit();
}

// Gọi phương thức đổi mật khẩu từ UserManager
// Vì UserManager.php của bạn có updatePasswordByEmail, chúng ta sẽ dùng nó.
// Đầu tiên, lấy email của người dùng từ thông tin $user đã có.
$userEmail = $user['email'];

$updateSuccess = $userManager->updatePasswordByEmail($userEmail, $newPassword);

if ($updateSuccess) {
    $result = ['status' => 'success', 'message' => 'Mật khẩu đã được thay đổi thành công.'];
} else {
    // Trường hợp updatePasswordByEmail trả về false (lỗi DB)
    $result = ['status' => 'error', 'message' => 'Có lỗi xảy ra khi cập nhật mật khẩu trong cơ sở dữ liệu. Vui lòng thử lại.'];
}

echo json_encode($result);
?>