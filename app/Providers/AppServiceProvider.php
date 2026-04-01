<?php

namespace App\Providers;

use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
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
        // Resolve {project} only when it belongs to the current user (prevents access to others' projects and ID enumeration).
        Route::bind('project', function (string $value): Project {
            $project = Project::find($value);
            if (! $project || $project->user_id !== auth()->id()) {
                abort(404);
            }

            return $project;
        });

        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('local')) {
            $this->app->register(\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class);
        }

        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });

        Event::listen(Registered::class, function (Registered $event) {
            app(NotificationService::class)->sendWelcomeEmail($event->user);
        });
    }
}
