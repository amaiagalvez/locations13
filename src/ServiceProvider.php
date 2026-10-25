<?php

namespace Locations13;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'locations13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'locations13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');

        $this->publishes([
            __DIR__.'/../config/locations13.php' => config_path('locations13.php'),
        ], 'locations13-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/locations13'),
        ], 'locations13-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/locations13'),
        ], 'locations13-lang');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\ImportEuskadiCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/locations13.php', 'locations13');
    }
}
