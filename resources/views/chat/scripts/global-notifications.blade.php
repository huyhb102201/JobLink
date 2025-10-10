<script>
    window.authId = {{ auth()->id() ?? 'null' }};
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

    function showChatToast(sender, content, avatarUrl, timeText = "Vừa xong", onClick = null) {
        const container = document.getElementById('chatToastContainer');
        if (!container) return;

        const toastEl = document.createElement('div');
        toastEl.className = "toast show mb-2 border-0 shadow-sm rounded-3";
        toastEl.setAttribute("role", "alert");

        toastEl.innerHTML = `
            <div class="toast-body p-2 d-flex align-items-start gap-2">
                <img src="${avatarUrl}" alt="avatar" 
                     class="rounded-circle flex-shrink-0" width="42" height="42">
                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">${sender}</span>
                        <small class="text-muted">${timeText}</small>
                    </div>
                    <div class="text-truncate">${content.length > 50 ? content.substring(0, 50) + '...' : content}</div>
                </div>
            </div>
        `;

        if (onClick) {
            toastEl.querySelector('.toast-body').addEventListener('click', onClick);
            toastEl.querySelector('.toast-body').classList.add("cursor-pointer");
        }

        container.appendChild(toastEl);

        setTimeout(() => {
            toastEl.classList.remove("show");
            toastEl.addEventListener("transitionend", () => toastEl.remove());
        }, 5000);
    }

    function handleMessageEvent(e) {
        const incomingMsg = e.message;
        if (incomingMsg.sender_id === window.authId) return;

        const sound = document.getElementById('chatNotifySound');
        if (sound) sound.play();

        const onClick = () => {
            if (incomingMsg.conversation_id) {
                window.location.href = `/chat/user/${incomingMsg.sender.username}`;
            } else if (incomingMsg.job_id) {
                window.location.href = `/chat/group/${incomingMsg.job_id}`;
            } else if (incomingMsg.org_id) {
                window.location.href = `/chat/org/${incomingMsg.org_id}`;
            }
        };

        showChatToast(
            incomingMsg.sender.name,
            incomingMsg.content || '[Hình ảnh]',
            incomingMsg.sender.avatar_url ?? "{{ asset('assets/img/defaultavatar.jpg') }}",
            "Vừa xong",
            onClick
        );

        if (Notification.permission === "granted") {
            new Notification("Tin nhắn mới từ " + incomingMsg.sender.name, {
                body: incomingMsg.content || '[Hình ảnh]',
                icon: incomingMsg.sender.avatar_url ?? "{{ asset('assets/img/defaultavatar.jpg') }}"
            });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    new Notification("Tin nhắn mới từ " + incomingMsg.sender.name, {
                        body: incomingMsg.content || '[Hình ảnh]',
                        icon: incomingMsg.sender.avatar_url ?? "{{ asset('assets/img/defaultavatar.jpg') }}"
                    });
                }
            });
        }
    }

    function subscribeToChannels() {
        if (!window.Echo || !window.authId) return;

        fetch('/chat/list')
            .then(res => {
                if (!res.ok) throw new Error('Failed to fetch chat list');
                return res.json();
            })
            .then(data => {
                data.forEach(box => {
                    if (box.partner_id) {
                        const userIds = [window.authId, box.partner_id].sort((a, b) => a - b);
                        const channel = 'chat.' + userIds.join('.');
                        window.Echo.private(channel).listen('MessageSent', handleMessageEvent);
                        console.log('Subscribed to 1-1 channel:', channel);
                    } else if (box.job_id) {
                        const channel = 'chat-group.' + box.job_id;
                        window.Echo.join(channel).listen('MessageSent', handleMessageEvent);
                        console.log('Subscribed to group channel:', channel);
                    } else if (box.org_id) {
                        const channel = 'chat-org.' + box.org_id;
                        window.Echo.join(channel).listen('MessageSent', handleMessageEvent);
                        console.log('Subscribed to org channel:', channel);
                    }
                });
            })
            .catch(err => {
                console.error('Error fetching chat list:', err);
            });
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

    window.addEventListener('DOMContentLoaded', () => {
        if (window.Echo) {
            const checkEchoReady = () => {
                const pusher = window.Echo.connector.pusher;
                if (pusher.connection.state === 'connected') {
                    console.log('🟢 Echo connected, initializing presence and channels...');
                    initPresenceChannel();
                    subscribeToChannels();
                } else if (pusher.connection.state === 'disconnected') {
                    console.warn('🔴 Pusher disconnected, retrying in 3s...');
                    setTimeout(checkEchoReady, 3000);
                } else {
                    setTimeout(checkEchoReady, 1000);
                }
            };
            checkEchoReady();
        }
    });
</script>