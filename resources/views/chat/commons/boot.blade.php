{{-- resources/views/chat/commons/boot.blade.php --}}
<script>
(function () {
  if (window.AppChat) return;

  const state = {
    echoReady: false,
    presenceJoined: false,
    presenceUsers: new Set(),
    currentChannelName: null,
    currentSub: null,
    messagesCache: new Map(), // boxId -> { list, etag, lastId, fetchedAt }
    inFlight: null,
    authId: window.__AUTH_ID || null,
  };

  // ========= Utils =========
  const esc = (s='') => (s+'')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');

  function bumpBadge(id, delta=1) {
    const el = document.getElementById(id);
    if (!el) return;
    const cur = parseInt(el.textContent || '0') || 0;
    const next = Math.max(cur + delta, 0);
    if (next > 0) { el.textContent = next; el.classList.remove('d-none'); }
    else { el.classList.add('d-none'); el.textContent = ''; }
  }

  function toast(n, icon='info') {
    window.Swal?.fire({
      title: n.title || 'Thông báo',
      text: n.body || '',
      icon, toast: true, position: 'bottom-end',
      showConfirmButton: false, timer: 3500
    });
  }

  function setUserOnline(userId, online) {
    const els = document.querySelectorAll(`#status-${userId}, #chatHeaderStatus`);
    els.forEach(el => {
      el.classList.toggle('status-online', !!online);
      el.classList.toggle('status-offline', !online);
      const txt = el.closest('#chatHeader')?.querySelector('#chatHeaderStatusText');
      if (txt) txt.textContent = online ? 'Đang hoạt động' : 'Ngoại tuyến';
    });
  }

  // ========= Presence (chỉ join 1 lần) =========
  function ensurePresence() {
    if (state.presenceJoined || !window.Echo?.join) return;
    state.presenceJoined = true;
    window.Echo.join('online-users')
      .here(users => users.forEach(u => { state.presenceUsers.add(u.id); setUserOnline(u.id, true); }))
      .joining(u => { state.presenceUsers.add(u.id); setUserOnline(u.id, true); })
      .leaving(u => { state.presenceUsers.delete(u.id); setUserOnline(u.id, false); })
      .error(err => console.warn('[presence.error]', err));
  }

  // ========= Wait Echo (1 lần) =========
  (function waitEcho() {
    const ready = !!(window.Echo && window.Echo.connector && window.Echo.connector.pusher);
    if (!ready) return setTimeout(waitEcho, 250);
    const pusher = window.Echo.connector.pusher;
    const spin = () => {
      if (pusher.connection.state === 'connected') {
        state.echoReady = true;
        ensurePresence();
      } else setTimeout(spin, 600);
    };
    spin();
  })();

  // ========= Fetchers (full / since / slice) =========
  async function fetchFull(boxId, limit = 30) {
    if (state.inFlight) try{ state.inFlight.cancel(); }catch(_){}
    state.inFlight = axios.CancelToken.source();

    const cached = state.messagesCache.get(boxId);
    const headers = {};
    if (cached?.etag) headers['If-None-Match'] = cached.etag;

    const res = await axios.get(`/chat/box/${boxId}/messages`, {
      params: { limit },
      headers,
      cancelToken: state.inFlight.token,
      validateStatus: s => (s>=200 && s<300) || s===304
    });
    state.inFlight = null;

    if (res.status === 304) return { fromCache: true };
    const list = Array.isArray(res.data) ? res.data : [];
    const etag = res.headers?.etag || null;
    const lastId = list.length ? list[list.length-1].id : (cached?.lastId || null);
    state.messagesCache.set(boxId, { list, etag, lastId, fetchedAt: Date.now() });
    return { list };
  }

  async function fetchSince(boxId) {
    const cached = state.messagesCache.get(boxId);
    if (!cached?.lastId) return 0;
    const res = await axios.get(`/chat/box/${boxId}/messages`, {
      params: { since_id: cached.lastId },
      validateStatus: s => s>=200 && s<300
    });
    const inc = Array.isArray(res.data) ? res.data : [];
    if (!inc.length) return 0;

    const map = new Map();
    [...cached.list, ...inc].forEach(m => map.set(m.id, m));
    const merged = [...map.values()].sort((a,b)=> new Date(a.created_at)-new Date(b.created_at));
    const newLastId = merged.length ? merged[merged.length-1].id : cached.lastId;
    const etag = res.headers?.etag || cached.etag || null;
    state.messagesCache.set(boxId, { list: merged, etag, lastId: newLastId, fetchedAt: Date.now() });
    return inc.length;
  }

  async function fetchBefore(boxId, beforeId, limit = 30) {
    const res = await axios.get(`/chat/box/${boxId}/messages`, {
      params: { before_id: beforeId, limit },
      validateStatus: s => s>=200 && s<300
    });
    const older = Array.isArray(res.data) ? res.data : [];
    if (!older.length) return 0;

    const cached = state.messagesCache.get(boxId) || { list: [], etag: null, lastId: null, fetchedAt: 0 };
    const map = new Map();
    [...older, ...cached.list].forEach(m => map.set(m.id, m));
    const merged = [...map.values()].sort((a,b)=> new Date(a.created_at)-new Date(b.created_at));
    const lastId = merged.length ? merged[merged.length-1].id : cached.lastId;
    state.messagesCache.set(boxId, { list: merged, etag: cached.etag, lastId, fetchedAt: Date.now() });
    return older.length;
  }

  // ========= Render =========
  let lastMessageTime = null, lastSenderId = null;

  function parseReplyContent(content) {
    if (!content || !content.startsWith('Trả lời ')) return { replyTo:null, mainContent:content };
    function parseLayer(str) {
      const m = str.match(/^Trả lời ([^:]+):\s*/); if (!m) return { quoted:'', rest:str };
      const name = m[1]; let rest = str.slice(m[0].length).trim();
      if (!rest.startsWith('"')) return { quoted:'', rest, name };
      rest = rest.slice(1); let quoted='', i=0;
      while (i<rest.length) {
        if (rest.slice(i).startsWith('Trả lời ')) { const inner = parseLayer(rest.slice(i)); quoted = inner.quoted; rest = inner.rest; i=0; continue; }
        if (rest[i] === '"') { quoted = rest.slice(0,i).trim(); rest = rest.slice(i+1).trim(); break; }
        i++;
      }
      if (!quoted) { quoted = rest.trim(); rest=''; }
      return { quoted, rest, name };
    }
    const r = parseLayer(content);
    return { replyTo: `${r.name}: "${r.quoted}"`, mainContent: r.rest };
  }

  function scrollToBottom() {
    const box = document.getElementById('chatMessages');
    if (!box) return;
    box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
  }

  function appendMessage(msg, {isMe=false, status=''} = {}) {
    const wrap = document.getElementById('chatMessages');
    if (!wrap || document.querySelector(`[data-message-id="${msg.id}"]`)) return;

    const t = new Date(msg.created_at);
    if (!lastMessageTime || (t - lastMessageTime) / 60000 > 10) {
      const timeDiv = document.createElement('div');
      timeDiv.className = 'message-time-center';
      timeDiv.innerText = t.toLocaleString('vi-VN');
      wrap.appendChild(timeDiv);
    }
    lastMessageTime = t;

    const hideAvatar = !isMe && lastSenderId === msg.sender_id;
    lastSenderId = msg.sender_id;

    const ct = document.createElement('div');
    ct.className = `message-container ${isMe ? 'me':'other'} ${hideAvatar?'hide-avatar':''}`;
    ct.dataset.messageId = msg.id;

    const avHTML = isMe
      ? `<div class="avatar-container"></div>`
      : `<div class="avatar-container">
           <img src="${esc(msg.sender?.avatar_url || '/assets/img/defaultavatar.jpg')}" alt="" loading="lazy">
         </div>`;

    const pr = parseReplyContent(msg.content || '');
    let html = '';
    if (pr.replyTo) html += `<div class="reply-quote">${esc(pr.replyTo)}</div>`;
    if (pr.mainContent) html += `<div class="main-text">${esc(pr.mainContent)}</div>`;
    if (msg.img) html += `<img src="${esc(msg.img)}" class="message-img" alt="Image" loading="lazy"
                     onerror="this.onerror=null; this.src='/assets/img/blog/blog-1.jpg'">`;
    if (isMe && status) html += `<div class="message-status">${esc(status)}</div>`;

    ct.innerHTML = `${avHTML}
      <div class="message ${isMe?'me':'other'}">
        <span class="sender">${isMe ? 'Bạn' : esc(msg.sender?.name || 'Unknown')}</span>
        ${html}
      </div>`;
    wrap.appendChild(ct);
    return ct;
  }

  function renderCache(boxId, emptyLabel='Chưa có tin nhắn') {
    const cached = state.messagesCache.get(boxId);
    const wrap = document.getElementById('chatMessages');
    wrap.innerHTML = '';
    lastMessageTime = null; lastSenderId = null;

    if (!cached?.list?.length) {
      wrap.innerHTML = `<div class="message text-muted">${esc(emptyLabel)}</div>`;
      return;
    }
    cached.list.forEach(m => appendMessage(m, { isMe: m.sender_id === state.authId, status: m.sender_id === state.authId ? 'Đã nhận' : '' }));
    scrollToBottom();
  }

  // ========= Realtime subscribe (chống trùng) =========
  function subscribe(channelName, onMessage) {
    if (!window.Echo) return;
    if (state.currentChannelName === channelName) return;
    if (state.currentSub) window.Echo.leave(state.currentChannelName);
    state.currentChannelName = channelName;

    if (channelName.startsWith('chat.')) {
      state.currentSub = window.Echo.private(channelName).listen('MessageSent', onMessage);
    } else {
      state.currentSub = window.Echo.join(channelName).listen('MessageSent', onMessage);
    }
  }

  // ========= Public API =========
  window.AppChat = {
    registerHeaderHooks(userId) {
      if (!userId || !window.Echo) return;
      const ch = 'user-notification.' + userId;

      window.Echo.channel(ch).listen('.new-comment-notification', (e) => {
        const n = e.notification || {};
        bumpBadge('notif-badge', +1);
        toast(n, 'info');
      });

      window.Echo.channel(ch).listen('.new-message-notification', (e) => {
        const n = e.notification || {};
        bumpBadge('chat-badge', +1);
        bumpBadge('notif-badge', +1);
        toast({
          title: n.title || (n.sender_name ? `Tin nhắn từ ${n.sender_name}` : 'Tin nhắn mới'),
          body:  n.body || n.preview || ''
        }, 'info');
      });
    },

    async mountChatPage({ boxId, name, partnerId=null, jobId=null, orgId=null, pageSize=30 }) {
      // Header & input
      const header = document.getElementById('chatHeader');
      const chatInput = document.getElementById('chatInput');
      chatInput && (chatInput.style.display = 'flex');

      if (partnerId) {
        header.innerHTML = `
          <div class="d-flex align-items-center gap-2">
            <div class="position-relative" style="width:45px;height:45px;">
              <img id="chatHeaderAvatar" src="/assets/img/defaultavatar.jpg" class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">
              <span class="status-dot status-offline" id="chatHeaderStatus"></span>
            </div>
            <div>
              <div class="fw-bold" id="chatHeaderName">${esc(name)} <span class="chat-icon icon-1-1"><i class="bi bi-person"></i></span></div>
              <div class="small text-muted" id="chatHeaderStatusText">Ngoại tuyến</div>
            </div>
          </div>`;
      } else if (jobId) {
        header.innerHTML = `<div class="fw-bold" id="chatHeaderName">${esc(name)} <span class="chat-icon icon-group"><i class="bi bi-people"></i></span></div>
                            <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm</div>`;
      } else if (orgId) {
        header.innerHTML = `<div class="fw-bold" id="chatHeaderName">${esc(name)} <span class="chat-icon icon-org"><i class="bi bi-building"></i></span></div>
                            <div class="small text-muted" id="chatHeaderStatusText">Chat nhóm tổ chức</div>`;
      } else {
        header.innerHTML = `<div class="fw-bold">Chọn một cuộc trò chuyện</div>`;
      }

      const box = document.getElementById('chatMessages');
      box.innerHTML = `<div class="message text-muted">Đang tải tin nhắn...</div>`;
      lastMessageTime = null; lastSenderId = null;

      // 1) Render từ cache nếu có
      if (state.messagesCache.get(boxId)?.list?.length) renderCache(boxId, `Chưa có tin nhắn với ${esc(name)}`);

      // 2) Fetch full (30 tin mới nhất, có ETag 304)
      const full = await fetchFull(boxId, pageSize);
      if (full?.list) renderCache(boxId, `Chưa có tin nhắn với ${esc(name)}`);

      // 3) Infinite scroll: kéo lên để tải older
      box.addEventListener('scroll', async function onScroll() {
        if (box.scrollTop < 80) {
          const cached = state.messagesCache.get(boxId);
          const oldest = cached?.list?.[0];
          if (!oldest) return;
          const added = await fetchBefore(boxId, oldest.id, pageSize);
          if (added > 0) {
            const prevH = box.scrollHeight;
            renderCache(boxId, `Chưa có tin nhắn với ${esc(name)}`);
            // giữ vị trí cuộn
            box.scrollTop = box.scrollHeight - prevH + 120;
          }
        }
      }, { passive: true });

      // 4) Realtime subscribe
      let chName = '';
      if (partnerId) {
        const ids = [state.authId, partnerId].sort((a,b)=>a-b);
        chName = 'chat.' + ids.join('.');
      } else if (jobId) chName = 'chat-group.' + jobId;
      else if (orgId) chName = 'chat-org.' + orgId;

      subscribe(chName, (e) => {
        const msg = e.message;
        const belongs =
          (partnerId && (msg.sender_id === partnerId || msg.conversation_id === partnerId)) ||
          (jobId && msg.job_id === jobId) ||
          (orgId && msg.org_id === orgId);
        if (!belongs) return;

        const cached = state.messagesCache.get(boxId) || { list:[], etag:null, lastId:null, fetchedAt:0 };
        if (!cached.list.some(m => m.id === msg.id)) {
          cached.list.push(msg);
          cached.lastId = msg.id;
          cached.fetchedAt = Date.now();
          state.messagesCache.set(boxId, cached);
          appendMessage(msg, { isMe: msg.sender_id === state.authId, status: msg.sender_id === state.authId ? 'Đã nhận' : '' });
          scrollToBottom();
        }
      });

      // 5) Incremental 1 nhịp
      if (state.messagesCache.get(boxId)?.lastId) {
        const added = await fetchSince(boxId);
        if (added > 0) renderCache(boxId, `Chưa có tin nhắn với ${esc(name)}`);
      }
    },

    async send({ text, imageFile, partnerId=null, jobId=null, orgId=null }) {
      if (!text && !imageFile) throw new Error('Vui lòng nhập nội dung hoặc chọn ảnh');
      const form = new FormData();
      if (text) form.append('content', text);
      if (partnerId) form.append('receiver_id', partnerId);
      else if (jobId) form.append('job_id', jobId);
      else if (orgId) form.append('org_id', orgId);
      if (imageFile) form.append('img', imageFile);

      const res = await axios.post(`{{ route('messages.send') }}`, form, {
        headers: {'Content-Type':'multipart/form-data'}
      });
      return res.data;
    },

    setUserOnline,
    bumpBadge,
    toast,
  };
})();
</script>
