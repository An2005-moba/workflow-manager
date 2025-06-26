<?php
// C:/xampp/htdocs/Web_Project/Modules/User/ProfileManager.php

// Include file kết nối database
require_once realpath(__DIR__ . '/../../Context/db_connection.php'); 

class ProfileManager {
    private $conn;

    public function __construct() {
        $this->conn = getDbConnection(); // Lấy đối tượng kết nối PDO
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
            error_log("Get User By ID Error in ProfileManager: " . $e->getMessage());
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

        // Kiểm tra và thêm 'name' nếu có
        if (isset($data['name'])) {
            $setClauses[] = 'name = :name';
            $params[':name'] = $data['name'];
        }

        // Kiểm tra và thêm 'email' nếu có
        if (isset($data['email'])) {
            // Kiểm tra xem email mới có bị trùng với người dùng khác không
            if ($this->isEmailTakenByOtherUser($data['email'], $userId)) {
                return ['status' => 'error', 'message' => 'Email mới đã được sử dụng bởi tài khoản khác.'];
            }
            $setClauses[] = 'email = :email';
            $params[':email'] = $data['email'];
            // Cập nhật cả username nếu username được đặt trùng với email
            $setClauses[] = 'username = :username'; 
            $params[':username'] = $data['email'];
        }

        // Xử lý phone_number: lưu chuỗi rỗng nếu là null hoặc rỗng
        if (array_key_exists('phone_number', $data)) {
            $setClauses[] = 'phone_number = :phone_number';
            $params[':phone_number'] = ($data['phone_number'] === null || $data['phone_number'] === '') ? '' : $data['phone_number'];
        }

        // Xử lý date_of_birth: lưu NULL nếu là null hoặc rỗng
        if (array_key_exists('date_of_birth', $data)) {
            $setClauses[] = 'date_of_birth = :date_of_birth';
            $params[':date_of_birth'] = ($data['date_of_birth'] === null || $data['date_of_birth'] === '') ? null : $data['date_of_birth'];
        }

        // Xử lý address: lưu chuỗi rỗng nếu là null hoặc rỗng
        if (array_key_exists('address', $data)) {
            $setClauses[] = 'address = :address';
            $params[':address'] = ($data['address'] === null || $data['address'] === '') ? '' : $data['address'];
        }

        // Nếu không có trường nào để cập nhật, trả về lỗi
        if (empty($setClauses)) {
            return ['status' => 'error', 'message' => 'Không có dữ liệu nào để cập nhật.'];
        }

        // Xây dựng câu lệnh SQL UPDATE
        $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => &$val) {
                // Bind riêng cho các trường có thể là NULL (như date_of_birth)
                if ($key === ':date_of_birth' && $val === null) {
                    $stmt->bindParam($key, $val, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam($key, $val); // Mặc định bind là PARAM_STR
                }
            }
            
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Cập nhật thông tin thành công!'];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Update User Profile SQL Error in ProfileManager: " . $errorInfo[2]);
                return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi cập nhật thông tin.'];
            }
        } catch (PDOException $e) {
            error_log("Update User Profile PDO Exception in ProfileManager: " . $e->getMessage());
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
            error_log("Check Email Taken By Other User Error in ProfileManager: " . $e->getMessage());
            return false; // Trả về false trong trường hợp lỗi PDO
        }
    }
}
?>