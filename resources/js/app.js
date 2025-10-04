import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

window.currentPartnerId = null;

// Presence channel listener
window.Echo.join('online-users')
    .here((users) => {
        console.log('Danh sách người dùng online ban đầu:', users); // Debug: xem ai online khi tải trang
        users.forEach((u) => window.setUserOnline(u.id, true));
    })
    .joining((user) => {
        console.log('Người dùng vừa online:', user); // Debug: khi có người mới vào
        window.setUserOnline(user.id, true);
    })
    .leaving((user) => {
        console.log('Người dùng vừa offline:', user); // Debug: khi người dùng rời
        window.setUserOnline(user.id, false);
    })
    .error((error) => {
        console.error('Lỗi presence channel:', error); // Debug: kiểm tra lỗi xác thực/kết nối
    });

// Function to update status (global để dùng ở blade)
window.setUserOnline = function (userId, online) {
    const dot = document.getElementById(`status-${userId}`);
    if (dot) {
        dot.classList.toggle('status-online', online);
        dot.classList.toggle('status-offline', !online);
    }

    if (window.currentPartnerId === userId) {
        const headerDot = document.getElementById('chatHeaderStatus');
        const headerStatus = document.getElementById('chatHeaderStatusText');

        if (headerDot) {
            headerDot.classList.toggle('status-online', online);
            headerDot.classList.toggle('status-offline', !online);
        }
        if (headerStatus) {
            headerStatus.innerText = online ? 'Đang hoạt động' : 'Ngoại tuyến';
        }
    }
};

window.setUserOnline = setUserOnline;