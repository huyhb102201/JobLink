{{-- resources/views/chat/partials/conversation-item.blade.php --}}
@props(['boxItem', 'box'])

@if($boxItem->type == 1)
    @php
        $partnerId = $boxItem->sender_id == auth()->id() ? $boxItem->receiver_id : $boxItem->sender_id;
        $partner = \App\Models\Account::find($partnerId);
        $latestMsg = $boxItem->messages->first();
        $avatar = $partner?->avatar_url ?: asset('assets/img/defaultavatar.jpg');
        $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
    @endphp
    @if($partner)
        <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
             data-box-id="{{ $boxItem->id }}" data-partner-id="{{ $partner->account_id }}"
             onclick="openBoxChat('{{ $partner->name }}', this, {{ $partner->account_id }}, {{ $boxItem->id }})">
            <div class="position-relative me-2" style="width:55px;height:55px;">
                <img src="{{ $avatar }}" class="rounded-circle" style="width:55px;height:55px;object-fit:cover;">
                <span class="status-dot status-offline" id="status-{{ $partner->account_id }}"></span>
            </div>
            <div>
                <div class="fw-bold">{{ $partner->name }} <span class="chat-icon icon-1-1"><i class="bi bi-person"></i></span></div>
                @if($latestMsg)
                    <div class="text-muted" style="font-size:0.85rem;">
                        {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $partner->name . ': ' }}
                        {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                        • {{ $latestMsg->created_at->diffForHumans() }}
                    </div>
                @else
                    <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
                @endif
            </div>
        </div>
    @endif
@elseif($boxItem->type == 2)
    @php
        $latestMsg = $boxItem->messages->first();
        $avatar = asset('assets/img/group-icon.png');
        $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
    @endphp
    <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
         data-box-id="{{ $boxItem->id }}" data-job-id="{{ $boxItem->job_id }}"
         onclick="openBoxChat('{{ $boxItem->name }}', this, null, {{ $boxItem->id }}, {{ $boxItem->job_id }})">
        <div class="position-relative me-2" style="width:55px;height:55px;">
            <img src="{{ $avatar }}" class="rounded-circle" style="width:55px;height:55px;object-fit:cover;">
        </div>
        <div>
            <div class="fw-bold">{{ $boxItem->name }} <span class="chat-icon icon-group"><i class="bi bi-people"></i></span></div>
            @if($latestMsg)
                <div class="text-muted" style="font-size:0.85rem;">
                    {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $latestMsg->sender->name . ': ' }}
                    {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                    • {{ $latestMsg->created_at->diffForHumans() }}
                </div>
            @else
                <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
            @endif
        </div>
    </div>
@else
    @php
        $latestMsg = $boxItem->messages->first();
        $avatar = asset('assets/img/org-icon.png');
        $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
    @endphp
    <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
         data-box-id="{{ $boxItem->id }}" data-org-id="{{ $boxItem->org_id }}"
         onclick="openBoxChat('{{ $boxItem->name }}', this, null, {{ $boxItem->id }}, null, {{ $boxItem->org_id }})">
        <div class="position-relative me-2" style="width:55px;height:55px;">
            <img src="{{ $avatar }}" class="rounded-circle" style="width:55px;height:55px;object-fit:cover;">
        </div>
        <div>
            <div class="fw-bold">{{ $boxItem->name }} <span class="chat-icon icon-org"><i class="bi bi-building"></i></span></div>
            @if($latestMsg)
                <div class="text-muted" style="font-size:0.85rem;">
                    {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : $latestMsg->sender->name . ': ' }}
                    {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                    • {{ $latestMsg->created_at->diffForHumans() }}
                </div>
            @else
                <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
            @endif
        </div>
    </div>
@endif