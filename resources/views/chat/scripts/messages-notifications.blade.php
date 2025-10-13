<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Đảm bảo Echo đã được khởi tạo
        const checkEchoReady = setInterval(() => {
            if (window.Echo) {
                clearInterval(checkEchoReady);
                initEchoListeners();
            }
        }, 200);

        function initEchoListeners() {
            const USER_ID = {{ Auth::user()->account_id ?? 'null' }};

            if (!USER_ID) return;

            console.log('✅ Echo ready, listening for user:', USER_ID);

            window.Echo.channel('user-notification.' + USER_ID)
                .listen('.new-message-notification', (e) => {
                    console.log('💬 Tin nhắn mới realtime:', e.notification);

                    // Badge header chat
                    const badge = document.getElementById('chat-badge');
                    if (badge) {
                        let current = parseInt(badge.textContent || '0');
                        badge.textContent = current + 1;
                        badge.classList.remove('d-none');
                    }

                    // Reload header chat list
                    if (typeof loadChatHeader === 'function') {
                        loadChatHeader();
                    }
                });

            window.Echo.channel('user-notification.' + USER_ID)
                .listen('.new-comment-notification', (e) => {
                    console.log('💬 Bình luận realtime:', e.notification);

                    // Cập nhật badge
                    const badge = document.getElementById('notif-badge');
                    const current = parseInt(badge.textContent || 0) + 1;
                    badge.textContent = current;
                    badge.classList.remove('d-none');

                    // Thêm vào danh sách thông báo
                    const notifList = document.getElementById('notif-list');
                    const n = e.notification;
                    const html = `
                    <li class="unread">
                        <a class="dropdown-item py-2 d-flex align-items-start gap-2" href="/notifications/${n.id}">
                            <i class="bi bi-chat-dots text-primary fs-5 mt-1"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-truncate" style="max-width:170px;">${n.title}</div>
                                <small class="text-muted text-truncate d-block" style="max-width:170px;">${n.body}</small>
                            </div>
                            <span class="badge bg-primary ms-auto">Mới</span>
                        </a>
                    </li>
                `;
                    notifList.insertAdjacentHTML('afterbegin', html);

                    // Toast thông báo
                    Swal.fire({
                        title: n.title,
                        text: n.body,
                        icon: 'info',
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 4000,
                    });
                });
        }
    });

</script>