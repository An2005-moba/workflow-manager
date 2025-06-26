// File: C:/xampp/htdocs/Web_Project/Features/User/Settings/change_password.js

// Bọc toàn bộ logic trong một hàm để có thể gọi lại khi script được tải động
function initChangePasswordPage() {
    const currentPasswordInput = $('#currentPassword');
    const newPasswordInput = $('#newPassword');
    const confirmNewPasswordInput = $('#confirmNewPassword');

    const messageDisplay = $('#messageDisplay');

    const savePasswordBtn = $('#savePasswordBtn');
    const cancelChangePasswordBtn = $('#cancelChangePasswordBtn');

    // Error message spans
    const currentPasswordError = $('#currentPasswordError');
    const newPasswordError = $('#newPasswordError');
    const confirmNewPasswordError = $('#confirmNewPasswordError');

    // Function to show messages
    function showMessage(message, type = 'success') {
        messageDisplay.text(message).removeClass('success error info').addClass(type).css({
            'visibility': 'visible',
            'opacity': '1'
        });
        // Tùy chọn: Tự động ẩn thông báo sau 5 giây
        setTimeout(hideMessage, 5000); 
    }

    // Function to hide messages
    function hideMessage() {
        messageDisplay.css({
            'visibility': 'hidden',
            'opacity': '0'
        }).text('');
    }

    // Function to show local error messages below inputs
    function showError(elementId, message) {
        $('#' + elementId).text(message).show();
    }

    // Function to clear local error messages
    function clearError(elementId) {
        $('#' + elementId).text('').hide();
    }

    function clearAllErrors() {
        clearError('currentPasswordError');
        clearError('newPasswordError');
        clearError('confirmNewPasswordError');
    }

    // Function to validate inputs
    function validatePasswordInputs() {
        let isValid = true;
        clearAllErrors();
        hideMessage();

        const currentPassword = currentPasswordInput.val();
        const newPassword = newPasswordInput.val();
        const confirmNewPassword = confirmNewPasswordInput.val();

        if (currentPassword.length === 0) {
            showError('currentPasswordError', 'Vui lòng nhập mật khẩu hiện tại.');
            isValid = false;
        }

        if (newPassword.length < 8) {
            showError('newPasswordError', 'Mật khẩu mới phải có ít nhất 8 ký tự.');
            isValid = false;
        } else if (!/[A-Z]/.test(newPassword) || !/[a-z]/.test(newPassword) || !/[0-9]/.test(newPassword) || !/[^A-Za-z0-9]/.test(newPassword)) {
            showError('newPasswordError', 'Mật khẩu mới phải bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.');
            isValid = false;
        }

        if (newPassword !== confirmNewPassword) {
            showError('confirmNewPasswordError', 'Mật khẩu xác nhận không khớp với mật khẩu mới.');
            isValid = false;
        }

        if (currentPassword === newPassword && newPassword.length > 0) { // Only if new password is not empty
            showError('newPasswordError', 'Mật khẩu mới không được trùng với mật khẩu hiện tại.');
            isValid = false;
        }

        return isValid;
    }

    // Function to handle password change submission
    async function changePassword() {
        if (!validatePasswordInputs()) {
            showMessage('Vui lòng kiểm tra lại các thông tin mật khẩu đã nhập.', 'error');
            return;
        }

        showMessage('Đang thay đổi mật khẩu...', 'info');

        const passwordData = {
            current_password: currentPasswordInput.val(),
            new_password: newPasswordInput.val(),
            confirm_new_password: confirmNewPasswordInput.val()
        };

        try {
            const response = await $.ajax({
                url: '../change_password_api.php', // Đường dẫn API đã được sửa đúng
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(passwordData),
                dataType: 'json'
            });

            if (response.status === 'success') {
                showMessage(response.message || 'Thay đổi mật khẩu thành công!', 'success');
                // Xóa các trường input sau khi thành công
                currentPasswordInput.val('');
                newPasswordInput.val('');
                confirmNewPasswordInput.val('');
            } else {
                showMessage(response.message || 'Thay đổi mật khẩu thất bại. Vui lòng thử lại.', 'error');
            }
        } catch (jqXHR) {
            console.error('Lỗi khi thay đổi mật khẩu:', jqXHR);
            let errorMessage = 'Đã xảy ra lỗi mạng hoặc lỗi server khi thay đổi mật khẩu.';
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
            showMessage(errorMessage, 'error');
        }
    }

    // Event listeners
    savePasswordBtn.on('click', function() {
        changePassword();
    });

    cancelChangePasswordBtn.on('click', function() {
        currentPasswordInput.val('');
        newPasswordInput.val('');
        confirmNewPasswordInput.val('');
        clearAllErrors();
        hideMessage();
    });

    // Clear messages/errors when input changes
    $('input[type="password"]').on('input', function() {
        hideMessage();
        const inputId = $(this).attr('id');
        if (inputId === 'currentPassword') clearError('currentPasswordError');
        if (inputId === 'newPassword') clearError('newPasswordError');
        if (inputId === 'confirmNewPassword') clearError('confirmNewPasswordError');
    });
}

// Quan trọng: Đặt hàm này vào biến global scope để account_settings.js có thể gọi
window.initChangePasswordPage = initChangePasswordPage;
