<?php
// Bắt đầu session để có thể thao tác với session hiện tại
session_start();

// Hủy tất cả các biến session
$_SESSION = array(); // Xóa tất cả dữ liệu trong mảng $_SESSION

// Nếu sử dụng cookie cho session, hãy xóa nó
// Lưu ý: Thao tác này sẽ hủy session, không chỉ dữ liệu session!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Cuối cùng, hủy session
session_destroy();

// Chuyển hướng người dùng về trang index.html
// Giả sử index.html nằm ở thư mục gốc của dự án (Web_Project)
// Từ Features/Auth/, để quay về thư mục gốc, cần dùng ../../
header("Location: ../../index.html"); 
exit(); // Đảm bảo rằng không có mã nào được thực thi sau khi chuyển hướng
?>