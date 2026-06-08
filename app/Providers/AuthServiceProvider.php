<?php

namespace App\Providers;

use App\Models\Note;
use App\Policies\NotePolicy;
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
        Note::class => NotePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        Gate::define('view-audit-trails', function ($user) {
            return $user->hasRole('super_admin'); 
        });
    }
}
