<?php
/**
 * Lớp ProjectManager chịu trách nhiệm cho tất cả các hoạt động
 * liên quan đến dự án trong cơ sở dữ liệu.
 *
 * PHIÊN BẢN SỬA LỖI: Đã xóa dòng require_once.
 */
require_once realpath(__DIR__ . '/../../Context/db_connection.php'); 
class ProjectManager {
    private $conn;

    /**
     * Hàm khởi tạo, NHẬN vào một kết nối cơ sở dữ liệu có sẵn.
     * @param PDO $dbConnection Đối tượng kết nối PDO.
     */
    public function __construct($dbConnection) {
        if ($dbConnection instanceof PDO) {
            $this->conn = $dbConnection;
        } else {
            throw new Exception("Lỗi: Kết nối cơ sở dữ liệu không hợp lệ được cung cấp cho ProjectManager.");
        }
    }

    /**
     * Tạo một dự án mới và lưu vào cơ sở dữ liệu.
     *
     * @param string $projectName Tên của dự án.
     * @param string $description Mô tả chi tiết cho dự án.
     * @param int $userId ID của người dùng tạo dự án.
     * @return array Mảng chứa trạng thái ('status') và thông báo ('message').
     */
    public function createProject($projectName, $description, $userId) {
        // Xác thực dữ liệu đầu vào
        if (empty($projectName) || empty($userId)) {
            return ['status' => 'error', 'message' => 'Tên dự án và ID người tạo không được để trống.'];
        }
        if (!is_numeric($userId)) {
            return ['status' => 'error', 'message' => 'ID người tạo phải là một số nguyên.'];
        }

        $sql = "INSERT INTO projects (project_name, description, created_by_user_id) 
                VALUES (:project_name, :description, :user_id)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':project_name', $projectName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Dự án đã được tạo thành công!'];
            } else {
                return ['status' => 'error', 'message' => 'Không thể tạo dự án do lỗi không xác định.'];
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                return ['status' => 'error', 'message' => 'Lỗi: ID người tạo không tồn tại. Vui lòng kiểm tra lại.'];
            }
            error_log("Create Project Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống, không thể tạo dự án vào lúc này.'];
        }
    }
    /**
     * Lấy tất cả các dự án từ cơ sở dữ liệu.
     *
     * @return array Mảng chứa trạng thái và danh sách các dự án.
     */
    public function getAllProjects() {
        // Sắp xếp theo ngày tạo mới nhất
        $sql = "SELECT p.id, p.project_name, p.description, p.creation_date, u.name as created_by_name 
                FROM projects p
                LEFT JOIN users u ON p.created_by_user_id = u.id
                ORDER BY p.creation_date DESC";
        try {
            $stmt = $this->conn->query($sql);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'projects' => $projects];
        } catch (PDOException $e) {
            error_log("Get All Projects Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Không thể lấy danh sách dự án.'];
        }
    }
    
    // Các hàm khác như getProjectById(), updateProject(), deleteProject() có thể được thêm vào đây.
}
?>
