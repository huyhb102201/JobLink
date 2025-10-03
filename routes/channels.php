<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('job.{jobId}.{user1}.{user2}', function ($user, $jobId, $user1, $user2) {
    return in_array($user->account_id, [(int)$user1, (int)$user2]);
});

Broadcast::channel('online-users', function ($user) {
    return [
        'id'     => $user->account_id,
        'name'   => $user->name,
        'avatar' => $user->avatar_url,
    ];
});
