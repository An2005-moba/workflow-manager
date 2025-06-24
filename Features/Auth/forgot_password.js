$(document).ready(function() {
    const forgotPasswordForm = $('#forgotPasswordForm');
    const emailInput = $('#email');
    const messageDisplay = $('#messageDisplay');

    // Function to display messages (success/error)
    function showMessage(message, type = 'success') {
        messageDisplay.text(message).removeClass('success error').addClass(type).css({
            'visibility': 'visible',
            'opacity': '1'
        });
    }

    // Function to hide messages
    function hideMessage() {
        messageDisplay.css({
            'visibility': 'hidden',
            'opacity': '0'
        }).text('');
    }

    // Event listener for form submission
    forgotPasswordForm.on('submit', async function(event) {
        event.preventDefault(); // Prevent default form submission
        hideMessage(); // Clear previous messages

        const email = emailInput.val().trim();

        if (email === '') {
            showMessage('Vui lòng nhập địa chỉ email của bạn.', 'error');
            emailInput.focus();
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showMessage('Địa chỉ email không hợp lệ.', 'error');
            emailInput.focus();
            return;
        }

        // Show a temporary message while sending
        showMessage('Đang gửi yêu cầu...', 'info'); // Using 'info' type for temporary visual feedback

        try {
            const response = await $.ajax({
                url: 'forgot_password_api.php', // API endpoint for forgot password
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email })
            });

            if (response.status === 'success') {
                showMessage(response.message, 'success');
                // Optionally disable the form or redirect after success
                forgotPasswordForm.find('button[type="submit"]').prop('disabled', true);
                emailInput.prop('disabled', true);
                // No redirect here, user needs to check email
            } else {
                showMessage(response.message, 'error');
            }

        } catch (jqXHR) {
            console.error('Lỗi khi gửi yêu cầu quên mật khẩu:', jqXHR);
            let errorMessage = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            } else if (jqXHR.responseText) {
                try {
                    const errorResponse = JSON.parse(jqXHR.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    // If responseText is not JSON, use it directly
                    errorMessage = `Lỗi từ server: ${jqXHR.responseText}`;
                }
            }
            showMessage(errorMessage, 'error');
        }
    });

    // Clear message when user starts typing again
    emailInput.on('input', function() {
        hideMessage();
    });
});