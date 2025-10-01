@php
  $items = [
    ['label'=>'My info',              'route'=>'settings.myinfo'],
    ['label'=>'Billing & Payments',   'route'=>'settings.billing'],
    ['label'=>'Password & Security',  'route'=>'settings.security'],
    ['label'=>'Membership Settings',  'route'=>'settings.membership'],
    ['label'=>'Teams',                'route'=>'settings.teams'],
    ['label'=>'Notification Settings','route'=>'settings.notifications'],
    ['label'=>'Members & Permissions','route'=>'settings.members'],
    ['label'=>'Tax Information',      'route'=>'settings.tax'],
    ['label'=>'Connected Services',   'route'=>'settings.connected'],
    ['label'=>'Appeals Tracker',      'route'=>'settings.appeals'],
    ['label'=>'Công việc đã nộp',     'route'=>'settings.submitted_jobs'],
  ];
@endphp

<div class="list-group list-group-flush sticky-top" style="top:80px;">
  @foreach($items as $it)
    <a href="{{ route($it['route']) }}"
       class="list-group-item list-group-item-action {{ request()->routeIs($it['route']) ? 'active' : '' }}">
       {{ $it['label'] }}
    </a>
  @endforeach
</div>
