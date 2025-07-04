<?php

// Bật báo lỗi để dễ dàng debug trong quá trình phát triển (có thể tắt khi triển khai thực tế)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cấu hình Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Mật khẩu trống cho XAMPP
define('DB_NAME', 'web_project_db');

/**
 * Hàm để thiết lập và trả về đối tượng PDO Connection.
 *
 * @return PDO Đối tượng kết nối PDO.
 * @throws PDOException Nếu kết nối database thất bại.
 */
function getDbConnection() {
    $conn = null;
    try {
        $dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Chế độ báo lỗi: ném ra Exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Chế độ lấy dữ liệu mặc định: mảng kết hợp
            PDO::ATTR_EMULATE_PREPARES => false, // Tắt mô phỏng prepared statements (dùng prepared statements thật)
        ];
        $conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        // Ghi log lỗi thay vì chỉ hiển thị trực tiếp lên trình duyệt
        error_log("Database Connection Error: " . $e->getMessage(), 0);
        // Có thể redirect đến trang lỗi hoặc hiển thị thông báo thân thiện hơn cho người dùng
        die("Kết nối database thất bại: " . $e->getMessage()); // Dừng script và báo lỗi
    }
    return $conn;
}
/**
 * HÀM QUAN TRỌNG: Chuyển đổi chuỗi có dấu thành chuỗi không dấu, an toàn cho CSS/URL.
 * Ví dụ: "Đang làm" -> "danglam"
 */
function create_slug($string){
   $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#', '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#', '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#', '#(ỳ|ý|ỵ|ỷ|ỹ)#', '#(đ)#',
        '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#', '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#',
        '#(Ì|Í|Ị|Ỉ|Ĩ)#', '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#',
        '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#', '#(Ỳ|Ý|Ỵ|Ỷ|Ỹ)#', '#(Đ)#',
        "/[^a-zA-Z0-9\-\_]/"
   );
   $replace = array(
        'a', 'e', 'i', 'o', 'u', 'y', 'd',
        'A', 'E', 'I', 'O', 'U', 'Y', 'D',
        '-'
   );
   $string = preg_replace($search, $replace, $string);
   $string = preg_replace('/(-)+/', '-', $string);
   $string = strtolower($string);
   $string = str_replace(' ', '', $string); 
   
   // SỬA LỖI: Xóa bỏ dấu gạch ngang để khớp với CSS
   $string = str_replace('-', '', $string);
   
   return $string;
}
function get_deadline_info($deadline_str) {
    if (empty($deadline_str)) return ['text' => '', 'class' => ''];
    try {
        $deadline = new DateTime($deadline_str);
        $now = new DateTime();
        $deadline->setTime(0, 0, 0);
        $now->setTime(0, 0, 0);
        $diff = $now->diff($deadline);
        $days_left = (int)$diff->format('%r%a');
        if ($days_left < 0) return ['text' => 'Quá hạn ' . abs($days_left) . ' ngày', 'class' => 'overdue'];
        if ($days_left == 0) return ['text' => 'Hết hạn hôm nay', 'class' => 'due-today'];
        if ($days_left <= 3) return ['text' => 'Còn ' . $days_left . ' ngày', 'class' => 'due-soon'];
        return ['text' => 'Hạn chót: ' . date('d/m/Y', strtotime($deadline_str)), 'class' => 'safe'];
    } catch (Exception $e) { return ['text' => '', 'class' => '']; }
}
// Bạn có thể thêm một số hàm tiện ích khác ở đây nếu muốn,
// ví dụ: một hàm để đóng kết nối (mặc dù PDO tự động đóng khi script kết thúc)

?>