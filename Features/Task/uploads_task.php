<?php
// Khai báo các biến để lưu thông báo và nội dung đã gửi
$upload_message = '';
$text_content_display = '';

// Chỉ xử lý khi người dùng nhấn nút submit (khi request method là POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Xử lý phần văn bản ---
    if (isset($_POST["submitted_text"]) && !empty($_POST["submitted_text"])) {
        $text_content_display = htmlspecialchars($_POST["submitted_text"]);
    }

    // --- Xử lý phần file ---
    if (isset($_FILES["submitted_file"]) && $_FILES["submitted_file"]["error"] == 0) {
        
        $upload_dir = "uploads/"; // Thư mục lưu file

        // Tạo thư mục 'uploads' nếu nó chưa tồn tại
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = basename($_FILES["submitted_file"]["name"]);
        $target_file = $upload_dir . $file_name;

        // Di chuyển file đã upload vào thư mục 'uploads'
        if (move_uploaded_file($_FILES["submitted_file"]["tmp_name"], $target_file)) {
            $upload_message = "<p class='success'>File <strong>". htmlspecialchars($file_name) . "</strong> đã được tải lên thành công.</p>";
        } else {
            $upload_message = "<p class='error'>Đã có lỗi xảy ra khi tải file lên.</p>";
        }
    } else if (isset($_FILES["submitted_file"]) && $_FILES["submitted_file"]["error"] != UPLOAD_ERR_NO_FILE) {
        // Bắt các lỗi upload khác
        $upload_message = "<p class='error'>Lỗi upload file. Mã lỗi: " . $_FILES["submitted_file"]["error"] . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <link rel="stylesheet" href="../../Assets/styles/features/task/uploads_task.css?v=1">
    <title>Nộp File và Văn bản</title>
   
</head>
<body>
    <div class="container">
        <h1>Nộp bài tập</h1>
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="result">
                <h2>Kết quả</h2>
                <?php 
                    echo $upload_message;
                    if (!empty($text_content_display)) {
                        echo "<p><strong>Nội dung văn bản đã nộp:</strong></p>";
                        echo "<pre>" . $text_content_display . "</pre>";
                    }
                ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="submitted_file">1. Nộp file của bạn</label>
                <input type="file" name="submitted_file" id="submitted_file">
            </div>
            <div class="form-group">
                <label for="submitted_text">2. Nộp văn bản (nếu có)</label>
                <textarea name="submitted_text" id="submitted_text" placeholder="Nhập nội dung văn bản vào đây..."><?php echo htmlspecialchars($_POST['submitted_text'] ?? ''); ?></textarea>
            </div>
            <button type="submit">Nộp bài</button>
        </form>
    </div>
</body>
</html>