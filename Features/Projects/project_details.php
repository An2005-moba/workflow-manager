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

    // Lấy danh sách thành viên trước, vì form lọc cần nó
    $members = $memberManager->getProjectMembers($projectId);

    // BƯỚC 1: Lấy các giá trị lọc từ URL
    $filter_status = $_GET['status'] ?? 'all';
    $filter_assignee = $_GET['assignee'] ?? 'all';

    // BƯỚC 2: Tạo mảng $filters
    $filters = [
        'status' => $filter_status,
        'assignee' => $filter_assignee
    ];

    // BƯỚC 3: Lấy danh sách nhiệm vụ và truyền $filters vào
    $tasks = $taskManager->getTasksByProjectId($projectId, $filters);
} catch (Exception $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
// --- BẮT ĐẦU: Tính toán tiến độ dự án ---
$total_tasks = count($tasks);
$completed_tasks = 0;
foreach ($tasks as $task) {
    // Coi "Hoàn thành" và "Đã duyệt" là đã xong
    if ($task['status'] === 'Hoàn thành' || $task['status'] === 'Đã duyệt') {
        $completed_tasks++;
    }
}
// Tính phần trăm, tránh lỗi chia cho 0
$percentage = ($total_tasks > 0) ? ($completed_tasks / $total_tasks) * 100 : 0;
// --- KẾT THÚC: Tính toán tiến độ dự án ---
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết dự án: <?php echo htmlspecialchars($project['project_name'] ?? 'Không rõ'); ?></title>
    <link rel="stylesheet" href="../../Assets/styles/features/projects/project_details.css?v=3">
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

            <div class="project-progress-summary">
                <div class="progress-text">
                    <span>Tiến độ</span>
                    <strong id="progress-summary-text"><?php echo $completed_tasks; ?>/<?php echo $total_tasks; ?></strong>
                </div>
                <div class="progress-bar-container">
                    <div id="progress-bar-fill" class="progress-bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                </div>
            </div>
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
                    <h2 class="header-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" x2="19" y1="8" y2="14"></line>
                            <line x1="22" x2="16" y1="11" y2="11"></line>
                        </svg>
                        <span>Thành viên</span>
                    </h2>

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
                <h2 class="header-with-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Tạo nhiệm vụ mới</span>
                </h2>
                <form id="create-task-form" action="../Task/handle_create_task.php" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">


                    <div class="form-group">
                        <label for="deadline">Thời hạn (Deadline)</label>
                        <<input type="datetime-local" id="deadline" name="deadline">
                    </div>
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
                <div id="task-ajax-message" class="flash-message" style="margin-bottom: 16px;"></div>

                <form method="GET"
                    action="project_details.php"
                    class="filter-form"
                    data-api-url="../Task/get_filtered_tasks_api.php">
                    <input type="hidden" name="id" value="<?php echo $projectId; ?>">
                    <div class="filter-group">
                        <label for="filter-status">Lọc theo trạng thái:</label>
                        <select name="status" id="filter-status">
                            <option value="all">Tất cả trạng thái</option>
                            <option value="Cần làm" <?php if (isset($_GET['status']) && $_GET['status'] == 'Cần làm') echo 'selected'; ?>>Cần làm</option>
                            <option value="Đang làm" <?php if (isset($_GET['status']) && $_GET['status'] == 'Đang làm') echo 'selected'; ?>>Đang làm</option>
                            <option value="Hoàn thành" <?php if (isset($_GET['status']) && $_GET['status'] == 'Hoàn thành') echo 'selected'; ?>>Hoàn thành</option>
                            <option value="Đã duyệt" <?php if (isset($_GET['status']) && $_GET['status'] == 'Đã duyệt') echo 'selected'; ?>>Đã duyệt</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-assignee">Lọc theo người được giao:</label>
                        <select name="assignee" id="filter-assignee">
                            <option value="all">Tất cả thành viên</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['id']; ?>" <?php if (isset($_GET['assignee']) && $_GET['assignee'] == $member['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">Lọc</button>
                </form>

                <div class="task-list">
                    <?php if (empty($tasks)): ?>
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
                                            // Luôn sử dụng hàm create_slug để đảm bảo tính nhất quán
                                            $status_class = create_slug($status_text);
                                            ?>
                                            <span class="status-badge" data-status="<?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($status_text); ?>
                                            </span>
                                        </div>
                                        <div class="task-actions">
                                            <button class="task-action-btn edit-task-btn">Sửa</button>
                                            <button
                                                class="task-action-btn delete-task-btn"
                                                data-task-id="<?php echo $task['id']; ?>"
                                                data-project-id="<?php echo $projectId; ?>">
                                                Xóa
                                            </button>
                                        </div>
                                    </div>
                                    <div class="task-comments-section">
                                        <div class="comment-list" data-task-id="<?php echo $task['id']; ?>">
                                            <?php
                                            // Lấy các bình luận cho nhiệm vụ này
                                            $comments = $taskManager->getCommentsByTaskId($task['id']);
                                            foreach ($comments as $comment) :
                                            ?>
                                                <div class="comment-item">
                                                    <strong><?php echo htmlspecialchars($comment['user_name']); ?>:</strong>
                                                    <span><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <form class="add-comment-form" action="../Task/handle_add_comment.php" method="POST">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
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
                                            <?php
                                            // Chuyển đổi định dạng ngày giờ để hiển thị đúng
                                            $deadline_value = '';
                                            if (!empty($task['deadline'])) {
                                                $deadline_value = date('Y-m-d\TH:i', strtotime($task['deadline']));
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>



    <script src="../../Features/Projects/project_details.js"></script>
</body>

</html>