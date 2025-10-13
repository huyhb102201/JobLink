<script>
    window.authId = {{ auth()->id() ?? 'null' }};
    window.currentPartnerId = null;
    window.currentJobId = null;
    window.currentOrgId = null;
    window.presenceInitialized = false;

    // ====== HÀM LƯU & LẤY DỮ LIỆU TỪ SESSION STORAGE ======

    function saveChatListToStorage(chatList) {
        const data = {
            timestamp: Date.now(),
            chatList: chatList
        };
        sessionStorage.setItem('chatList', JSON.stringify(data));
    }

    function getChatListFromStorage() {
        const data = sessionStorage.getItem('chatList');
        if (!data) return null;

        const parsed = JSON.parse(data);
        const ttl = 5 * 60 * 1000; // 5 phút hết hạn
        if (Date.now() - parsed.timestamp > ttl) {
            sessionStorage.removeItem('chatList');
            return null;
        }
        return parsed.chatList;
    }

    function saveSubscribedChannels(channels) {
        sessionStorage.setItem('subscribedChannels', JSON.stringify(channels));
    }

    function getSubscribedChannels() {
        const channels = sessionStorage.getItem('subscribedChannels');
        return channels ? JSON.parse(channels) : [];
    }

    // ====== HIỂN THỊ TRẠNG THÁI ONLINE ======

    function setUserOnline(userId, online) {
        const elements = document.querySelectorAll(`#status-${userId}, #chatHeaderStatus`);
        elements.forEach(el => {
            if (el) {
                el.classList.toggle('status-online', online);
                el.classList.toggle('status-offline', !online);
                const statusText = el.closest('#chatHeader')?.querySelector('#chatHeaderStatusText');
                if (statusText) statusText.textContent = online ? 'Đang hoạt động' : 'Ngoại tuyến';
            }
        });
    }

    // ====== HIỂN THỊ TOAST ======

    function showChatToast(sender, content, avatarUrl, timeText = "Vừa xong", onClick = null) {
        const container = document.getElementById('chatToastContainer');
        if (!container) return;

        const toastEl = document.createElement('div');
        toastEl.className = "toast show mb-2 border-0 shadow-sm rounded-3";
        toastEl.setAttribute("role", "alert");

        toastEl.innerHTML = `
            <div class="toast-body p-2 d-flex align-items-start gap-2">
                <img src="${avatarUrl}" alt="avatar" class="rounded-circle flex-shrink-0" width="42" height="42">
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

    // ====== XỬ LÝ TIN NHẮN MỚI ======

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
        }
    }

    // ====== ĐĂNG KÝ KÊNH TRÒ CHUYỆN ======

    function subscribeToChannels() {
        if (!window.Echo || !window.authId) {
            console.warn('❌ Echo chưa sẵn sàng hoặc người dùng chưa đăng nhập');
            return;
        }

        // 🔥 Mỗi lần load lại trang => reset danh sách kênh (để đảm bảo đăng ký lại)
        const subscribedChannels = [];
        sessionStorage.removeItem('subscribedChannels');

        const cachedChatList = getChatListFromStorage();

        if (cachedChatList) {
            console.log('📦 Dùng danh sách trò chuyện từ sessionStorage');

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
                    try {
                        if (box.partner_id) {
                            window.Echo.private(channel).listen('MessageSent', handleMessageEvent);
                        } else {
                            window.Echo.join(channel).listen('MessageSent', handleMessageEvent);
                        }
                        subscribedChannels.push(channel);
                        console.log('✅ Đã đăng ký kênh:', channel);
                    } catch (error) {
                        console.error('⚠️ Lỗi khi đăng ký kênh:', channel, error);
                    }
                }
            });

            saveSubscribedChannels(subscribedChannels);
        } else {
            console.log('🌐 Không có cache, gọi API lấy danh sách trò chuyện...');
            fetch('/chat/list')
                .then(res => res.json())
                .then(data => {
                    saveChatListToStorage(data);
                    subscribeToChannels(); // Gọi lại sau khi có dữ liệu
                })
                .catch(err => console.error('Lỗi khi lấy danh sách trò chuyện:', err));
        }
    }

    // ====== PRESENCE CHANNEL ======

    function initPresenceChannel() {
        if (window.presenceInitialized || !window.Echo) return;
        window.presenceInitialized = true;

        window.Echo.join('online-users')
            .here(users => {
                console.log('👥 Người dùng online ban đầu:', users);
                users.forEach(u => setUserOnline(u.id, true));
            })
            .joining(user => {
                console.log('🟢 Người dùng vừa online:', user);
                setUserOnline(user.id, true);
            })
            .leaving(user => {
                console.log('🔴 Người dùng vừa offline:', user);
                setUserOnline(user.id, false);
            })
            .error(error => console.error('Lỗi presence channel:', error));
    }

    // ====== CHỜ ECHO KẾT NỐI RỒI MỚI LẮNG NGHE ======

    window.addEventListener('DOMContentLoaded', () => {
        const waitForEcho = () => {
            if (!window.Echo || !window.Echo.connector || !window.Echo.connector.pusher) {
                console.warn('⏳ Đang chờ Echo khởi tạo...');
                return setTimeout(waitForEcho, 500);
            }

            const pusher = window.Echo.connector.pusher;

            const checkConnection = () => {
                if (pusher.connection.state === 'connected') {
                    console.log('🟢 Echo đã kết nối, khởi tạo presence và đăng ký kênh...');
                    initPresenceChannel();
                    subscribeToChannels();
                } else {
                    console.warn('⏳ Echo chưa sẵn sàng, thử lại sau 1 giây...');
                    setTimeout(checkConnection, 1000);
                }
            };

            checkConnection();
        };

        waitForEcho();
    });
</script>
