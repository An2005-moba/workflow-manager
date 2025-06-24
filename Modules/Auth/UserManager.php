<?php
// C:/xampp/htdocs/Web_Project/Modules/Auth/UserManager.php

// Include file kết nối database
require_once realpath(__DIR__ . '/../../Context/db_connection.php'); 

class UserManager {
    private $conn;

    public function __construct() {
        $this->conn = getDbConnection(); // Lấy đối tượng kết nối PDO
    }

    /**
     * Đăng ký một người dùng mới vào hệ thống.
     * Mật khẩu sẽ được mã hóa bằng password_hash().
     *
     * @param string $name Tên đầy đủ của người dùng.
     * @param string $email Địa chỉ email của người dùng (dùng làm username).
     * @param string $password Mật khẩu thô (chưa mã hóa).
     * @param string $role Vai trò của người dùng (mặc định là 'user').
     * @return array Mảng chứa trạng thái (success/error) và thông báo.
     */
    public function registerUser($name, $email, $password, $role = 'user') {
        // 1. Kiểm tra xem email đã tồn tại chưa
        if ($this->isEmailTaken($email)) {
            return ['status' => 'error', 'message' => 'Email đã được đăng ký. Vui lòng sử dụng email khác.'];
        }

        // 2. Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Chuẩn bị câu lệnh SQL để chèn dữ liệu
        $sql = "INSERT INTO users (name, email, username, password_hashed, role) VALUES (:name, :email, :username, :password_hashed, :role)"; 

        try {
            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $email); // Email cũng là username
            $stmt->bindParam(':password_hashed', $hashed_password); 
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Đăng ký thành công!'];
            } else {
                return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi đăng ký người dùng.'];
            }
        } catch (PDOException $e) {
            error_log("Register User Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.'];
        }
    }

    /**
     * Xác thực thông tin đăng nhập của người dùng.
     *
     * @param string $email Địa chỉ email của người dùng.
     * @param string $password Mật khẩu thô do người dùng nhập.
     * @return array Mảng chứa trạng thái (success/error), thông báo và thông tin người dùng nếu thành công.
     */
    public function loginUser($email, $password) {
        $sql = "SELECT id, name, email, password_hashed, role FROM users WHERE email = :email"; 

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['status' => 'error', 'message' => 'Email hoặc mật khẩu không đúng.'];
            }

            if (password_verify($password, $user['password_hashed'])) { 
                unset($user['password_hashed']); 
                return ['status' => 'success', 'message' => 'Đăng nhập thành công!', 'user' => $user];
            } else {
                return ['status' => 'error', 'message' => 'Email hoặc mật khẩu không đúng.'];
            }
        } catch (PDOException $e) {
            error_log("Login User Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.'];
        }
    }

    /**
     * Kiểm tra xem một email đã được sử dụng bởi người dùng khác chưa.
     *
     * @param string $email Địa chỉ email cần kiểm tra.
     * @return bool True nếu email đã tồn tại, False nếu chưa.
     */
    private function isEmailTaken($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check Email Taken Error: " . $e->getMessage());
            return true; // Để an toàn, nếu có lỗi thì coi như email đã bị dùng
        }
    }

    /**
     * Cập nhật mật khẩu mới cho người dùng dựa trên email.
     * Mật khẩu mới sẽ được lưu trữ dưới dạng Băm.
     *
     * @param string $email Địa chỉ email của người dùng cần cập nhật.
     * @param string $newHashedPassword Mật khẩu mới đã được mã hóa.
     * @return bool True nếu cập nhật thành công, False nếu có lỗi.
     */
    public function updatePasswordByEmail($email, $newHashedPassword) {
        $sql = "UPDATE users SET password_hashed = :password_hashed WHERE email = :email"; 
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':password_hashed', $newHashedPassword); 
            $stmt->bindParam(':email', $email);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update Password Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin người dùng dựa trên email.
     * KHÔNG trả về mật khẩu đã băm.
     *
     * @param string $email Địa chỉ email của người dùng.
     * @return array|null Mảng chứa thông tin người dùng nếu tìm thấy, ngược lại là null.
     */
    public function getUserByEmail($email) {
        // Không lấy password_hashed trừ khi thật sự cần để password_verify (như trong loginUser)
        $sql = "SELECT id, name, email, role FROM users WHERE email = :email"; 
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user;
        } catch (PDOException $e) {
            error_log("Get User By Email Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy thông tin người dùng dựa trên ID.
     * KHÔNG trả về mật khẩu.
     *
     * @param int $userId ID của người dùng.
     * @return array|null Mảng chứa thông tin người dùng (id, name, email, role) nếu tìm thấy, ngược lại là null.
     */
    public function getUserById($userId) {
        // Chỉ chọn các trường cần thiết, KHÔNG bao gồm mật khẩu.
        $sql = "SELECT id, name, email, role FROM users WHERE id = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user;
        } catch (PDOException $e) {
            error_log("Get User By ID Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cập nhật thông tin hồ sơ của người dùng (tên, email).
     *
     * @param int $userId ID của người dùng cần cập nhật.
     * @param array $data Mảng chứa các trường cần cập nhật (ví dụ: ['name' => 'Tên mới', 'email' => 'email_moi@example.com']).
     * @return array Mảng chứa trạng thái (success/error) và thông báo.
     */
    public function updateUserProfile($userId, array $data) {
        $setClauses = [];
        $params = [':id' => $userId];

        if (isset($data['name'])) {
            $setClauses[] = 'name = :name';
            $params[':name'] = $data['name'];
        }

        if (isset($data['email'])) {
            // Kiểm tra xem email mới có bị trùng với email của người dùng khác không
            // trừ trường hợp email đó là của chính người dùng đang cập nhật
            if ($this->isEmailTakenByOtherUser($data['email'], $userId)) {
                return ['status' => 'error', 'message' => 'Email mới đã được sử dụng bởi tài khoản khác.'];
            }
            $setClauses[] = 'email = :email';
            $params[':email'] = $data['email'];
            // Nếu bạn dùng username là email, cũng cần cập nhật username
            $setClauses[] = 'username = :username';
            $params[':username'] = $data['email'];
        }

        if (empty($setClauses)) {
            return ['status' => 'error', 'message' => 'Không có dữ liệu nào để cập nhật.'];
        }

        $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => &$val) {
                $stmt->bindParam($key, $val);
            }

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Cập nhật thông tin thành công!'];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Update User Profile Error: " . $errorInfo[2]);
                return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi cập nhật thông tin.'];
            }
        } catch (PDOException $e) {
            error_log("Update User Profile PDO Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.'];
        }
    }

    /**
     * Kiểm tra xem một email đã được sử dụng bởi người dùng khác chưa (loại trừ chính người dùng đó).
     * Hữu ích khi người dùng cập nhật email của họ.
     *
     * @param string $email Địa chỉ email cần kiểm tra.
     * @param int $currentUserId ID của người dùng hiện tại (để loại trừ khi kiểm tra).
     * @return bool True nếu email đã tồn tại bởi người dùng khác, False nếu chưa.
     */
    private function isEmailTakenByOtherUser($email, $currentUserId) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email AND id != :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $currentUserId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check Email Taken By Other User Error: " . $e->getMessage());
            return true; // Để an toàn, nếu có lỗi thì coi như email đã bị dùng
        }
    }
}