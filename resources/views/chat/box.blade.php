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

    <main class="main d-flex flex-column" style="height:92vh; background-color:#f0f2f5; color:#000;">
        <div class="container-fluid p-0 d-flex flex-column flex-xl-row position-relative h-100">
            <!-- Desktop Sidebar -->
            <div id="chatListDesktop" class="chat-panel col-xl-3 bg-white p-3 shadow-sm d-none d-xl-block h-100">
                @foreach($conversations as $boxItem)
                    @include('chat.partials.conversation-item', ['boxItem' => $boxItem, 'box' => $box])
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
                            @include('chat.partials.message-item', [
                                'msg' => $msg,
                                'isMe' => $msg->sender_id == auth()->id(),
                                'hideAvatar' => !$msg->sender_id == auth()->id() && $previousSenderId === $msg->sender_id,
                                'previousSenderId' => $previousSenderId
                            ])
                            @php
                                $previousSenderId = $msg->sender_id;
                            @endphp
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

                @include('chat.partials.reply-preview')
                @include('chat.partials.image-preview')

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
                        @include('chat.partials.conversation-item', ['boxItem' => $boxItem, 'box' => $box])
                    @endforeach
                </div>
            </div>
        </div>
    </main>

    @include('chat.scripts.chat-js')
@endsection