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
    // CHÚ Ý: Cột 'password_hashed' ĐÃ ĐƯỢC THAY THẾ BẰNG 'password'
    $sql_create_table = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `name` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ";
    $sql_create_projects_table = "
        CREATE TABLE IF NOT EXISTS `projects` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `created_by_user_id` INT,
            `creation_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`created_by_user_id`) 
                REFERENCES `users`(`id`)
                ON DELETE SET NULL 
                ON UPDATE CASCADE
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ";
    
    // Ghi chú:
    // `FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`)`: Tạo liên kết đến cột id của bảng users.
    // `ON DELETE SET NULL`: Nếu một user bị xóa, giá trị `created_by_user_id` trong các project của họ sẽ được set thành NULL (thay vì xóa cả project).
    // `ON UPDATE CASCADE`: Nếu id của một user được cập nhật, id đó cũng sẽ được cập nhật trong bảng projects.

    // Thực thi câu lệnh tạo bảng projects
    $conn->exec($sql_create_projects_table);
    echo "Table `projects` created successfully or already exists.<br>";

    echo "Database setup completed successfully!";


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