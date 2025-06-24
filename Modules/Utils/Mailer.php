<?php
// C:/xampp/htdocs/Web_Project/Modules/Utils/Mailer.php

// Dòng này cần được THAY THẾ:
// require_once __DIR__ . '/../../vendor/autoload.php';

// Thay vào đó, chúng ta sẽ require_once thủ công các file chính của PHPMailer
// Đảm bảo đường dẫn này khớp với thư mục bạn đã tạo ở các bước trước: PHPMailer_Library
require_once __DIR__ . '/PHPMailer_Library/Exception.php'; // Đường dẫn đến Exception.php
require_once __DIR__ . '/PHPMailer_Library/PHPMailer.php'; // Đường dẫn đến PHPMailer.php
require_once __DIR__ . '/PHPMailer_Library/SMTP.php';     // Đường dẫn đến SMTP.php
// Nếu bạn sử dụng các tính năng OAuth hoặc POP3 của PHPMailer, hãy thêm các dòng sau:
// require_once __DIR__ . '/PHPMailer_Library/POP3.php';
// require_once __DIR__ . '/PHPMailer_Library/OAuth.php';


// Vẫn giữ nguyên các dòng `use` này vì các class PHPMailer vẫn sử dụng namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true); // true enables exceptions
        // Cấu hình SMTP mặc định
        try {
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'garanhutmau256@gmail.com'; // <<< THAY THẾ BẰNG EMAIL CỦA BẠN
            $this->mail->Password   = 'kycx xpqt tvfp otjh';    // <<< THAY THAY THẾ BẰNG MẬT KHẨU HOẶC APP PASSWORD CỦA BẠN
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port       = 465;

            $this->mail->setFrom('garanhutmau256@gmail.com', 'WorkFlow Team');
            $this->mail->CharSet = 'UTF-8';
        } catch (Exception $e) {
            // Log lỗi cấu hình mailer
            error_log("Mailer configuration error: " . $e->getMessage());
        }
    }

    /**
     * Sends an email.
     *
     * @param string $toEmail The recipient's email address.
     * @param string $subject The email subject.
     * @param string $body The HTML content of the email.
     * @param string $altBody The plain-text content of the email.
     * @return bool True on success, false on failure.
     */
    public function sendEmail($toEmail, $subject, $body, $altBody = '') {
        try {
            // Clear all addresses and attachments for the next iteration
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            $this->mail->addAddress($toEmail);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->AltBody = $altBody;

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            // Log lỗi, sử dụng thuộc tính ErrorInfo của đối tượng PHPMailer
            error_log("Message could not be sent to {$toEmail}. Mailer Error: {$this->mail->ErrorInfo}. Exception: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Returns the last error message from PHPMailer.
     *
     * @return string The error message.
     */
    public function getErrorMessage() {
        return $this->mail->ErrorInfo;
    }
}