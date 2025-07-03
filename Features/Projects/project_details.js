document.addEventListener('DOMContentLoaded', function() {
    // --- KHAI BÁO CÁC BIẾN VÀ HÀM TRỢ GIÚP ---
    const taskMessageDiv = document.getElementById('task-ajax-message');

    // Hàm hiển thị thông báo
    function showTaskMessage(message, type) {
        if (!taskMessageDiv) return;
        taskMessageDiv.textContent = message;
        taskMessageDiv.className = 'flash-message';
        taskMessageDiv.classList.add(type === 'success' ? 'flash-success' : 'flash-error');
        taskMessageDiv.style.display = 'block'; // Hiển thị thông báo
        setTimeout(() => {
            taskMessageDiv.textContent = '';
            taskMessageDiv.style.display = 'none'; // Ẩn đi
            taskMessageDiv.className = 'flash-message';
        }, 5000);
    }

    // Hàm chuyển đổi trạng thái Tiếng Việt sang class CSS
    function convertStatusToClass(statusText) {
        if (statusText === 'Đang làm') return 'danglam';
        if (statusText === 'Hoàn thành') return 'hoanthanh';
        if (statusText === 'Đã duyệt') return 'daduyet';
        return 'canlam'; // Mặc định cho "Cần làm" hoặc "To Do"
    }
    
    // Hàm gán tất cả sự kiện cho một thẻ nhiệm vụ
    function attachTaskEventListeners(taskItem) {
        const editBtn = taskItem.querySelector('.edit-task-btn');
        const cancelBtn = taskItem.querySelector('.cancel-edit-btn');
        const taskView = taskItem.querySelector('.task-view');
        const editFormContainer = taskItem.querySelector('.edit-task-form-container');
        const updateForm = editFormContainer ? editFormContainer.querySelector('form') : null;
        const deleteBtn = taskView ? taskView.querySelector('.delete-task-btn') : null;

        // Sự kiện cho nút Sửa
        if (editBtn && taskView && editFormContainer) {
            editBtn.addEventListener('click', () => {
                taskView.style.display = 'none';
                editFormContainer.style.display = 'block';
            });
        }

        // Sự kiện cho nút Hủy
        if (cancelBtn && taskView && editFormContainer) {
            cancelBtn.addEventListener('click', () => {
                editFormContainer.style.display = 'none';
                taskView.style.display = '';
            });
        }
        
        // Sự kiện cho form Cập nhật
        if (updateForm) {
            updateForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(updateForm);

                fetch(updateForm.action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.updated_task) {
                        const updatedTask = data.updated_task;
                        
                        taskItem.querySelector('.task-name').textContent = updatedTask.task_name;
                        taskItem.querySelector('.task-description').innerHTML = updatedTask.description.replace(/\n/g, '<br>');
                        taskItem.querySelector('.task-assignee span').textContent = updatedTask.assignee_names || 'Chưa gán';
                        
                        const statusBadge = taskItem.querySelector('.status-badge');
                        statusBadge.textContent = updatedTask.status;
                        statusBadge.dataset.status = convertStatusToClass(updatedTask.status);

                        cancelBtn.click(); // Đóng form sửa
                        showTaskMessage(data.message, 'success');
                    } else {
                        showTaskMessage(data.message || 'Có lỗi xảy ra.', 'error');
                    }
                })
                .catch(error => showTaskMessage('Lỗi kết nối khi cập nhật.', 'error'));
            });
        }

        // --- SỬA LỖI LOGIC XÓA ---
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (confirm('Bạn có chắc muốn xóa nhiệm vụ này?')) {
                    const taskId = deleteBtn.dataset.taskId;
                    const projectId = deleteBtn.dataset.projectId;

                    const formData = new FormData();
                    formData.append('task_id', taskId);
                    formData.append('project_id', projectId);

                    // Sử dụng fetch để gọi file PHP xử lý xóa
                    fetch('../Task/handle_delete_task.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json()) // Mong đợi phản hồi JSON
                    .then(data => {
                        // Kiểm tra trạng thái từ JSON trả về
                        if (data.status === 'success') {
                            // Nếu thành công, xóa phần tử task khỏi giao diện
                            taskItem.remove(); 
                        }
                        // Hiển thị thông báo (thành công hoặc lỗi)
                        showTaskMessage(data.message, data.status);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showTaskMessage('Lỗi kết nối khi xóa.', 'error');
                    });
                }
            });
        }
    }

    // --- LOGIC CHÍNH KHI TẢI TRANG ---

    // 1. Gán sự kiện cho các nhiệm vụ đã có sẵn
    document.querySelectorAll('.task-item').forEach(attachTaskEventListeners);

    // 2. Logic cho popover thêm thành viên
    const toggleBtn = document.getElementById('toggleAddMemberPopover');
    const closeBtn = document.getElementById('closeAddMemberPopover');
    const popover = document.getElementById('addMemberPopover');
    if (toggleBtn && popover) {
        toggleBtn.addEventListener('click', function(event) { event.stopPropagation(); popover.classList.toggle('show'); });
    }
    if (closeBtn && popover) {
        closeBtn.addEventListener('click', function() { popover.classList.remove('show'); });
    }
    document.addEventListener('click', function(event) {
        if (popover && popover.classList.contains('show') && !toggleBtn.contains(event.target) && !popover.contains(event.target)) {
            popover.classList.remove('show');
        }
    });

    // --- SỬA LỖI LOGIC LỌC ---
    const filterForm = document.querySelector('.filter-form');
    const taskListContainer = document.querySelector('.task-list');
    if (filterForm && taskListContainer) {
        filterForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Ngăn form submit và tải lại trang
            
            // Lấy URL của API từ thuộc tính data-api-url mà chúng ta sẽ thêm vào thẻ form
            const apiBaseURL = filterForm.dataset.apiUrl;
            const params = new URLSearchParams(new FormData(filterForm)).toString();
            const fullApiURL = `${apiBaseURL}?${params}`;

            // Hiển thị trạng thái đang tải
            taskListContainer.innerHTML = '<p class="empty-list">Đang tải danh sách nhiệm vụ...</p>';

            fetch(fullApiURL)
                .then(response => {
                    if (!response.ok) throw new Error('Lỗi mạng khi tải dữ liệu.');
                    return response.text(); // API trả về HTML
                })
                .then(html => {
                    // Thay thế nội dung của danh sách nhiệm vụ bằng kết quả mới
                    taskListContainer.innerHTML = html;
                    // QUAN TRỌNG: Gán lại sự kiện cho các nút Sửa/Xóa trên các task vừa được tải về
                    taskListContainer.querySelectorAll('.task-item').forEach(attachTaskEventListeners);
                })
                .catch(error => {
                    taskListContainer.innerHTML = `<p class="empty-list error">${error.message}</p>`;
                });
        });
    }
});