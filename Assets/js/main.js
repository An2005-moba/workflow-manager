// Dashboard JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get dropdown elements
    const dropdownToggle = document.getElementById('userDropdownToggle'); // Lấy nút bằng ID mới
    const dropdownMenu = document.getElementById('userDropdownMenu');     // Lấy menu bằng ID mới
    
    if (!dropdownToggle || !dropdownMenu) {
        console.warn('Dropdown elements not found. Make sure IDs "userDropdownToggle" and "userDropdownMenu" exist.');
        return;
    }

    // Toggle dropdown function
    function toggleDropdown(event) {
        event.preventDefault();
        event.stopPropagation(); // Ngăn chặn event nổi bọt ra ngoài
        
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
        dropdownToggle.classList.add('active');
        
        // Add event listener to close dropdown when clicking outside
        // Sử dụng setTimeout 0ms để đảm bảo event listener được thêm vào sau khi click hiện tại kết thúc
        setTimeout(() => {
            document.addEventListener('click', handleOutsideClick);
        }, 0);
    }

    // Close dropdown function
    function closeDropdown() {
        dropdownMenu.classList.remove('show');
        dropdownToggle.classList.remove('active');
        
        // Remove event listener
        document.removeEventListener('click', handleOutsideClick);
    }

    // Handle clicks outside dropdown
    function handleOutsideClick(event) {
        const isClickInsideDropdown = dropdownToggle.contains(event.target) || 
                                     dropdownMenu.contains(event.target);
        
        if (!isClickInsideDropdown) {
            closeDropdown();
        }
    }

    // Handle escape key
    function handleEscapeKey(event) {
        if (event.key === 'Escape' && dropdownMenu.classList.contains('show')) {
            closeDropdown();
        }
    }

    // Add event listeners
    dropdownToggle.addEventListener('click', toggleDropdown);
    document.addEventListener('keydown', handleEscapeKey);

    // Handle dropdown item clicks
    const dropdownItems = dropdownMenu.querySelectorAll('.dashboard-dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(event) {
            // If it's the logout item, show confirmation
            if (this.classList.contains('dashboard-logout-item')) { // Sử dụng class mới dashboard-logout-item
                event.preventDefault(); // Ngăn chặn hành vi mặc định của thẻ a (chuyển trang ngay lập tức)
                
                const confirmLogout = confirm('Bạn có chắc chắn muốn đăng xuất không?');
                if (confirmLogout) {
                    // Redirect to logout
                    window.location.href = this.getAttribute('href');
                }
            }
            // For other items, close dropdown and let default action happen
            else {
                closeDropdown();
                // Add your custom logic here for other menu items
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
            // Add notification functionality here
            console.log('Notification button clicked');
        });
    }

    // Search functionality (optional enhancement - giữ nguyên)
    const searchInput = document.querySelector('.dashboard-search input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length > 2) {
                // Add search functionality here
                console.log('Searching for:', query);
            }
        });

        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                const query = this.value.trim();
                if (query.length > 0) {
                    // Handle search submission
                    console.log('Search submitted:', query);
                }
            }
        });
    }

    // Create project button functionality (optional enhancement - giữ nguyên)
    const createBtn = document.querySelector('.dashboard-create-btn');
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            // Add create project functionality here
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
        }
    },
    
    open: function() {
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const dropdownToggle = document.getElementById('userDropdownToggle');
        
        if (dropdownMenu && dropdownToggle) {
            dropdownMenu.classList.add('show');
            dropdownToggle.classList.add('active');
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