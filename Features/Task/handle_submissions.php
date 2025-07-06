<?php
// FILE: handle_submissions.php
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

if (!isset($_POST['task_id']) || !isset($_POST['action'])) {
    header("Location: dashboard.php"); // Hoặc trang lỗi
    exit();
}

$taskId = (int)$_POST['task_id'];
$action = $_POST['action'];

try {
    $db = getDbConnection();
    $taskManager = new TaskManager($db);

    switch ($action) {
       case 'update_description':
    // Lấy dữ liệu từ textarea có name="submission_text"
    $submissionText = $_POST['submission_text'] ?? ''; 
    // Gọi hàm mới trong TaskManager
    $taskManager->updateTaskSubmissionText($taskId, $submissionText); 
    $_SESSION['flash_message'] = ['status' => 'success', 'message' => 'Đã cập nhật nội dung thành công.'];
    break;

        case 'add_files':
            if (isset($_FILES['submitted_files'])) {
                $files = $_FILES['submitted_files'];
                $file_count = count($files['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] === 0) {
                        $fileName = basename($files['name'][$i]);
                        $fileMimeType = $files['type'][$i];
                        $fileContent = file_get_contents($files['tmp_name'][$i]);
                        $taskManager->addFileToTask($taskId, $fileName, $fileMimeType, $fileContent);
                    }
                }
                $_SESSION['flash_message'] = ['status' => 'success', 'message' => 'Đã tải lên các tệp thành công.'];
            }
            break;

        case 'delete_file':
            if (isset($_POST['file_id'])) {
                $fileId = (int)$_POST['file_id'];
                $taskManager->deleteFileById($fileId);
                $_SESSION['flash_message'] = ['status' => 'success', 'message' => 'Đã xóa tệp thành công.'];
            }
            break;
    }
} catch (Exception $e) {
     $_SESSION['flash_message'] = ['status' => 'error', 'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()];
}

// Quay trở lại trang quản lý bài nộp
header("Location: uploads_task.php?task_id=" . $taskId);
exit();
?>