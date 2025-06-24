$(document).ready(function() {
    const userNameSpan = $('#userName');
    const userEmailSpan = $('#userEmail');
    const messageDisplay = $('#messageDisplay');

    // Function to show messages
    function showMessage(message, type = 'success') {
        messageDisplay.text(message).removeClass('success error info').addClass(type).css({
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

    // Function to fetch user data
    async function fetchUserProfile() {
        showMessage('Đang tải thông tin...', 'info'); // Show loading message

        try {
            // Assume there's an API endpoint to get user profile data
            // This API should return JSON like: { status: 'success', user: { name: '...', email: '...' } }
            // Or { status: 'error', message: '...' }
            const response = await $.ajax({
                url: '../../Features/User/get_profile_api.php', // This API endpoint needs to be created
                method: 'GET',
                dataType: 'json' // Expect JSON response
            });

            if (response.status === 'success' && response.user) {
                userNameSpan.text(response.user.name);
                userEmailSpan.text(response.user.email);
                // If you have more fields, update them here:
                // $('#userPhone').text(response.user.phone);
                // $('#userRole').text(response.user.role);
                hideMessage(); // Hide loading message on success
            } else {
                userNameSpan.text('Không có dữ liệu');
                userEmailSpan.text('Không có dữ liệu');
                showMessage(response.message || 'Không thể tải thông tin người dùng.', 'error');
            }
        } catch (jqXHR) {
            console.error('Lỗi khi tải thông tin người dùng:', jqXHR);
            let errorMessage = 'Đã xảy ra lỗi mạng hoặc lỗi server khi tải thông tin.';
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
            userNameSpan.text('Lỗi tải dữ liệu');
            userEmailSpan.text('Lỗi tải dữ liệu');
            showMessage(errorMessage, 'error');

            // Optionally, if the error is 401 Unauthorized, redirect to login
            if (jqXHR.status === 401) {
                setTimeout(() => {
                    window.location.href = '../../Features/Auth/login.html'; // Redirect to login page
                }, 2000);
            }
        }
    }

    // Call the function to fetch user profile data when the page loads
    fetchUserProfile();
});