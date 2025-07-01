document.addEventListener('DOMContentLoaded', function() {
    // Lấy các phần tử từ DOM
    const toggleBtn = document.getElementById('toggleAddMemberPopover');
    const closeBtn = document.getElementById('closeAddMemberPopover');
    const popover = document.getElementById('addMemberPopover');

    // Chỉ thực hiện nếu nút bật/tắt popover tồn tại
    if (toggleBtn && popover) {
        // Sự kiện khi nhấn vào icon dấu cộng
        toggleBtn.addEventListener('click', function(event) {
            // Ngăn sự kiện click lan ra ngoài document
            event.stopPropagation(); 
            // Bật/tắt class 'show' để hiện/ẩn popover
            popover.classList.toggle('show');
        });
    }

    // Chỉ thực hiện nếu nút đóng popover tồn tại
    if (closeBtn && popover) {
        // Sự kiện khi nhấn vào nút 'x' để đóng
        closeBtn.addEventListener('click', function() {
            popover.classList.remove('show');
        });
    }

    // Sự kiện khi nhấn vào bất kỳ đâu trên trang để đóng popover
    document.addEventListener('click', function(event) {
        // Nếu popover đang mở và người dùng không nhấn vào popover hoặc nút bật
        if (popover && popover.classList.contains('show') && !toggleBtn.contains(event.target) && !popover.contains(event.target)) {
            // Thì ẩn popover đi
            popover.classList.remove('show');
        }
    });
});