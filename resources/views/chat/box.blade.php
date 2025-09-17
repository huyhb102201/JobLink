@extends('layouts.app')

@section('content')
<div class="chat-container">
    <div id="chat-box" class="chat-box">
        @foreach($messages as $message)
            <div class="message @if($message->sender_id == auth()->id()) me @else other @endif">
                <div class="message-meta">
                    <small>ID: {{ $message->id }} | Job: {{ $message->job_id }} | Conv: {{ $message->conversation_id }}</small>
                </div>
                <span class="sender">{{ $message->sender_id == auth()->id() ? 'You' : $message->sender->name }}</span>
                <p class="content">{{ $message->content }}</p>
            </div>
        @endforeach
    </div>

    <form id="chat-form" class="chat-form">
        @csrf
        <input type="hidden" name="job_id" value="{{ $job->job_id }}">
        <input type="text" id="message" placeholder="Nhập tin nhắn...">
        <button type="submit" id="send-btn">
            <span class="btn-text">Gửi</span>
            <span class="spinner" style="display:none;"></span>
        </button>
    </form>
</div>

@vite('resources/js/app.js')

<style>
.chat-container {
    max-width: 600px;
    margin: 20px auto;
    display: flex;
    flex-direction: column;
    height: 80vh;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    background: #f5f5f5;
    position: relative;
}

.chat-box {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #fff;
}

.message {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 12px;
    word-wrap: break-word;
    position: relative;
    font-size: 0.9rem;
    line-height: 1.3;
}

.message.me {
    align-self: flex-end;
    background-color: #4f46e5;
    color: #fff;
    border-bottom-right-radius: 0;
}

.message.other {
    align-self: flex-start;
    background-color: #e5e7eb;
    color: #000;
    border-bottom-left-radius: 0;
}

.sender {
    font-weight: bold;
    font-size: 0.85rem;
    display: block;
    margin-bottom: 4px;
}

.message-meta {
    font-size: 0.7rem;
    color: gray;
    margin-bottom: 4px;
}

.chat-form {
    display: flex;
    padding: 10px 15px;
    border-top: 1px solid #ccc;
    background: #f9f9f9;
    position: sticky;
    bottom: 0;
}

.chat-form input {
    flex: 1;
    padding: 10px 15px;
    border-radius: 20px;
    border: 1px solid #ccc;
    margin-right: 10px;
    outline: none;
    font-size: 0.9rem;
}

.chat-form button {
    padding: 0 20px;
    border-radius: 20px;
    border: none;
    background: #4f46e5;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.spinner {
    border: 2px solid #f3f3f3;
    border-top: 2px solid white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    margin-left: 8px;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg);}
    100% { transform: rotate(360deg);}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const jobId = {{ $job->job_id }};
    const receiverId = {{ $receiverId }};
    const input = document.getElementById('message');
    const form = document.getElementById('chat-form');
    const sendBtn = document.getElementById('send-btn');
    const spinner = sendBtn.querySelector('.spinner');
    const btnText = sendBtn.querySelector('.btn-text');

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    scrollToBottom();

    function sendMessage() {
        const content = input.value.trim();
        if(!content) return;

        btnText.style.display = 'none';
        spinner.style.display = 'inline-block';
        sendBtn.disabled = true;

        axios.post('{{ route("messages.send") }}', { job_id: jobId, content })
            .then(res => {
                const msg = res.data;
                chatBox.innerHTML += `
                    <div class="message me">
                        <div class="message-meta">
                            <small>ID: ${msg.id} | Job: ${msg.job_id} | Conv: ${msg.conversation_id}</small>
                        </div>
                        <span class="sender">${msg.sender_name}</span>
                        <p class="content">${msg.content}</p>
                    </div>`;
                input.value = '';
                scrollToBottom();
            })
            .catch(err => console.error(err))
            .finally(() => {
                btnText.style.display = 'inline';
                spinner.style.display = 'none';
                sendBtn.disabled = false;
            });
    }

    form.addEventListener('submit', e => { e.preventDefault(); sendMessage(); });
    input.addEventListener('keypress', e => { if(e.key === 'Enter'){ e.preventDefault(); sendMessage(); } });

    if(window.Echo){
        const userIds = [{{ auth()->id() }}, receiverId].sort((a,b) => a-b); // numeric sort
        const channelName = `job.${jobId}.${userIds.join('.')}`;

        window.Echo.private(channelName)
            .listen('MessageSent', e => {
                const isMe = e.message.sender.id === {{ auth()->id() }};
                chatBox.innerHTML += `
                    <div class="message ${isMe ? 'me' : 'other'}">
                        <div class="message-meta">
                            <small>ID: ${e.message.id} | Job: ${e.message.job_id} | Conv: ${e.message.conversation_id}</small>
                        </div>
                        <span class="sender">${e.message.sender.name}</span>
                        <p class="content">${e.message.content}</p>
                    </div>`;
                scrollToBottom();
            });
    }
});
</script>
@endsection
