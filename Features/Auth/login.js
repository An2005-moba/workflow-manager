$(document).ready(function() {
    const loginForm = $('#loginForm');

    // Hàm để hiển thị thông báo lỗi
    function showError(elementId, message) {
        $('#' + elementId).text(message);
    }

    // Hàm để xóa thông báo lỗi
    function clearError(elementId) {
        $('#' + elementId).text('');
    }

    // Lắng nghe sự kiện submit của form
    loginForm.on('submit', function(event) {
        event.preventDefault(); // Ngăn chặn hành vi gửi form mặc định

        // Xóa tất cả các thông báo lỗi cũ
        clearError('emailError');
        clearError('passwordError');

        // Lấy giá trị từ các trường nhập liệu
        const email = $('#email').val().trim();
        const password = $('#password').val();

        let isValid = true; // Biến cờ để kiểm tra xem form có hợp lệ không

        // --- Kiểm tra Email ---
        if (email === '') {
            showError('emailError', 'Email không được để trống.');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('emailError', 'Địa chỉ email không hợp lệ.');
            isValid = false;
        }

        // --- Kiểm tra Mật khẩu ---
        if (password === '') {
            showError('passwordError', 'Mật khẩu không được để trống.');
            isValid = false;
        } else if (password.length < 6) { // Thêm điều kiện độ dài mật khẩu (tùy chọn)
            showError('passwordError', 'Mật khẩu phải có ít nhất 6 ký tự.');
            isValid = false;
        }

        // --- Xử lý khi form hợp lệ hoặc không hợp lệ ---
        if (isValid) {
            console.log('Dữ liệu đăng nhập hợp lệ:');
            console.log('Email:', email);
            // Bạn KHÔNG NÊN log mật khẩu ra console trong thực tế.
            // console.log('Mật khẩu:', password); 
            
            alert('Đăng nhập thành công! (Dữ liệu đã được log ra console)');
            
            // Ở đây bạn sẽ gửi dữ liệu đăng nhập đến API backend:
            // Ví dụ:
            /*
            $.ajax({
                url: '/api/login', // Địa chỉ API đăng nhập của bạn
                method: 'POST',
                data: { email: email, password: password },
                success: function(response) {
                    console.log('Đăng nhập thành công:', response);
                    // Lưu token hoặc thông tin người dùng vào localStorage/sessionStorage
                    // window.location.href = '/dashboard'; // Chuyển hướng đến trang dashboard
                },
                error: function(xhr, status, error) {
                    console.error('Đăng nhập thất bại:', xhr.responseText);
                    showError('emailError', 'Email hoặc mật khẩu không đúng.'); // Hoặc một thông báo chung
                }
            });
            */

        } else {
            console.log('Đăng nhập thất bại: Vui lòng kiểm tra lại thông tin.');
        }
    });

    // Bonus: Xóa thông báo lỗi ngay khi người dùng bắt đầu nhập
    $('#loginForm input').on('input', function() {
        const errorSpanId = $(this).attr('id') + 'Error';
        clearError(errorSpanId);
    });
});