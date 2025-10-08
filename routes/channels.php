<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{user1}.{user2}', function ($user, $user1, $user2) {
    return in_array($user->account_id, [(int)$user1, (int)$user2]);
});

Broadcast::channel('chat-group.{jobId}', function ($user, $jobId) {
    $job = \App\Models\Job::find($jobId);
    if (!$job || $job->status !== 'in_progress') {
        return false;
    }
    $members = explode(',', $job->apply_id) ?? [];
    $members[] = $job->account_id;
    if (in_array($user->account_id, array_filter($members))) {
        return [
            'id' => $user->account_id,
            'name' => $user->name,
            'avatar' => $user->avatar_url ?? asset('assets/img/blog/blog-1.jpg'),
        ];
    }
    return false;
});

Broadcast::channel('online-users', function ($user) {
    return [
        'id'     => $user->account_id,
        'name'   => $user->name,
        'avatar' => $user->avatar_url,
    ];
});