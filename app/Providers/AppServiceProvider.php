<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Services\OrganizationScope;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scope results are cached per request/job only.
        $this->app->scoped(OrganizationScope::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Abilities that are not tied to a single model. Data-level scope is applied in the queries.
     */
    protected function configureGates(): void
    {
        $abilities = [
            'viewReports' => Permission::ReportsView,
            'exportReports' => Permission::ReportsExport,
            'viewAuditLogs' => Permission::AuditView,
            'manageVerificationFlow' => Permission::VerificationFlowsManage,
        ];

        foreach ($abilities as $ability => $permission) {
            Gate::define($ability, fn (User $user) => $user->is_active && $user->can($permission->value));
        }

        Gate::define('verify', fn (User $user) => $user->is_active
            && ($user->can(Permission::EvidenceVerify->value) || $user->can(Permission::EvidenceRequestRevision->value)));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('downloads', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('exports', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
    }
}
