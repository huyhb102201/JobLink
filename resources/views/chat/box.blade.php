@extends('layouts.app')

@section('title', 'JobLink - Chat Realtime')

@section('content')
    @php
        if ($job ?? false) {
            $employer = \App\Models\Account::find($job->account_id);
        } elseif ($org ?? false) {
            // Org chat, name from org
        }
    @endphp

    <link href="{{ asset('assets/css/chat.css') }}" rel="stylesheet">

    <div id="chatToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <main class="main d-flex flex-column" style="height:92vh; background-color:#f0f2f5; color:#000;">
        <div class="container-fluid p-0 d-flex flex-column flex-xl-row position-relative h-100">
            <!-- Desktop Sidebar -->
            <div id="chatListDesktop" class="chat-panel col-xl-3 bg-white p-3 shadow-sm d-none d-xl-block h-100">
                @foreach($conversations as $boxItem)
                    @if($boxItem->type == 1)
                        @php
                            $partnerId = $boxItem->sender_id == auth()->id() ? $boxItem->receiver_id : $boxItem->sender_id;
                            $partner = \App\Models\Account::find($partnerId);
                            $latestMsg = $boxItem->messages->first();
                            $avatar = $partner?->avatar_url ?: asset('assets/img/defaultavatar.jpg');
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
                                    <div class="fw-bold">{{ $partner->name }} <span class="chat-icon icon-1-1"><i class="bi bi-person"></i></span></div>
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
                    @elseif($boxItem->type == 2)
                        @php
                            $latestMsg = $boxItem->messages->first();
                            $avatar = asset('assets/img/group-icon.png'); // Icon nhóm job
                            $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
                        @endphp
                        <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
                            data-box-id="{{ $boxItem->id }}" data-job-id="{{ $boxItem->job_id }}"
                            onclick="openBoxChat('{{ $boxItem->name }}', this, null, {{ $boxItem->id }}, {{ $boxItem->job_id }})">
                            <div class="position-relative me-2" style="width:55px;height:55px;">
                                <img src="{{ $avatar }}" class="rounded-circle" style="width:55px;height:55px;object-fit:cover;">
                            </div>
                            <div>
                                <div class="fw-bold">{{ $boxItem->name }} <span class="chat-icon icon-group"><i class="bi bi-people"></i></span></div>
                                @if($latestMsg)
                                    <div class="text-muted" style="font-size:0.85rem;">
                                        {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $latestMsg->sender->name . ': ' }}
                                        {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                                        • {{ $latestMsg->created_at->diffForHumans() }}
                                    </div>
                                @else
                                    <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Type 3: Org group --}}
                        @php
                            $latestMsg = $boxItem->messages->first();
                            $avatar = asset('assets/img/org-icon.png'); // Icon tổ chức
                            $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
                        @endphp
                        <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
                            data-box-id="{{ $boxItem->id }}" data-org-id="{{ $boxItem->org_id }}"
                            onclick="openBoxChat('{{ $boxItem->name }}', this, null, {{ $boxItem->id }}, null, {{ $boxItem->org_id }})">
                            <div class="position-relative me-2" style="width:55px;height:55px;">
                                <img src="{{ $avatar }}" class="rounded-circle" style="width:55px;height:55px;object-fit:cover;">
                            </div>
                            <div>
                                <div class="fw-bold">{{ $boxItem->name }} <span class="chat-icon icon-org"><i class="bi bi-building"></i></span></div>
                                @if($latestMsg)
                                    <div class="text-muted" style="font-size:0.85rem;">
                                        {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $latestMsg->sender->name . ': ' }}
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
                    @if($box && $employer && $receiverId)
                        <div class="position-relative" style="width:45px;height:45px;">
                            <img id="chatHeaderAvatar"
                                src="{{ $employer->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                                class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">
                            <span class="status-dot status-offline" id="chatHeaderStatus"></span>
                        </div>
                        <div>
                            <div class="fw-bold" id="chatHeaderName">{{ $employer->name }} <span class="chat-icon icon-1-1"><i class="bi bi-person"></i></span></div>
                            <div class="small text-muted" id="chatHeaderStatusText">Ngoại tuyến</div>
                        </div>
                    @elseif($box && !$receiverId && $box->job_id)
                        <div class="fw-bold" id="chatHeaderName">{{ $box->name }} <span class="chat-icon icon-group"><i class="bi bi-people"></i></span></div>
                        <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm</div>
                    @elseif($box && !$receiverId && $box->org_id)
                        <div class="fw-bold" id="chatHeaderName">{{ $box->name }} <span class="chat-icon icon-org"><i class="bi bi-building"></i></span></div>
                        <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm tổ chức</div>
                    @else
                        <div class="fw-bold">Chọn một cuộc trò chuyện</div>
                    @endif
                </div>

                <div id="chatMessages" class="chat-messages">
                    @if($box && $messages && $messages->count())
                        @php
                            $previousSenderId = null;
                        @endphp
                        @foreach($messages as $msg)
                            @php
                                $isMe = $msg->sender_id == auth()->id();
                                $hideAvatar = !$isMe && $previousSenderId === $msg->sender_id;
                                $previousSenderId = $msg->sender_id;
                            @endphp
                            <div class="message-container {{ $isMe ? 'me' : 'other' }} {{ $hideAvatar ? 'hide-avatar' : '' }}"
                                data-created="{{ $msg->created_at->toISOString() }}" data-message-id="{{ $msg->id }}">
                                @if(!$isMe)
                                    <div class="avatar-container">
                                        <img src="{{ $msg->sender->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                                            alt="Sender Avatar">
                                    </div>
                                @else
                                    <div class="avatar-container"></div> <!-- Placeholder để giữ cấu trúc -->
                                @endif
                                <div class="message {{ $isMe ? 'me' : 'other' }}">
                                    <span class="sender">{{ $isMe ? 'Bạn' : $msg->sender->name }}</span>
                                    @if($msg->content)
                                        <p>{{ $msg->content }}</p>
                                    @endif
                                    @if($msg->img)
                                        <img src="{{ asset($msg->img) }}" class="message-img" alt="Message Image"
                                            onerror="this.src='{{ asset('assets/img/blog/blog-1.jpg') }}'; this.onerror=null; console.error('Image load failed:', '{{ $msg->img }}');">
                                    @endif
                                    @if($isMe)
                                        <div class="message-status">Đã nhận</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @elseif($box && $employer && $receiverId)
                        <div class="message text-muted">
                            Chưa có tin nhắn với {{ $employer->name }}.
                        </div>
                    @elseif($box && !$receiverId)
                        <div class="message text-muted">
                            Chưa có tin nhắn trong {{ isset($org) ? 'tổ chức' : 'nhóm' }}.
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
                        @if($boxItem->type == 1)
                            @php
                                $partnerId = $boxItem->sender_id == auth()->id() ? $boxItem->receiver_id : $boxItem->sender_id;
                                $partner = \App\Models\Account::find($partnerId);
                                $latestMsg = $boxItem->messages->first();
                                $avatar = $partner?->avatar_url ?: asset('assets/img/defaultavatar.jpg');
                                $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
                            @endphp
                            @if($partner)
                                <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
                                    data-box-id="{{ $boxItem->id }}" data-partner-id="{{ $partner->account_id }}"
                                    onclick="openBoxChat('{{ $partner->name }}', this, {{ $partner->account_id }}, {{ $boxItem->id }})">
                                    <img src="{{ $avatar }}" alt="avatar" class="rounded-circle me-2"
                                        style="width:55px;height:55px;object-fit:cover;">
                                    <div>
                                        <div class="fw-bold">{{ $partner->name }} <span class="chat-icon icon-1-1"><i class="bi bi-person"></i></span></div>
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
                        @elseif($boxItem->type == 2)
                            @php
                                $latestMsg = $boxItem->messages->first();
                                $avatar = asset('assets/img/group-icon.png');
                                $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
                            @endphp
                            <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
                                data-box-id="{{ $boxItem->id }}" data-job-id="{{ $boxItem->job_id }}"
                                onclick="openBoxChat('{{ $boxItem->name }}', this, null, {{ $boxItem->id }}, {{ $boxItem->job_id }})">
                                <img src="{{ $avatar }}" alt="group" class="rounded-circle me-2"
                                    style="width:55px;height:55px;object-fit:cover;">
                                <div>
                                    <div class="fw-bold">{{ $boxItem->name }} <span class="chat-icon icon-group"><i class="bi bi-people"></i></span></div>
                                    @if($latestMsg)
                                        <div class="text-muted" style="font-size:0.85rem;">
                                            {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $latestMsg->sender->name . ': ' }}
                                            {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                                            • {{ $latestMsg->created_at->diffForHumans() }}
                                        </div>
                                    @else
                                        <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                                    @endif
                                </div>
                            </div>
                        @else
                            {{-- Type 3: Org group --}}
                            @php
                                $latestMsg = $boxItem->messages->first();
                                $avatar = asset('assets/img/org-icon.png');
                                $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
                            @endphp
                            <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
                                data-box-id="{{ $boxItem->id }}" data-org-id="{{ $boxItem->org_id }}"
                                onclick="openBoxChat('{{ $boxItem->name }}', this, null, {{ $boxItem->id }}, null, {{ $boxItem->org_id }})">
                                <img src="{{ $avatar }}" alt="org" class="rounded-circle me-2"
                                    style="width:55px;height:55px;object-fit:cover;">
                                <div>
                                    <div class="fw-bold">{{ $boxItem->name }} <span class="chat-icon icon-org"><i class="bi bi-building"></i></span></div>
                                    @if($latestMsg)
                                        <div class="text-muted" style="font-size:0.85rem;">
                                            {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $latestMsg->sender->name . ': ' }}
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
        let currentPartnerId = {{ $box && $receiverId ? $receiverId : 'null' }};
        let currentJobId = {{ ($box && !$receiverId && $box->job_id) ? $box->job_id : 'null' }};
        let currentOrgId = {{ ($box && !$receiverId && $box->org_id) ? $box->org_id : 'null' }};
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

        let lastSenderId = null; // Biến toàn cục để theo dõi người gửi trước đó

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

            const hideAvatar = !isMe && lastSenderId === msg.sender_id;
            lastSenderId = msg.sender_id; // Cập nhật người gửi trước đó

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
                avatarHTML = `<div class="avatar-container"></div>`; // Placeholder cho tin nhắn của người dùng hiện tại
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
                // Chat 1-1
                const userIds = [authId, partnerId].sort((a, b) => a - b);
                currentChannel = 'chat.' + userIds.join('.');
                window.Echo.private(currentChannel).listen('MessageSent', handleMessageEvent);
            } else if (jobId) {
                // Chat nhóm job
                currentChannel = 'chat-group.' + jobId;
                window.Echo.join(currentChannel).listen('MessageSent', handleMessageEvent);
            } else if (orgId) {
                // Chat nhóm org
                currentChannel = 'chat-org.' + orgId;
                window.Echo.join(currentChannel).listen('MessageSent', handleMessageEvent);
            }

            console.log('Subscribed to channel:', currentChannel);
        }

        function handleMessageEvent(e) {
            console.log('Received MessageSent event:', e);
            const incomingMsg = e.message;
            const isMe = incomingMsg.sender_id === authId;

            if (!isMe && (currentPartnerId === incomingMsg.sender_id || currentJobId === incomingMsg.job_id || currentOrgId === incomingMsg.org_id)) {
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
                // 1-1
                const avatarImg = element.querySelector("img");
                const avatarUrl = avatarImg ? avatarImg.src : "{{ asset('assets/img/defaultavatar.jpg') }}";
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
                            <div class="fw-bold" id="chatHeaderName">${name} <span class="chat-icon icon-1-1"><i class="bi bi-person"></i></span></div>
                            <div class="small text-muted" id="chatHeaderStatusText">${isOnline ? 'Đang hoạt động' : 'Ngoại tuyến'}</div>
                        </div>
                    </div>
                `;
            } else if (jobId) {
                // Nhóm công việc
                header.innerHTML = `
                    <div class="fw-bold" id="chatHeaderName">${name} <span class="chat-icon icon-group"><i class="bi bi-people"></i></span></div>
                    <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm</div>
                `;
            } else if (orgId) {
                // Nhóm tổ chức
                header.innerHTML = `
                    <div class="fw-bold" id="chatHeaderName">${name} <span class="chat-icon icon-org"><i class="bi bi-building"></i></span></div>
                    <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm tổ chức</div>
                `;
            } else {
                // Không có cuộc trò chuyện
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
                sender: { id: authId, name: 'Bạn' },
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