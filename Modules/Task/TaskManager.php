<?php
class TaskManager
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function getTasksByProjectId($projectId)
    {
        // Cập nhật câu lệnh SQL để lấy danh sách tên người được gán
        $sql = "SELECT t.*, GROUP_CONCAT(u.name SEPARATOR ', ') as assignee_names
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.id = ta.task_id
                LEFT JOIN users u ON ta.user_id = u.id
                WHERE t.project_id = :project_id
                GROUP BY t.id
                ORDER BY t.created_at DESC";
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

    public function getAssigneeIdsForTask($taskId)
    {
        $sql = "SELECT user_id FROM task_assignments WHERE task_id = :task_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
        $stmt->execute();
        // Trả về một mảng các ID
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function reassignTask($taskId, $assigneeIds = [])
    {
        try {
            $this->conn->beginTransaction();
            // 1. Xóa tất cả các phân công cũ của nhiệm vụ này
            $stmt_delete = $this->conn->prepare("DELETE FROM task_assignments WHERE task_id = :task_id");
            $stmt_delete->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            $stmt_delete->execute();

            // 2. Thêm các phân công mới
            if (!empty($assigneeIds)) {
                $sql_insert = "INSERT INTO task_assignments (task_id, user_id) VALUES (:task_id, :user_id)";
                $stmt_insert = $this->conn->prepare($sql_insert);
                foreach ($assigneeIds as $userId) {
                    $stmt_insert->execute([':task_id' => $taskId, ':user_id' => $userId]);
                }
            }
            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Đã cập nhật phân công.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Reassign Task Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi khi cập nhật phân công.'];
        }
    }

    public function createTask($projectId, $taskName, $description)
    {
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
            }
        } catch (PDOException $e) {
            error_log("Create Task Error: " . $e->getMessage());
        }
        return ['status' => 'error', 'message' => 'Không thể tạo nhiệm vụ.'];
    }

    /**
     * Cập nhật nhanh trạng thái của một nhiệm vụ.
     */
    public function updateTask($taskId, $taskName, $description, $status)
    {
        if (empty($taskId) || empty($taskName)) {
            return ['status' => 'error', 'message' => 'ID và tên nhiệm vụ không được để trống.'];
        }

        // Thêm kiểm tra trạng thái hợp lệ
        $allowedStatus = ['Cần làm', 'Đang làm', 'Hoàn thành', 'Đã duyệt'];
        if (!in_array($status, $allowedStatus)) {
            return ['status' => 'error', 'message' => 'Trạng thái không hợp lệ.'];
        }

        $sql = "UPDATE tasks SET 
                task_name = :task_name, 
                description = :description, 
                status = :status 
            WHERE id = :task_id";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindParam(':task_name', $taskName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Cập nhật thông tin nhiệm vụ thành công.'];
            }
        } catch (PDOException $e) {
            error_log("Update Task Error: " . $e->getMessage());
        }

        return ['status' => 'error', 'message' => 'Không thể cập nhật nhiệm vụ.'];
    }


    public function deleteTask($taskId)
    {
        if (empty($taskId)) {
            return ['status' => 'error', 'message' => 'ID nhiệm vụ không được để trống.'];
        }
        // Do có ON DELETE CASCADE, các bản ghi trong task_assignments cũng sẽ bị xóa
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
