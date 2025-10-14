<script>
document.addEventListener('DOMContentLoaded', function () {
  const checkEchoReady = setInterval(() => {
    if (window.Echo) { clearInterval(checkEchoReady); initEchoListeners(); }
  }, 200);

  function initEchoListeners() {
    const USER_ID = {{ Auth::user()->account_id ?? 'null' }};
    if (!USER_ID) return;
    console.log('✅ Echo ready, listening for user:', USER_ID);

    // ============== Helpers dùng chung ==============
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

    function prependNotifItem(n, { icon = 'bi-bell-fill' } = {}) {
      const list = document.getElementById('notif-list');
      if (!list) return;
      const html = `
        <li class="unread">
          <a class="dropdown-item py-2 d-flex align-items-start gap-2" href="/notifications/${esc(n.id)}">
            <i class="bi ${icon} text-primary fs-5 mt-1"></i>
            <div class="flex-grow-1">
              <div class="fw-semibold text-truncate" style="max-width:170px;">${esc(n.title || '(Không tiêu đề)')}</div>
              <small class="text-muted text-truncate d-block" style="max-width:170px;">${esc(n.body || '')}</small>
            </div>
            <span class="badge bg-primary ms-auto">Mới</span>
          </a>
        </li>`;
      // nếu đang có "Không có thông báo" thì xóa đi
      if (list.firstElementChild && list.firstElementChild.classList.contains('text-muted')) {
        list.innerHTML = '';
      }
      list.insertAdjacentHTML('afterbegin', html);
    }

    function toast(n, icon='info') {
      // SweetAlert2
      Swal?.fire({
        title: n.title || 'Thông báo',
        text: n.body || '',
        icon, toast: true, position: 'bottom-end',
        showConfirmButton: false, timer: 4000
      });
    }

    // ============== COMMENT NOTI (giữ nguyên ý tưởng cũ) ==============
    window.Echo.channel('user-notification.' + USER_ID)
      .listen('.new-comment-notification', (e) => {
        const n = e.notification || {};
        console.log('💬 Bình luận realtime:', n);

        bumpBadge('notif-badge', +1);
        prependNotifItem(n, { icon: 'bi-chat-dots' });
        toast(n, 'info');
      });

    // ============== MESSAGE NOTI (làm y hệt comment) ==============
    window.Echo.channel('user-notification.' + USER_ID)
      .listen('.new-message-notification', (e) => {
        const n = e.notification || {};
        console.log('💬 Tin nhắn mới realtime:', n);

        // Badge chat + danh sách chat
        bumpBadge('chat-badge', +1);
        if (typeof loadChatHeader === 'function') loadChatHeader();

        // (A) Nếu bạn muốn message cũng xuất hiện trong danh sách thông báo:
        bumpBadge('notif-badge', +1);
        prependNotifItem(
          {
            id: n.id,
            title: n.title || 'Tin nhắn mới',
            body: n.body || n.preview || (n.sender_name ? `Tin nhắn từ ${n.sender_name}` : '')
          },
          { icon: 'bi-envelope-fill' }
        );

        // (B) Toast giống comment
        toast(
          {
            title: n.title || (n.sender_name ? `Tin nhắn từ ${n.sender_name}` : 'Tin nhắn mới'),
            body:  n.body || n.preview || ''
          },
          'info'
        );
      });
  }
});
</script>
