<?php
// C:/xampp/htdocs/Web_Project/Modules/Auth/SessionManager.php

class SessionManager {

    public function __construct() {
        // Đảm bảo session đã được bắt đầu
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Đăng nhập người dùng vào session.
     *
     * @param array $userData Mảng chứa thông tin người dùng (id, name, email, role).
     */
    public function login($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['name'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_role'] = $userData['role'];
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true); // Tái tạo ID session để ngăn chặn Session Fixation
    }

    /**
     * Kiểm tra xem người dùng đã đăng nhập chưa.
     *
     * @return bool True nếu đã đăng nhập, False nếu chưa.
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Lấy ID của người dùng đang đăng nhập.
     *
     * @return int|null ID người dùng nếu đã đăng nhập, ngược lại là null.
     */
    public function getUserId() {
        return $this->isLoggedIn() ? $_SESSION['user_id'] : null;
    }

    /**
     * Lấy tên của người dùng đang đăng nhập.
     *
     * @return string|null Tên người dùng nếu đã đăng nhập, ngược lại là null.
     */
    public function getUserName() {
        return $this->isLoggedIn() ? $_SESSION['user_name'] : null;
    }

    /**
     * Lấy email của người dùng đang đăng nhập.
     *
     * @return string|null Email người dùng nếu đã đăng nhập, ngược lại là null.
     */
    public function getUserEmail() {
        return $this->isLoggedIn() ? $_SESSION['user_email'] : null;
    }

    /**
     * Lấy vai trò của người dùng đang đăng nhập.
     *
     * @return string|null Vai trò người dùng nếu đã đăng nhập, ngược lại là null.
     */
    public function getUserRole() {
        return $this->isLoggedIn() ? $_SESSION['user_role'] : null;
    }

    /**
     * Đăng xuất người dùng khỏi session.
     */
    public function logout() {
        // Xóa tất cả các biến session
        $_SESSION = array();

        // Hủy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Hủy session
        session_destroy();
    }
}