<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Ví dụ: Post::class => PostPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies(); // <- thường được gọi trong boot()

        Gate::define('access-admin', function ($user) {
            dd($user->toArray(), $user->type?->toArray());
            $user->loadMissing('type');
            return $user->type?->code === 'ADMIN'; 
            // hoặc: return (int)$user->account_type_id === 1;
        });
    }
}
