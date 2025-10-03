<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\MarkUserOnline;
use App\Listeners\MarkUserOffline;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            MarkUserOnline::class,
        ],
        Logout::class => [
            MarkUserOffline::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
