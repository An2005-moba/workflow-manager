// Dashboard JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get dropdown elements
    const dashboardUserContainer = document.querySelector('.dashboard-user'); // Lấy toàn bộ container
    const dropdownToggle = document.getElementById('userDropdownToggle'); 
    const dropdownMenu = document.getElementById('userDropdownMenu'); 
    
    if (!dashboardUserContainer || !dropdownToggle || !dropdownMenu) {
        console.warn('One or more dropdown elements not found. Make sure class "dashboard-user" and IDs "userDropdownToggle", "userDropdownMenu" exist.');
        return;
    }

    // Toggle dropdown function
    // Chúng ta không cần 'event.preventDefault()' ở đây vì nó sẽ được xử lý ở event listener
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
        
        // Add event listener to close dropdown when clicking outside
        // Sử dụng setTimeout 0ms để đảm bảo event listener được thêm vào sau khi click hiện tại kết thúc
        setTimeout(() => {
            document.addEventListener('click', handleOutsideClick);
        }, 0);
    }

    // Close dropdown function
    function closeDropdown() {
        dropdownMenu.classList.remove('show');
        dropdownToggle.classList.remove('active'); // Đảm bảo mũi tên trở lại trạng thái ban đầu
        
        // Remove event listener
        document.removeEventListener('click', handleOutsideClick);
    }

    // Handle clicks outside dropdown
    function handleOutsideClick(event) {
        // Kiểm tra xem click có nằm trong container tổng (.dashboard-user)
        // hoặc nằm trong menu dropdown (.dashboard-user-dropdown)
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

    // --- Thay đổi lớn ở đây: Lắng nghe sự kiện click trên toàn bộ dashboardUserContainer ---
    dashboardUserContainer.addEventListener('click', function(event) {
        event.stopPropagation(); // Ngăn chặn sự kiện nổi bọt lên document ngay lập tức
        toggleDropdown(); // Gọi hàm toggle chung để mở/đóng
    });

    // Các event listener khác đã có:
    document.addEventListener('keydown', handleEscapeKey);

    // Handle dropdown item clicks
    const dropdownItems = dropdownMenu.querySelectorAll('.dashboard-dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(event) {
            // Ngăn chặn event nổi bọt từ item lên container và document
            event.stopPropagation(); 

            // If it's the logout item, show confirmation
            if (this.classList.contains('dashboard-logout-item')) {
                event.preventDefault(); // Ngăn chặn hành vi mặc định của thẻ a (chuyển trang ngay lập tức)
                
                const confirmLogout = confirm('Bạn có chắc chắn muốn đăng xuất không?');
                if (confirmLogout) {
                    window.location.href = this.getAttribute('href');
                }
            }
            // For other items, close dropdown and let default action happen
            else {
                closeDropdown();
                console.log('Menu item clicked:', this.textContent.trim());
            }
        });
    });

    // Add smooth animation for dropdown arrow rotation (giữ nguyên logic của bạn)
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

    // Notification button functionality (optional enhancement - giữ nguyên)
    const notificationBtn = document.querySelector('.dashboard-notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            console.log('Notification button clicked');
        });
    }

    // Search functionality (optional enhancement - giữ nguyên)
    const searchInput = document.querySelector('.dashboard-search input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length > 2) {
                console.log('Searching for:', query);
            }
        });

        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                const query = this.value.trim();
                if (query.length > 0) {
                    console.log('Search submitted:', query);
                }
            }
        });
    }

    // Create project button functionality (optional enhancement - giữ nguyên)
    const createBtn = document.querySelector('.dashboard-create-btn');
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            console.log('Create project button clicked');
        });
    }
});

// Utility functions for dropdown management (giữ nguyên các hàm này nếu bạn muốn gọi từ bên ngoài)
window.DashboardDropdown = {
    close: function() {
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const dropdownToggle = document.getElementById('userDropdownToggle');
        
        if (dropdownMenu && dropdownToggle) {
            dropdownMenu.classList.remove('show');
            dropdownToggle.classList.remove('active');
            document.removeEventListener('click', handleOutsideClick); // Đảm bảo gỡ bỏ listener
        }
    },
    
    open: function() {
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const dropdownToggle = document.getElementById('userDropdownToggle');
        
        if (dropdownMenu && dropdownToggle) {
            dropdownMenu.classList.add('show');
            dropdownToggle.classList.add('active');
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