<div class="container">

    <h4 class="comments-count" data-count="{{ $job->comments->count() }}">
        {{ $job->comments->count() }} Bình luận
    </h4>

    @if($job->comments->isEmpty())
        <p>Hãy là người đầu tiên bình luận vào bài viết này!</p>
    @endif

    <div class="comments-list">
        @foreach($job->comments->where('parent_id', null) as $comment)
            @include('jobs.partials.comment', ['comment' => $comment])
        @endforeach
    </div>

    <div class="comment-form mt-4">
        <h5>Viết bình luận</h5>
        <form id="commentForm">
            @csrf
            <input type="hidden" name="parent_id" id="parent_id" value="">
            <div class="mb-3">
                <textarea name="content" id="content" class="form-control" rows="4" placeholder="Nội dung bình luận..."
                    required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Gửi bình luận</button>
        </form>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const commentForm = document.getElementById('commentForm');
        const submitBtn = commentForm.querySelector('button[type="submit"]');
        const commentCountEl = document.querySelector('.comments-count');

        // Reply buttons
        document.querySelectorAll('.reply-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                document.getElementById('parent_id').value = btn.dataset.id;
                window.scrollTo({ top: document.querySelector('.comment-form').offsetTop, behavior: 'smooth' });
            });
        });

        // Submit comment
        commentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Đang gửi...`;

            fetch("{{ route('comments.store', $job->job_id) }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (data.error) {
                        Swal.fire('Lỗi', data.error, 'error');
                        return;
                    }

                    Swal.fire('Thành công', 'Bình luận đã được gửi!', 'success');

                    const avatar = data.avatar_url;
                    const html = `
            <div id="comment-${data.id}" class="comment ${data.parent_id ? 'comment-reply' : ''}">
                <div class="d-flex mb-3">
                    <div class="comment-img me-3">
                        <img src="${avatar}" width="50" alt="Người dùng">
                    </div>
                    <div>
                        <h5>
                            <a href="#">${data.account_name}</a>
                            <a href="#" class="reply-btn" data-id="${data.id}"><i class="bi bi-reply-fill"></i> Trả lời</a>
                        </h5>
                        <time datetime="${data.created_at}">${new Date(data.created_at).toLocaleDateString('vi-VN')}</time>
                        <p>${data.content}</p>
                    </div>
                </div>
            </div>`;

                    if (data.parent_id) {
                        const parentDiv = document.getElementById(`comment-${data.parent_id}`);
                        if (parentDiv) parentDiv.insertAdjacentHTML('beforeend', html);
                    } else {
                        document.querySelector('.comments-list').insertAdjacentHTML('afterbegin', html);
                    }

                    // Update count
                    let count = parseInt(commentCountEl.getAttribute('data-count') || 0) + 1;
                    commentCountEl.setAttribute('data-count', count);
                    commentCountEl.textContent = `${count} Bình luận`;

                    commentForm.reset();
                    document.getElementById('parent_id').value = '';


                    window.Echo.channel('job-comments.' + {{ $job->job_id }})
                        .listen('.new-comment', e => {
                            const data = e.comment;

                            const html = `
            <div id="comment-${data.id}" class="comment ${data.parent_id ? 'comment-reply' : ''}">
                <div class="d-flex mb-3">
                    <div class="comment-img me-3">
                        <img src="${data.account?.avatar_url || '/assets/img/blog/comments-1.jpg'}" width="50" alt="Người dùng">
                    </div>
                    <div>
                        <h5>
                            <a href="#">${data.account?.name || 'Khách'}</a>
                            <a href="#" class="reply-btn" data-id="${data.id}">
                                <i class="bi bi-reply-fill"></i> Trả lời
                            </a>
                        </h5>
                        <time datetime="${data.created_at}">${new Date(data.created_at).toLocaleDateString('vi-VN')}</time>
                        <p>${data.content}</p>
                    </div>
                </div>
            </div>`;

                            // Nếu là reply
                            if (data.parent_id) {
                                const parentDiv = document.getElementById(`comment-${data.parent_id}`);
                                if (parentDiv) parentDiv.insertAdjacentHTML('beforeend', html);
                            } else {
                                document.querySelector('.comments-list').insertAdjacentHTML('afterbegin', html);
                            }

                            // Cập nhật đếm số bình luận
                            const commentCountEl = document.querySelector('.comments-count');
                            let count = parseInt(commentCountEl.getAttribute('data-count') || 0) + 1;
                            commentCountEl.setAttribute('data-count', count);
                            commentCountEl.textContent = `${count} Bình luận`;
                        });

                })
                .catch(err => {
                    console.error(err);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    Swal.fire('Lỗi', 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
                });
        });

        // --- Realtime Echo ---
        const USER_ID = {{ Auth::user()->account_id ?? 'null' }};
        if (USER_ID && window.Echo) {
            window.Echo.channel('user-notification.' + USER_ID)
                .listen('.new-comment-notification', e => {
                    const n = e.notification;

                    // Badge
                    const badge = document.getElementById('notif-badge');
                    let current = parseInt(badge.textContent || 0) + 1;
                    badge.textContent = current;
                    badge.classList.remove('d-none');

                    // Thêm vào dropdown
                    const notifList = document.getElementById('notif-list');
                    const html = `
                <li class="unread">
                    <a class="dropdown-item py-2 d-flex align-items-start gap-2" href="/notifications/${n.id}">
                        <i class="bi bi-chat-dots text-primary fs-5 mt-1"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-truncate" style="max-width:170px;">${n.title}</div>
                            <small class="text-muted text-truncate d-block" style="max-width:170px;">${n.body}</small>
                        </div>
                        <span class="badge bg-primary ms-auto">Mới</span>
                    </a>
                </li>`;
                    notifList.insertAdjacentHTML('afterbegin', html);

                    // Toast
                    Swal.fire({
                        title: n.title,
                        text: n.body,
                        icon: 'info',
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 4000,
                    });
                });
        }




    });
</script>