<?php
// FILE: uploads_task.php (Đã cập nhật)
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

if (!isset($_GET['task_id'])) die("Lỗi: Không tìm thấy ID của nhiệm vụ.");
$taskId = (int)$_GET['task_id'];

try {
    $db = getDbConnection();
    $taskManager = new TaskManager($db);
    $current_task = $taskManager->getTaskById($taskId);
    $submitted_files = $taskManager->getFilesForTask($taskId);
} catch (Exception $e) {
    die("Không thể kết nối CSDL: " . $e->getMessage());
}

// Lấy projectId để tạo link quay lại
$projectId = $current_task['project_id'] ?? 0;

// Lấy và xóa thông báo flash
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Bài Nộp - Nhiệm Vụ #<?php echo $taskId; ?></title>
    <link rel="stylesheet" href="../../Assets/styles/features/task/uploads_task.css?v=2">
</head>
<body>
    <div class="container">
        
        <a href="../Projects/project_details.php?id=<?php echo $projectId; ?>" class="back-to-project-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            <span>Quay lại dự án</span>
        </a>

        <h1>Quản lý Bài Nộp: <?php echo htmlspecialchars($current_task['task_name'] ?? ''); ?></h1>

        <?php if ($flash_message): ?>
            <div class="result">
                <p class="<?php echo $flash_message['status']; ?>"><?php echo htmlspecialchars($flash_message['message']); ?></p>
            </div>
        <?php endif; ?>

        <form action="handle_submissions.php" method="POST">
    <input type="hidden" name="action" value="update_description">
    <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
    <div class="form-group">
        <label for="submission_text">Nội dung / Ghi chú</pre>
        <textarea name="submission_text" id="submission_text" placeholder="Nhập mô tả hoặc ghi chú..."><?php echo htmlspecialchars($current_task['submitted_text_content'] ?? ''); ?></textarea>
    </div>
    <button type="submit">Cập nhật Nội dung</button>
</form>
        <hr>

        <div class="form-group">
            <label>Các tệp đã nộp</label>
            <div class="file-list">
                <?php if (empty($submitted_files)): ?>
                    <p>Chưa có tệp nào được nộp.</p>
                <?php else: ?>
                    <?php foreach ($submitted_files as $file): ?>
                        <div class="file-item">
                            <a href="download.php?file_id=<?php echo $file['id']; ?>" target="_blank"><?php echo htmlspecialchars($file['file_name']); ?></a>
                            <form action="handle_submissions.php" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tệp này?');">
                                <input type="hidden" name="action" value="delete_file">
                                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                <button type="submit" class="delete-btn">Xóa</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <hr>

        <form action="handle_submissions.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_files">
            <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
            <div class="form-group">
                <label for="submitted_files">Tải lên tệp mới</label>
                <input type="file" name="submitted_files[]" id="submitted_files" multiple>
                <label for="submitted_files" class="file-drop-area">
                    <span class="file-msg"><strong>Chọn tệp</strong> hoặc kéo thả vào đây (có thể chọn nhiều tệp)</span>
                </label>
            </div>
            <button type="submit">Tải lên</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('submitted_files');
            const dropArea = document.querySelector('.file-drop-area');
            const fileMsgSpan = dropArea.querySelector('.file-msg');
            const originalFileMsg = fileMsgSpan.innerHTML;

            // Hàm cập nhật hiển thị tên file
            function updateFileDisplay() {
                const files = fileInput.files;
                if (files.length > 0) {
                    let fileNames = [];
                    for (let i = 0; i < files.length; i++) {
                        fileNames.push(files[i].name);
                    }
                    // Hiển thị tên các file đã chọn
                    fileMsgSpan.textContent = `Đã chọn: ${fileNames.join(', ')}`;
                } else {
                    // Trả về văn bản gốc nếu không có file nào được chọn
                    fileMsgSpan.innerHTML = originalFileMsg;
                }
            }

            // Lắng nghe sự kiện khi người dùng chọn file bằng cách click
            fileInput.addEventListener('change', updateFileDisplay);

            // Cập nhật sự kiện kéo thả để hiển thị tên file
            dropArea.addEventListener('dragover', (e) => { e.preventDefault(); });
            dropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                // Gán file đã thả vào input và kích hoạt sự kiện 'change'
                fileInput.files = e.dataTransfer.files;
                updateFileDisplay(); 
            });
        });
    </script>

</body>
</html>