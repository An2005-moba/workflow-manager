// Dashboard JavaScript functionality - Chứa các logic UI chung của dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Get dropdown elements
    const dashboardUserContainer = document.querySelector('.dashboard-user');
    const dropdownToggle = document.getElementById('userDropdownToggle');
    const dropdownMenu = document.getElementById('userDropdownMenu');

    if (!dashboardUserContainer || !dropdownToggle || !dropdownMenu) {
        console.warn('One or more dropdown elements not found. Make sure class "dashboard-user" and IDs "userDropdownToggle", "userDropdownMenu" exist.');
        // Không return ở đây để các phần khác của JS vẫn chạy
    } else {
        // Toggle dropdown function
        function toggleDropdown() {
            const isOpen = dropdownMenu.classList.contains('show');

            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        }

        // Open dropdown function
        function openDropdown() {
            dropdownMenu.classList.add('show');
            dropdownToggle.classList.add('active'); // Đảm bảo mũi tên xoay

            setTimeout(() => {
                document.addEventListener('click', handleOutsideClick);
            }, 0);
        }

        // Close dropdown function
        function closeDropdown() {
            dropdownMenu.classList.remove('show');
            dropdownToggle.classList.remove('active'); // Đảm bảo mũi tên trở lại trạng thái ban đầu

            document.removeEventListener('click', handleOutsideClick);
        }

        // Handle clicks outside dropdown
        function handleOutsideClick(event) {
            const isClickInsideDropdownArea = dashboardUserContainer.contains(event.target);

            if (!isClickInsideDropdownArea) {
                closeDropdown();
            }
        }

        // Handle escape key
        function handleEscapeKey(event) {
            if (event.key === 'Escape' && dropdownMenu.classList.contains('show')) {
                closeDropdown();
            }
        }

        // --- Lắng nghe sự kiện click trên toàn bộ dashboardUserContainer ---
        dashboardUserContainer.addEventListener('click', function(event) {
            event.stopPropagation(); // Ngăn chặn sự kiện nổi bọt lên document ngay lập tức
            toggleDropdown(); // Gọi hàm toggle chung để mở/đóng
        });

        // Các event listener khác:
        document.addEventListener('keydown', handleEscapeKey);

        // Handle dropdown item clicks
        const dropdownItems = dropdownMenu.querySelectorAll('.dashboard-dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(event) {
                event.stopPropagation();

                if (this.classList.contains('dashboard-logout-item')) {
                    event.preventDefault();

                    const confirmLogout = window.confirm('Bạn có chắc chắn muốn đăng xuất không?');
                    if (confirmLogout) {
                        window.location.href = this.getAttribute('href');
                    }
                } else {
                    closeDropdown();
                    console.log('Menu item clicked:', this.textContent.trim());
                }
            });
        });

        // Add smooth animation for dropdown arrow rotation
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target === dropdownToggle) {
                        // Arrow rotation is handled by CSS transition
                    }
                }
            });
        });

        observer.observe(dropdownToggle, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Notification button functionality
    const notificationBtn = document.querySelector('.dashboard-notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            console.log('Notification button clicked');
        });
    }

    // Create project button functionality (optional enhancement - this is the one in the main content, not header)
    // Note: The header create button was already removed from list.php.
    // These are for buttons like 'Tạo dự án mới' within the main content/empty state if they exist
    const createBtnEmpty = document.querySelector('.create-project-btn-empty');
    if (createBtnEmpty) {
        createBtnEmpty.addEventListener('click', function() {
            console.log('Create project button (empty state) clicked');
        });
    }
    const createProjectBtn = document.querySelector('.create-project-btn'); // For any create button in page header (if added back)
    if (createProjectBtn) {
        createProjectBtn.addEventListener('click', function() {
            console.log('Create project button (page header) clicked');
        });
    }
});

// Utility functions for dropdown management (giữ nguyên các hàm này nếu bạn muốn gọi từ bên ngoài)
// Có thể xóa nếu không cần gọi từ bên ngoài main.js
window.DashboardDropdown = {
    close: function() {
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const dropdownToggle = document.getElementById('userDropdownToggle');

        if (dropdownMenu && dropdownToggle) {
            dropdownMenu.classList.remove('show');
            dropdownToggle.classList.remove('active');
            dropdownToggle.setAttribute('aria-expanded', 'false');
            document.removeEventListener('click', handleOutsideClick);
        }
    },

    open: function() {
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const dropdownToggle = document.getElementById('userDropdownToggle');

        if (dropdownMenu && dropdownToggle) {
            dropdownMenu.classList.add('show');
            dropdownToggle.classList.add('active');
            dropdownToggle.setAttribute('aria-expanded', 'true');
            setTimeout(() => {
                document.addEventListener('click', handleOutsideClick);
            }, 0);
        }
    },

    toggle: function() {
        const dropdownMenu = document.getElementById('userDropdownMenu');

        if (dropdownMenu) {
            if (dropdownMenu.classList.contains('show')) {
                this.close();
            } else {
                this.open();
            }
        }
    }
};