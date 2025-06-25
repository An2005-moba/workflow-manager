// C:/xampp/htdocs/Web_Project/public/js/register.js

$(document).ready(function() {
    const registrationForm = $('#registrationForm');
    const messageDiv = $('#registrationMessage'); // Div/p để hiển thị thông báo tổng thể từ BE

    // Hàm để hiển thị thông báo lỗi cục bộ cho từng trường
    function showError(elementId, message) {
        const errorSpan = $('#' + elementId);
        errorSpan.text(message).css('color', 'red').show(); // Hiển thị lỗi và đặt màu đỏ
        // Thêm class 'is-invalid' vào input tương ứng để hiển thị viền đỏ (nếu dùng Bootstrap)
        // const inputId = elementId.replace('Error', '');
        // $('#' + inputId).addClass('is-invalid'); 
    }

    // Hàm để xóa thông báo lỗi cục bộ
    function clearError(elementId) {
        const errorSpan = $('#' + elementId);
        errorSpan.text('').hide(); // Ẩn thông báo lỗi
        // Xóa class 'is-invalid' khỏi input tương ứng
        // const inputId = elementId.replace('Error', '');
        // $('#' + inputId).removeClass('is-invalid');
    }

    // Lắng nghe sự kiện submit của form bằng jQuery
    registrationForm.on('submit', async function(event) {
        event.preventDefault(); // Ngăn chặn hành vi gửi form mặc định của trình duyệt

        // Xóa tất cả các thông báo lỗi cũ và thông báo tổng thể trước mỗi lần submit mới
        clearError('fullNameError');
        clearError('emailError');
        clearError('phoneError');
        clearError('passwordError');
        clearError('confirmPasswordError');
        // clearError('dateOfBirthError'); // Nếu có
        // clearError('addressError'); // Nếu có
        if (messageDiv && messageDiv.length) {
            messageDiv.text('').hide(); // Ẩn thông báo tổng thể
        }

        // Lấy giá trị từ các trường nhập liệu bằng jQuery và loại bỏ khoảng trắng thừa
        const fullName = $('#fullName').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const password = $('#password').val(); // Không trim password
        const confirmPassword = $('#confirmPassword').val(); // Không trim confirmPassword
        // const dateOfBirth = $('#date_of_birth').val(); // Lấy giá trị ngày sinh (có thể rỗng)
        // const address = $('#address').val().trim(); // Lấy giá trị địa chỉ (có thể rỗng)

        let isValid = true; // Biến cờ để kiểm tra xem form có hợp lệ không

        // --- Các bước kiểm tra (Validation) và hiển thị lỗi ở Frontend ---

        if (fullName === '') {
            showError('fullNameError', 'Tên không được để trống.');
            isValid = false;
        } else if (fullName.length < 5) {
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

        // Phone là trường bắt buộc (theo logic hiện tại của bạn)
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

        // // Validation cho ngày sinh (nếu cần)
        // if (dateOfBirth && !/^\d{4}-\d{2}-\d{2}$/.test(dateOfBirth)) {
        //     showError('dateOfBirthError', 'Ngày sinh không hợp lệ (YYYY-MM-DD).');
        //     isValid = false;
        // } else if (dateOfBirth) {
        //     const today = new Date();
        //     const dob = new Date(dateOfBirth);
        //     if (dob > today) {
        //         showError('dateOfBirthError', 'Ngày sinh không thể ở tương lai.');
        //         isValid = false;
        //     }
        // }

        // // Validation cho địa chỉ (nếu cần)
        // if (address && address.length > 512) { // Giới hạn ký tự giống DB
        //     showError('addressError', 'Địa chỉ quá dài (tối đa 512 ký tự).');
        //     isValid = false;
        // }


        // --- Xử lý khi form hợp lệ hoặc không hợp lệ ---
        if (isValid) {
            // Dữ liệu sẽ gửi đi đến backend
            const postData = {
                name: fullName,
                email: email,
                phone_number: phone, // ĐÃ SỬA TÊN TRƯỜNG TẠI ĐÂY để khớp với backend
                password: password,
                confirm_password: confirmPassword,
                // date_of_birth: dateOfBirth === '' ? null : dateOfBirth, // Gửi null nếu rỗng
                // address: address === '' ? '' : address // Gửi chuỗi rỗng nếu rỗng
            };

            // CONSOLE.LOG ĐỂ KIỂM TRA DỮ LIỆU TRƯỚC KHI GỬI
            console.log('Dữ liệu gửi đi từ frontend:', postData);

            try {
                // Sử dụng jQuery AJAX cho tính nhất quán
                const response = await $.ajax({
                    url: '/Web_Project/Features/Auth/register_api.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(postData)
                });

                if (messageDiv && messageDiv.length) {
                    messageDiv.text(response.message).show(); // Hiển thị thông báo
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
                    messageDiv.text(errorMessage).css('color', 'red').show();
                }
            }
        } else {
            console.log('Đăng ký thất bại: Vui lòng kiểm tra lại thông tin.');
            if (messageDiv && messageDiv.length) {
                messageDiv.text('Đăng ký thất bại: Vui lòng kiểm tra lại các trường bị lỗi.').css('color', 'red').show();
            }
        }
    });

    // Bonus: Xóa thông báo lỗi ngay khi người dùng bắt đầu nhập vào ô
    $('#registrationForm input').on('input', function() {
        const inputId = $(this).attr('id');
        const errorSpanId = inputId + 'Error';
        clearError(errorSpanId);
        // Ẩn thông báo tổng thể khi người dùng bắt đầu tương tác với form
        if (messageDiv && messageDiv.length) {
            messageDiv.text('').hide();
        }
    });

    // Xóa thông báo lỗi khi focus vào ô input
    $('#registrationForm input').on('focus', function() {
        const inputId = $(this).attr('id');
        const errorSpanId = inputId + 'Error';
        clearError(errorSpanId);
    });
});