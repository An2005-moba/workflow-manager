<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Nhúng các file cần thiết
require_once '../../Context/db_connection.php';
require_once '../../Modules/Task/TaskManager.php';

// --- ✅ HÀM time_ago PHIÊN BẢN SỬA LỖI ---
function time_ago($datetime) {
    if (empty($datetime)) {
        return '';
    }
    try {
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        // Lấy tổng số ngày, đây là cách tính chính xác nhất
        $total_days = (int)$diff->format('%a');

        if ($diff->y > 0) return $diff->y . ' năm trước';
        if ($diff->m > 0) return $diff->m . ' tháng trước';
        if ($total_days >= 7) return floor($total_days / 7) . ' tuần trước';
        if ($diff->d > 0) return $diff->d . ' ngày trước';
        if ($diff->h > 0) return $diff->h . ' giờ trước';
        if ($diff->i > 0) return $diff->i . ' phút trước';
        
        return 'vài giây trước';

    } catch (Exception $e) {
        error_log("Time Ago Error: " . $e->getMessage());
        return ''; // Trả về chuỗi rỗng nếu có lỗi
    }
}


if (!isset($_SESSION['user_id'])) {
    echo '<p class="notification-item">Vui lòng đăng nhập.</p>';
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();
    $taskManager = new TaskManager($db); 
    
    $notifications = $taskManager->getNotificationsForUser($userId);

    if (empty($notifications)) {
        echo '<p class="notification-item-empty">Bạn không có thông báo mới.</p>';
    } else {
       foreach ($notifications as $noti) {
    // TẠO LINK MỚI: Trỏ trực tiếp đến task trong dự án
   $task_link = "Features/Projects/project_details.php?id=" . htmlspecialchars($noti['project_id']) . "#task-" . htmlspecialchars($noti['task_id']);
    // Định dạng lại thời gian được tạo
    $time_ago = time_ago($noti['task_created_at']);

    // Lấy và định dạng deadline (nếu có)
    $deadline_text = '';
    if (!empty($noti['deadline'])) {
        $deadline_date = date("d/m/Y", strtotime($noti['deadline']));
        $deadline_text = "<span class='notification-deadline'>
                            <svg width='14' height='14' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M8 2V5' stroke='currentColor' stroke-width='2' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M16 2V5' stroke='currentColor' stroke-width='2' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M3.5 9.09H20.5' stroke='currentColor' stroke-width='2' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z' stroke='currentColor' stroke-width='2' stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/><path d='M15.6947 13.7H15.7037' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M15.6947 16.7H15.7037' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M11.9955 13.7H12.0045' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M11.9955 16.7H12.0045' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M8.29431 13.7H8.30331' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M8.29431 16.7H8.30331' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>
                            Deadline: " . $deadline_date . "
                        </span>";
    }
?>
<a href="<?php echo $task_link; ?>" class="notification-item">
    <div class="notification-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 0 0-7 7c0 3.03 1.63 5.57 4 6.67V17a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-1.33c2.37-1.1 4-3.64 4-6.67a7 7 0 0 0-7-7z"></path><line x1="12" y1="22" x2="12" y2="18"></line></svg>
    </div>
    <div class="notification-content">
        <p class="notification-text">
            Bạn được gán nhiệm vụ mới: <strong><?php echo htmlspecialchars($noti['task_name']); ?></strong> trong dự án <strong><?php echo htmlspecialchars($noti['project_name']); ?></strong>.
        </p>
        <div class="notification-meta">
            <span class="notification-time"><?php echo $time_ago; ?></span>
            <?php echo $deadline_text; // Hiển thị deadline nếu có ?>
        </div>
    </div>
</a>
<?php
}
    }

} catch (Exception $e) {
    echo '<p class="notification-item">Lỗi khi tải thông báo.</p>';
    error_log("Notification API Error: " . $e->getMessage());
}
?>