<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Http\Resolver;
use App\Support\Http\SystemResolver;
use App\Support\Launch\ChecklistWaitingItems;
use App\Support\Launch\SignoffWaitingItems;
use App\Support\Waiting\WaitingOnClient;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        // One registry per app, so modules can add their providers at boot.
        $this->app->singleton(WaitingOnClient::class);

        // Real DNS for SafeFetcher; tests bind a fake (ADR 0007).
        $this->app->bind(Resolver::class, SystemResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->registerWaitingItems();
    }

    /**
     * Each module's contribution to the client's "Waiting on you" list.
     */
    protected function registerWaitingItems(): void
    {
        $waiting = $this->app->make(WaitingOnClient::class);

        $waiting->register(fn (User $client) => (new ChecklistWaitingItems)($client));
        $waiting->register(fn (User $client) => (new SignoffWaitingItems)($client));
    }

    /**
     * Named rate limiters used by routes.
     */
    protected function configureRateLimiting(): void
    {
        // CI calls this once per deploy; anything more is a guess at the token.
        RateLimiter::for('deploy', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // New demo sandboxes per visitor IP (story 08).
        RateLimiter::for('demo', fn (Request $request) => Limit::perHour(config()->integer('demo.per_ip_per_hour'))->by($request->ip()));

        // Proofmark uploads: decoding images costs CPU on a shared host (ADR 0009).
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
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
}
