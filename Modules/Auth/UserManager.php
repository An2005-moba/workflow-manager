<?php
// Include file kết nối database
// Sử dụng realpath để đảm bảo đường dẫn tuyệt đối được giải quyết đúng
require_once realpath(__DIR__ . '/../../Context/db_connection.php'); 

class UserManager {
    private $conn;

    public function __construct() {
        $this->conn = getDbConnection(); // Lấy đối tượng kết nối PDO
    }

    /**
     * Đăng ký một người dùng mới vào hệ thống.
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
        // Chúng ta sẽ dùng email cho cả username và email như đã thống nhất
        $sql = "INSERT INTO users (name, email, username, password_hashed, role) VALUES (:name, :email, :username, :password_hashed, :role)";

        try {
            // Chuẩn bị prepared statement để ngăn chặn SQL Injection
            $stmt = $this->conn->prepare($sql);

            // Gán giá trị cho các tham số
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $email); // Email cũng là username
            $stmt->bindParam(':password_hashed', $hashed_password);
            $stmt->bindParam(':role', $role);

            // Thực thi câu lệnh
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Đăng ký thành công!'];
            } else {
                return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi đăng ký người dùng.'];
            }
        } catch (PDOException $e) {
            // Ghi log lỗi để dễ dàng debug
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

            // Kiểm tra xem người dùng có tồn tại không
            if (!$user) {
                return ['status' => 'error', 'message' => 'Email hoặc mật khẩu không đúng.'];
            }

            // Kiểm tra mật khẩu
            // password_verify() sẽ so sánh mật khẩu thô với mật khẩu đã hash
            if (password_verify($password, $user['password_hashed'])) {
                // Đăng nhập thành công
                // Loại bỏ mật khẩu đã hash trước khi trả về để bảo mật thông tin
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
            // Lấy số lượng bản ghi có email này
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check Email Taken Error: " . $e->getMessage());
            return true; // Để an toàn, nếu có lỗi thì coi như email đã bị dùng
        }
    }

    // Các hàm khác như getUser, updateProfile có thể được thêm vào đây sau
    // public function getUserById($userId) { ... }
    // public function updateProfile($userId, $data) { ... }
}
?>