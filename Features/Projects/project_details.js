document.addEventListener('DOMContentLoaded', function () {
    // --- KHAI BÁO BIẾN CHO CÁC THÀNH PHẦN GIAO DIỆN ---
    const taskMessageDiv = document.getElementById('task-ajax-message');
    const progressTextElement = document.getElementById('progress-summary-text');
    const progressBarFillElement = document.getElementById('progress-bar-fill');
    const taskListContainer = document.querySelector('.task-list');
    const createTaskForm = document.getElementById('create-task-form');
    const filterForm = document.querySelector('.filter-form');
    const toggleBtn = document.getElementById('toggleAddMemberPopover');
    const closeBtn = document.getElementById('closeAddMemberPopover');
    const popover = document.getElementById('addMemberPopover');

    /**
     * Hàm tính toán và cập nhật lại tiến độ dự án trên giao diện.
     */
    function updateProjectProgress() {
        if (!progressTextElement || !progressBarFillElement) return;
        const allTasks = taskListContainer.querySelectorAll('.task-item');
        const totalTasks = allTasks.length;
        let completedTasks = 0;
        allTasks.forEach(task => {
            const statusBadge = task.querySelector('.status-badge');
            if (statusBadge) {
                const status = statusBadge.dataset.status;
                if (status === 'hoanthanh' || status === 'daduyet') {
                    completedTasks++;
                }
            }
        });
        const percentage = totalTasks > 0 ? (completedTasks / totalTasks) * 100 : 0;
        progressTextElement.textContent = `${completedTasks}/${totalTasks}`;
        progressBarFillElement.style.width = `${percentage}%`;
    }

    function showTaskMessage(message, type) {
        if (!taskMessageDiv) return;
        taskMessageDiv.textContent = message;
        taskMessageDiv.className = 'flash-message';
        taskMessageDiv.classList.add(type === 'success' ? 'flash-success' : 'flash-error');
        taskMessageDiv.style.display = 'block';
        setTimeout(() => {
            taskMessageDiv.textContent = '';
            taskMessageDiv.style.display = 'none';
            taskMessageDiv.className = 'flash-message';
        }, 5000);
    }

    function convertStatusToClass(statusText) {
        if (statusText === 'Đang làm') return 'danglam';
        if (statusText === 'Hoàn thành') return 'hoanthanh';
        if (statusText === 'Đã duyệt') return 'daduyet';
        return 'canlam';
    }

    function attachTaskEventListeners(taskItem) {
        const editBtn = taskItem.querySelector('.edit-task-btn');
        const cancelBtn = taskItem.querySelector('.cancel-edit-btn');
        const taskView = taskItem.querySelector('.task-view');
        const editFormContainer = taskItem.querySelector('.edit-task-form-container');
        const updateForm = editFormContainer ? editFormContainer.querySelector('form') : null;
        const deleteBtn = taskView ? taskView.querySelector('.delete-task-btn') : null;
        const submitBtn = taskItem.querySelector('.submit-assignment-btn');
        const commentForm = taskItem.querySelector('.add-comment-form');

        if (editBtn) {
            editBtn.addEventListener('click', () => {
                taskView.style.display = 'none';
                editFormContainer.style.display = 'block';
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                editFormContainer.style.display = 'none';
                taskView.style.display = '';
            });
        }

        if (updateForm) {
            updateForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const formData = new FormData(updateForm);
                fetch(updateForm.action, { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.updated_task) {
                            const updatedTask = data.updated_task;

                            // Cập nhật các thông tin cơ bản
                            taskItem.querySelector('.task-name').textContent = updatedTask.task_name;
                            taskItem.querySelector('.task-description').innerHTML = updatedTask.description.replace(/\n/g, '<br>');
                            taskItem.querySelector('.task-assignee span').textContent = updatedTask.assignee_names || 'Chưa gán';
                            const statusBadge = taskItem.querySelector('.status-badge');
                            statusBadge.textContent = updatedTask.status;
                            statusBadge.dataset.status = convertStatusToClass(updatedTask.status);

                            // --- BẮT ĐẦU: CẬP NHẬT DEADLINE ---
                            const deadlineContainer = taskItem.querySelector('.task-deadline');
                            const deadlineInfo = updatedTask.deadline_info;

                            if (deadlineInfo && deadlineInfo.text) {
                                // Nếu có thông tin deadline mới
                                const newDeadlineHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span>${deadlineInfo.text}</span>
                        `;

                                if (deadlineContainer) {
                                    // Nếu đã có sẵn khu vực deadline, chỉ cập nhật
                                    deadlineContainer.className = `task-deadline ${deadlineInfo.class}`;
                                    deadlineContainer.innerHTML = newDeadlineHTML;
                                } else {
                                    // Nếu chưa có (ví dụ task trước đó không có deadline), tạo mới
                                    const assigneeDiv = taskItem.querySelector('.task-assignee');
                                    assigneeDiv.insertAdjacentHTML('afterend', `<div class="task-deadline ${deadlineInfo.class}">${newDeadlineHTML}</div>`);
                                }
                            } else if (deadlineContainer) {
                                // Nếu deadline bị xóa, loại bỏ luôn khu vực hiển thị
                                deadlineContainer.remove();
                            }
                            // --- KẾT THÚC: CẬP NHẬT DEADLINE ---

                            // Đóng form sửa và hiển thị thông báo
                            cancelBtn.click();
                            showTaskMessage(data.message, 'success');
                            updateProjectProgress();
                        } else {
                            showTaskMessage(data.message || 'Có lỗi xảy ra.', 'error');
                        }
                    })
                    .catch(error => showTaskMessage('Lỗi kết nối khi cập nhật.', 'error'));
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                if (confirm('Bạn có chắc muốn xóa nhiệm vụ này?')) {
                    const formData = new FormData();
                    formData.append('task_id', deleteBtn.dataset.taskId);
                    formData.append('project_id', deleteBtn.dataset.projectId);
                    fetch('../Task/handle_delete_task.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                taskItem.remove();
                                updateProjectProgress();
                            }
                            showTaskMessage(data.message, data.status);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showTaskMessage('Lỗi kết nối khi xóa.', 'error');
                        });
                }
            });
        }
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                // Lấy ID của task từ thẻ cha
                const taskId = taskItem.dataset.taskId;

                // Chuyển hướng đến trang upload, đính kèm task_id vào URL
                window.location.href = `../../Features/Task/uploads_task.php?task_id=${taskId}`;
            });
        }
        if (commentForm) {
            attachCommentFormListener(commentForm);
        }

    }

    // --- GÁN SỰ KIỆN KHI TẢI TRANG ---
    document.querySelectorAll('.task-item').forEach(attachTaskEventListeners);

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (event) { event.stopPropagation(); popover.classList.toggle('show'); });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function () { popover.classList.remove('show'); });
    }
    document.addEventListener('click', function (event) {
        if (popover && popover.classList.contains('show') && !toggleBtn.contains(event.target) && !popover.contains(event.target)) {
            popover.classList.remove('show');
        }
    });

    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const apiBaseURL = filterForm.dataset.apiUrl;
            const params = new URLSearchParams(new FormData(filterForm)).toString();
            const fullApiURL = `${apiBaseURL}?${params}`;
            taskListContainer.innerHTML = '<p class="empty-list">Đang tải...</p>';
            fetch(fullApiURL)
                .then(response => response.text())
                .then(html => {
                    taskListContainer.innerHTML = html;
                    taskListContainer.querySelectorAll('.task-item').forEach(attachTaskEventListeners);
                    updateProjectProgress(); // <-- THÊM DÒNG NÀY
                })
                .catch(error => {
                    taskListContainer.innerHTML = `<p class="empty-list error">${error.message}</p>`;
                });
        });
    }

    if (createTaskForm) {
        createTaskForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(createTaskForm);
            fetch(createTaskForm.action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    showTaskMessage(data.message, data.status);
                    if (data.status === 'success' && data.task_html) {
                        createTaskForm.querySelector('#task_name').value = '';
                        createTaskForm.querySelector('#description').value = '';

                        // Chèn HTML của task mới vào đầu danh sách
                        taskListContainer.insertAdjacentHTML('afterbegin', data.task_html);

                        // Lấy lại đúng phần tử task item vừa được chèn vào, bỏ qua các khoảng trắng
                        const newTaskItem = taskListContainer.firstElementChild;

                        // Gán sự kiện cho các nút Sửa/Xóa của task mới này
                        attachTaskEventListeners(newTaskItem);

                        // Cập nhật lại thanh tiến độ
                        updateProjectProgress();
                        newTaskItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showTaskMessage('Lỗi kết nối khi tạo nhiệm vụ.', 'error');
                });
        });
    }
    if (taskListContainer) {
        taskListContainer.addEventListener('click', function (event) {
            if (event.target.classList.contains('delete-comment-btn')) {
                const commentItem = event.target.closest('.comment-item');
                const commentId = commentItem.dataset.commentId;
                if (confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
                    const formData = new FormData();
                    formData.append('comment_id', commentId);
                    fetch('../Task/handle_delete_comment.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                commentItem.style.transition = 'opacity 0.3s ease-out';
                                commentItem.style.opacity = '0';
                                setTimeout(() => commentItem.remove(), 300);
                            } else {
                                alert(data.message || 'Không thể xóa bình luận.');
                            }
                        })
                        .catch(() => alert('Lỗi kết nối khi xóa bình luận.'));
                }
            }
        });
    }
    // Thêm hàm này vào bên trong hàm attachTaskEventListeners(taskItem)

    function attachCommentFormListener(form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(form);
            const commentInput = form.querySelector('input[name="comment_text"]');
            const commentList = form.closest('.task-comments-section').querySelector('.comment-list');

            fetch(form.action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.new_comment) {
                        const newComment = data.new_comment;

                        // Kiểm tra và tạo nút xóa nếu đúng người dùng
                        const deleteBtnHTML = (newComment.user_id == currentUserId) ? `<button class="delete-comment-btn" title="Xóa bình luận">&times;</button>` : '';

                        // Tạo HTML cho bình luận mới.
                        // Đảm bảo không có bất kỳ ký tự hay văn bản thừa nào bên ngoài cặp thẻ `<div>`.
                        const commentHTML = `
                        <div class="comment-item" data-comment-id="${newComment.id}">
                            <div class="comment-content">
                                <strong>${newComment.user_name}:</strong>
                                <span>${newComment.comment_text}</span>
                            </div>
                            ${deleteBtnHTML}
                        </div>`;

                        commentList.insertAdjacentHTML('beforeend', commentHTML);
                        commentInput.value = '';
                        commentList.scrollTop = commentList.scrollHeight;
                    } else {
                        alert(data.message || 'Lỗi khi gửi bình luận.');
                    }
                })
                .catch(error => alert('Lỗi kết nối khi gửi bình luận.'));
        });
    }

    // Trong hàm attachTaskEventListeners, tìm đến dòng cuối và thêm:


    updateProjectProgress();
});