<?php
class TaskManager {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Lấy tất cả các nhiệm vụ của một dự án cụ thể.
     */
    public function getTasksByProjectId($projectId) {
        $sql = "SELECT * FROM tasks WHERE project_id = :project_id ORDER BY created_at DESC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Tasks Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tạo một nhiệm vụ mới.
     */
    public function createTask($projectId, $taskName, $description) {
        if (empty($projectId) || empty($taskName)) {
            return ['status' => 'error', 'message' => 'Tên nhiệm vụ và ID dự án không được để trống.'];
        }

        $sql = "INSERT INTO tasks (project_id, task_name, description) VALUES (:project_id, :task_name, :description)";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
            $stmt->bindParam(':task_name', $taskName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Nhiệm vụ đã được tạo thành công.'];
            } else {
                return ['status' => 'error', 'message' => 'Không thể tạo nhiệm vụ.'];
            }
        } catch (PDOException $e) {
            error_log("Create Task Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi hệ thống khi tạo nhiệm vụ.'];
        }
    }
    /**
     * Cập nhật một nhiệm vụ.
     */
    public function updateTask($taskId, $taskName, $description, $status) {
        if (empty($taskId) || empty($taskName)) {
            return ['status' => 'error', 'message' => 'ID và tên nhiệm vụ không được để trống.'];
        }
        $sql = "UPDATE tasks SET task_name = :task_name, description = :description, status = :status WHERE id = :task_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindParam(':task_name', $taskName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Cập nhật nhiệm vụ thành công.'];
            }
        } catch (PDOException $e) {
            error_log("Update Task Error: " . $e->getMessage());
        }
        return ['status' => 'error', 'message' => 'Không thể cập nhật nhiệm vụ.'];
    }

    /**
     * Xóa một nhiệm vụ.
     */
    public function deleteTask($taskId) {
        if (empty($taskId)) {
            return ['status' => 'error', 'message' => 'ID nhiệm vụ không được để trống.'];
        }
        $sql = "DELETE FROM tasks WHERE id = :task_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Đã xóa nhiệm vụ.'];
            }
        } catch (PDOException $e) {
            error_log("Delete Task Error: " . $e->getMessage());
        }
        return ['status' => 'error', 'message' => 'Không thể xóa nhiệm vụ.'];
    }
}
?>