$(document).ready(function() {
    const loginForm = $('#loginForm');
    // Đảm bảo bạn có một phần tử để hiển thị thông báo tổng thể từ API,
    // ví dụ: <div class="form-group"><span class="error-message" id="apiMessage"></span></div>
    // hoặc một div riêng biệt sau nút submit.
    // Tôi sẽ giả định bạn sẽ thêm <span class="error-message" id="apiMessage"></span> 
    // vào login.html của bạn để hiển thị thông báo chung.
    const apiMessageDiv = $('#apiMessage'); // Tạo biến cho thông báo chung

    // Hàm để hiển thị thông báo lỗi cho từng trường nhập liệu
    function showError(elementId, message) {
        $('#' + elementId).text(message).show(); // Hiển thị lỗi và đảm bảo nó visible
    }

    // Hàm để xóa thông báo lỗi cho từng trường nhập liệu
    function clearError(elementId) {
        $('#' + elementId).text('').hide(); // Ẩn lỗi
    }

    // Hàm để hiển thị thông báo chung từ API (thành công/lỗi)
    function showApiMessage(message, type) {
        apiMessageDiv.text(message);
        apiMessageDiv.removeClass('success-message error-message').hide(); // Reset classes và ẩn

        if (type === 'success') {
            apiMessageDiv.addClass('success-message').show();
        } else if (type === 'error') {
            apiMessageDiv.addClass('error-message').show();
        }
    }

    // Hàm để xóa thông báo chung từ API
    function clearApiMessage() {
        apiMessageDiv.text('').removeClass('success-message error-message').hide();
    }

    // Lắng nghe sự kiện submit của form
    loginForm.on('submit', function(event) {
        event.preventDefault(); // Ngăn chặn hành vi gửi form mặc định

        // Xóa tất cả các thông báo lỗi cũ
        clearError('emailError');
        clearError('passwordError');
        clearApiMessage(); // Xóa thông báo API cũ

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

        // --- Xử lý khi form hợp lệ ---
        if (isValid) {
            console.log('Dữ liệu đăng nhập hợp lệ. Đang gửi đến API...');
            
            
            // Gửi dữ liệu đăng nhập đến API backend sử dụng jQuery.ajax
            $.ajax({
                url: 'login_api.php', // Đường dẫn tương đối từ login.js đến login_api.php
                method: 'POST',
                // Chuyển đổi dữ liệu thành JSON string để gửi đi
                // Backend của bạn đang mong đợi JSON (json_decode(file_get_contents("php://input"), true))
                contentType: 'application/json', 
                data: JSON.stringify({ email: email, password: password }),
                dataType: 'json', // Mong đợi phản hồi là JSON

                success: function(response, textStatus, xhr) {
                    console.log('Phản hồi từ server:', response);
                    console.log('Status code:', xhr.status);
                    
                    // Nếu server trả về 200 OK (và không có redirect header)
                    if (response.status === 'success') {
                        // Trường hợp này chỉ xảy ra nếu PHP không gửi header Location
                        // Hoặc nếu AJAX yêu cầu XHR không tự động theo dõi redirect.
                        // Nếu PHP đã gửi header Location, khối này sẽ KHÔNG bao giờ chạy
                        // vì trình duyệt đã tự động chuyển hướng.
                        showApiMessage(response.message, 'success');
                        console.log('Đăng nhập thành công, nhưng không có redirect từ AJAX. Chuyển hướng thủ công.');
                        // Fallback chuyển hướng nếu server không tự redirect XHR
                        window.location.href = '../../dashboard.php'; 
                    } else {
                        // Đăng nhập thất bại, hiển thị thông báo lỗi từ server
                        showApiMessage(response.message, 'error');
                        console.error('Đăng nhập thất bại:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX khi đăng nhập:', status, error);
                    console.error('Phản hồi lỗi:', xhr.responseText);
                    // Nếu lỗi là do server không trả về JSON khi có redirect (ví dụ status 302/303/307)
                    // nhưng jQuery cố gắng parse JSON, nó sẽ gây ra lỗi parseError.
                    // Chúng ta cần kiểm tra xem có phải là lỗi chuyển hướng không.
                    if (xhr.status === 200 && xhr.responseText && xhr.responseText.startsWith('<!DOCTYPE html>')) {
                        // Đây có thể là trường hợp PHP đã render trang dashboard HTML,
                        // nhưng AJAX cố gắng đọc nó như JSON và thất bại.
                        // Trong trường hợp này, chúng ta cần chuyển hướng thủ công.
                        console.log("Server đã phản hồi HTML (có thể là dashboard). Chuyển hướng...");
                        window.location.href = '../../dashboard.php';
                    } else if (xhr.status === 0 && error === "") {
                        // Lỗi mạng hoặc cross-origin, hoặc redirect được thực hiện
                        // và jQuery không bắt được phản hồi json
                        console.log("Network error or silent redirect occurred. Attempting manual redirect.");
                        // Thử chuyển hướng đến dashboard, vì có thể đăng nhập đã thành công
                        // nhưng AJAX không bắt được phản hồi do redirect.
                        window.location.href = '../../dashboard.php'; 
                    } else {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            showApiMessage(errorResponse.message || 'Lỗi không xác định từ server.', 'error');
                        } catch (e) {
                            showApiMessage('Lỗi kết nối hoặc phản hồi không mong muốn từ server.', 'error');
                        }
                    }
                }
            });

        } else {
            console.log('Đăng nhập thất bại: Vui lòng kiểm tra lại thông tin.');
        }
    });

    // Bonus: Xóa thông báo lỗi ngay khi người dùng bắt đầu nhập
    $('#loginForm input').on('input', function() {
        const errorSpanId = $(this).attr('id') + 'Error';
        clearError(errorSpanId);
        clearApiMessage(); // Xóa thông báo chung khi người dùng bắt đầu nhập lại
    });
});