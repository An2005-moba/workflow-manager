<?php
class MemberManager {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Lấy danh sách thành viên của một dự án.
     */
    public function getProjectMembers($projectId) {
        $sql = "SELECT u.id, u.name, u.email FROM users u
                JOIN project_members pm ON u.id = pm.user_id
                WHERE pm.project_id = :project_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm thành viên vào dự án bằng email.
     */
    public function addMemberByEmail($projectId, $email) {
        // 1. Tìm user_id từ email
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['status' => 'error', 'message' => 'Lỗi: Không tìm thấy người dùng với email này.'];
        }
        $userId = $user['id'];

        // 2. Kiểm tra xem thành viên đã ở trong dự án chưa
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = :project_id AND user_id = :user_id");
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            return ['status' => 'error', 'message' => 'Người dùng này đã là thành viên của dự án.'];
        }

        // 3. Thêm thành viên
        $sql = "INSERT INTO project_members (project_id, user_id) VALUES (:project_id, :user_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Đã thêm thành viên thành công.'];
        }
        return ['status' => 'error', 'message' => 'Không thể thêm thành viên.'];
    }

    public function removeMember($projectId, $userId) {
        $sql = "DELETE FROM project_members WHERE project_id = :project_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Đã xóa thành viên thành công.'];
        }
        return ['status' => 'error', 'message' => 'Không thể xóa thành viên.'];
    }
}
?>