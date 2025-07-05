<?php
session_start();
header('Content-Type: application/json');

require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';
require_once '../../Modules/Projects/MemberManager.php';

function send_json_response($data)
{
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    send_json_response(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
}

$projectId = $_POST['project_id'] ?? 0;
$taskName = trim($_POST['task_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$deadline = $_POST['deadline'] ?? null; // ĐÃ THÊM

if (empty($projectId) || empty($taskName)) {
    send_json_response(['status' => 'error', 'message' => 'Tên nhiệm vụ và ID dự án không được để trống.']);
}

try {
    $db = getDbConnection();
    $taskManager = new TaskManager($db);
    $memberManager = new MemberManager($db);

    $result = $taskManager->createTask($projectId, $taskName, $description, $deadline); // ĐÃ THÊM DEADLINE

    if ($result['status'] === 'success' && isset($result['new_task_id'])) {
        $newTask = $taskManager->getTaskById($result['new_task_id']);
        $members = $memberManager->getProjectMembers($projectId);

        ob_start();
?>
        <div class="task-item" data-task-id="<?php echo $newTask['id']; ?>">
            <div class="task-view">
                <div class="task-info">
                    <h3 class="task-name"><?php echo htmlspecialchars($newTask['task_name']); ?></h3>
                    <p class="task-description"><?php echo nl2br(htmlspecialchars($newTask['description'])); ?></p>
                    <div class="task-assignee">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span><?php echo !empty($newTask['assignee_names']) ? htmlspecialchars($newTask['assignee_names']) : 'Chưa gán'; ?></span>
                    </div>

                    <?php $deadline_info = get_deadline_info($newTask['deadline']); ?>
                    <?php if (!empty($deadline_info['text'])): ?>
                        <div class="task-deadline <?php echo $deadline_info['class']; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span><?php echo htmlspecialchars($deadline_info['text']); ?></span>
                        </div>
                    <?php endif; ?>

                </div>
                <div class="task-right-panel">
                    <div class="task-status">
                        <?php
                        $status_text = ($newTask['status'] === 'To Do') ? 'Cần làm' : $newTask['status'];
                        $status_class = function_exists('create_slug') ? create_slug($status_text) : 'can-lam';
                        ?>
                        <span class="status-badge" data-status="<?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status_text); ?>
                        </span>
                    </div>
                    <div class="task-actions">
                        <button class="task-action-btn edit-task-btn">Sửa</button>
                        <button class="task-action-btn delete-task-btn" data-task-id="<?php echo $newTask['id']; ?>" data-project-id="<?php echo $projectId; ?>">Xóa</button>
                    </div>
                </div>

                <div class="task-comments-section">
                    <div class="comment-list" data-task-id="<?php echo $newTask['id']; ?>">
                    </div>
                    <form class="add-comment-form" action="../Task/handle_add_comment.php" method="POST">
                        <input type="hidden" name="task_id" value="<?php echo $newTask['id']; ?>">
                        <input type="text" name="comment_text" placeholder="Viết bình luận..." required>
                        <button type="submit">Gửi</button>
                    </form>
                </div>
            </div>

            <div class="edit-task-form-container" style="display: none;">
                <form action="../Task/handle_update_task.php" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                    <input type="hidden" name="task_id" value="<?php echo $newTask['id']; ?>">
                    <div class="form-group">
                        <label>Tên nhiệm vụ</label>
                        <input type="text" name="task_name" value="<?php echo htmlspecialchars($newTask['task_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($newTask['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Gán cho thành viên</label>
                        <div class="assignee-checkbox-container">
                            <?php
                            $assignedUserIds = $taskManager->getAssigneeIdsForTask($newTask['id']);
                            ?>
                            <?php foreach ($members as $member): ?>
                                <div class="assignee-checkbox-item">
                                    <input type="checkbox" name="assignee_ids[]" value="<?php echo $member['id']; ?>" id="task-<?php echo $newTask['id']; ?>-member-<?php echo $member['id']; ?>" <?php if (in_array($member['id'], $assignedUserIds)) echo 'checked'; ?>>
                                    <label for="task-<?php echo $newTask['id']; ?>-member-<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status">
                            <option value="Cần làm" <?php if ($newTask['status'] == 'Cần làm' || $newTask['status'] == 'To Do') echo 'selected'; ?>>Cần làm</option>
                            <option value="Đang làm" <?php if ($newTask['status'] == 'Đang làm') echo 'selected'; ?>>Đang làm</option>
                            <option value="Hoàn thành" <?php if ($newTask['status'] == 'Hoàn thành') echo 'selected'; ?>>Hoàn thành</option>
                            <option value="Đã duyệt" <?php if ($newTask['status'] == 'Đã duyệt') echo 'selected'; ?>>Đã duyệt</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thời hạn (Deadline)</label>
                        <?php
                        // Chuyển đổi định dạng ngày giờ để hiển thị đúng
                        $deadline_value = '';
                        if (!empty($newTask['deadline'])) {
                            $deadline_value = date('Y-m-d\TH:i', strtotime($newTask['deadline']));
                        }
                        ?>
                        <input type="datetime-local" name="deadline" value="<?php echo $deadline_value; ?>">
                    </div>
                    <div class="edit-form-actions">
                        <button type="submit" class="btn-submit">Lưu thay đổi</button>
                        <button type="button" class="btn-cancel cancel-edit-btn">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
<?php
        $task_html = trim(ob_get_clean());

        $projectProgress = $taskManager->getProjectProgress($projectId);

        send_json_response([
            'status' => 'success',
            'message' => 'Nhiệm vụ đã được tạo!',
            'task_html' => $task_html,
            'project_progress' => $projectProgress
        ]);
    } else {
        send_json_response($result);
    }
} catch (Exception $e) {
    error_log("Create Task API Error: " . $e->getMessage());
    send_json_response(['status' => 'error', 'message' => 'Lỗi hệ thống khi tạo nhiệm vụ.']);
}
?>