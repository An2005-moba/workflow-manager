document.addEventListener('DOMContentLoaded', function() {
    // =================================================
    // === PHẦN 1: LOGIC CHO POPOVER THÊM THÀNH VIÊN ===
    // =================================================
    const toggleBtn = document.getElementById('toggleAddMemberPopover');
    const closeBtn = document.getElementById('closeAddMemberPopover');
    const popover = document.getElementById('addMemberPopover');

    if (toggleBtn && popover) {
        toggleBtn.addEventListener('click', function(event) {
            event.stopPropagation(); 
            popover.classList.toggle('show');
        });
    }

    if (closeBtn && popover) {
        closeBtn.addEventListener('click', function() {
            popover.classList.remove('show');
        });
    }

    document.addEventListener('click', function(event) {
        if (popover && popover.classList.contains('show') && !toggleBtn.contains(event.target) && !popover.contains(event.target)) {
            popover.classList.remove('show');
        }
    });

    // =================================================
    // === PHẦN 2: LOGIC MỚI CHO SỬA/HỦY NHIỆM VỤ ===
    // =================================================
    
    // Lấy tất cả các thẻ nhiệm vụ
    const taskItems = document.querySelectorAll('.task-item');

    // Lặp qua mỗi thẻ nhiệm vụ để gán sự kiện
    taskItems.forEach(function(taskItem) {
        const editBtn = taskItem.querySelector('.edit-task-btn');
        const cancelBtn = taskItem.querySelector('.cancel-edit-btn');
        
        const taskView = taskItem.querySelector('.task-view');
        const editForm = taskItem.querySelector('.edit-task-form-container');

        // Sự kiện khi nhấn nút "Sửa"
        if (editBtn && taskView && editForm) {
            editBtn.addEventListener('click', function() {
                taskView.style.display = 'none'; // Ẩn phần hiển thị
                editForm.style.display = 'block'; // Hiện form sửa
            });
        }

        // Sự kiện khi nhấn nút "Hủy"
        if (cancelBtn && taskView && editForm) {
            cancelBtn.addEventListener('click', function() {
                editForm.style.display = 'none'; // Ẩn form sửa
                taskView.style.display = 'flex';  // Hiện lại phần hiển thị
            });
        }
    });
});