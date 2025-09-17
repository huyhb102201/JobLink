<h2>Danh sách Freelancer đã nhắn</h2>
<ul>
@foreach($freelancers as $f)
    <li>
        <a href="{{ route('chat.with', [$job->id, $f->id]) }}">
            {{ $f->name }}
        </a>
    </li>
@endforeach
</ul>
