<div id="comment-{{ $comment->id }}" class="comment {{ $comment->parent_id ? 'comment-reply' : '' }}">
    <div class="d-flex mb-3">
        <div class="comment-img me-3">
            <img src="{{ $comment->account->avatar_url ?? asset('assets/img/blog/comments-1.jpg') }}" alt="Người dùng"
                width="50">
        </div>
        <div>
            <h5>
                <a
                    href="{{ route('portfolios.show', $comment->account->profile->username) }}">{{ $comment->account->name ?? 'Khách' }}</a>
                <a href="#" class="reply-btn" data-id="{{ $comment->id }}"><i class="bi bi-reply-fill"></i> Trả lời</a>
            </h5>
            <time datetime="{{ $comment->created_at }}">{{ $comment->created_at->format('d M, Y H:i') }}</time>
            <p>{{ $comment->content }}</p>
        </div>
    </div>

    @foreach($comment->replies as $reply)
        @include('jobs.partials.comment', ['comment' => $reply])
    @endforeach
</div>