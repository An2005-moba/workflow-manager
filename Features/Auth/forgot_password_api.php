<?php
// C:/xampp/htdocs/Web_Project/Features/Auth/forgot_password_api.php

// Bật báo lỗi để dễ debug trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bao gồm các file cần thiết
require_once __DIR__ . '/../../Context/db_connection.php';
require_once __DIR__ . '/../../Modules/Auth/UserManager.php';
require_once __DIR__ . '/../../Modules/Utils/HelperFunctions.php';
require_once __DIR__ . '/../../Modules/Utils/Mailer.php';

// Thiết lập header cho JSON response
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => '']; // Mặc định là lỗi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $email = $data['email'] ?? '';

    if (empty($email)) {
        $response['message'] = "Vui lòng nhập địa chỉ email của bạn.";
        echo json_encode($response);
        exit();
    }

    $userManager = new UserManager(); // Khởi tạo UserManager

    // 1. Kiểm tra email có tồn tại trong database không
    $user = $userManager->getUserByEmail($email);

    if (!$user) {
        // Mặc dù email không tồn tại, để bảo mật, ta vẫn trả về thông báo thành công chung chung
        // để tránh kẻ xấu biết được email nào có tồn tại trong hệ thống.
        $response['status'] = 'success';
        $response['message'] = "Nếu email này tồn tại trong hệ thống, mật khẩu mới sẽ được gửi đến.";
        echo json_encode($response);
        exit();
    }

    // 2. Tạo mật khẩu mới ngẫu nhiên
    $new_password = HelperFunctions::generateRandomPassword(12);
    // DÒNG DƯỚI ĐÂY ĐÃ BỊ XÓA (không còn băm mật khẩu mới)
    // $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT); 

    // 3. Cập nhật mật khẩu mới vào database
    // CHÚ Ý: Hàm updatePasswordByEmail bây giờ nhận mật khẩu plaintext
    $update_success = $userManager->updatePasswordByEmail($email, $new_password); // Đã đổi biến

    if ($update_success) {
        // 4. Gửi mật khẩu mới qua email
        $mailer = new Mailer();
        $subject = 'Mật khẩu mới của bạn từ WorkFlow';
        $body    = "Chào bạn,<br><br>"
                    . "Mật khẩu mới của bạn cho tài khoản WorkFlow là: <strong>" . htmlspecialchars($new_password) . "</strong><br>"
                    . "Vui lòng đăng nhập bằng mật khẩu này và đổi mật khẩu ngay lập tức để bảo mật.<br><br>"
                    . "Trân trọng,<br>"
                    . "WorkFlow Team";
        $altBody = "Mật khẩu mới của bạn cho tài khoản WorkFlow là: " . $new_password . ". Vui lòng đăng nhập bằng mật khẩu này và đổi mật khẩu ngay lập tức để bảo mật.";

        $email_sent = $mailer->sendEmail($email, $subject, $body, $altBody);

        if ($email_sent) {
            $response['status'] = 'success';
            $response['message'] = "Mật khẩu mới đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.";
        } else {
            // Lỗi khi gửi email
            $response['status'] = 'error';
            // SỬA TẠI ĐÂY: Lấy lỗi từ thuộc tính ErrorInfo của PHPMailer
            $response['message'] = "Mật khẩu đã được cập nhật thành công, nhưng có lỗi khi gửi email. Vui lòng kiểm tra lại cấu hình email hoặc liên hệ hỗ trợ. Chi tiết: " . $mailer->getErrorMessage(); 
            error_log("Failed to send email for password reset to " . $email . ". PHPMailer Error: " . $mailer->getErrorMessage());
        }
    } else {
        // Lỗi khi cập nhật mật khẩu trong database
        $response['message'] = "Có lỗi xảy ra khi cập nhật mật khẩu. Vui lòng thử lại.";
        error_log("Failed to update password for email: " . $email);
    }

} else {
    // Nếu không phải POST request
    $response['message'] = "Phương thức yêu cầu không hợp lệ.";
}

echo json_encode($response);
exit();
?>