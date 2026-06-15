<?php

namespace App\Providers;

use App\Models\DebtRecord;
use App\Models\User;
use App\Policies\DebtRecordPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => StudentPolicy::class,
        DebtRecord::class => DebtRecordPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Define custom gates if needed
        Gate::define('is-admin', function (User $user): bool {
            return $user->isAdmin();
        });

        Gate::define('is-mahasiswa', function (User $user): bool {
            return $user->isMahasiswa();
        });
    }
}
