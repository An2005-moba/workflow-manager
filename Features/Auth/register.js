// register.js
$(document).ready(function() {
    const registrationForm = $('#registrationForm');
    const messageDiv = $('#registrationMessage'); // Thêm một div/p để hiển thị thông báo tổng thể từ BE

    // Hàm để hiển thị thông báo lỗi cục bộ cho từng trường
    function showError(elementId, message) {
        $('#' + elementId).text(message).css('color', 'red'); // Đặt màu đỏ cho lỗi
    }

    // Hàm để xóa thông báo lỗi cục bộ
    function clearError(elementId) {
        $('#' + elementId).text('');
    }

    // Lắng nghe sự kiện submit của form bằng jQuery
    registrationForm.on('submit', async function(event) { // Thêm 'async' để dùng await
        event.preventDefault(); // Ngăn chặn hành vi gửi form mặc định của trình duyệt

        // Xóa tất cả các thông báo lỗi cũ và thông báo tổng thể
        clearError('fullNameError');
        clearError('emailError');
        clearError('phoneError');
        clearError('passwordError');
        clearError('confirmPasswordError');
        if (messageDiv && messageDiv.length) { // Kiểm tra sự tồn tại của messageDiv
            messageDiv.text(''); // Xóa thông báo tổng thể
        }

        // Lấy giá trị từ các trường nhập liệu bằng jQuery
        const fullName = $('#fullName').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();

        let isValid = true; // Biến cờ để kiểm tra xem form có hợp lệ không

        // --- Các bước kiểm tra (Validation) và hiển thị lỗi ở Frontend ---

        if (fullName === '') {
            showError('fullNameError', 'Tên không được để trống.');
            isValid = false;
        } else if (fullName.length < 5) { // <<< THÊM ĐIỀU KIỆN ĐỘ DÀI TẠI ĐÂY
            showError('fullNameError', 'Họ tên phải có ít nhất 5 ký tự.');
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
            // Dữ liệu sẽ gửi đi đến backend
            const postData = {
                name: fullName,
                email: email,
                phone: phone, // Đã thêm phone vào đây để gửi đi
                password: password,
                confirm_password: confirmPassword 
            };

            try {
                const response = await $.ajax({
                    url: '/Web_Project/Features/Auth/register_api.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(postData)
                });

                if (messageDiv && messageDiv.length) {
                    messageDiv.text(response.message);
                    if (response.status === 'success') {
                        messageDiv.css('color', 'green');
                        registrationForm[0].reset(); // Xóa dữ liệu form
                        setTimeout(() => {
                            window.location.href = 'login.html'; // Chuyển hướng đến trang đăng nhập
                        }, 2000);
                    } else {
                        messageDiv.css('color', 'red');
                    }
                }

            } catch (jqXHR) {
                console.error('Lỗi khi gửi yêu cầu đăng ký:', jqXHR);
                console.error('XHR status:', jqXHR.status);
                console.error('XHR responseText:', jqXHR.responseText);

                let errorMessage = 'Đã xảy ra lỗi mạng hoặc lỗi server.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                } else if (jqXHR.responseText) {
                    try {
                        const errorResponse = JSON.parse(jqXHR.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        errorMessage = `Lỗi từ server: ${jqXHR.responseText}`;
                    }
                }

                if (messageDiv && messageDiv.length) {
                    messageDiv.text(errorMessage);
                    messageDiv.css('color', 'red');
                }
            }
        } else {
            console.log('Đăng ký thất bại: Vui lòng kiểm tra lại thông tin.');
            if (messageDiv && messageDiv.length) {
                messageDiv.text('Đăng ký thất bại: Vui lòng kiểm tra lại các trường bị lỗi.');
                messageDiv.css('color', 'red');
            }
        }
    });

    // Bonus: Xóa thông báo lỗi ngay khi người dùng bắt đầu nhập vào ô
    $('#registrationForm input').on('input', function() {
        const errorSpanId = $(this).attr('id') + 'Error';
        clearError(errorSpanId);
    });
});