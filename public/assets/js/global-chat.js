// assets/js/global-chat.js
console.log('Global chat script loaded');

if (typeof window.authId === 'undefined') window.authId = null;
if (!window.chatConfig) window.chatConfig = {
    defaultAvatar: "{{ asset('assets/img/defaultavatar.jpg') }}",
    chatListUrl: "{{ route('messages.chat_list') }}"
};

let globalSubscribedChannels = new Set();

// ----------------------
// Toast hiển thị tin nhắn (centralized)
// ----------------------
window.showChatToast = function(sender, content, avatarUrl, timeText = "Vừa xong", onClick = null) {
    const container = document.getElementById('chatToastContainer');
    if (!container) return console.error('Toast container not found');

    // Avoid duplicates
    if (container.querySelector(`[data-sender="${sender}"]`)) return;

    const toastEl = document.createElement('div');
    toastEl.className = "toast show mb-2 border-0 shadow-sm rounded-3";
    toastEl.dataset.sender = sender;

    toastEl.innerHTML = `
        <div class="toast-body p-2 d-flex align-items-start gap-2 cursor-pointer">
            <img src="${avatarUrl}" alt="avatar" class="rounded-circle flex-shrink-0" width="42" height="42" onerror="this.src='${window.chatConfig.defaultAvatar}'">
            <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">${sender}</span>
                    <small class="text-muted">${timeText}</small>
                </div>
                <div class="text-truncate">${content.length > 50 ? content.substring(0,50) + '...' : content}</div>
            </div>
        </div>
    `;

    if (onClick) {
        toastEl.querySelector('.toast-body').addEventListener('click', onClick);
    }
    container.appendChild(toastEl);

    setTimeout(() => {
        if (toastEl.parentNode) {
            toastEl.classList.remove("show");
            toastEl.addEventListener("transitionend", () => {
                if (toastEl.parentNode) toastEl.remove();
            });
        }
    }, 5000);
};

// ----------------------
// Xử lý tin nhắn mới global (for notifications only)
// ----------------------
window.handleGlobalMessageEvent = function(e) {
    console.log('🔵 Received global MessageSent event:', e);
    const incomingMsg = e.message;
    if (!incomingMsg || incomingMsg.sender_id === window.authId) return;

    const sound = document.getElementById('chatNotifySound');
    if (sound) sound.play().catch(err => console.warn('Sound play failed:', err));

    const avatarUrl = incomingMsg.sender?.avatar_url || window.chatConfig.defaultAvatar;

    showChatToast(
        incomingMsg.sender?.name || 'Unknown',
        incomingMsg.content || '[Hình ảnh]',
        avatarUrl,
        "Vừa xong",
        () => {
            let url;
            if (incomingMsg.conversation_id) url = `/chat?partner=${incomingMsg.sender_id}`;
            else if (incomingMsg.job_id) url = `/jobs/${incomingMsg.job_id}/chat`;
            else if (incomingMsg.org_id) url = `/chat/org/${incomingMsg.org_id}`;
            else url = '/chat';
            window.location.href = url;
        }
    );

    // Notification trình duyệt
    if (Notification.permission === "granted") {
        const notification = new Notification("Tin nhắn mới từ " + (incomingMsg.sender?.name || 'Unknown'), {
            body: incomingMsg.content || '[Hình ảnh]',
            icon: avatarUrl,
            tag: `chat-${incomingMsg.id}`
        });
        notification.onclick = () => window.focus();
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                new Notification("Tin nhắn mới từ " + (incomingMsg.sender?.name || 'Unknown'), {
                    body: incomingMsg.content || '[Hình ảnh]',
                    icon: avatarUrl,
                    tag: `chat-${incomingMsg.id}`
                });
            }
        });
    }
};

// ----------------------
// Subscribe kênh chat global (for notifications)
// ----------------------
function subscribeChannel(channelName, isPrivate = true) {
    if (!window.Echo) return;
    if (globalSubscribedChannels.has(channelName)) return;

    if (isPrivate) {
        window.Echo.private(channelName).listen('MessageSent', handleGlobalMessageEvent);
    } else {
        window.Echo.join(channelName).listen('MessageSent', handleGlobalMessageEvent);
    }

    globalSubscribedChannels.add(channelName);
    console.log('Global subscribed to channel:', channelName);
}

// ----------------------
// Fetch danh sách chat và subscribe global
// ----------------------
function fetchAndSubscribeChats() {
    if (!window.authId || window.authId === 'null') return;

    fetch(window.chatConfig.chatListUrl, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(conversations => {
        if (!Array.isArray(conversations)) return;
        conversations.slice(0, 10).forEach(c => {  // Limit to 10 to avoid overload
            let channelName;
            if (c.partner_id) {
                const ids = [parseInt(window.authId), parseInt(c.partner_id)].sort((a,b)=>a-b);
                channelName = `chat.${ids.join('.')}`;
                subscribeChannel(channelName);
            } else if (c.job_id) {
                channelName = `chat-group.${c.job_id}`;
                subscribeChannel(channelName, false);
            } else if (c.org_id) {
                channelName = `chat-org.${c.org_id}`;
                subscribeChannel(channelName, false);
            }
        });
        console.log('🎉 Global subscribed to', globalSubscribedChannels.size, 'channels');
    })
    .catch(err => console.error('💥 Failed to fetch chat list:', err));
}

// ----------------------
// Khởi tạo global chat (run once)
// ----------------------
function initGlobalChat() {
    if (!window.Echo || !window.authId || window.authId === 'null') return;

    const checkConnection = () => {
        const pusher = window.Echo.connector.pusher;
        if (pusher.connection.state === 'connected') {
            console.log('🟢 Echo connected, subscribing global chats...');
            fetchAndSubscribeChats();
        } else {
            setTimeout(checkConnection, 1000);
        }
    };
    checkConnection();

    window.addEventListener('beforeunload', () => {
        globalSubscribedChannels.forEach(ch => window.Echo.leave(ch));
        globalSubscribedChannels.clear();
    });

    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission().then(p => console.log("🔔 Notification permission:", p));
    }
}

// Run init only once, after DOM loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initGlobalChat, 500));
} else {
    setTimeout(initGlobalChat, 500);
}