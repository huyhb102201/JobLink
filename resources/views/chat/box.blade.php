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
        }

        .message.me {
            align-self: flex-end;
            background: #007bff;
            color: #fff;
            border-bottom-right-radius: 0;
        }

        .message.other {
            align-self: flex-start;
            background: #f1f0f0;
            color: #000;
            border-bottom-left-radius: 0;
        }

        .sender {
            font-weight: bold;
            font-size: 0.85rem;
            display: block;
            margin-bottom: 4px;
        }

        /* trạng thái tin nhắn */
        .message-status {
            font-size: 0.7rem;
            color: gray;
            text-align: right;
            margin-top: 3px;
        }

        /* tin nhắn giữa */
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
            animation: moveUp 3s forwards;
        }

        @keyframes moveUp {
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
                transform: translateY(-10px);
            }

            100% {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        /* chat input */
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

        .chat-input input {
            flex: 1;
            padding: 15px 20px;
            border-radius: 25px;
            border: 1px solid #ccc;
            outline: none;
            font-size: 1rem;
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

        /* hiệu ứng xoay nút gửi */
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
    </style>

    <main class="main d-flex flex-column" style="height:92vh; background-color:#f0f2f5; color:#000;">
        <div class="container-fluid p-0 d-flex flex-column flex-xl-row position-relative h-100">
            <!-- Desktop Sidebar (luôn hiển thị từ xl trở lên) -->
            <div id="chatListDesktop" class="chat-panel col-xl-3 bg-white p-3 shadow-sm d-none d-xl-block h-100">
                @foreach($conversations as $partnerId => $msgs)
                    @php
                        $partner = \App\Models\Account::find($partnerId);
                        $latestMsg = $msgs->first();
                        $avatar = $partner?->avatar_url ?: asset('assets/img/blog/blog-1.jpg');
                    @endphp
                    @if($partner)
                        <div class="chat-item d-flex align-items-center mb-2 p-2 rounded"
                            data-partner-id="{{ $partner->account_id }}" data-job-id="{{ $latestMsg->job_id ?? 'null' }}"
                            onclick="openChat('{{ $partner->name }}', this, {{ $partner->account_id }}, {{ $latestMsg->job_id ?? 'null' }})">
                            <img src="{{ $avatar }}" alt="avatar" class="rounded-circle me-2"
                                style="width:55px;height:55px;object-fit:cover;">
                            <div>
                                <div class="fw-bold">{{ $partner->name }}</div>
                                @if($latestMsg)
                                    <div class="text-muted" style="font-size:0.85rem;">
                                        {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $partner->name . ': ' }}
                                        {{ Str::limit($latestMsg->content, 25) }} • {{ $latestMsg->created_at->diffForHumans() }}
                                    </div>
                                @else
                                    <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
                @if(!count($conversations) && $job)
                    <div class="chat-item d-flex align-items-center mb-2 p-2 rounded"
                        data-partner-id="{{ $employer->account_id }}" data-job-id="{{ $job->job_id }}"
                        onclick="openChat('{{ $employer->name }}', this, {{ $employer->account_id }}, {{ $job->job_id }})">
                        <img src="{{ $employer->avatar_url ?: asset('assets/img/blog/blog-1.jpg') }}" alt="avatar"
                            class="rounded-circle me-2" style="width:55px;height:55px;object-fit:cover;">
                        <div>
                            <div class="fw-bold">{{ $employer->name }}</div>
                            <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Chat area (full trên mobile/desktop nhỏ, bên phải trên xl) -->
            <div id="chatArea" class="chat-panel col-12 col-xl-9 d-flex flex-column bg-white p-3 shadow-sm h-100">
                <!-- Nút mở Offcanvas trên mobile (d-xl-none) -->
                <div class="d-xl-none mb-2">
                    <button class="btn btn-secondary" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#chatOffcanvas">
                        <i class="bi bi-chat-dots me-2"></i> Danh sách Chat
                    </button>
                </div>

                <div class="chat-header fw-bold mb-2" id="chatHeader">
                    @if($job)
                        Chat với {{ $employer->name }}
                    @else
                        Chọn một cuộc trò chuyện
                    @endif
                </div>
                <div id="chatMessages" class="chat-messages">
                    @if($job && $messages && $messages->count())
                        @foreach($messages as $msg)
                            <div class="message {{ $msg->sender_id == auth()->id() ? 'me' : 'other' }}"
                                data-created="{{ $msg->created_at }}">
                                <span class="sender">{{ $msg->sender_id == auth()->id() ? 'Bạn' : $msg->sender->name }}</span>
                                <p>{{ $msg->content }}</p>
                                @if($msg->sender_id == auth()->id())
                                    <div class="message-status">Đã nhận</div>
                                @endif
                            </div>
                        @endforeach
                    @elseif(!$job)
                        <div class="welcome-message">
                            Chào mừng bạn đến với JobLink Chat!<br>
                            Chọn một cuộc trò chuyện bên trái để bắt đầu nhắn tin.
                        </div>
                    @else
                        <div class="message text-muted">
                            Chưa có tin nhắn. Bắt đầu chat với {{ $employer->name }}.
                        </div>
                    @endif
                </div>
                <div class="chat-input" id="chatInput" style="{{ $job ? 'display:flex;' : 'display:none;' }}">
                    <input type="text" id="messageInput" placeholder="Nhập tin nhắn...">
                    <button id="sendBtn" onclick="sendMessage()">➤</button>
                </div>
            </div>
        </div>

        <!-- Mobile Offcanvas (chỉ hiển thị trên mobile, d-xl-none) -->
        <div class="offcanvas offcanvas-start d-xl-none" tabindex="-1" id="chatOffcanvas"
            aria-labelledby="chatOffcanvasLabel" style="height: 92vh;">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="chatOffcanvasLabel">Danh sách Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-3" style="overflow-y: auto;">
                <div id="chatListMobile">
                    @foreach($conversations as $partnerId => $msgs)
                        @php
                            $partner = \App\Models\Account::find($partnerId);
                            $latestMsg = $msgs->first();
                            $avatar = $partner?->avatar_url ?: asset('assets/img/blog/blog-1.jpg');
                        @endphp
                        @if($partner)
                            <div class="chat-item d-flex align-items-center mb-2 p-2 rounded"
                                data-partner-id="{{ $partner->account_id }}" data-job-id="{{ $latestMsg->job_id ?? 'null' }}"
                                onclick="openChat('{{ $partner->name }}', this, {{ $partner->account_id }}, {{ $latestMsg->job_id ?? 'null' }})">
                                <img src="{{ $avatar }}" alt="avatar" class="rounded-circle me-2"
                                    style="width:55px;height:55px;object-fit:cover;">
                                <div>
                                    <div class="fw-bold">{{ $partner->name }}</div>
                                    @if($latestMsg)
                                        <div class="text-muted" style="font-size:0.85rem;">
                                            {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $partner->name . ': ' }}
                                            {{ Str::limit($latestMsg->content, 25) }} • {{ $latestMsg->created_at->diffForHumans() }}
                                        </div>
                                    @else
                                        <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @if(!count($conversations) && $job)
                        <div class="chat-item d-flex align-items-center mb-2 p-2 rounded"
                            data-partner-id="{{ $employer->account_id }}" data-job-id="{{ $job->job_id }}"
                            onclick="openChat('{{ $employer->name }}', this, {{ $employer->account_id }}, {{ $job->job_id }})">
                            <img src="{{ $employer->avatar_url ?: asset('assets/img/blog/blog-1.jpg') }}" alt="avatar"
                                class="rounded-circle me-2" style="width:55px;height:55px;object-fit:cover;">
                            <div>
                                <div class="fw-bold">{{ $employer->name }}</div>
                                <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    @vite('resources/js/app.js')

    <script>
        let currentChat = '';
        let currentItem = null;
        let currentPartnerId = {{ $job ? $employer->account_id : 'null' }};
        let currentJobId = {{ $job ? $job->job_id : 'null' }};
        let currentChannel = null;
        let lastMessageTime = null;
        let offcanvas = null; // Bootstrap Offcanvas instance

        function scrollToBottom() {
            const chatBox = document.getElementById('chatMessages');
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Append message với trạng thái gửi
        function appendMessage(msg, isMe = false, status = 'Đang gửi') {
            const chatBox = document.getElementById('chatMessages');
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

            let statusHTML = isMe ? `<div class="message-status">${status}</div>` : '';
            msgDiv.innerHTML = `<span class="sender">${isMe ? 'Bạn' : msg.sender.name}</span>
                            <p>${msg.content}</p>
                            ${statusHTML}`;

            msgDiv.addEventListener('click', () => {
                if (msgDiv.dataset.shownTime === 'true') return;
                msgDiv.dataset.shownTime = 'true';
                const timeLabel = document.createElement('div');
                timeLabel.className = 'message-time-bottom';
                timeLabel.innerText = msgTime.toLocaleString('vi-VN');
                msgDiv.appendChild(timeLabel);
                setTimeout(() => msgDiv.removeChild(timeLabel), 3000);
            });

            chatBox.appendChild(msgDiv);
            scrollToBottom();
            return msgDiv;
        }

        function openChat(name, element, partnerId, jobId) {
            currentChat = name;
            currentPartnerId = partnerId;
            currentJobId = jobId;

            // Highlight item nếu có element (desktop hoặc mobile nếu visible)
            if (currentItem) currentItem.classList.remove('active');
            if (element) {
                currentItem = element;
                currentItem.classList.add('active');
            }

            document.getElementById('chatHeader').innerText = name;
            document.getElementById('chatInput').style.display = 'flex';

            const chatBox = document.getElementById('chatMessages');
            chatBox.innerHTML = `<div class="message text-muted">Đang tải tin nhắn...</div>`;
            lastMessageTime = null;

            let url = currentJobId ? `/chat/messages/${partnerId}/${currentJobId}` : `/chat/messages/${partnerId}`;
            fetch(url).then(res => res.json()).then(data => {
                chatBox.innerHTML = '';
                if (data.length === 0) {
                    chatBox.innerHTML = `<div class="message text-muted">Chưa có tin nhắn với ${name}</div>`;
                } else data.forEach(msg => appendMessage(msg, msg.sender_id ==={{ auth()->id() }}, msg.sender_id ==={{ auth()->id() }} ? 'Đã nhận' : ''));
                scrollToBottom();
            });

            if (window.Echo) {
                if (currentChannel) window.Echo.leave(currentChannel);
                const userIds = [{{ auth()->id() }}, partnerId].sort((a, b) => a - b);
                const channelName = `job.${jobId ?? 'null'}.${userIds.join('.')}`;
                currentChannel = `private-${channelName}`;
                window.Echo.private(channelName).listen('MessageSent', e => {
                    const isMe = e.message.sender.id === {{ auth()->id() }};
                    const msgDiv = appendMessage(e.message, isMe);
                    if (isMe) {
                        const statusDiv = msgDiv.querySelector('.message-status');
                        if (statusDiv) statusDiv.innerText = 'Đã nhận';
                    }
                });
            }

            // Trên mobile: ẩn offcanvas sau khi chọn chat
            if (offcanvas && window.innerWidth < 1200) {
                offcanvas.hide();
            }
        }

        // Gửi tin nhắn
        function sendMessage() {
            if (!currentChat) return alert('Chọn một cuộc trò chuyện trước!');
            const input = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            const text = input.value.trim();
            if (!text) return;

            // thêm tin nhắn tạm với trạng thái "Đang gửi"
            const msgDiv = appendMessage({ content: text, sender: { id:{{ auth()->id() }}, name: 'Bạn' }, created_at: new Date().toISOString() }, true, 'Đang gửi');

            sendBtn.classList.add('sending');
            input.value = '';

            axios.post('{{ route("messages.send") }}', {
                job_id: currentJobId,
                content: text,
                receiver_id: currentPartnerId
            }).then(res => {
                const statusDiv = msgDiv.querySelector('.message-status');
                if (statusDiv) statusDiv.innerText = 'Đã gửi';
                sendBtn.classList.remove('sending');
            }).catch(err => {
                const statusDiv = msgDiv.querySelector('.message-status');
                if (statusDiv) statusDiv.innerText = 'Gửi thất bại';
                sendBtn.classList.remove('sending');
                console.error(err);
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
            // Khởi tạo Offcanvas
            const offcanvasElement = document.getElementById('chatOffcanvas');
            if (offcanvasElement) {
                offcanvas = new bootstrap.Offcanvas(offcanvasElement);
            }

            @if($job)
                // Auto open chat nếu có job (không cần element vì không highlight trên mobile)
                openChat('{{ $employer->name }}', null, {{ $employer->account_id }}, {{ $job->job_id }});
            @endif
    });

        // Enter / Ctrl+Enter
        const messageInput = document.getElementById('messageInput');
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
    </script>
@endsection