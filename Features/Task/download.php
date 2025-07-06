<?php
// FILE: download.php (PHIÊN BẢN SỬA LỖI TypeError)

ob_start();

require_once '../../Context/db_connection.php';

if (!isset($_GET['file_id']) || empty($_GET['file_id'])) {
    http_response_code(400);
    die("Lỗi: Thiếu ID của file.");
}
$fileId = (int)$_GET['file_id'];

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare(
        "SELECT file_name, mime_type, file_content FROM task_files WHERE id = :file_id"
    );
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();

    // --- THAY ĐỔI CHÍNH Ở ĐÂY ---
    // Lấy dữ liệu vào một mảng thay vì bind vào từng biến
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        if (empty($file['file_content'])) {
            http_response_code(404);
            die("File không có nội dung.");
        }
        
        // Gán dữ liệu từ mảng vào các biến
        $fileName = $file['file_name'];
        $mimeType = $file['mime_type'];
        $fileContent = $file['file_content']; // Bây giờ $fileContent sẽ là một chuỗi (string)

        ob_end_clean();

        header("Content-Type: " . ($mimeType ?? 'application/octet-stream'));
        header("Content-Length: " . strlen($fileContent)); // Lệnh này bây giờ sẽ hoạt động
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        echo $fileContent;
        exit;

    } else {
        http_response_code(404);
        die("Không tìm thấy file.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Download File Error: " . $e->getMessage());
    die("Lỗi cơ sở dữ liệu.");
}
?>