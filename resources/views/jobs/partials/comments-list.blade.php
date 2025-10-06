<div class="container">

    <h4 class="comments-count">{{ $job->comments->count() }} Bình luận</h4>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const commentForm = document.getElementById('commentForm');
    const submitBtn = commentForm.querySelector('button[type="submit"]');
    const commentCountEl = document.querySelector('.comments-count');

    // Reply button
    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const parentId = btn.getAttribute('data-id');
            document.getElementById('parent_id').value = parentId;
            window.scrollTo({ top: document.querySelector('.comment-form').offsetTop, behavior: 'smooth' });
        });
    });

    commentForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        // Spinner nút gửi
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang gửi...`;

        fetch("{{ route('comments.store', $job->job_id) }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                "Accept": "application/json"
            },
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                // Reset trạng thái nút
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;

                if (data.error) {
                    Swal.fire('Lỗi', data.error, 'error');
                } else {
                    Swal.fire('Thành công', 'Bình luận đã được gửi!', 'success');

                    // Tạo HTML comment
                    const avatar = data.avatar_url || "{{ asset('assets/img/blog/comments-1.jpg') }}";
                    const html = `
                <div id="comment-${data.id}" class="comment ${data.parent_id ? 'comment-reply' : ''}">
                    <div class="d-flex mb-3">
                        <div class="comment-img me-3">
                            <img src="${avatar}" alt="Người dùng" width="50">
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
                </div>
            `;

                    // Thêm vào đúng vị trí
                    if (data.parent_id) {
                        const parentDiv = document.getElementById(`comment-${data.parent_id}`);
                        if (parentDiv) parentDiv.insertAdjacentHTML('beforeend', html);
                    } else {
                        const commentList = document.querySelector('.comments-list');
                        commentList.insertAdjacentHTML('afterbegin', html);
                    }

                    // Cập nhật số lượng bình luận
                    let count = parseInt(commentCountEl.getAttribute('data-count') || "{{ $job->comments->count() }}");
                    count++;
                    commentCountEl.setAttribute('data-count', count);
                    commentCountEl.textContent = `${count} Bình luận`;

                    commentForm.reset();
                    document.getElementById('parent_id').value = '';
                }
            })
            .catch(err => {
                console.error(err);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                Swal.fire('Lỗi', 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
            });
    });
</script>