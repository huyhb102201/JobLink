import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

window.Echo.join('online-users')
    .here(users => {
        users.forEach(u => setUserOnline(u.id, true));
    })
    .joining(user => {
        setUserOnline(user.id, true);
    })
    .leaving(user => {
        setUserOnline(user.id, false);
    });

function setUserOnline(userId, online) {
    // Cập nhật sidebar dot
    const dot = document.getElementById(`status-${userId}`);
    if (dot) {
        dot.classList.toggle("status-online", online);
        dot.classList.toggle("status-offline", !online);
    }

    // Nếu đang mở chat với user này → cập nhật header
    if (currentPartnerId === userId) {
        const headerDot = document.getElementById("chatHeaderStatus");
        const headerStatus = document.getElementById("chatHeaderStatusText");

        if (headerDot) {
            headerDot.classList.toggle("status-online", online);
            headerDot.classList.toggle("status-offline", !online);
        }
        if (headerStatus) {
            headerStatus.innerText = online ? "Đang hoạt động" : "Ngoại tuyến";
        }
    }
}

