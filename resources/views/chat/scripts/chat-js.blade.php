<script>
    // Existing variables
    let currentChat = '';
    let currentItem = null;
    let currentPartnerId = {{ $box && $receiverId ? $receiverId : 'null' }};
    let currentJobId = {{ ($box && !$receiverId && $box->job_id) ? $box->job_id : 'null' }};
    let currentOrgId = {{ ($box && !$receiverId && $box->org_id) ? $box->org_id : 'null' }};
    let currentBoxId = {{ $box ? $box->id : 'null' }};
    let currentChannel = null;
    let lastMessageTime = null;
    let offcanvas = null;
    let replyingTo = null;
    let selectedImage = null;

    // === Added: Global variable for presence channel ===
    window.presenceInitialized = false;

    // === Added: Function to set user online/offline status ===
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

    // === Added: Initialize presence channel ===
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

    function scrollToBottom() {
        const chatBox = document.getElementById('chatMessages');
        chatBox.scrollTo({
            top: chatBox.scrollHeight,
            behavior: 'smooth'
        });
    }

    let lastSenderId = null;

    function appendMessage(msg, isMe = false, status = 'Đang gửi') {
        const chatBox = document.getElementById('chatMessages');
        if (!chatBox || document.querySelector(`[data-message-id="${msg.id}"]`)) {
            return null;
        }

        const msgTime = new Date(msg.created_at);
        if (!lastMessageTime || (msgTime - lastMessageTime) / 1000 / 60 > 10) {
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time-center';
            timeDiv.innerText = msgTime.toLocaleString('vi-VN');
            chatBox.appendChild(timeDiv);
        }
        lastMessageTime = msgTime;

        const hideAvatar = !isMe && lastSenderId === msg.sender_id;
        lastSenderId = msg.sender_id;

        const msgContainer = document.createElement('div');
        msgContainer.className = `message-container ${isMe ? 'me' : 'other'} ${hideAvatar ? 'hide-avatar' : ''}`;
        msgContainer.dataset.created = msg.created_at;
        msgContainer.dataset.messageId = msg.id;

        let avatarHTML = '';
        if (!isMe) {
            avatarHTML = `
                <div class="avatar-container">
                    <img src="${msg.sender.avatar_url ?? '{{ asset('assets/img/defaultavatar.jpg') }}'}" 
                         alt="Sender Avatar">
                </div>
            `;
        } else {
            avatarHTML = `<div class="avatar-container"></div>`;
        }

        let statusHTML = isMe ? `<div class="message-status">${status}</div>` : '';

        let contentHTML = '';
        const { replyTo, mainContent } = parseReplyContent(msg.content || '');

        if (replyTo) {
            contentHTML += `<div class="reply-quote">${replyTo}</div>`;
        }
        if (mainContent) {
            contentHTML += `<div class="main-text">${mainContent}</div>`;
        }
        if (msg.img) {
            contentHTML += `
                <img src="${msg.img}" class="message-img" alt="Message Image"
                     onerror="this.src='{{ asset('assets/img/blog/blog-1.jpg') }}'; this.onerror=null; console.error('Image load failed:', '${msg.img}');">
            `;
        }

        msgContainer.innerHTML = `
            ${avatarHTML}
            <div class="message ${isMe ? 'me' : 'other'}">
                <span class="sender">${isMe ? 'Bạn' : msg.sender.name}</span>
                ${contentHTML}
                ${statusHTML}
            </div>
        `;

        msgContainer.addEventListener('click', (e) => {
            if (msgContainer.dataset.shownTime === 'true' || e.longPress) return;
            msgContainer.dataset.shownTime = 'true';
            const timeLabel = document.createElement('div');
            timeLabel.className = 'message-time-bottom';
            timeLabel.innerText = msgTime.toLocaleString('vi-VN');
            msgContainer.querySelector('.message').appendChild(timeLabel);
            setTimeout(() => {
                if (timeLabel.parentNode) timeLabel.remove();
                msgContainer.dataset.shownTime = 'false';
            }, 5000);
        });

        let pressTimer;
        const startLongPress = (e) => {
            pressTimer = setTimeout(() => {
                e.longPress = true;
                highlightMessage(msgContainer, msg);
            }, 500);
        };

        const cancelLongPress = () => {
            clearTimeout(pressTimer);
        };

        msgContainer.addEventListener('mousedown', startLongPress);
        msgContainer.addEventListener('mouseup', cancelLongPress);
        msgContainer.addEventListener('mouseleave', cancelLongPress);
        msgContainer.addEventListener('touchstart', startLongPress);
        msgContainer.addEventListener('touchend', cancelLongPress);
        msgContainer.addEventListener('touchmove', cancelLongPress);

        chatBox.appendChild(msgContainer);
        scrollToBottom();
        return msgContainer;
    }

    function highlightMessage(msgContainer, msg) {
        document.querySelectorAll('.message-container.highlight').forEach(el => {
            el.classList.remove('highlight');
            const replyBtn = el.querySelector('.reply-btn');
            if (replyBtn) replyBtn.remove();
        });

        msgContainer.classList.add('highlight');

        const replyBtn = document.createElement('button');
        replyBtn.className = 'reply-btn';
        replyBtn.innerHTML = "<i class='bi bi-reply'></i> Trả lời";
        replyBtn.addEventListener('click', () => {
            replyingTo = msg;
            showReplyPreview(msg.sender.name, msg.content || '[Hình ảnh]');
            msgContainer.classList.remove('highlight');
            replyBtn.remove();
            document.getElementById('messageInput').focus();
        });

        msgContainer.querySelector('.message').appendChild(replyBtn);
    }

    function showReplyPreview(sender, content) {
        const preview = document.getElementById('replyPreview');
        document.getElementById('replySender').innerText = sender;
        document.getElementById('replyContent').innerText = content.length > 50 ? content.substring(0, 50) + '...' : content;
        preview.classList.add('show');
    }

    function clearReplyPreview() {
        const preview = document.getElementById('replyPreview');
        preview.classList.remove('show');
        replyingTo = null;
    }

    function showImagePreview(file) {
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            preview.classList.add('show');
        };
        reader.readAsDataURL(file);
    }

    function clearImagePreview() {
        const preview = document.getElementById('imagePreview');
        preview.classList.remove('show');
        selectedImage = null;
        document.getElementById('fileInput').value = '';
    }

    function parseReplyContent(content) {
        if (!content.startsWith("Trả lời ")) return { replyTo: null, mainContent: content };

        function parseLayer(str) {
            const nameMatch = str.match(/^Trả lời ([^:]+):\s*/);
            if (!nameMatch) return { quoted: '', rest: str };
            const name = nameMatch[1];
            let rest = str.slice(nameMatch[0].length).trim();

            if (!rest.startsWith('"')) return { quoted: '', rest };

            rest = rest.slice(1);
            let quoted = '';
            let i = 0;
            while (i < rest.length) {
                if (rest.slice(i).startsWith('Trả lời ')) {
                    const innerResult = parseLayer(rest.slice(i));
                    quoted = innerResult.quoted;
                    rest = innerResult.rest;
                    i = 0;
                    continue;
                } else if (rest[i] === '"') {
                    quoted = rest.slice(0, i).trim();
                    rest = rest.slice(i + 1).trim();
                    break;
                } else {
                    i++;
                }
            }

            if (!quoted) quoted = rest.trim(), rest = '';
            return { quoted, rest, name };
        }

        const result = parseLayer(content);
        return {
            replyTo: `${result.name}: "${result.quoted}"`,
            mainContent: result.rest
        };
    }

    function subscribeToChannel(partnerId, jobId = null, orgId = null) {
        if (currentChannel) {
            window.Echo.leave(currentChannel);
        }

        if (partnerId) {
            const userIds = [window.authId, partnerId].sort((a, b) => a - b);
            currentChannel = 'chat.' + userIds.join('.');
            window.Echo.private(currentChannel).listen('MessageSent', handleMessageEvent);
        } else if (jobId) {
            currentChannel = 'chat-group.' + jobId;
            window.Echo.join(currentChannel).listen('MessageSent', handleMessageEvent);
        } else if (orgId) {
            currentChannel = 'chat-org.' + orgId;
            window.Echo.join(currentChannel).listen('MessageSent', handleMessageEvent);
        }

        console.log('Subscribed to channel:', currentChannel);
    }

    function handleMessageEvent(e) {
        console.log('Received MessageSent event:', e);
        const incomingMsg = e.message;
        const isMe = incomingMsg.sender_id === window.authId;

        if (!isMe && (currentPartnerId === incomingMsg.sender_id || currentJobId === incomingMsg.job_id || currentOrgId === incomingMsg.org_id)) {
            const msgDiv = appendMessage(incomingMsg, false);
        }
    }

    function openBoxChat(name, element, partnerId, boxId, jobId = null, orgId = null) {
        currentChat = name;
        currentBoxId = boxId;
        if (partnerId) {
            currentPartnerId = partnerId;
            currentJobId = null;
            currentOrgId = null;
        } else if (jobId) {
            currentPartnerId = null;
            currentJobId = jobId;
            currentOrgId = null;
        } else if (orgId) {
            currentPartnerId = null;
            currentJobId = null;
            currentOrgId = orgId;
        }
        window.currentPartnerId = currentPartnerId;
        window.currentJobId = currentJobId;
        window.currentOrgId = currentOrgId;

        if (currentItem) currentItem.classList.remove('active');
        if (element) {
            currentItem = element;
            currentItem.classList.add('active');
        }

        const header = document.getElementById('chatHeader');
        const chatInput = document.getElementById('chatInput');

        if (partnerId) {
            const avatarImg = element.querySelector("img");
            const avatarUrl = avatarImg ? avatarImg.src : "{{ asset('assets/img/defaultavatar.jpg') }}";
            // === Modified: Remove initial online status assumption, rely on presence channel ===
            header.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <div class="position-relative" style="width:45px;height:45px;">
                        <img id="chatHeaderAvatar" src="${avatarUrl}" 
                             class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">
                        <span class="status-dot status-offline" id="chatHeaderStatus"></span>
                    </div>
                    <div>
                        <div class="fw-bold" id="chatHeaderName">${name} <span class="chat-icon icon-1-1"><i class="bi bi-person"></i></span></div>
                        <div class="small text-muted" id="chatHeaderStatusText">Ngoại tuyến</div>
                    </div>
                </div>
            `;
            // === Added: Update online status dynamically ===
            if (window.Echo && partnerId) {
                fetch(`/chat/user-status/${partnerId}`)
                    .then(res => res.json())
                    .then(data => {
                        setUserOnline(partnerId, data.is_online);
                    })
                    .catch(err => console.error('Lỗi khi lấy trạng thái người dùng:', err));
            }
        } else if (jobId) {
            header.innerHTML = `
                <div class="fw-bold" id="chatHeaderName">${name} <span class="chat-icon icon-group"><i class="bi bi-people"></i></span></div>
                <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm</div>
            `;
        } else if (orgId) {
            header.innerHTML = `
                <div class="fw-bold" id="chatHeaderName">${name} <span class="chat-icon icon-org"><i class="bi bi-building"></i></span></div>
                <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm tổ chức</div>
            `;
        } else {
            header.innerHTML = `<div class="fw-bold">Chọn một cuộc trò chuyện</div>`;
        }

        chatInput.style.display = 'flex';

        const chatBox = document.getElementById('chatMessages');
        chatBox.innerHTML = `<div class="message text-muted">Đang tải tin nhắn...</div>`;
        lastMessageTime = null;

        fetch(`/chat/box/${boxId}/messages`)
            .then(res => {
                if (!res.ok) throw new Error('Failed to load messages');
                return res.json();
            })
            .then(data => {
                chatBox.innerHTML = '';
                if (data.length === 0 || (Array.isArray(data) && data.length === 0)) {
                    chatBox.innerHTML = `<div class="message text-muted">Chưa có tin nhắn với ${name}</div>`;
                } else {
                    data.forEach(msg => appendMessage(
                        msg,
                        msg.sender_id === window.authId,
                        msg.sender_id === window.authId ? 'Đã nhận' : ''
                    ));
                }
                scrollToBottom();
            })
            .catch(err => {
                console.error(err);
                chatBox.innerHTML = `<div class="message text-muted">Lỗi khi tải tin nhắn.</div>`;
            });

        if (window.Echo) {
            subscribeToChannel(partnerId, jobId, orgId);
        }

        if (offcanvas && window.innerWidth < 1200) {
            offcanvas.hide();
        }
    }

    function sendMessage() {
        if (!currentChat || (!currentPartnerId && !currentJobId && !currentOrgId)) {
            alert('Chọn một cuộc trò chuyện trước!');
            return;
        }
        const input = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const fileInput = document.getElementById('fileInput');
        let text = input.value.trim();

        if (!text && !selectedImage) {
            alert('Vui lòng nhập nội dung tin nhắn hoặc chọn hình ảnh!');
            return;
        }

        if (replyingTo) {
            text = `Trả lời ${replyingTo.sender.name}: "${replyingTo.content || '[Hình ảnh]'}"\n` + text;
            clearReplyPreview();
        }

        const tempId = 'temp-' + Date.now();
        const tempMsg = {
            id: tempId,
            content: text || null,
            img: selectedImage ? URL.createObjectURL(selectedImage) : null,
            sender: { id: window.authId, name: 'Bạn', avatar_url: '{{ auth()->user()->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}' },
            created_at: new Date().toISOString()
        };
        const msgDiv = appendMessage(tempMsg, true, 'Đang gửi');

        sendBtn.classList.add('sending');
        input.value = '';

        const formData = new FormData();
        if (text) formData.append('content', text);
        if (currentPartnerId) {
            formData.append('receiver_id', currentPartnerId);
        } else if (currentJobId) {
            formData.append('job_id', currentJobId);
        } else if (currentOrgId) {
            formData.append('org_id', currentOrgId);
        }
        if (selectedImage) {
            formData.append('img', selectedImage);
            console.log('Gửi FormData với ảnh:', selectedImage.name, selectedImage.type, selectedImage.size);
        } else {
            console.log('FormData không chứa ảnh');
        }

        for (let pair of formData.entries()) {
            console.log('FormData entry:', pair[0], pair[1]);
        }
        clearReplyPreview();

        axios.post('{{ route("messages.send") }}', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
            .then(res => {
                const sentMsg = res.data;
                msgDiv.dataset.messageId = sentMsg.id;
                if (sentMsg.img) {
                    const imgElem = msgDiv.querySelector('.message-img');
                    if (imgElem) imgElem.src = sentMsg.img;
                }
                const statusDiv = msgDiv.querySelector('.message-status');
                if (statusDiv) statusDiv.innerText = 'Đã nhận';
                sendBtn.classList.remove('sending');
                selectedImage = null;
                fileInput.value = '';
                clearImagePreview();
                console.log('Message sent:', sentMsg);
            })
            .catch(err => {
                console.error('Send error:', err);
                const statusDiv = msgDiv.querySelector('.message-status');
                if (statusDiv) statusDiv.innerText = 'Gửi thất bại';
                sendBtn.classList.remove('sending');
                alert('Gửi tin nhắn thất bại: ' + (err.response?.data?.error || err.message));
            });
    }

    window.addEventListener('DOMContentLoaded', () => {
        const offcanvasElement = document.getElementById('chatOffcanvas');
        if (offcanvasElement) {
            offcanvas = new bootstrap.Offcanvas(offcanvasElement);
        }

        // === Added: Initialize Echo and presence channel ===
        const waitForEcho = () => {
            if (!window.Echo || !window.Echo.connector || !window.Echo.connector.pusher) {
                console.warn('⏳ Đang chờ Echo khởi tạo...');
                return setTimeout(waitForEcho, 500);
            }

            const pusher = window.Echo.connector.pusher;

            const checkConnection = () => {
                if (pusher.connection.state === 'connected') {
                    console.log('🟢 Echo đã kết nối, khởi tạo presence channel...');
                    initPresenceChannel();
                } else {
                    console.warn('⏳ Echo chưa sẵn sàng, thử lại sau 1 giây...');
                    setTimeout(checkConnection, 1000);
                }
            };

            checkConnection();
        };

        waitForEcho();

        @if($box && $receiverId)
            const boxElement = document.querySelector(`[data-box-id="${currentBoxId}"]`);
            if (boxElement) {
                openBoxChat('{{ $employer->name }}', boxElement, {{ $receiverId }}, {{ $box->id }});
            }
        @elseif($box && !$receiverId && $box->job_id)
            const boxElement = document.querySelector(`[data-box-id="${currentBoxId}"]`);
            if (boxElement) {
                openBoxChat('{{ $box->name }}', boxElement, null, {{ $box->id }}, {{ $box->job_id }});
            }
        @elseif($box && !$receiverId && $box->org_id)
            const boxElement = document.querySelector(`[data-box-id="${currentBoxId}"]`);
            if (boxElement) {
                openBoxChat('{{ $box->name }}', boxElement, null, {{ $box->id }}, null, {{ $box->org_id }});
            }
        @endif

        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    selectedImage = e.target.files[0];
                    console.log('File ảnh đã chọn:', selectedImage.name, selectedImage.type, selectedImage.size);
                    showImagePreview(selectedImage);
                } else {
                    console.log('Không có file ảnh được chọn');
                    selectedImage = null;
                    clearImagePreview();
                }
            });
        }

        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('paste', (e) => {
                const items = e.clipboardData.items;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        selectedImage = items[i].getAsFile();
                        console.log('Ảnh dán từ clipboard:', selectedImage.name, selectedImage.type, selectedImage.size);
                        showImagePreview(selectedImage);
                        e.preventDefault();
                        break;
                    }
                }
            });

            messageInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    if (e.ctrlKey || e.metaKey) {
                        const cursorPos = this.selectionStart;
                        const value = this.value;
                        this.value = value.substring(0, cursorPos) + "\n" + value.substring(cursorPos);
                        this.selectionEnd = cursorPos + 1;
                        e.preventDefault();
                    } else {
                        e.preventDefault();
                        sendMessage();
                    }
                }
            });

            messageInput.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        const chatArea = document.getElementById('chatArea');
        if (chatArea) {
            chatArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                chatArea.style.backgroundColor = '#e9ecef';
            });
            chatArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                chatArea.style.backgroundColor = '#fff';
            });
            chatArea.addEventListener('drop', (e) => {
                e.preventDefault();
                chatArea.style.backgroundColor = '#fff';
                const files = e.dataTransfer.files;
                if (files.length > 0 && files[0].type.startsWith('image/')) {
                    selectedImage = files[0];
                    console.log('Ảnh kéo thả:', selectedImage.name, selectedImage.type, selectedImage.size);
                    showImagePreview(selectedImage);
                    fileInput.files = e.dataTransfer.files;
                }
            });
        }
    });
</script>