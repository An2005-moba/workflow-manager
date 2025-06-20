// Sử dụng jQuery cho sự kiện DOMContentLoaded và submit
// document.addEventListener('DOMContentLoaded', function() { ... });
// Có thể thay thế bằng jQuery: $(document).ready(function() { ... });
$(document).ready(function() {
    const registrationForm = $('#registrationForm');

    // Hàm để hiển thị thông báo lỗi
    function showError(elementId, message) {
        // Sử dụng jQuery để chọn phần tử và đặt text
        $('#' + elementId).text(message);
    }

    // Hàm để xóa thông báo lỗi
    function clearError(elementId) {
        // Sử dụng jQuery để chọn phần tử và xóa text
        $('#' + elementId).text('');
    }

    // Lắng nghe sự kiện submit của form bằng jQuery
    registrationForm.on('submit', function(event) {
        event.preventDefault(); // Ngăn chặn hành vi gửi form mặc định của trình duyệt

        // Xóa tất cả các thông báo lỗi cũ trước khi kiểm tra lại
        clearError('fullNameError');
        clearError('emailError');
        clearError('phoneError');
        clearError('passwordError');
        clearError('confirmPasswordError');

        // Lấy giá trị từ các trường nhập liệu bằng jQuery
        const fullName = $('#fullName').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();

        let isValid = true; // Biến cờ để kiểm tra xem form có hợp lệ không

        // --- Các bước kiểm tra (Validation) và hiển thị lỗi ---

        if (fullName === '') {
            showError('fullNameError', 'Tên không được để trống.');
            isValid = false;
        }

        if (email === '') {
            showError('emailError', 'Email không được để trống.');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('emailError', 'Địa chỉ email không hợp lệ.');
            isValid = false;
        }

        if (phone === '') {
            showError('phoneError', 'Số điện thoại bắt buộc phải nhập.');
            isValid = false;
        } else if (!/^\d{10,11}$/.test(phone)) {
            showError('phoneError', 'Số điện thoại không hợp lệ (phải có 10 hoặc 11 chữ số).');
            isValid = false;
        }

        if (password === '') {
            showError('passwordError', 'Mật khẩu bắt buộc phải nhập.');
            isValid = false;
        } else if (password.length < 6) {
            showError('passwordError', 'Mật khẩu phải có ít nhất 6 ký tự.');
            isValid = false;
        }

        if (confirmPassword === '') {
            showError('confirmPasswordError', 'Vui lòng nhập lại mật khẩu.');
            isValid = false;
        } else if (password !== confirmPassword) {
            showError('confirmPasswordError', 'Mật khẩu và mật khẩu nhập lại không khớp.');
            isValid = false;
        }

        // --- Xử lý khi form hợp lệ hoặc không hợp lệ ---
        if (isValid) {
            console.log('Dữ liệu đăng ký hợp lệ:');
            console.log('Họ tên:', fullName);
            console.log('Email:', email);
            console.log('Số điện thoại:', phone);
            console.log('Mật khẩu: Đã nhập'); 
            alert('Đăng ký thành công! (Dữ liệu đã được log ra console)');
            // Ở đây bạn sẽ gửi dữ liệu đến API backend (sử dụng jQuery.ajax() hoặc fetch API)
        } else {
            console.log('Đăng ký thất bại: Vui lòng kiểm tra lại thông tin.');
        }
    });

    // Bonus: Xóa thông báo lỗi ngay khi người dùng bắt đầu nhập vào ô
    // Lắng nghe sự kiện 'input' trên tất cả các trường input trong form
    $('#registrationForm input').on('input', function() {
        // Lấy ID của span lỗi tương ứng
        const errorSpanId = $(this).attr('id') + 'Error';
        clearError(errorSpanId);
    });
});