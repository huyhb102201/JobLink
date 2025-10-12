<script>
    window.authId = {{ auth()->id() ?? 'null' }};
    window.currentPartnerId = null;
    window.currentJobId = null;
    window.currentOrgId = null;
    window.presenceInitialized = false;

    // Hàm lưu danh sách trò chuyện vào sessionStorage với thời gian hết hạn
    function saveChatListToStorage(chatList) {
        const data = {
            timestamp: Date.now(),
            chatList: chatList
        };
        sessionStorage.setItem('chatList', JSON.stringify(data));
    }

    // Hàm lấy danh sách trò chuyện từ sessionStorage
    function getChatListFromStorage() {
        const data = sessionStorage.getItem('chatList');
        if (!data) return null;

        const parsed = JSON.parse(data);
        const ttl = 5 * 60 * 1000; // 5 phút (thời gian hết hạn)
        if (Date.now() - parsed.timestamp > ttl) {
            sessionStorage.removeItem('chatList'); // Xóa nếu hết hạn
            return null;
        }
        return parsed.chatList;
    }

    // Hàm lưu danh sách kênh đã đăng ký
    function saveSubscribedChannels(channels) {
        sessionStorage.setItem('subscribedChannels', JSON.stringify(channels));
    }

    // Hàm lấy danh sách kênh đã đăng ký
    function getSubscribedChannels() {
        const channels = sessionStorage.getItem('subscribedChannels');
        return channels ? JSON.parse(channels) : [];
    }

    // Hàm cập nhật trạng thái online/offline của người dùng
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

    // Hàm hiển thị thông báo toast cho tin nhắn mới
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

    // Hàm xử lý sự kiện tin nhắn mới
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

    // Hàm đăng ký các kênh trò chuyện
    function subscribeToChannels() {
        if (!window.Echo || !window.authId) return;

        const subscribedChannels = getSubscribedChannels();
        const cachedChatList = getChatListFromStorage();

        if (cachedChatList) {
            // Sử dụng danh sách trò chuyện từ sessionStorage
            console.log('Sử dụng danh sách trò chuyện từ sessionStorage');
            cachedChatList.forEach(box => {
                let channel;
                if (box.partner_id) {
                    const userIds = [window.authId, box.partner_id].sort((a, b) => a - b);
                    channel = 'chat.' + userIds.join('.');
                } else if (box.job_id) {
                    channel = 'chat-group.' + box.job_id;
                } else if (box.org_id) {
                    channel = 'chat-org.' + box.org_id;
                }

                if (channel && !subscribedChannels.includes(channel)) {
                    if (box.partner_id) {
                        window.Echo.private(channel).listen('MessageSent', handleMessageEvent);
                    } else {
                        window.Echo.join(channel).listen('MessageSent', handleMessageEvent);
                    }
                    subscribedChannels.push(channel);
                    console.log('Đã đăng ký kênh:', channel);
                }
            });
            saveSubscribedChannels(subscribedChannels);
        } else {
            // Gọi API nếu không có dữ liệu trong sessionStorage
            fetch('/chat/list')
                .then(res => {
                    if (!res.ok) throw new Error('Không thể lấy danh sách trò chuyện');
                    return res.json();
                })
                .then(data => {
                    saveChatListToStorage(data); // Lưu vào sessionStorage
                    data.forEach(box => {
                        let channel;
                        if (box.partner_id) {
                            const userIds = [window.authId, box.partner_id].sort((a, b) => a - b);
                            channel = 'chat.' + userIds.join('.');
                        } else if (box.job_id) {
                            channel = 'chat-group.' + box.job_id;
                        } else if (box.org_id) {
                            channel = 'chat-org.' + box.org_id;
                        }

                        if (channel && !subscribedChannels.includes(channel)) {
                            if (box.partner_id) {
                                window.Echo.private(channel).listen('MessageSent', handleMessageEvent);
                            } else {
                                window.Echo.join(channel).listen('MessageSent', handleMessageEvent);
                            }
                            subscribedChannels.push(channel);
                            console.log('Đã đăng ký kênh:', channel);
                        }
                    });
                    saveSubscribedChannels(subscribedChannels);
                })
                .catch(err => {
                    console.error('Lỗi khi lấy danh sách trò chuyện:', err);
                });
        }
    }

    // Hàm khởi tạo presence channel
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

    // Sự kiện tải trang
    window.addEventListener('DOMContentLoaded', () => {
        if (window.Echo) {
            const checkEchoReady = () => {
                const pusher = window.Echo.connector.pusher;
                if (pusher.connection.state === 'connected') {
                    console.log('🟢 Echo đã kết nối, khởi tạo presence và các kênh...');
                    initPresenceChannel();
                    subscribeToChannels();
                } else if (pusher.connection.state === 'disconnected') {
                    console.warn('🔴 Pusher ngắt kết nối, thử lại sau 3 giây...');
                    setTimeout(checkEchoReady, 3000);
                } else {
                    setTimeout(checkEchoReady, 1000);
                }
            };
            checkEchoReady();
        }
    });
</script>