<?php

// Bật báo lỗi để dễ dàng debug trong quá trình phát triển (có thể tắt khi triển khai thực tế)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cấu hình Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Mật khẩu trống cho XAMPP
define('DB_NAME', 'web_project_db');

/**
 * Hàm để thiết lập và trả về đối tượng PDO Connection.
 *
 * @return PDO Đối tượng kết nối PDO.
 * @throws PDOException Nếu kết nối database thất bại.
 */
function getDbConnection() {
    $conn = null;
    try {
        $dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Chế độ báo lỗi: ném ra Exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Chế độ lấy dữ liệu mặc định: mảng kết hợp
            PDO::ATTR_EMULATE_PREPARES => false, // Tắt mô phỏng prepared statements (dùng prepared statements thật)
        ];
        $conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        // Ghi log lỗi thay vì chỉ hiển thị trực tiếp lên trình duyệt
        error_log("Database Connection Error: " . $e->getMessage(), 0);
        // Có thể redirect đến trang lỗi hoặc hiển thị thông báo thân thiện hơn cho người dùng
        die("Kết nối database thất bại: " . $e->getMessage()); // Dừng script và báo lỗi
    }
    return $conn;
}

// Bạn có thể thêm một số hàm tiện ích khác ở đây nếu muốn,
// ví dụ: một hàm để đóng kết nối (mặc dù PDO tự động đóng khi script kết thúc)

?>