$(document).ready(function() {
    // Referencing span elements instead of input elements
    const userNameDisplay = $('#userName');
    const userEmailDisplay = $('#userEmail');
    const userPhoneDisplay = $('#userPhone');
    const userDOBDisplay = $('#userDOB');
    const userAddressDisplay = $('#userAddress');

    const messageDisplay = $('#messageDisplay');

    // Error message spans (kept for consistency, but won't be actively used for client-side validation in view-only mode)
    const phoneError = $('#phoneError');
    const dobError = $('#dobError');
    const addressError = $('#addressError');

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

    // Function to show local error messages below display elements (will likely not be triggered now)
    function showError(elementId, message) {
        $('#' + elementId).text(message).show();
    }

    // Function to clear local error messages (will likely not be triggered now)
    function clearError(elementId) {
        $('#' + elementId).text('').hide();
    }

    function clearAllErrors() {
        clearError('phoneError');
        clearError('dobError');
        clearError('addressError');
    }

    // Function to fetch user data and populate the display spans
    async function fetchUserProfile() {
        showMessage('Đang tải thông tin...', 'info');

        try {
            const response = await $.ajax({
                url: './get_profile_api.php', // ĐÃ SỬA: Đường dẫn API tương đối đúng
                method: 'GET',
                dataType: 'json'
            });

            if (response.status === 'success' && response.user) {
                userNameDisplay.text(response.user.name || 'Chưa cập nhật');
                userEmailDisplay.text(response.user.email || 'Chưa cập nhật');
                userPhoneDisplay.text(response.user.phone_number || 'Chưa cập nhật');
                
                // Format date for display if available
                const dob = response.user.date_of_birth;
                userDOBDisplay.text(dob && dob !== '0000-00-00' ? new Date(dob).toLocaleDateString('vi-VN') : 'Chưa cập nhật');
                
                userAddressDisplay.text(response.user.address || 'Chưa cập nhật');
                hideMessage();
            } else {
                userNameDisplay.text('Không có dữ liệu');
                userEmailDisplay.text('Không có dữ liệu');
                userPhoneDisplay.text('Không có dữ liệu');
                userDOBDisplay.text('Không có dữ liệu');
                userAddressDisplay.text('Không có dữ liệu');
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
            userNameDisplay.text('Lỗi tải dữ liệu');
            userEmailDisplay.text('Lỗi tải dữ liệu');
            userPhoneDisplay.text('Lỗi tải dữ liệu');
            userDOBDisplay.text('Lỗi tải dữ liệu');
            userAddressDisplay.text('Lỗi tải dữ liệu');
            showMessage(errorMessage, 'error');

            if (jqXHR.status === 401) {
                setTimeout(() => {
                    window.location.href = '../../Features/Auth/login.html';
                }, 2000);
            }
        }
    }

    // No event listeners for edit/save/cancel buttons as they are removed.
    // No validation logic needed as there are no editable inputs.

    // Initial fetch of user profile data when the page loads
    fetchUserProfile();
});