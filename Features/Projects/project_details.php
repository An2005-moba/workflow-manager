<?php
session_start();
// --- Kiểm tra đăng nhập ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Auth/login.html");
    exit();
}

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($projectId === 0) {
    die("Lỗi: Không có ID dự án.");
}

// --- Nhúng các file cần thiết ---
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/ProjectManager.php';
require_once '../../Modules/Task/TaskManager.php';
require_once '../../Modules/Projects/MemberManager.php';

$project = null;
$tasks = [];
$members = [];
$isProjectCreator = false;

try {
    $dbConnection = getDbConnection();
    $projectManager = new ProjectManager($dbConnection);
    $taskManager = new TaskManager($dbConnection);
    $memberManager = new MemberManager($dbConnection);

    // Lấy thông tin dự án
    $projectResult = $projectManager->getProjectById($projectId);
    if ($projectResult['status'] === 'success') {
        $project = $projectResult['project'];
        if (isset($project['created_by_user_id']) && $project['created_by_user_id'] == $_SESSION['user_id']) {
            $isProjectCreator = true;
        }
    } else {
        die("Không tìm thấy dự án.");
    }

    // Lấy danh sách nhiệm vụ và thành viên
    $tasks = $taskManager->getTasksByProjectId($projectId);
    $members = $memberManager->getProjectMembers($projectId);
} catch (Exception $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết dự án: <?php echo htmlspecialchars($project['project_name'] ?? 'Không rõ'); ?></title>
    <link rel="stylesheet" href="../../Assets/styles/features/projects/project_details.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="page-container">
        <a href="list.php" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18l-6-6l6-6" />
            </svg>
            <span>Quay lại danh sách dự án</span>
        </a>

        <header class="project-header">
            <h1><?php echo htmlspecialchars($project['project_name']); ?></h1>
            <p><?php echo htmlspecialchars($project['description']); ?></p>
        </header>

        <?php if (isset($_SESSION['flash'])): ?>
            <?php
            $flash = $_SESSION['flash'];
            $flash_status_class = ($flash['status'] === 'success') ? 'flash-success' : 'flash-error';
            ?>
            <div class="flash-message <?php echo $flash_status_class; ?>">
                <?php
                echo htmlspecialchars($flash['message']);
                unset($_SESSION['flash']);
                ?>
            </div>
        <?php endif; ?>

        <main class="project-main-content">
            <section class="members-section content-box">
                <div class="section-header">
                    <h2>Thành viên</h2>

                    <div class="add-member-container">
                        <?php if ($isProjectCreator): ?>
                            <button id="toggleAddMemberPopover" class="add-member-icon-btn" title="Thêm thành viên mới">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        <?php endif; ?>

                        <div id="addMemberPopover" class="add-member-popover">
                            <div class="popover-header">
                                <h4>Thêm thành viên</h4>
                                <button id="closeAddMemberPopover" class="close-popover-btn">&times;</button>
                            </div>
                            <div class="popover-content">
                                <form action="handle_add_member.php" method="POST" class="add-member-form">
                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                    <div class="form-group">
                                        <label for="email">Email thành viên:</label>
                                        <input type="email" id="email" name="email" required placeholder="nhapemail@example.com">
                                    </div>
                                    <button type="submit" class="btn-submit">Thêm</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="member-list">
                    <?php if (empty($members)): ?>
                        <p class="empty-list">Dự án này chưa có thành viên.</p>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                            <div class="member-item">
                                <span class="member-name"><?php echo htmlspecialchars($member['name']); ?></span>
                                <?php if ($isProjectCreator && $member['id'] != $_SESSION['user_id']): ?>
                                    <form action="handle_remove_member.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thành viên này?');">
                                        <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" class="remove-member-btn" title="Xóa thành viên">×</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="members-section content-box">
            </section>

            <section class="create-task-section content-box">
                <h2>Tạo nhiệm vụ mới</h2>
                <form action="../Task/handle_create_task.php" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                    <div class="form-group">
                        <label for="task_name">Tên nhiệm vụ</label>
                        <input type="text" id="task_name" name="task_name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Thêm nhiệm vụ</button>
                </form>
            </section>

            <section class="task-list-section content-box">
                <h2>Danh sách nhiệm vụ</h2>
                <div class="task-list">
                    <?php if (empty($tasks)): ?>
                        <p class="empty-list">Chưa có nhiệm vụ nào trong dự án này.</p>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-item" data-task-id="<?php echo $task['id']; ?>">
                                <div class="task-view">
                                    <div class="task-info" data-status="<?php echo htmlspecialchars(strtolower($task['status'])); ?>">
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
                                    </div>
                                    <div class="task-status">
                                        <span><?php echo htmlspecialchars($task['status']); ?></span>
                                    </div>
                                    <div class="task-actions">
                                        <button class="task-action-btn edit-task-btn">Sửa</button>
                                        <form action="../Task/handle_delete_task.php" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa nhiệm vụ này?');">
                                            <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <button type="submit" class="task-action-btn delete-task-btn">Xóa</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="edit-task-form-container" style="display: none;">
                                    <form action="handle_update_task.php" method="POST">
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
                                                // Lấy danh sách ID của các thành viên đã được gán cho task này
                                                $assignedUserIds = $taskManager->getAssigneeIdsForTask($task['id']);
                                                ?>
                                                <?php foreach ($members as $member): ?>
                                                    <div class="assignee-checkbox-item">
                                                        <input
                                                            type="checkbox"
                                                            name="assignee_ids[]"
                                                            value="<?php echo $member['id']; ?>"
                                                            id="task-<?php echo $task['id']; ?>-member-<?php echo $member['id']; ?>"
                                                            <?php if (in_array($member['id'], $assignedUserIds)) echo 'checked'; ?>>
                                                        <label for="task-<?php echo $task['id']; ?>-member-<?php echo $member['id']; ?>">
                                                            <?php echo htmlspecialchars($member['name']); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Trạng thái</label>
                                            <select name="status">
                                                <option value="To Do" <?php if ($task['status'] == 'To Do') echo 'selected'; ?>>To Do</option>
                                                <option value="In Progress" <?php if ($task['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                                                <option value="Done" <?php if ($task['status'] == 'Done') echo 'selected'; ?>>Done</option>
                                            </select>
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
                </div>
            </section>

        </main>
    </div>



    <script src="../../Features/Projects/project_details.js"></script>
</body>

</html>