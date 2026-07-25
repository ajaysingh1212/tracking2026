<?php

namespace App\Providers;

use App\Models\LicensePlan;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Models\TrackingRelation;
use App\Models\User;
use App\Observers\AuditableObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Interfaces\Repositories\ActivityLogRepositoryInterface::class, \App\Repositories\ActivityLogRepository::class);
        $this->app->bind(\App\Interfaces\Repositories\AuditLogRepositoryInterface::class, \App\Repositories\AuditLogRepository::class);
        $this->app->bind(\App\Interfaces\Repositories\LicensePlanRepositoryInterface::class, \App\Repositories\LicensePlanRepository::class);
        $this->app->bind(\App\Interfaces\Repositories\UserLicenseRepositoryInterface::class, \App\Repositories\UserLicenseRepository::class);
        $this->app->bind(\App\Interfaces\Repositories\UserRepositoryInterface::class, \App\Repositories\UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());
        Paginator::useBootstrapFive();

        User::observe(UserObserver::class);

        LicensePlan::observe(AuditableObserver::class);
        NotificationTemplate::observe(AuditableObserver::class);
        Setting::observe(AuditableObserver::class);
        TrackingRelation::observe(AuditableObserver::class);
    }
}
