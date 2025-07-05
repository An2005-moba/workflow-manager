// File: projects.js (Phiên bản cuối cùng với đường dẫn fetch đã sửa)
document.addEventListener('DOMContentLoaded', function () {
    // ... (toàn bộ các phần khai báo biến và logic khác không đổi) ...
    
    // --- CÁC HÀM GỌI API ---
    async function deleteProject(projectId, cardElement) {
        const formData = new FormData();
        formData.append('project_id', projectId);
        try {
            // ✅ SỬA ĐƯỜNG DẪN NGẮN GỌN
            const response = await fetch('handle_delete_project.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                alert(result.message);
                cardElement.closest('.project-link-card').remove();
                exitActionMode();
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (error) {
            console.error('Lỗi khi xóa dự án:', error);
            alert('Đã xảy ra lỗi kết nối khi xóa.');
        }
    }

    async function leaveProject(projectId, cardElement) {
        const formData = new FormData();
        formData.append('project_id', projectId);
        try {
            // ✅ SỬA ĐƯỜNG DẪN NGẮN GỌN
            const response = await fetch('handle_leave_project.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                alert(result.message || 'Bạn đã rời khỏi dự án thành công.');
                cardElement.closest('.project-link-card').remove();
                exitActionMode();
            } else {
                alert('Lỗi: ' + (result.message || 'Không thể rời khỏi dự án.'));
            }
        } catch (error) {
            console.error('Lỗi khi rời dự án:', error);
            alert('Đã xảy ra lỗi kết nối. Vui lòng thử lại.');
        }
    }

    // ... (toàn bộ các hàm và logic khác giữ nguyên như cũ) ...
    // --- KHAI BÁO BIẾN ---
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const projectSearchInput = document.getElementById('projectSearchInput');
    const projectGrid = document.querySelector('.project-grid');
    const projectCards = document.querySelectorAll('.project-card');
    const emptyState = document.getElementById('emptyState');
    const actionModeBar = document.getElementById('action-mode-bar');
    const actionModeMessage = document.getElementById('action-mode-message');
    const cancelActionButton = document.getElementById('cancel-action-btn');

    let currentAction = null;

    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            userDropdownMenu.classList.toggle('show');
        });
    }
    document.addEventListener('click', (event) => {
        if (userDropdownMenu && userDropdownMenu.classList.contains('show') && !userDropdownToggle.contains(event.target) && !userDropdownMenu.contains(event.target)) {
            userDropdownMenu.classList.remove('show');
        }
    });
    if (projectSearchInput) {
        projectSearchInput.addEventListener('input', function () {
            const searchTerm = projectSearchInput.value.toLowerCase().trim();
            let anyProjectVisible = false;
            projectCards.forEach(card => {
                const projectName = card.dataset.projectName || '';
                const projectDescription = card.dataset.projectDescription || '';
                const isMatch = projectName.includes(searchTerm) || projectDescription.includes(searchTerm);
                const linkCard = card.closest('.project-link-card');
                if (linkCard) {
                    linkCard.style.display = isMatch ? 'block' : 'none';
                    if (isMatch) anyProjectVisible = true;
                }
            });
            if (projectGrid) projectGrid.style.display = anyProjectVisible ? 'grid' : 'none';
            if (emptyState) {
                emptyState.style.display = anyProjectVisible ? 'none' : 'block';
                if (!anyProjectVisible) {
                    const h2 = emptyState.querySelector('h2');
                    const p = emptyState.querySelector('p');
                    const createBtn = emptyState.querySelector('.create-project-btn-empty');
                    if (h2) h2.textContent = 'Không tìm thấy dự án';
                    if (p) p.textContent = `Không có kết quả nào khớp với "${searchTerm}"`;
                    if (createBtn) createBtn.style.display = 'none';
                }
            }
        });
    }

    function enterActionMode(action) {
        currentAction = action;
        let message = '';
        document.body.classList.remove('delete-mode-active', 'edit-mode-active', 'leave-mode-active');
        projectCards.forEach(card => card.classList.remove('can-leave'));
        if (action === 'delete-project') {
            message = "Chế độ xóa: Nhấp vào một dự án để xóa.";
            document.body.classList.add('delete-mode-active');
        } else if (action === 'edit-profile') {
             message = "Chế độ sửa: Nhấp vào một dự án để chỉnh sửa.";
             document.body.classList.add('edit-mode-active');
        } else if (action === 'leave-project') {
            message = "Chế độ rời nhóm: Chỉ có thể rời khỏi dự án bạn không phải là người tạo.";
            document.body.classList.add('leave-mode-active');
            projectCards.forEach(card => {
                if (card.dataset.createdBy != currentUserId) {
                    card.classList.add('can-leave');
                }
            });
        }
        if (actionModeMessage) actionModeMessage.textContent = message;
        if (actionModeBar) actionModeBar.style.display = 'block';
        if (userDropdownMenu) userDropdownMenu.classList.remove('show');
    }

    function exitActionMode() {
        currentAction = null;
        if (actionModeBar) actionModeBar.style.display = 'none';
        document.body.classList.remove('delete-mode-active', 'edit-mode-active', 'leave-mode-active');
        projectCards.forEach(card => card.classList.remove('can-leave'));
    }

    if (userDropdownMenu) {
        userDropdownMenu.addEventListener('click', (e) => {
            const targetItem = e.target.closest('[data-action]');
            if (targetItem) {
                e.preventDefault();
                const action = targetItem.dataset.action;
                enterActionMode(action);
            }
        });
    }

    if (cancelActionButton) {
        cancelActionButton.addEventListener('click', exitActionMode);
    }

    function handleProjectEdit(card) {
        const projectId = card.dataset.projectId;
        window.location.href = `edit_project.php?id=${projectId}`;
    }

    function handleProjectDelete(card) {
        const projectId = card.dataset.projectId;
        const cardTitleElement = card.querySelector('.project-card-title');
        const projectName = cardTitleElement ? cardTitleElement.textContent.trim() : 'Dự án không tên';
        if (confirm(`Bạn có chắc muốn xóa dự án "${projectName}" không?`)) {
            deleteProject(projectId, card);
        }
    }

    function handleProjectLeave(card) {
        const projectId = card.dataset.projectId;
        const createdById = card.dataset.createdBy;
        if (createdById == currentUserId) {
            alert('Bạn là người tạo nên không thể rời khỏi dự án này. Bạn chỉ có thể Xóa dự án.');
            return;
        }
        const cardTitleElement = card.querySelector('.project-card-title');
        const projectName = cardTitleElement ? cardTitleElement.textContent.trim() : 'Dự án không tên';
        if (confirm(`Bạn có chắc chắn muốn rời khỏi dự án "${projectName}" không?`)) {
            leaveProject(projectId, card);
        }
    }

    if (projectGrid) {
        projectGrid.addEventListener('click', function (event) {
            if (!currentAction) return;
            event.preventDefault();
            const card = event.target.closest('.project-card');
            if (!card) return;
            if (currentAction === 'edit-profile') {
                handleProjectEdit(card);
            } else if (currentAction === 'delete-project') {
                handleProjectDelete(card);
            } else if (currentAction === 'leave-project') {
                handleProjectLeave(card);
            }
        });
    }
});