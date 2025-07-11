// File: C:/xampp/htdocs/Web_Project/Features/User/Settings/personal_info.js

// Bọc toàn bộ logic trong một hàm để có thể gọi lại khi script được tải động
function initPersonalInfoPage() {
    const userNameInput = $('#userName');
    const userEmailInput = $('#userEmail');
    const userPhoneInput = $('#userPhone');
    const userDOBInput = $('#userDOB');
    const userAddressInput = $('#userAddress');

    const messageDisplay = $('#messageDisplay');

    const editProfileBtn = $('#editProfileBtn');
    const saveProfileBtn = $('#saveProfileBtn');
    const cancelEditBtn = $('#cancelEditBtn');

    let originalUserData = {}; // To store user data when entering edit mode

    // Error message spans
    const phoneError = $('#phoneError');
    const dobError = $('#dobError');
    const addressError = $('#addressError');

    // Function to show messages
    function showMessage(message, type = 'success') {
        messageDisplay.text(message).removeClass('success error info').addClass(type).css({
            'visibility': 'visible',
            'opacity': '1'
        });
        setTimeout(hideMessage, 5000); // Tự động ẩn sau 5 giây
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
        clearError('phoneError');
        clearError('dobError');
        clearError('addressError');
    }

    // Function to toggle edit mode
    function toggleEditMode(enable) {
        if (enable) {
            // Save current data before enabling edit
            originalUserData = {
                name: userNameInput.val(),
                email: userEmailInput.val(),
                phone_number: userPhoneInput.val(),
                date_of_birth: userDOBInput.val(),
                address: userAddressInput.val()
            };

            // Readonly fields (Name and Email will remain readonly)
            userNameInput.prop('readonly', true);
            userEmailInput.prop('readonly', true);

            // Editable fields
            userPhoneInput.prop('readonly', false).focus();
            userDOBInput.prop('readonly', false);
            userAddressInput.prop('readonly', false);

            editProfileBtn.addClass('hidden');
            saveProfileBtn.removeClass('hidden');
            cancelEditBtn.removeClass('hidden');
        } else {
            // Readonly fields
            userNameInput.prop('readonly', true);
            userEmailInput.prop('readonly', true);

            // Editable fields
            userPhoneInput.prop('readonly', true);
            userDOBInput.prop('readonly', true);
            userAddressInput.prop('readonly', true);

            editProfileBtn.removeClass('hidden');
            saveProfileBtn.addClass('hidden');
            cancelEditBtn.addClass('hidden');
            hideMessage();
            clearAllErrors();
        }
    }

    // Function to fetch user data
    async function fetchUserProfile() {
        showMessage('Đang tải thông tin...', 'info');

        try {
            const response = await $.ajax({
                url: '../get_profile_api.php', // Đường dẫn API đã được sửa đúng
                method: 'GET',
                dataType: 'json'
            });

            if (response.status === 'success' && response.user) {
                userNameInput.val(response.user.name || '');
                userEmailInput.val(response.user.email || '');
                userPhoneInput.val(response.user.phone_number || '');
                const dobValue = (response.user.date_of_birth === '0000-00-00' || response.user.date_of_birth === null) ? '' : response.user.date_of_birth;
                userDOBInput.val(dobValue);
                userAddressInput.val(response.user.address || '');
                hideMessage();
            } else {
                userNameInput.val('N/A');
                userEmailInput.val('N/A');
                userPhoneInput.val('N/A');
                userDOBInput.val('');
                userAddressInput.val('N/A');
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
            userNameInput.val('Lỗi tải dữ liệu');
            userEmailInput.val('Lỗi tải dữ liệu');
            userPhoneInput.val('Lỗi tải dữ liệu');
            userDOBInput.val('');
            userAddressInput.val('Lỗi tải dữ liệu');
            showMessage(errorMessage, 'error');

            if (jqXHR.status === 401) {
                setTimeout(() => {
                    window.location.href = '../../../Features/Auth/login.html';
                }, 2000);
            }
        }
    }

    // Function to validate inputs before saving
    function validateInputs() {
        let isValid = true;
        clearAllErrors();

        const phone = userPhoneInput.val().trim();
        const dob = userDOBInput.val();
        const address = userAddressInput.val().trim();

        // Phone number validation (optional, can be empty or a valid format)
        if (phone !== '' && !/^\d{10,11}$/.test(phone)) {
            showError('phoneError', 'Số điện thoại không hợp lệ (10-11 chữ số).');
            isValid = false;
        }

        // Date of Birth validation (optional, can be empty or a valid date)
        if (dob !== '') {
            const today = new Date();
            const birthDate = new Date(dob);
            if (birthDate > today) {
                showError('dobError', 'Ngày sinh không thể ở tương lai.');
                isValid = false;
            }
        }

        // Address validation (optional, can be empty or a reasonable length)
        if (address !== '' && address.length > 255) {
            showError('addressError', 'Địa chỉ quá dài (tối đa 255 ký tự).');
            isValid = false;
        }

        return isValid;
    }

    // Function to update user profile
    async function updateUserProfile() {
        if (!validateInputs()) {
            showMessage('Vui lòng kiểm tra lại các thông tin đã nhập.', 'error');
            return;
        }

        showMessage('Đang lưu thông tin...', 'info');

        const updatedData = {
            name: userNameInput.val(),
            email: userEmailInput.val(),
            phone_number: userPhoneInput.val(),
            date_of_birth: userDOBInput.val(),
            address: userAddressInput.val()
        };

        try {
            const response = await $.ajax({
                url: '../update_profile_api.php', // Đường dẫn API đã được sửa đúng
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(updatedData),
                dataType: 'json'
            });

            if (response.status === 'success') {
                showMessage(response.message || 'Cập nhật thông tin thành công!', 'success');
                toggleEditMode(false); // Exit edit mode on success
                fetchUserProfile(); // Re-fetch data to ensure consistency
            } else {
                showMessage(response.message || 'Không thể cập nhật thông tin.', 'error');
            }
        } catch (jqXHR) {
            console.error('Lỗi khi cập nhật thông tin người dùng:', jqXHR);
            let errorMessage = 'Đã xảy ra lỗi mạng hoặc lỗi server khi cập nhật thông tin.';
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
    editProfileBtn.on('click', function() {
        toggleEditMode(true);
    });

    saveProfileBtn.on('click', function() {
        updateUserProfile();
    });

    cancelEditBtn.on('click', function() {
        // Revert inputs to original data
        userNameInput.val(originalUserData.name);
        userEmailInput.val(originalUserData.email);
        userPhoneInput.val(originalUserData.phone_number);
        userDOBInput.val(originalUserData.date_of_birth);
        userAddressInput.val(originalUserData.address);
        toggleEditMode(false); // Exit edit mode
    });

    // Clear messages/errors when input changes
    $('.profile-info input').on('input', function() {
        hideMessage();
        const inputId = $(this).attr('id');
        if (inputId === 'userPhone') clearError('phoneError');
        if (inputId === 'userDOB') clearError('dobError');
        if (inputId === 'userAddress') clearError('addressError');
    });

    // Initial fetch of user profile data when this page's logic is initialized
    fetchUserProfile();
}

window.initPersonalInfoPage = initPersonalInfoPage;