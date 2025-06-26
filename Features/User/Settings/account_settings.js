// File: C:/xampp/htdocs/Web_Project/Features/User/Settings/account_settings.js
$(document).ready(function() {
    const dynamicContentArea = $('#dynamicContent');
    const settingsNavItems = $('.settings-nav-item a');

    // Đối tượng để theo dõi trạng thái script đã tải và các hàm khởi tạo
    const loadedScripts = {
        'personal_info.html': { loaded: false, initFunc: null, path: './personal_info.js' },
        'change_password.html': { loaded: false, initFunc: null, path: './change_password.js' }
        // Thêm các tab khác vào đây nếu có
    };

    // Hàm để tải nội dung trang và script liên quan
    function loadPage(pageHtmlFileName, activateNavLink) {
        dynamicContentArea.html('<div class="loading-indicator" style="text-align: center; padding: 50px;">Đang tải...</div>');

        const pageUrl = pageHtmlFileName; 
        const scriptInfo = loadedScripts[pageHtmlFileName];

        $.ajax({
            url: pageUrl,
            method: 'GET',
            success: function(data) {
                dynamicContentArea.html(data); // Chèn HTML vào DOM

                if (scriptInfo) {
                    if (scriptInfo.loaded) {
                        // Nếu script đã được tải trước đó, chỉ cần gọi hàm khởi tạo lại
                        console.log(`${scriptInfo.path} already loaded. Re-initializing.`);
                        if (typeof scriptInfo.initFunc === 'function') {
                            scriptInfo.initFunc();
                        }
                    } else {
                        // Tải và thực thi script mới
                        $.getScript(scriptInfo.path)
                            .done(function() {
                                console.log(`${scriptInfo.path} loaded and executed.`);
                                scriptInfo.loaded = true; // Đặt cờ đã tải

                                // Gán hàm khởi tạo từ global scope
                                if (pageHtmlFileName === 'personal_info.html') {
                                    scriptInfo.initFunc = window.initPersonalInfoPage;
                                } else if (pageHtmlFileName === 'change_password.html') {
                                    scriptInfo.initFunc = window.initChangePasswordPage;
                                }

                                // Gọi hàm khởi tạo sau khi script được tải và gán
                                if (typeof scriptInfo.initFunc === 'function') {
                                    scriptInfo.initFunc();
                                }
                            })
                            .fail(function(jqxhr, settings, exception) {
                                console.error(`Lỗi khi tải script ${scriptInfo.path}:`, exception);
                            });
                    }
                }

                if (activateNavLink) {
                    settingsNavItems.removeClass('active');
                    activateNavLink.addClass('active');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                dynamicContentArea.html('<p style="color: red; text-align: center; padding: 50px;">Không thể tải trang: ' + textStatus + '</p>');
                console.error('Lỗi khi tải trang:', textStatus, errorThrown, jqXHR);
                if (jqXHR.status === 401) {
                    setTimeout(() => {
                        window.location.href = '../../../Features/Auth/login.html';
                    }, 1500);
                }
            }
        });
    }

    // Xử lý sự kiện click trên các mục điều hướng
    settingsNavItems.on('click', function(e) {
        e.preventDefault();
        const pageToLoad = $(this).data('page');
        if (pageToLoad) {
            loadPage(pageToLoad, $(this));
        }
    });

    // Tải trang mặc định khi account_settings.html được mở lần đầu
    // Đảm bảo chọn đúng link ban đầu
    const initialActiveLink = settingsNavItems.filter('.active');
    if (initialActiveLink.length > 0) {
        loadPage(initialActiveLink.data('page'), initialActiveLink);
    } else {
        // Mặc định tải trang thông tin cá nhân nếu không có link active ban đầu
        // và kích hoạt link "Thông tin cá nhân" là active
        const personalInfoLink = settingsNavItems.filter('[data-page="personal_info.html"]');
        if (personalInfoLink.length > 0) {
            loadPage('personal_info.html', personalInfoLink);
        }
    }
});