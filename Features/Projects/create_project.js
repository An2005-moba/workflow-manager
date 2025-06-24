// Chờ cho toàn bộ nội dung trang được tải xong rồi mới chạy script
document.addEventListener('DOMContentLoaded', function () {

    // Lấy các phần tử DOM cần thiết
    const projectForm = document.getElementById('projectForm');
    const messageDiv = document.getElementById('message');

    // Thêm một trình nghe sự kiện 'submit' cho form
    projectForm.addEventListener('submit', function (event) {
        // Ngăn chặn hành vi mặc định của form (tải lại trang)
        event.preventDefault();

        // 1. SỬA LỖI: Chỉ cần validate tên dự án
        const projectName = document.getElementById('project_name').value.trim();

        // Chỉ kiểm tra projectName vì userId giờ được lấy từ session
        if (projectName === '') {
            showMessage('Vui lòng điền Tên Dự Án.', 'error');
            return; // Dừng thực thi nếu validation thất bại
        }
        
        // 2. Gửi dữ liệu bằng Fetch API (AJAX)
        // Tạo một đối tượng FormData từ chính form.
        // FormData sẽ tự động lấy các trường 'project_name' và 'description'.
        const formData = new FormData(projectForm);

        // Hiển thị thông báo đang xử lý
        showMessage('Đang xử lý, vui lòng chờ...', 'processing');

        // 2. SỬA LỖI: Đổi tên file thành 'create_project.php'
        fetch('create_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Kiểm tra nếu response không phải là JSON hoặc lỗi server
            if (!response.ok) {
                // Nếu lỗi 401 (chưa đăng nhập), sẽ hiển thị thông báo tương ứng
                if (response.status === 401) {
                    throw new Error('Bạn phải đăng nhập để tạo dự án.');
                }
                throw new Error('Lỗi từ máy chủ. Vui lòng thử lại.');
            }
            return response.json(); // Chuyển đổi phản hồi từ server sang JSON
        })
        .then(data => {
            // Xử lý dữ liệu JSON nhận được
            if (data.status === 'success') {
                showMessage(data.message, 'success');
                projectForm.reset(); // Xóa các trường trong form sau khi thành công
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            // Xử lý các lỗi về mạng hoặc lỗi khi parse JSON
            console.error('Fetch Error:', error);
            showMessage(error.message, 'error'); // Hiển thị thông báo lỗi thân thiện hơn
        });
    });

    /**
     * Hàm trợ giúp để hiển thị thông báo
     * @param {string} text - Nội dung thông báo
     * @param {string} type - Loại thông báo ('success', 'error', 'processing')
     */
    function showMessage(text, type) {
        messageDiv.textContent = text;
        // Thêm các class để tạo hiệu ứng (nếu có trong CSS)
        messageDiv.className = 'message-area'; 
        messageDiv.classList.add(type);
    }
});
