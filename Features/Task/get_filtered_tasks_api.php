<?php
session_start();
// --- Nhúng các file cần thiết ---
require_once '../../Context/db_connection.php'; // Đã có hàm create_slug() ở đây
require_once '../../Modules/Projects/ProjectManager.php';
require_once '../../Modules/Task/TaskManager.php';
require_once '../../Modules/Projects/MemberManager.php';

// --- Kiểm tra đăng nhập và các tham số cần thiết ---
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit();
}

$projectId = (int)$_GET['id'];

try {
    $dbConnection = getDbConnection();
    $taskManager = new TaskManager($dbConnection);
    $memberManager = new MemberManager($dbConnection);

    $members = $memberManager->getProjectMembers($projectId);
    $filters = [
        'status' => $_GET['status'] ?? 'all',
        'assignee' => $_GET['assignee'] ?? 'all'
    ];
    $tasks = $taskManager->getTasksByProjectId($projectId, $filters);
} catch (Exception $e) {
    $tasks = [];
}

// --- Trả về phần HTML của danh sách nhiệm vụ ---
if (empty($tasks)): ?>
    <p class="empty-list">Không có nhiệm vụ nào khớp với bộ lọc của bạn.</p>
<?php else: ?>
    <?php foreach ($tasks as $task): ?>
        <div class="task-item" data-task-id="<?php echo $task['id']; ?>">
            <div class="task-view">
                <div class="task-info">
                    <h3 class="task-name"><?php echo htmlspecialchars($task['task_name']); ?></h3>
                    <p class="task-description"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    <div class="task-assignee">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span><?php echo !empty($task['assignee_names']) ? htmlspecialchars($task['assignee_names']) : 'Chưa gán'; ?></span>
                    </div>
                    <?php $deadline_info = get_deadline_info($task['deadline']); ?>
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
                        $status_text = ($task['status'] === 'To Do') ? 'Cần làm' : $task['status'];
                        // SỬ DỤNG HÀM CHUNG TẠI ĐÂY
                        $status_class = create_slug($status_text);
                        ?>
                        <span class="status-badge" data-status="<?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status_text); ?>
                        </span>
                    </div>
                    <div class="task-actions">
                        <button class="task-action-btn edit-task-btn">Sửa</button>
                        <button class="task-action-btn delete-task-btn" data-task-id="<?php echo $task['id']; ?>" data-project-id="<?php echo $projectId; ?>">Xóa</button>
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
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <div class="form-group">
                        <label>Tên nhiệm vụ</label>
                        <input type="text" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Gán cho thành viên</label>
                        <div class="assignee-checkbox-container">
                            <?php
                            $assignedUserIds = $taskManager->getAssigneeIdsForTask($task['id']);
                            ?>
                            <?php foreach ($members as $member): ?>
                                <div class="assignee-checkbox-item">
                                    <input type="checkbox" name="assignee_ids[]" value="<?php echo $member['id']; ?>" id="task-<?php echo $task['id']; ?>-member-<?php echo $member['id']; ?>" <?php if (in_array($member['id'], $assignedUserIds)) echo 'checked'; ?>>
                                    <label for="task-<?php echo $task['id']; ?>-member-<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status">
                            <option value="Cần làm" <?php if ($task['status'] == 'Cần làm' || $task['status'] == 'To Do') echo 'selected'; ?>>Cần làm</option>
                            <option value="Đang làm" <?php if ($task['status'] == 'Đang làm') echo 'selected'; ?>>Đang làm</option>
                            <option value="Hoàn thành" <?php if ($task['status'] == 'Hoàn thành') echo 'selected'; ?>>Hoàn thành</option>
                            <option value="Đã duyệt" <?php if ($task['status'] == 'Đã duyệt') echo 'selected'; ?>>Đã duyệt</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thời hạn (Deadline)</label>
                        <input type="datetime-local" name="deadline" value="<?php echo htmlspecialchars($task['deadline'] ?? ''); ?>">
                    </div>
                    <div class="edit-form-actions">
                        <button type="submit" class="btn-submit">Lưu thay đổi</button>
                        <button type="button" class="btn-cancel cancel-edit-btn">Hủy</button>
                    </div>
                </form>
            </div>

        </div>
    <?php endforeach; ?>
<?php endif; ?>