<?php
session_start();
require_once '../../Context/db_connection.php';
require_once '../../Modules/Projects/MemberManager.php';

// Kiểm tra đăng nhập và phương thức POST
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../Auth/login.html");
    exit();
}

$projectId = $_POST['project_id'] ?? 0;
$userId = $_POST['user_id'] ?? 0;

if ($projectId && $userId) {
    $dbConnection = getDbConnection();
    $memberManager = new MemberManager($dbConnection);
    $result = $memberManager->removeMember($projectId, $userId);
    $_SESSION['flash_message'] = $result['message'];
} else {
    $_SESSION['flash_message'] = "Dữ liệu không hợp lệ.";
}

header("Location: project_details.php?id=" . $projectId);
exit();
?>