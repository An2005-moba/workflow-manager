<?php
// Bật báo lỗi để dễ dàng debug trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Cấu hình kết nối Database (Đây là các thông tin mặc định của XAMPP) ---
$servername = "localhost";
$username = "root"; // Tên người dùng mặc định của MySQL trong XAMPP
$password = "";     // Mật khẩu mặc định của MySQL trong XAMPP (thường là trống)
$dbname = "web_project_db"; // Tên database mà chúng ta muốn tạo

// --- Bước 1: Kết nối MySQL server để tạo database ---
try {
    // Sử dụng PDO để kết nối. Ban đầu không chỉ định database cụ thể
    $conn = new PDO("mysql:host=$servername", $username, $password);
    // Thiết lập chế độ báo lỗi PDO thành Exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tạo database nếu nó chưa tồn tại
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    $conn->exec($sql_create_db);
    echo "Database `$dbname` created successfully or already exists.<br>";

    // Sau khi database được tạo, ngắt kết nối và kết nối lại, hoặc chọn database để sử dụng.
    // Cách đơn giản nhất là ngắt và kết nối lại với database đã chọn.
    $conn = null; // Đóng kết nối cũ

    // --- Bước 2: Kết nối lại với database đã tạo để tạo bảng ---
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL để tạo bảng users
    $sql_create_table = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(255) NOT NULL UNIQUE,
            `password_hashed` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `name` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ";

    // Thực thi câu lệnh tạo bảng
    $conn->exec($sql_create_table);
    echo "Table `users` created successfully or already exists.<br>";

    echo "Database setup completed successfully!";

} catch(PDOException $e) {
    // Bắt lỗi nếu có vấn đề trong quá trình kết nối hoặc thực thi SQL
    echo "Error: " . $e->getMessage();
}

// Đóng kết nối
$conn = null;
?>