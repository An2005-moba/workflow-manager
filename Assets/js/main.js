// File: main.js (Phiên bản cuối cùng, đã sửa lỗi cấu trúc)
document.addEventListener('DOMContentLoaded', function() {

    // --- LOGIC CHO DROPDOWN NGƯỜI DÙNG ---
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', function(event) {
            // Ngăn sự kiện click lan ra ngoài, tránh việc document listener đóng dropdown ngay lập tức
            event.stopPropagation();
            // Toggle class để hiển thị/ẩn dropdown và xoay mũi tên
            userDropdownMenu.classList.toggle('show');
            userDropdownToggle.classList.toggle('active'); // Giả sử .active sẽ xoay mũi tên
        });

        // Xử lý khi click vào các mục trong dropdown
        userDropdownMenu.querySelectorAll('.dashboard-dropdown-item').forEach(item => {
            item.addEventListener('click', function(event) {
                if (this.classList.contains('dashboard-logout-item')) {
                    event.preventDefault(); // Ngăn chuyển hướng ngay lập tức
                    if (window.confirm('Bạn có chắc chắn muốn đăng xuất không?')) {
                        window.location.href = this.getAttribute('href');
                    }
                }
            });
        });
    }

    // --- LOGIC CHO CHUÔNG THÔNG BÁO ---
    const notificationBell = document.querySelector('.dashboard-notification-btn');
    let notificationDropdown; // Khai báo biến ở phạm vi rộng hơn

    if (notificationBell) {
        // Tạo một wrapper để dễ dàng quản lý vị trí tương đối
        const wrapper = document.createElement('div');
        wrapper.className = 'notification-wrapper';
        notificationBell.parentNode.insertBefore(wrapper, notificationBell);
        wrapper.appendChild(notificationBell);

        // Hàm tạo dropdown nếu chưa tồn tại
        function createNotificationDropdown() {
            if (document.getElementById('notificationDropdown')) {
                notificationDropdown = document.getElementById('notificationDropdown');
                return;
            }
            notificationDropdown = document.createElement('div');
            notificationDropdown.id = 'notificationDropdown';
            notificationDropdown.className = 'notification-dropdown';
            notificationDropdown.innerHTML = `
                <div class="notification-header">
                    <h3>Thông báo</h3>
                </div>
                <div id="notificationList" class="notification-list"></div>
               
            `;
            wrapper.appendChild(notificationDropdown);

            // Tạo badge đếm số thông báo
            const badge = document.createElement('span');
            badge.id = 'notificationCountBadge';
            badge.className = 'notification-badge';
            badge.style.display = 'none'; // Ẩn ban đầu
            notificationBell.appendChild(badge);
        }
        
        // Gọi hàm tạo dropdown một lần khi trang tải
        createNotificationDropdown();

        // Hàm fetch số lượng thông báo chưa đọc
        function fetchNotificationCount() {
            // API này cần được bạn tạo ra, nó sẽ trả về JSON вида: { "unread_count": 5 }
            // Tạm thời chưa có nên sẽ không hiển thị badge
            /*
            fetch('./Features/Notifications/get_notification_count_api.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notificationCountBadge');
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error fetching notification count:', error));
            */
        }

        notificationBell.addEventListener('click', function(event) {
            event.stopPropagation();
            
            const isShown = notificationDropdown.classList.toggle('show');

            if (isShown) {
                const list = document.getElementById('notificationList');
                list.innerHTML = '<p class="notification-item-empty">Đang tải...</p>';
                
                // Fetch nội dung thông báo
                fetch('./Features/Notifications/get_notifications_api.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {
                        list.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        list.innerHTML = '<p class="notification-item-empty">Không thể tải thông báo.</p>';
                    });
                
                // Ẩn badge số lượng khi mở dropdown
                const badge = document.getElementById('notificationCountBadge');
                if (badge) {
                    badge.style.display = 'none';
                }
            }
        });

        fetchNotificationCount();
    }

    // --- ĐÓNG TẤT CẢ DROPDOWN KHI CLICK RA NGOÀI ---
    document.addEventListener('click', function(event) {
        // Đóng dropdown người dùng
        if (userDropdownMenu && userDropdownMenu.classList.contains('show')) {
            if (!userDropdownToggle.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('show');
                userDropdownToggle.classList.remove('active');
            }
        }
        
        // Đóng dropdown thông báo
        if (notificationDropdown && notificationDropdown.classList.contains('show')) {
            const bellWrapper = notificationBell.parentElement;
            if (!bellWrapper.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
        }
    });
});