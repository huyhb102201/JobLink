@extends('settings.layout')

@section('settings_content')
<section class="card border-0 shadow-sm">
  <div class="card-body d-flex justify-content-between align-items-start" style="min-height:500px;">
    <div>
      <h5 class="mb-1">Membership Settings</h5>
      <div class="text-muted">Gói hiện tại: <b>{{ $account->type->name ?? 'Guest' }}</b></div>
    </div>
    <form action="{{ route('settings.membership.change') }}" method="POST" class="d-flex gap-2">
      @csrf
      <select class="form-select form-select-sm" name="account_type_id" style="width: 240px;">
        @foreach(\App\Models\AccountType::where('status',1)->orderBy('account_type_id')->get() as $t)
          <option value="{{ $t->account_type_id }}" @selected($t->account_type_id==$account->account_type_id)>
            {{ $t->name }}
          </option>
        @endforeach
      </select>
    </form>
  </div>
</section>
@endsection
