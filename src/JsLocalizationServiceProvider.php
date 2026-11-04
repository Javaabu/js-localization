<?php

namespace Javaabu\JsLocalization;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\JsLocalization\Commands\ExportCommand;
use Javaabu\JsLocalization\Http\Controllers\TranslationsController;

class JsLocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/js-localization.php', 'js-localization');

        $this->app->singleton(JsLocalization::class, fn ($app) => new JsLocalization(
            $app['translator']->getLoader(),
            $app['files'],
            $app['config'],
            $app['cache'],
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'js-localization');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/js-localization.php' => config_path('js-localization.php'),
            ], 'js-localization-config');

            $this->publishes([
                __DIR__ . '/../lang' => $this->app->langPath('vendor/js-localization'),
            ], 'js-localization-translations');
        }
    }

    protected function registerRoutes(): void
    {
        Route::macro('jsLocalization', function (?string $prefix = null): RoutingRoute {
            $prefix = trim($prefix ?? (string) config('js-localization.route.prefix', 'js-localization'), '/');

            return Route::get(ltrim("{$prefix}/{locale}.json", '/'), [TranslationsController::class, 'show'])
                ->where('locale', '[A-Za-z0-9_-]+')
                ->name(config('js-localization.route.name', 'js-localization.show'));
        });

        if (! config('js-localization.route.enabled', true)) {
            return;
        }

        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(config('js-localization.route.middleware', []))
            ->group(fn () => Route::jsLocalization());
    }
}
