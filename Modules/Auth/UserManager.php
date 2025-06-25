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
     * Mật khẩu sẽ được lưu dưới dạng plaintext.
     * Đã mở rộng để bao gồm phone_number, date_of_birth, address.
     *
     * @param string $name Tên đầy đủ của người dùng.
     * @param string $email Địa chỉ email của người dùng (dùng làm username).
     * @param string $password Mật khẩu thô (plaintext).
     * @param string|null $phone_number Số điện thoại của người dùng (tùy chọn).
     * @param string|null $date_of_birth Ngày sinh của người dùng (tùy chọn, định dạng ISO 8601-MM-DD).
     * @param string|null $address Địa chỉ của người dùng (tùy chọn).
     * @param string $role Vai trò của người dùng (mặc định là 'user').
     * @return array Mảng chứa trạng thái (success/error) và thông báo.
     */
    public function registerUser($name, $email, $password, $phone_number = null, $date_of_birth = null, $address = null, $role = 'user') {
        // Kiểm tra xem email đã tồn tại hay chưa
        if ($this->isEmailTaken($email)) {
            return ['status' => 'error', 'message' => 'Email đã được đăng ký. Vui lòng sử dụng email khác.'];
        }

        $sql = "INSERT INTO users (name, email, username, password, phone_number, date_of_birth, address, role) 
                VALUES (:name, :email, :username, :password, :phone_number, :date_of_birth, :address, :role)"; 

        try {
            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $email); // Username thường được đặt trùng với email
            $stmt->bindParam(':password', $password); // Mật khẩu chưa mã hóa (cần hash trong môi trường thực tế)
            
            // --- Logic bind cho phone_number, date_of_birth, address ---
            // Đối với phone_number và address: lưu chuỗi rỗng '' nếu giá trị là null hoặc rỗng, phù hợp với VARCHAR NOT NULL DEFAULT ''
            $phone_number_to_bind = ($phone_number === null || $phone_number === '') ? '' : $phone_number;
            $stmt->bindParam(':phone_number', $phone_number_to_bind, PDO::PARAM_STR);

            // Đối với date_of_birth: lưu NULL nếu giá trị là null hoặc rỗng, phù hợp với kiểu DATE NULL
            $date_of_birth_to_bind = ($date_of_birth === null || $date_of_birth === '') ? null : $date_of_birth;
            $stmt->bindParam(':date_of_birth', $date_of_birth_to_bind, ($date_of_birth_to_bind === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);

            // Đối với address: lưu chuỗi rỗng '' nếu giá trị là null hoặc rỗng, phù hợp với VARCHAR NOT NULL DEFAULT ''
            $address_to_bind = ($address === null || $address === '') ? '' : $address;
            $stmt->bindParam(':address', $address_to_bind, PDO::PARAM_STR);
            // --- Kết thúc phần logic bind ---
            
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Đăng ký thành công!'];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Register User SQL Error: " . $errorInfo[2]);
                return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi đăng ký người dùng.'];
            }
        } catch (PDOException $e) {
            error_log("Register User PDO Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.'];
        }
    }

    /**
     * Xác thực thông tin đăng nhập của người dùng.
     * Mật khẩu sẽ được so sánh trực tiếp (plaintext).
     *
     * @param string $email Địa chỉ email của người dùng.
     * @param string $password Mật khẩu thô do người dùng nhập.
     * @return array Mảng chứa trạng thái (success/error), thông báo và thông tin người dùng nếu thành công.
     */
    public function loginUser($email, $password) {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = :email"; 

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['status' => 'error', 'message' => 'Email hoặc mật khẩu không đúng.'];
            }

            // So sánh mật khẩu plaintext (trong môi trường thực tế, bạn cần sử dụng password_verify)
            if ($password === $user['password']) {
                // Đảm bảo không trả về mật khẩu cho frontend
                unset($user['password']); 
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
     * Kiểm tra xem một email đã được sử dụng hay chưa.
     *
     * @param string $email Địa chỉ email cần kiểm tra.
     * @return bool True nếu email đã được sử dụng, False nếu chưa hoặc có lỗi hệ thống.
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
            // QUAN TRỌNG: Nếu có lỗi database, giả định email KHÔNG bị trùng để không chặn đăng ký.
            // Lỗi thực tế sẽ được ghi vào log.
            return false; // ĐÃ SỬA: Thay đổi từ 'true' sang 'false'
        }
    }

    /**
     * Cập nhật mật khẩu của người dùng dựa trên email.
     *
     * @param string $email Địa chỉ email của người dùng.
     * @param string $newPassword Mật khẩu mới (plaintext).
     * @return bool True nếu cập nhật thành công, False nếu thất bại.
     */
    public function updatePasswordByEmail($email, $newPassword) { 
        $sql = "UPDATE users SET password = :password WHERE email = :email"; 
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':password', $newPassword);
            $stmt->bindParam(':email', $email);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update Password Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin người dùng bằng địa chỉ email.
     *
     * @param string $email Địa chỉ email của người dùng.
     * @return array|null Mảng thông tin người dùng nếu tìm thấy, ngược lại là null.
     */
    public function getUserByEmail($email) {
        $sql = "SELECT id, name, email, role, phone_number, date_of_birth, address FROM users WHERE email = :email"; 
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
     * Lấy thông tin người dùng bằng ID.
     *
     * @param int $userId ID của người dùng.
     * @return array|null Mảng thông tin người dùng nếu tìm thấy, ngược lại là null.
     */
    public function getUserById($userId) {
        $sql = "SELECT id, name, email, role, phone_number, date_of_birth, address FROM users WHERE id = :id";
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
     * Cập nhật thông tin hồ sơ của người dùng (tên, email, phone_number, date_of_birth, address).
     *
     * @param int $userId ID của người dùng cần cập nhật.
     * @param array $data Mảng chứa các trường cần cập nhật (chỉ những trường có thay đổi).
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
            if ($this->isEmailTakenByOtherUser($data['email'], $userId)) {
                return ['status' => 'error', 'message' => 'Email mới đã được sử dụng bởi tài khoản khác.'];
            }
            $setClauses[] = 'email = :email';
            $params[':email'] = $data['email'];
            $setClauses[] = 'username = :username'; // Cập nhật username theo email mới
            $params[':username'] = $data['email'];
        }

        // --- Logic bind cho phone_number, date_of_birth, address khi cập nhật ---
        // Đối với phone_number và address: lưu chuỗi rỗng '' nếu giá trị là null hoặc chuỗi rỗng
        if (array_key_exists('phone_number', $data)) {
            $setClauses[] = 'phone_number = :phone_number';
            $params[':phone_number'] = ($data['phone_number'] === null || $data['phone_number'] === '') ? '' : $data['phone_number'];
        }

        // Đối với date_of_birth: lưu NULL nếu giá trị là null hoặc chuỗi rỗng
        if (array_key_exists('date_of_birth', $data)) {
            $setClauses[] = 'date_of_birth = :date_of_birth';
            $params[':date_of_birth'] = ($data['date_of_birth'] === null || $data['date_of_birth'] === '') ? null : $data['date_of_birth'];
        }

        if (array_key_exists('address', $data)) {
            $setClauses[] = 'address = :address';
            $params[':address'] = ($data['address'] === null || $data['address'] === '') ? '' : $data['address'];
        }
        // --- Kết thúc phần logic bind ---

        if (empty($setClauses)) {
            return ['status' => 'error', 'message' => 'Không có dữ liệu nào để cập nhật.'];
        }

        $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => &$val) {
                // Kiểm tra riêng cho date_of_birth để bind PDO::PARAM_NULL nếu giá trị là null
                if ($key === ':date_of_birth' && $val === null) {
                    $stmt->bindParam($key, $val, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam($key, $val); // Mặc định bind là PARAM_STR cho các giá trị khác
                }
            }
            
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Cập nhật thông tin thành công!'];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Update User Profile SQL Error: " . $errorInfo[2]);
                return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi cập nhật thông tin.'];
            }
        } catch (PDOException $e) {
            error_log("Update User Profile PDO Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.'];
        }
    }

    /**
     * Kiểm tra xem một email đã được sử dụng bởi người dùng khác ngoài người dùng hiện tại hay không.
     *
     * @param string $email Địa chỉ email cần kiểm tra.
     * @param int $currentUserId ID của người dùng hiện tại.
     * @return bool True nếu email đã được sử dụng bởi người dùng khác, False nếu chưa hoặc có lỗi hệ thống.
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
            // QUAN TRỌNG: Nếu có lỗi database, giả định email KHÔNG bị trùng bởi người khác.
            // Lỗi thực tế sẽ được ghi vào log.
            return false; // ĐÃ SỬA: Thay đổi từ 'true' sang 'false'
        }
    }
}
?>