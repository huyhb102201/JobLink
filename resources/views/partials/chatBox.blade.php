<!-- 🌟 Floating Chat Box -->
<div id="chat-container" class="position-fixed bottom-0 end-0 me-3 mb-3" style="z-index: 1055;">
  <!-- Button toggle -->
  <button id="chat-toggle" class="btn btn-primary rounded-circle shadow position-relative"
    style="width:60px; height:60px;">
    <i class="fas fa-comments fa-lg"></i>
    <span id="chat-indicator" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle d-none"></span>
  </button>

  <!-- Chat Window -->
  <div id="chat-box" class="card shadow-lg border-0 rounded-4 d-none" style="width:370px; max-height:520px;">
    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white rounded-top-4">
      <div class="d-flex align-items-center">
        <i class="fas fa-user-tie me-2"></i> 
        <strong>Hỗ trợ trực tuyến</strong>
      </div>
      <div class="d-flex align-items-center">
        <button class="btn btn-light btn-sm me-1" id="refresh-chat" title="Làm mới">
          <i class="fas fa-sync-alt"></i>
        </button>
        <button class="btn btn-light btn-sm" id="close-chat" title="Đóng">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <div id="chatBox" class="card-body bg-light overflow-auto" style="height:380px; font-size:14px;"></div>

    <div class="card-footer bg-white border-top">
      <div class="input-group">
        <input type="text" id="message" class="form-control rounded-start-pill" placeholder="Nhập tin nhắn..." onkeydown="handleKey(event)">
        <button id="sendBtn" class="btn btn-primary rounded-end-pill">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const chatToggle = document.getElementById('chat-toggle');
const chatBox = document.getElementById('chat-box');
const chatArea = document.getElementById('chatBox');
const sendBtn = document.getElementById('sendBtn');
const msgInput = document.getElementById('message');
const refreshBtn = document.getElementById('refresh-chat');

let isWaiting = false;

// 🧠 Load lịch sử
if (localStorage.getItem('chat_history')) {
  chatArea.innerHTML = localStorage.getItem('chat_history');
  document.getElementById('chat-indicator').classList.remove('d-none');
} else {
  showWelcomeMessage();
}

chatToggle.onclick = () => {
  chatBox.classList.toggle('d-none');
  chatToggle.classList.toggle('d-none');
  document.getElementById('chat-indicator').classList.add('d-none');
  document.querySelectorAll('.btn.btn-success.rounded-circle, .btn.btn-danger.rounded-circle')
    .forEach(btn => btn.classList.add('d-none'));
  setTimeout(scrollToBottom, 200);
};

document.getElementById('close-chat').onclick = () => {
  chatBox.classList.add('d-none');
  chatToggle.classList.remove('d-none');
   document.querySelectorAll('.btn.btn-success.rounded-circle, .btn.btn-danger.rounded-circle')
    .forEach(btn => btn.classList.remove('d-none'));
};

refreshBtn.onclick = clearChat;

// 🧹 Làm mới chat
function clearChat() {
  chatArea.innerHTML = '';
  localStorage.removeItem('chat_history');
  showWelcomeMessage();
  saveHistory();
  fetch('/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ reset: '1' })
  });
}

// 👋 Lời chào ban đầu
function showWelcomeMessage() {
  const msg = `
  <div class="text-start mb-3">
    <div class="bg-white shadow-sm p-3 rounded-3 d-inline-block">
      <strong><i class="bi bi-robot"></i> Xin chào!</strong><br>
      Tôi là <b>trợ lý tư vấn việc làm</b>.<br>
      Hãy cho tôi biết lĩnh vực hoặc kỹ năng bạn quan tâm (ví dụ: lập trình web, viết content, thiết kế...) để tôi gợi ý việc phù hợp nhé.
      <div class="mt-2 text-muted"><i class="bi bi-stars"></i> Gợi ý nhanh:</div>
      <div class="mt-1">
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2 quick-suggest" data-text="Tôi có 2 năm làm Laravel + React, tìm job freelance">Laravel + React freelance</button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2 quick-suggest" data-text="Viết content SEO về du lịch, ngân sách 2-3 triệu">Content SEO du lịch</button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2 quick-suggest" data-text="Thiết kế logo cho shop thời trang, cần trong 1 tuần">Thiết kế logo 1 tuần</button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2 quick-suggest" data-text="Cần dev mobile Flutter, làm remote, trả theo giờ">Flutter remote theo giờ</button>
      </div>
    </div>
  </div>`;
  chatArea.insertAdjacentHTML('beforeend', msg);
  scrollToBottom();
}
function scrollToBottom() {
  chatArea.scrollTo({ top: chatArea.scrollHeight, behavior: 'smooth' });
}

function saveHistory() {
  localStorage.setItem('chat_history', chatArea.innerHTML);
}

function handleKey(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    sendMessage();
  }
}

sendBtn.onclick = sendMessage;

// Gửi tin nhắn
function sendMessage() {
  if (isWaiting) return;
  const msg = msgInput.value.trim();
  if (!msg) return;
  msgInput.value = '';

  chatArea.insertAdjacentHTML('beforeend', `
    <div class="text-end mb-2">
      <div class="bg-primary text-white p-2 px-3 rounded-3 d-inline-block">${msg}</div>
    </div>
  `);
  saveHistory();
  scrollToBottom();

  const loadingId = 'loading-' + Date.now();
  chatArea.insertAdjacentHTML('beforeend', `
    <div id="${loadingId}" class="text-start mb-2">
      <div class="bg-white shadow-sm p-2 rounded-3 d-inline-block">
        <i class="fas fa-spinner fa-spin text-primary"></i> Đang trả lời...
      </div>
    </div>
  `);
  scrollToBottom();

  isWaiting = true;
  sendBtn.disabled = true;

  fetch('/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ message: msg })
  })
  .then(res => res.json())
  .then(data => {
    document.getElementById(loadingId)?.remove();
    let reply = data.reply || "Xin lỗi, tôi chưa có câu trả lời.";
    chatArea.insertAdjacentHTML('beforeend', `
      <div class="text-start mb-3">
        <div class="bg-white shadow-sm p-3 rounded-3 d-inline-block">${reply}</div>
      </div>
    `);
    saveHistory();
    scrollToBottom();
  })
  .catch(() => {
    document.getElementById(loadingId)?.remove();
    chatArea.insertAdjacentHTML('beforeend', `
      <div class="text-start mb-3">
        <div class="bg-danger text-white p-2 rounded-3 d-inline-block">❌ Lỗi hệ thống. Vui lòng thử lại.</div>
      </div>
    `);
  })
  .finally(() => {
    isWaiting = false;
    sendBtn.disabled = false;
    saveHistory();
  });
}

// Một chút CSS tinh chỉnh responsive
const style = document.createElement('style');
style.innerHTML = `
#chat-box {
  animation: slideUp .25s ease;
}
@keyframes slideUp {
  from {transform: translateY(20px); opacity:0;}
  to {transform: translateY(0); opacity:1;}
}
@media (max-width: 576px) {
  #chat-box {
    width: 100%;
    max-height: 80vh;
    position: fixed;
    bottom: 0;
    right: 0;
    border-radius: 0;
  }
}
#chatBox img {
  max-width: 60px;
  height: auto;
  border-radius: 6px;
  margin-right: 8px;
}
#chatBox ul {
  padding-left: 1rem;
  margin-bottom: 0;
}
#chatBox li {
  margin-bottom: 10px;
  list-style-type: none;
}
#chatBox li .card {
  border-radius: 8px;
}
`;
document.head.appendChild(style);
</script>

<script>
// Gửi nhanh theo gợi ý
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.quick-suggest');
  if (!btn) return;

  const text = btn.getAttribute('data-text') || btn.textContent || '';
  if (!text.trim()) return;

  msgInput.value = text.trim();
  sendMessage();
});
</script>

