import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

window.currentPartnerId = null;
window.currentJobId = null;
window.currentOrgId = null;
window.presenceInitialized = false;

function setUserOnline(userId, online) {
    const fragment = document.createDocumentFragment();
    const elements = document.querySelectorAll(`#status-${userId}, #chatHeaderStatus`);
    elements.forEach(el => {
        if (el) {
            el.classList.toggle('status-online', online);
            el.classList.toggle('status-offline', !online);
            const statusText = el.closest('#chatHeader')?.querySelector('#chatHeaderStatusText');
            if (statusText) statusText.textContent = online ? 'Đang hoạt động' : 'Ngoại tuyến';
        }
    });
    document.body.appendChild(fragment);
}

function initPresenceChannel() {
    if (window.presenceInitialized || !window.Echo) return;
    window.presenceInitialized = true;

    window.Echo.join('online-users')
        .here(users => {
            console.log('Danh sách người dùng online ban đầu:', users);
            users.forEach(u => setUserOnline(u.id, true));
        })
        .joining(user => {
            console.log('Người dùng vừa online:', user);
            setUserOnline(user.id, true);
        })
        .leaving(user => {
            console.log('Người dùng vừa offline:', user);
            setUserOnline(user.id, false);
        })
        .error(error => {
            console.error('Lỗi presence channel:', error);
        });
}

window.setUserOnline = setUserOnline;

if (window.Echo) {
    const checkEchoReady = () => {
        const pusher = window.Echo.connector.pusher;
        if (pusher.connection.state === 'connected') {
            console.log('🟢 Echo connected, initializing presence...');
            initPresenceChannel();
        } else if (pusher.connection.state === 'disconnected') {
            console.warn('🔴 Pusher disconnected, retrying in 3s...');
            setTimeout(checkEchoReady, 3000);
        } else {
            setTimeout(checkEchoReady, 1000);
        }
    };
    checkEchoReady();
}