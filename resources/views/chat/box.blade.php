@extends('layouts.app')

@section('title', 'JobLink - Chat Realtime')

@section('content')
    @php
        if ($job ?? false) {
            $employer = \App\Models\Account::find($job->account_id);
        }
    @endphp

    <style>
        .chat-panel {
            transition: transform 0.3s ease;
        }

        .chat-item.active {
            background-color: #d1e7ff !important;
            border-radius: 12px;
        }

        .chat-item {
            cursor: pointer;
            transition: background 0.2s;
        }

        .chat-item:hover {
            background-color: #e3f2fd;
        }

        #chatListDesktop,
        #chatListMobile {
            overflow-y: auto;
            height: 100%;
        }

        #chatArea {
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .message {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 0.95rem;
            line-height: 1.4;
            word-wrap: break-word;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: background-color 0.2s ease;
            cursor: pointer;
        }

        .message:hover {
            background-color: #e0e0e0;
        }

        .message.me {
            align-self: flex-end;
            background: #007bff;
            color: #fff;
            border-bottom-right-radius: 0;
        }

        .message.me:hover {
            background: #0056b3;
        }

        .message.other {
            align-self: flex-start;
            background: #f1f0f0;
            color: #000;
            border-bottom-left-radius: 0;
        }

        .message.other:hover {
            background: #e0e0e0;
        }

        .sender {
            font-weight: bold;
            font-size: 0.85rem;
            display: block;
            margin-bottom: 4px;
        }

        .message-status {
            font-size: 0.7rem;
            color: gray;
            text-align: right;
            margin-top: 3px;
        }

        .message-time-center {
            text-align: center;
            font-size: 0.75rem;
            color: gray;
            margin: 10px 0;
        }

        .message-time-bottom {
            font-size: 0.7rem;
            color: gray;
            text-align: right;
            margin-top: 5px;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInOut 5s forwards;
        }

        @keyframes fadeInOut {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }

            10% {
                opacity: 1;
                transform: translateY(0);
            }

            90% {
                opacity: 1;
                transform: translateY(0);
            }

            100% {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        .chat-input {
            display: flex;
            gap: 8px;
            padding: 15px 20px;
            border-top: 1px solid #ccc;
            background: #fff;
            position: sticky;
            bottom: 0;
            border-radius: 10px;
        }

        .chat-input textarea {
            flex: 1;
            padding: 15px 20px;
            border-radius: 25px;
            border: 1px solid #ccc;
            outline: none;
            font-size: 1rem;
            resize: none;
            min-height: 50px;
            max-height: 150px;
        }

        .chat-input button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: #007bff;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        .sending {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .welcome-message {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            color: #555;
            text-align: center;
        }

        .chat-toast {
            background: #007bff;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 10px;
            min-width: 250px;
            max-width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.4s, fadeOut 0.4s 4.6s forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(20px);
            }
        }

        .status-dot {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .status-online {
            background-color: #28a745;
        }

        .status-offline {
            background-color: #6c757d;
        }

        .message.highlight {
            border: 2px solid #007bff;
            background-color: #e6f0ff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }

        .reply-btn {
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.85rem;
            margin-top: 8px;
            transition: background-color 0.2s ease;
            display: block;
            width: fit-content;
        }

        .message.me .reply-btn {
            margin-left: 5px;
            margin-right: auto;
        }

        .message.other .reply-btn {
            margin-right: 5px;
            margin-left: auto;
        }

        .reply-btn:hover {
            background-color: #0056b3;
        }

        .reply-preview,
        .image-preview {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: none;
            font-size: 0.9rem;
            color: #555;
        }

        .reply-preview.show,
        .image-preview.show {
            display: block;
        }

        .reply-preview .close-btn,
        .image-preview .close-btn {
            float: right;
            cursor: pointer;
            color: #aaa;
            font-weight: bold;
        }

        .reply-quote {
            font-size: 0.85rem;
            color: #555;
            background: #f1f0f0;
            border-left: 3px solid #007bff;
            padding: 4px 8px;
            margin-bottom: 6px;
            border-radius: 6px;
        }

        .message.me .reply-quote {
            background: rgba(255, 255, 255, 0.2);
            color: #e0e0e0;
            border-left: 3px solid #fff;
        }

        .main-text {
            white-space: pre-line;
        }

        .message-img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 5px;
            object-fit: contain;
        }

        .upload-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: #6c757d;
            color: #fff;
            cursor: pointer;
        }

        .image-preview img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
        }
    </style>

    <div id="chatToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <main class="main d-flex flex-column" style="height:92vh; background-color:#f0f2f5; color:#000;">
        <div class="container-fluid p-0 d-flex flex-column flex-xl-row position-relative h-100">
            <!-- Desktop Sidebar -->
            <div id="chatListDesktop" class="chat-panel col-xl-3 bg-white p-3 shadow-sm d-none d-xl-block h-100">
                @foreach($conversations as $boxItem)
                    @php
                        $partnerId = $boxItem->sender_id == auth()->id() ? $boxItem->receiver_id : $boxItem->sender_id;
                        $partner = \App\Models\Account::find($partnerId);
                        $latestMsg = $boxItem->messages->first();
                        $avatar = $partner?->avatar_url ?: asset('assets/img/blog/blog-1.jpg');
                        $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
                    @endphp

                    @if($partner)
                        <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
                            data-box-id="{{ $boxItem->id }}" data-partner-id="{{ $partner->account_id }}"
                            onclick="openBoxChat('{{ $partner->name }}', this, {{ $partner->account_id }}, {{ $boxItem->id }})">
                            <div class="position-relative me-2" style="width:55px;height:55px;">
                                <img src="{{ $avatar }}" class="rounded-circle" style="width:55px;height:55px;object-fit:cover;">
                                <span class="status-dot status-offline" id="status-{{ $partner->account_id }}"></span>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $partner->name }}</div>
                                @if($latestMsg)
                                    <div class="text-muted" style="font-size:0.85rem;">
                                        {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $partner->name . ': ' }}
                                        {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                                        • {{ $latestMsg->created_at->diffForHumans() }}
                                    </div>
                                @else
                                    <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Chat Area -->
            <div id="chatArea" class="chat-panel col-12 col-xl-9 d-flex flex-column bg-white p-3 shadow-sm h-100">
                <div class="d-xl-none mb-2">
                    <button class="btn btn-secondary" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#chatOffcanvas">
                        <i class="bi bi-chat-dots me-2"></i> Danh sách Chat
                    </button>
                </div>

                <div class="chat-header d-flex align-items-center gap-2 mb-2" id="chatHeader">
                    @if($box && $employer)
                        <div class="position-relative" style="width:45px;height:45px;">
                            <img id="chatHeaderAvatar" src="{{ $employer->avatar_url ?? asset('assets/img/blog/blog-1.jpg') }}"
                                class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">
                            <span class="status-dot status-offline" id="chatHeaderStatus"></span>
                        </div>
                        <div>
                            <div class="fw-bold" id="chatHeaderName">{{ $employer->name }}</div>
                            <div class="small text-muted" id="chatHeaderStatusText">Ngoại tuyến</div>
                        </div>
                    @else
                        <div class="fw-bold">Chọn một cuộc trò chuyện</div>
                    @endif
                </div>

                <div id="chatMessages" class="chat-messages">
                    @if($box && $messages && $messages->count())
                        @foreach($messages as $msg)
                            <div class="message {{ $msg->sender_id == auth()->id() ? 'me' : 'other' }}"
                                data-created="{{ $msg->created_at->toISOString() }}" data-message-id="{{ $msg->id }}">
                                <span class="sender">{{ $msg->sender_id == auth()->id() ? 'Bạn' : $msg->sender->name }}</span>
                                @if($msg->content)
                                    <p>{{ $msg->content }}</p>
                                @endif
                                @if($msg->img)
                                    <img src="{{ asset($msg->img) }}" class="message-img" alt="Message Image"
                                        onerror="this.src='{{ asset('assets/img/blog/blog-1.jpg') }}'; this.onerror=null; console.error('Image load failed:', '{{ $msg->img }}');">
                                @endif
                                @if($msg->sender_id == auth()->id())
                                    <div class="message-status">Đã nhận</div>
                                @endif
                            </div>
                        @endforeach
                    @elseif($box && $employer)
                        <div class="message text-muted">
                            Chưa có tin nhắn với {{ $employer->name }}.
                        </div>
                    @else
                        <div class="welcome-message">
                            Chào mừng bạn đến với JobLink Chat!<br>
                            Chọn một cuộc trò chuyện bên trái để bắt đầu nhắn tin.
                        </div>
                    @endif
                </div>

                <!-- Reply Preview -->
                <div id="replyPreview" class="reply-preview">
                    <span class="close-btn" onclick="clearReplyPreview()">&times;</span>
                    <strong id="replySender"></strong>: <span id="replyContent"></span>
                </div>

                <!-- Image Preview -->
                <div id="imagePreview" class="image-preview">
                    <span class="close-btn" onclick="clearImagePreview()">&times;</span>
                    <img id="previewImg" src="" alt="Preview Image">
                </div>

                <div class="chat-input" id="chatInput" style="{{ $box ? 'display:flex;' : 'display:none;' }}">
                    <button id="uploadBtn" class="upload-btn" onclick="document.getElementById('fileInput').click();">
                        <i class="bi bi-image"></i>
                    </button>
                    <input type="file" id="fileInput" accept="image/*" style="display:none;">
                    <textarea id="messageInput" placeholder="Nhập tin nhắn..."></textarea>
                    <button id="sendBtn" onclick="sendMessage()">➤</button>
                </div>
            </div>
        </div>

        <!-- Mobile Offcanvas -->
        <div class="offcanvas offcanvas-start d-xl-none" tabindex="-1" id="chatOffcanvas"
            aria-labelledby="chatOffcanvasLabel" style="height: 92vh;">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="chatOffcanvasLabel">Danh sách Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-3" style="overflow-y: auto;">
                <div id="chatListMobile">
                    @foreach($conversations as $boxItem)
                        @php
                            $partnerId = $boxItem->sender_id == auth()->id() ? $boxItem->receiver_id : $boxItem->sender_id;
                            $partner = \App\Models\Account::find($partnerId);
                            $latestMsg = $boxItem->messages->first();
                            $avatar = $partner?->avatar_url ?: asset('assets/img/blog/blog-1.jpg');
                            $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
                        @endphp
                        @if($partner)
                            <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
                                data-box-id="{{ $boxItem->id }}" data-partner-id="{{ $partner->account_id }}"
                                onclick="openBoxChat('{{ $partner->name }}', this, {{ $partner->account_id }}, {{ $boxItem->id }})">
                                <img src="{{ $avatar }}" alt="avatar" class="rounded-circle me-2"
                                    style="width:55px;height:55px;object-fit:cover;">
                                <div>
                                    <div class="fw-bold">{{ $partner->name }}</div>
                                    @if($latestMsg)
                                        <div class="text-muted" style="font-size:0.85rem;">
                                            {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $partner->name . ': ' }}
                                            {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                                            • {{ $latestMsg->created_at->diffForHumans() }}
                                        </div>
                                    @else
                                        <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </main>

    <audio id="chatNotifySound" src="{{ asset('assets/sounds/notify.mp3') }}" preload="auto"></audio>
    @vite('resources/js/app.js')

    <script>
        let currentChat = '';
        let currentItem = null;
        let currentPartnerId = {{ $box ? ($box->sender_id == auth()->id() ? $box->receiver_id : $box->sender_id) : 'null' }};
        let currentJobId = {{ $job ? $job->job_id : 'null' }};
        let currentBoxId = {{ $box ? $box->id : 'null' }};
        let currentChannel = null;
        let lastMessageTime = null;
        let offcanvas = null;
        const authId = {{ auth()->id() }};
        let replyingTo = null;
        let selectedImage = null;

        function scrollToBottom() {
            const chatBox = document.getElementById('chatMessages');
            chatBox.scrollTo({
                top: chatBox.scrollHeight,
                behavior: 'smooth'
            });
        }

        function appendMessage(msg, isMe = false, status = 'Đang gửi') {
            const chatBox = document.getElementById('chatMessages');

            if (document.querySelector(`[data-message-id="${msg.id}"]`)) {
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

            const msgDiv = document.createElement('div');
            msgDiv.className = 'message ' + (isMe ? 'me' : 'other');
            msgDiv.dataset.created = msg.created_at;
            msgDiv.dataset.shownTime = 'false';
            msgDiv.dataset.messageId = msg.id;

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

            msgDiv.innerHTML = `
                    <span class="sender">${isMe ? 'Bạn' : msg.sender.name}</span>
                    ${contentHTML}
                    ${statusHTML}
                `;

            msgDiv.addEventListener('click', (e) => {
                if (msgDiv.dataset.shownTime === 'true' || e.longPress) return;
                msgDiv.dataset.shownTime = 'true';
                const timeLabel = document.createElement('div');
                timeLabel.className = 'message-time-bottom';
                timeLabel.innerText = msgTime.toLocaleString('vi-VN');
                msgDiv.appendChild(timeLabel);
                setTimeout(() => {
                    if (timeLabel.parentNode) timeLabel.remove();
                    msgDiv.dataset.shownTime = 'false';
                }, 5000);
            });

            let pressTimer;
            const startLongPress = (e) => {
                pressTimer = setTimeout(() => {
                    e.longPress = true;
                    highlightMessage(msgDiv, msg);
                }, 500);
            };

            const cancelLongPress = () => {
                clearTimeout(pressTimer);
            };

            msgDiv.addEventListener('mousedown', startLongPress);
            msgDiv.addEventListener('mouseup', cancelLongPress);
            msgDiv.addEventListener('mouseleave', cancelLongPress);
            msgDiv.addEventListener('touchstart', startLongPress);
            msgDiv.addEventListener('touchend', cancelLongPress);
            msgDiv.addEventListener('touchmove', cancelLongPress);

            chatBox.appendChild(msgDiv);
            scrollToBottom();
            return msgDiv;
        }

        function highlightMessage(msgDiv, msg) {
            document.querySelectorAll('.message.highlight').forEach(el => {
                el.classList.remove('highlight');
                const replyBtn = el.querySelector('.reply-btn');
                if (replyBtn) replyBtn.remove();
            });

            msgDiv.classList.add('highlight');

            const replyBtn = document.createElement('button');
            replyBtn.className = 'reply-btn';
            replyBtn.innerHTML = "<i class='bi bi-reply'></i> Trả lời";
            replyBtn.addEventListener('click', () => {
                replyingTo = msg;
                showReplyPreview(msg.sender.name, msg.content || '[Hình ảnh]');
                msgDiv.classList.remove('highlight');
                replyBtn.remove();
                document.getElementById('messageInput').focus();
            });

            msgDiv.appendChild(replyBtn);
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

        function subscribeToChannel(partnerId) {
            if (currentChannel) {
                window.Echo.leave(currentChannel);
            }

            const userIds = [authId, partnerId].sort((a, b) => a - b);
            currentChannel = 'chat.' + userIds.join('.');

            window.Echo.private(currentChannel).listen('MessageSent', (e) => {
                console.log('Received MessageSent event:', e);
                const incomingMsg = e.message;
                const isMe = incomingMsg.sender_id === authId;

                if (!isMe && currentPartnerId === incomingMsg.sender_id) {
                    const msgDiv = appendMessage(incomingMsg, false);

                    if (msgDiv) {
                        const sound = document.getElementById('chatNotifySound');
                        if (sound) sound.play();

                        showChatToast(
                            incomingMsg.sender.name,
                            incomingMsg.content || '[Hình ảnh]',
                            incomingMsg.sender.avatar_url ?? "{{ asset('assets/img/blog/blog-1.jpg') }}"
                        );

                        if (Notification.permission === "granted") {
                            new Notification("Tin nhắn mới từ " + incomingMsg.sender.name, {
                                body: incomingMsg.content || '[Hình ảnh]',
                                icon: incomingMsg.sender.avatar_url ?? "{{ asset('assets/img/blog/blog-1.jpg') }}"
                            });
                        } else if (Notification.permission !== "denied") {
                            Notification.requestPermission().then(permission => {
                                if (permission === "granted") {
                                    new Notification("Tin nhắn mới từ " + incomingMsg.sender.name, {
                                        body: incomingMsg.content || '[Hình ảnh]',
                                        icon: incomingMsg.sender.avatar_url ?? "{{ asset('assets/img/blog/blog-1.jpg') }}"
                                    });
                                }
                            });
                        }
                    }
                }
            });

            console.log('Subscribed to channel:', currentChannel);
        }

        function openBoxChat(name, element, partnerId, boxId) {
            currentChat = name;
            currentPartnerId = partnerId;
            currentBoxId = boxId;
            currentJobId = null;
            window.currentPartnerId = partnerId;

            if (currentItem) currentItem.classList.remove('active');
            if (element) {
                currentItem = element;
                currentItem.classList.add('active');
            }

            const header = document.getElementById('chatHeader');
            const chatInput = document.getElementById('chatInput');
            const avatarImg = element.querySelector("img");
            const avatarUrl = avatarImg ? avatarImg.src : "{{ asset('assets/img/blog/blog-1.jpg') }}";
            const dot = element.querySelector(".status-dot");
            const isOnline = dot && dot.classList.contains("status-online");

            header.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <div class="position-relative" style="width:45px;height:45px;">
                            <img id="chatHeaderAvatar" src="${avatarUrl}" 
                                 class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">
                            <span class="status-dot ${isOnline ? 'status-online' : 'status-offline'}" id="chatHeaderStatus"></span>
                        </div>
                        <div>
                            <div class="fw-bold" id="chatHeaderName">${name}</div>
                            <div class="small text-muted" id="chatHeaderStatusText">${isOnline ? 'Đang hoạt động' : 'Ngoại tuyến'}</div>
                        </div>
                    </div>
                `;

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
                            msg.sender_id === authId,
                            msg.sender_id === authId ? 'Đã nhận' : ''
                        ));
                    }
                    scrollToBottom();
                })
                .catch(err => {
                    console.error(err);
                    chatBox.innerHTML = `<div class="message text-muted">Lỗi khi tải tin nhắn.</div>`;
                });

            if (window.Echo) {
                subscribeToChannel(partnerId);
            }

            if (offcanvas && window.innerWidth < 1200) {
                offcanvas.hide();
            }
        }

        function sendMessage() {
            if (!currentChat || !currentPartnerId) {
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
                sender: { id: authId, name: 'Bạn' },
                created_at: new Date().toISOString()
            };
            const msgDiv = appendMessage(tempMsg, true, 'Đang gửi');

            sendBtn.classList.add('sending');
            input.value = '';

            const formData = new FormData();
            if (text) formData.append('content', text);
            formData.append('receiver_id', currentPartnerId);
            if (currentJobId) formData.append('job_id', currentJobId);
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

        function showChatToast(sender, content, avatarUrl, timeText = "Vừa xong", onClick = null) {
            const container = document.getElementById('chatToastContainer');
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
                            <div class="text-truncate">${content}</div>
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

        window.addEventListener('DOMContentLoaded', () => {
            const offcanvasElement = document.getElementById('chatOffcanvas');
            if (offcanvasElement) {
                offcanvas = new bootstrap.Offcanvas(offcanvasElement);
            }

            @if($box && $employer)
                const boxElement = document.querySelector(`[data-box-id="${currentBoxId}"]`);
                if (boxElement) {
                    openBoxChat('{{ $employer->name }}', boxElement, {{ $box->sender_id == auth()->id() ? $box->receiver_id : $box->sender_id }}, {{ $box->id }});
                }
            @endif

                if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                Notification.requestPermission().then(permission => {
                    console.log("Notification permission:", permission);
                });
            }

            const fileInput = document.getElementById('fileInput');
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

            const messageInput = document.getElementById('messageInput');
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

            const chatArea = document.getElementById('chatArea');
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
        });
    </script>
@endsection