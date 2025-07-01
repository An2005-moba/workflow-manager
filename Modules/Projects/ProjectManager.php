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
     * Tạo một dự án mới và tự động thêm người tạo làm thành viên đầu tiên.
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

        try {
            // Bắt đầu một transaction để đảm bảo toàn vẹn dữ liệu
            $this->conn->beginTransaction();

            // 1. Tạo dự án trong bảng `projects`
            $sql_create_project = "INSERT INTO projects (project_name, description, created_by_user_id) 
                                   VALUES (:project_name, :description, :user_id)";
            
            $stmt = $this->conn->prepare($sql_create_project);
            $stmt->bindParam(':project_name', $projectName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Lấy ID của dự án vừa được tạo
            $newProjectId = $this->conn->lastInsertId();

            // 2. Tự động thêm người tạo làm thành viên trong bảng `project_members`
            $sql_add_creator_as_member = "INSERT INTO project_members (project_id, user_id) 
                                           VALUES (:project_id, :user_id)";
            
            $stmt_member = $this->conn->prepare($sql_add_creator_as_member);
            $stmt_member->bindParam(':project_id', $newProjectId, PDO::PARAM_INT);
            $stmt_member->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt_member->execute();

            // Nếu cả 2 bước trên thành công, xác nhận transaction
            $this->conn->commit();

            return ['status' => 'success', 'message' => 'Dự án đã được tạo thành công!'];

        } catch (PDOException $e) {
            // Nếu có bất kỳ lỗi nào, hủy bỏ tất cả các thay đổi
            $this->conn->rollBack();
            
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
    /**
     * Lấy thông tin của một dự án cụ thể bằng ID.
     *
     * @param int $projectId ID của dự án cần lấy.
     * @return array Mảng chứa trạng thái ('status') và thông tin dự án ('project').
     */
    public function getProjectById($projectId) {
        $sql = "SELECT p.*, u.name as creator_name 
                FROM projects p
                LEFT JOIN users u ON p.created_by_user_id = u.id
                WHERE p.id = :id";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $projectId, PDO::PARAM_INT);
            $stmt->execute();
            
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($project) {
                return ['status' => 'success', 'project' => $project];
            } else {
                return ['status' => 'error', 'message' => 'Không tìm thấy dự án với ID này.'];
            }

        } catch (PDOException $e) {
            error_log("Get Project By ID Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống khi truy vấn dự án.'];
        }
    }
   /**
     * Lấy tất cả các dự án mà một người dùng có quyền truy cập.
     * PHIÊN BẢN CUỐI CÙNG: Sử dụng UNION để đảm bảo hoạt động trên mọi cấu hình.
     *
     * @param int $userId ID của người dùng.
     * @return array Mảng chứa trạng thái và danh sách các dự án.
     */
    public function getProjectsForUser($userId) {
        $sql = "
            (SELECT p.id, p.project_name, p.description, p.creation_date, u.name as created_by_name
             FROM projects p
             LEFT JOIN users u ON p.created_by_user_id = u.id
             WHERE p.created_by_user_id = :userId1)
            
            UNION
            
            (SELECT p.id, p.project_name, p.description, p.creation_date, u.name as created_by_name
             FROM projects p
             JOIN project_members pm ON p.id = pm.project_id
             LEFT JOIN users u ON p.created_by_user_id = u.id
             WHERE pm.user_id = :userId2)
             
            ORDER BY creation_date DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            // Gán giá trị cho cả hai tham số
            $stmt->bindParam(':userId1', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':userId2', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'projects' => $projects];

        } catch (PDOException $e) {
            error_log("Get Projects For User Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Không thể lấy danh sách dự án cho người dùng. Chi tiết lỗi: ' . $e->getMessage()];
        }
    }
    // Các hàm khác như getProjectById(), updateProject(), deleteProject() có thể được thêm vào đây.
}
?>
