{{-- resources/views/chat/partials/conversation-item.blade.php --}}
@php
    $partnerId = $boxItem->sender_id == auth()->id() ? $boxItem->receiver_id : $boxItem->sender_id;
    $partner = $boxItem->type == 1 ? \App\Models\Account::find($partnerId) : null;
    $latestMsg = $boxItem->messages->first();
    $avatar = $boxItem->type == 1 ? ($partner?->avatar_url ?: asset('assets/img/defaultavatar.jpg')) : ($boxItem->type == 2 ? asset('assets/img/group-icon.png') : asset('assets/img/org-icon.png'));
    $isActive = ($box && $box->id == $boxItem->id) ? 'active' : '';
    $iconClass = $boxItem->type == 1 ? 'icon-1-1' : ($boxItem->type == 2 ? 'icon-group' : 'icon-org');
    $icon = $boxItem->type == 1 ? '<i class="bi bi-person"></i>' : ($boxItem->type == 2 ? '<i class="bi bi-people"></i>' : '<i class="bi bi-building"></i>');
    $name = $boxItem->type == 1 && $partner ? $partner->name : $boxItem->name;
@endphp

@if($boxItem->type != 1 || $partner)
    <div class="chat-item d-flex align-items-center mb-2 p-2 rounded {{ $isActive }}"
         data-box-id="{{ $boxItem->id }}"
         @if($boxItem->type == 1)
             data-partner-id="{{ $partner->account_id }}"
         @elseif($boxItem->type == 2)
             data-job-id="{{ $boxItem->job_id }}"
         @else
             data-org-id="{{ $boxItem->org_id }}"
         @endif
         onclick="openBoxChat('{{ $name }}', this, {{ $boxItem->type == 1 ? $partner->account_id : 'null' }}, {{ $boxItem->id }}, {{ $boxItem->type == 2 ? $boxItem->job_id : 'null' }}, {{ $boxItem->type == 3 ? $boxItem->org_id : 'null' }})">
        <div class="position-relative me-2" style="width:55px;height:55px;">
            <img src="{{ $avatar }}" class="rounded-circle" style="width:55px;height:55px;object-fit:cover;">
            @if($boxItem->type == 1)
                <span class="status-dot status-offline" id="status-{{ $partner->account_id }}"></span>
            @endif
        </div>
        <div>
            <div class="fw-bold">{{ $name }} <span class="chat-icon {{ $iconClass }}">{!! $icon !!}</span></div>
            @if($latestMsg)
                <div class="text-muted" style="font-size:0.85rem;">
                    {{ $latestMsg->sender_id == auth()->id() ? 'Bạn: ' : ($latestMsg->sender->name . ': ') }}
                    {{ $latestMsg->img ? '[Hình ảnh]' : \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25) }}
                    • {{ $latestMsg->created_at->diffForHumans() }}
                </div>
            @else
                <div class="text-muted" style="font-size:0.85rem;">Chưa có dữ liệu chat</div>
            @endif
        </div>
    </div>
@endif