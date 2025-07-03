// projects.js - Chứa các logic liên quan đến trang danh sách dự án
document.addEventListener('DOMContentLoaded', function() {
    // Client-side search functionality for projects
    const projectSearchInput = document.getElementById('projectSearchInput');
    const projectGrid = document.querySelector('.project-grid');
    // Quan trọng: projectCards cần tìm các thẻ bên trong project-link-card, nhưng
    // Selector hiện tại (.project-card) vẫn ổn vì nó sẽ chọn các div có class đó.
    // Dù sao, cần đảm bảo HTML có data-* attributes trên div.project-card.
    const projectCards = document.querySelectorAll('.project-card'); 
    const emptyState = document.getElementById('emptyState'); // Đảm bảo ID này tồn tại trên HTML

    // Vô hiệu hóa tìm kiếm nếu không tìm thấy các phần tử cần thiết
    if (!projectSearchInput || (!projectGrid && !emptyState)) {
        console.warn('One or more project search/display elements not found. Search functionality will not be enabled.');
        return; // Dừng thực thi nếu không có các phần tử cần thiết
    }

    // Function to update empty state message
    function updateEmptyState(isSearching = false, searchTerm = '') {
        if (!emptyState) return;

        if (isSearching) {
            emptyState.querySelector('h2').textContent = 'Không tìm thấy dự án nào phù hợp';
            emptyState.querySelector('p').textContent = `Không có dự án nào khớp với "${searchTerm}". Vui lòng thử một từ khóa khác.`;
            const createBtn = emptyState.querySelector('.create-project-btn-empty');
            if (createBtn) createBtn.style.display = 'none'; // Ẩn nút tạo dự án khi đang tìm kiếm và không có kết quả
        } else {
            emptyState.querySelector('h2').textContent = 'Bạn chưa có dự án nào.';
            emptyState.querySelector('p').textContent = 'Hãy bắt đầu bằng cách tạo dự án đầu tiên của bạn.';
            const createBtn = emptyState.querySelector('.create-project-btn-empty');
            if (createBtn) createBtn.style.display = 'inline-block'; // Hiển thị nút tạo dự án trong trạng thái rỗng ban đầu
        }
    }

    // Initial state check when page loads
    // Đảm bảo logic này hoạt động đúng với cả trường hợp ban đầu không có dự án nào
    if (projectCards.length === 0) {
        if (projectGrid) projectGrid.style.display = 'none';
        if (emptyState) {
            emptyState.style.display = 'block';
            updateEmptyState(false); // Initial empty state message
        }
    } else {
        if (projectGrid) projectGrid.style.display = 'grid'; // Ensure grid is visible if projects exist
        if (emptyState) emptyState.style.display = 'none'; // Ensure empty state is hidden if projects exist
    }

    projectSearchInput.addEventListener('keyup', function() {
        const searchTerm = projectSearchInput.value.toLowerCase().trim();
        let anyProjectVisible = false;

        // Nếu không có dự án nào ngay từ đầu, không cần lặp qua projectCards
        if (projectCards.length === 0 && !searchTerm) { // Chỉ hiển thị trạng thái ban đầu nếu không có tìm kiếm
            if (projectGrid) projectGrid.style.display = 'none';
            if (emptyState) {
                emptyState.style.display = 'block';
                updateEmptyState(false);
            }
            return;
        }

        projectCards.forEach(card => {
            // Đảm bảo lấy giá trị lowercase từ data- thuộc tính
            const projectName = card.dataset.projectName || ''; 
            const projectDescription = card.dataset.projectDescription || '';

            // So khớp tìm kiếm
            const isMatch = projectName.includes(searchTerm) || projectDescription.includes(searchTerm);

            if (isMatch) {
                card.style.display = 'flex'; // Show the card (using flex display from CSS)
                anyProjectVisible = true;
            } else {
                card.style.display = 'none'; // Hide the card
            }
        });

        if (anyProjectVisible) {
            if (emptyState) emptyState.style.display = 'none'; // Hide empty state if projects are found
            if (projectGrid) projectGrid.style.display = 'grid'; // Ensure grid is visible
        } else {
            if (emptyState) {
                emptyState.style.display = 'block'; // Show empty state if no projects match
                updateEmptyState(true, searchTerm); // Update message for search no results
            }
            if (projectGrid) projectGrid.style.display = 'none'; // Hide grid
        }
    });

    // Thêm một điều kiện để xử lý khi người dùng xóa sạch ô tìm kiếm
    // Nếu projectCards.length === 0, và searchterm rỗng, thì hiển thị empty state ban đầu
    projectSearchInput.addEventListener('input', function() { // Dùng 'input' để bắt cả xóa bằng chuột phải, v.v.
        if (projectSearchInput.value.trim() === '' && projectCards.length === 0) {
            if (emptyState) {
                emptyState.style.display = 'block';
                updateEmptyState(false);
            }
            if (projectGrid) projectGrid.style.display = 'none';
        }
    });
});