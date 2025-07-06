<?php
class TaskManager
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function getTasksByProjectId($projectId, $filters = [])
    {
        // Câu lệnh SQL cơ bản
        $sql = "SELECT t.*, GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as assignee_names
            FROM tasks t
            LEFT JOIN task_assignments ta ON t.id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE t.project_id = :project_id";

        // Mảng chứa các tham số để bind
        $params = [':project_id' => $projectId];

        // Thêm điều kiện lọc theo trạng thái
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Thêm điều kiện lọc theo người được giao
        // Dùng subquery để đảm bảo lọc đúng các task có người đó được gán
        if (!empty($filters['assignee']) && $filters['assignee'] !== 'all') {
            $sql .= " AND t.id IN (SELECT task_id FROM task_assignments WHERE user_id = :assignee_id)";
            $params[':assignee_id'] = $filters['assignee'];
        }

        // Luôn có GROUP BY và ORDER BY ở cuối
        $sql .= " GROUP BY t.id ORDER BY t.created_at DESC";

        try {
            $stmt = $this->conn->prepare($sql);

            // Thực thi với các tham số đã được thêm vào
            $stmt->execute($params);

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

    // Hàm createTask mới
    public function createTask($projectId, $taskName, $description, $deadline = null)
    {
        if (empty($projectId) || empty($taskName)) {
            return ['status' => 'error', 'message' => 'Tên nhiệm vụ và ID dự án không được để trống.'];
        }
        // Thêm cột 'deadline' vào câu lệnh INSERT
        $sql = "INSERT INTO tasks (project_id, task_name, description, status, deadline) VALUES (:project_id, :task_name, :description, 'Cần làm', :deadline)";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
            $stmt->bindParam(':task_name', $taskName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            // Thêm dòng này để gán giá trị cho deadline
            $stmt->bindValue(':deadline', empty($deadline) ? null : $deadline, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $newId = $this->conn->lastInsertId();
                return ['status' => 'success', 'message' => 'Nhiệm vụ đã được tạo thành công.', 'new_task_id' => $newId];
            }
        } catch (PDOException $e) {
            error_log("Create Task Error: " . $e->getMessage());
        }
        return ['status' => 'error', 'message' => 'Không thể tạo nhiệm vụ.'];
    }

    /**
     * Cập nhật nhanh trạng thái của một nhiệm vụ.
     */
    // Hàm updateTask mới
    public function updateTask($taskId, $taskName, $description, $status, $deadline = null)
    {
        if (empty($taskId) || empty($taskName)) {
            return ['status' => 'error', 'message' => 'ID và tên nhiệm vụ không được để trống.'];
        }

        $allowedStatus = ['Cần làm', 'Đang làm', 'Hoàn thành', 'Đã duyệt'];
        if (!in_array($status, $allowedStatus)) {
            return ['status' => 'error', 'message' => 'Trạng thái không hợp lệ.'];
        }

        // Thêm 'deadline = :deadline' vào câu lệnh UPDATE
        $sql = "UPDATE tasks SET 
                task_name = :task_name, 
                description = :description, 
                status = :status,
                deadline = :deadline 
            WHERE id = :task_id";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindParam(':task_name', $taskName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            // Thêm dòng này để gán giá trị cho deadline
            $stmt->bindValue(':deadline', empty($deadline) ? null : $deadline, PDO::PARAM_STR);

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
    public function getTaskById($taskId)
    {
        // Câu lệnh này lấy thông tin của một task, tương tự như trong getTasksByProjectId
        $sql = "SELECT t.*, GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as assignee_names
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.id = ta.task_id
                LEFT JOIN users u ON ta.user_id = u.id
                WHERE t.id = :task_id
                GROUP BY t.id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Task By ID Error: " . $e->getMessage());
            return null;
        }
    }
    public function getProjectProgress($projectId)
    {
        // Đếm tổng số nhiệm vụ trong dự án
        $stmt_total = $this->conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = :project_id");
        $stmt_total->execute([':project_id' => $projectId]);
        $total_tasks = $stmt_total->fetchColumn();

        // Đếm số nhiệm vụ đã hoàn thành (Hoàn thành hoặc Đã duyệt)
        $stmt_completed = $this->conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = :project_id AND (status = 'Hoàn thành' OR status = 'Đã duyệt')");
        $stmt_completed->execute([':project_id' => $projectId]);
        $completed_tasks = $stmt_completed->fetchColumn();

        return [
            'total_tasks' => (int)$total_tasks,
            'completed_tasks' => (int)$completed_tasks
        ];
    }
    // Thêm 2 hàm này vào cuối file, trước dấu } của class

    /**
     * Lấy tất cả bình luận của một nhiệm vụ.
     */
    public function getCommentsByTaskId($taskId)
    {
        $sql = "SELECT c.*, u.name as user_name 
            FROM task_comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.task_id = :task_id 
            ORDER BY c.created_at ASC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Comments Error: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Thêm một bình luận mới vào nhiệm vụ.
     */
    public function addComment($taskId, $userId, $commentText)
    {
        if (empty($commentText)) {
            return ['status' => 'error', 'message' => 'Nội dung bình luận không được để trống.'];
        }
        $sql = "INSERT INTO task_comments (task_id, user_id, comment_text) VALUES (:task_id, :user_id, :comment_text)";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':comment_text', $commentText, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $newCommentId = $this->conn->lastInsertId();
                // Lấy lại bình luận vừa tạo để trả về cho giao diện
                $newComment = $this->getCommentById($newCommentId); // Cần tạo hàm này
                return ['status' => 'success', 'message' => 'Đã thêm bình luận.', 'new_comment' => $newComment];
            }
        } catch (PDOException $e) {
            error_log("Add Comment Error: " . $e->getMessage());
        }
        return ['status' => 'error', 'message' => 'Không thể thêm bình luận.'];
    }

    /**
     * Lấy một bình luận theo ID (hàm hỗ trợ).
     */
    private function getCommentById($commentId)
    {
        $sql = "SELECT c.*, u.name as user_name 
            FROM task_comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = :comment_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':comment_id' => $commentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getNotificationsForUser($userId, $limit = 10)
    {
        $sql = "SELECT 
            t.id AS task_id,
            t.task_name,
            t.deadline,
            t.created_at AS task_created_at,
            p.id AS project_id,
            p.project_name
        FROM tasks t
        JOIN task_assignments ta ON t.id = ta.task_id
        JOIN projects p ON t.project_id = p.id
        WHERE ta.user_id = :user_id
        ORDER BY t.created_at DESC
        LIMIT :limit_val";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit_val', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Notifications Error: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Xóa một bình luận, chỉ khi người dùng là tác giả của bình luận đó.
     *
     * @param int $commentId ID của bình luận cần xóa.
     * @param int $userId ID của người dùng đang thực hiện hành động.
     * @return array Kết quả trả về dạng mảng.
     */
    public function deleteComment($commentId, $userId)
    {
        if (empty($commentId) || empty($userId)) {
            return ['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.'];
        }

        // Mệnh đề WHERE đảm bảo quy tắc phân quyền:
        // Bình luận chỉ bị xóa nếu ID của nó khớp VÀ user_id (tác giả) khớp với người dùng đang đăng nhập.
        $sql = "DELETE FROM task_comments WHERE id = :comment_id AND user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Kiểm tra xem có dòng nào thực sự bị xóa không.
            // Nếu không, có nghĩa là người dùng không có quyền (user_id không khớp).
            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Đã xóa bình luận.'];
            } else {
                return ['status' => 'error', 'message' => 'Bạn không có quyền xóa bình luận này.'];
            }
        } catch (PDOException $e) {
            error_log("Delete Comment DB Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu khi xóa bình luận.'];
        }
    }
    public function addFileToTask($taskId, $fileName, $fileMimeType, $fileContent)
    {
        $sql = "INSERT INTO task_files (task_id, file_name, mime_type, file_content) 
                VALUES (:task_id, :file_name, :mime_type, :file_content)";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindValue(':file_name', $fileName, PDO::PARAM_STR);
            $stmt->bindValue(':mime_type', $fileMimeType, PDO::PARAM_STR);
            $stmt->bindValue(':file_content', $fileContent, PDO::PARAM_LOB);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Add File To Task Error: " . $e->getMessage());
            return false;
        }
    }

    // HÀM MỚI: Lấy tất cả file của một nhiệm vụ
    public function getFilesForTask($taskId)
    {
        $sql = "SELECT id, file_name, uploaded_at FROM task_files WHERE task_id = :task_id ORDER BY uploaded_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':task_id' => $taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // HÀM MỚI: Xóa một file bằng ID của file
    public function deleteFileById($fileId)
    {
        $sql = "DELETE FROM task_files WHERE id = :file_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':file_id' => $fileId]);
    }

    // HÀM MỚI: Chỉ cập nhật mô tả của task
    // HÀM ĐÃ SỬA: Cập nhật vào cột submitted_text_content
    public function updateTaskSubmissionText($taskId, $submissionText)
    {
        $sql = "UPDATE tasks SET submitted_text_content = :submission_text WHERE id = :task_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
        $stmt->bindValue(':submission_text', $submissionText, PDO::PARAM_STR);
        return $stmt->execute();
    }
}
