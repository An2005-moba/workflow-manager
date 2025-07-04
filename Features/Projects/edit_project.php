<?php
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/ProjectManager.php';

// Kiểm tra đăng nhập và ID dự án
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$projectId = $_GET['id'];
$userId = $_SESSION['user_id'];
$project = null;
$errorMessage = '';

try {
    $db = getDbConnection();
    $pm = new ProjectManager($db);
    $result = $pm->getProjectById($projectId);

    // Kiểm tra xem người dùng có quyền sửa dự án này không (chỉ người tạo mới có quyền)
    if ($result['status'] === 'success') {
        if ($result['project']['created_by_user_id'] == $userId) {
            $project = $result['project'];
        } else {
            $errorMessage = "Bạn không có quyền chỉnh sửa dự án này.";
        }
    } else {
        $errorMessage = $result['message'];
    }
} catch (Exception $e) {
    $errorMessage = "Lỗi hệ thống: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa dự án - WorkFlow</title>
    <link rel="stylesheet" href="../../Assets/styles/features/projects/form_edit.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="form-container">
        <h1>Chỉnh sửa dự án</h1>

        <?php if ($project): ?>
            <form action="handle_update_project.php" method="POST">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['id']); ?>">

                <div class="form-group">
                    <label for="project_name">Tên dự án</label>
                    <input type="text" id="project_name" name="project_name" required 
                           value="<?php echo htmlspecialchars($project['project_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="6"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <a href="list.php" class="btn btn-secondary">Hủy</a>
                    <button type="submit" class="btn btn-primary">Cập nhật dự án</button>
                </div>
            </form>
        <?php else: ?>
            <div class="error-message">
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
                <a href="list.php" class="btn btn-secondary">Quay lại danh sách</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>