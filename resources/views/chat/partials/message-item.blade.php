{{-- resources/views/chat/partials/message-item.blade.php --}}
@props(['msg', 'isMe', 'hideAvatar', 'previousSenderId'])

@php
    $isMe = $msg->sender_id == auth()->id();
    $hideAvatar = !$isMe && $previousSenderId === $msg->sender_id;
@endphp

<div class="message-container {{ $isMe ? 'me' : 'other' }} {{ $hideAvatar ? 'hide-avatar' : '' }}"
     data-created="{{ $msg->created_at->toISOString() }}" data-message-id="{{ $msg->id }}">
    @if(!$isMe)
        <div class="avatar-container">
            <img src="{{ $msg->sender->avatar_url ?? asset('assets/img/defaultavatar.jpg') }}"
                 alt="Sender Avatar">
        </div>
    @else
        <div class="avatar-container"></div>
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