<?php

namespace Javaabu\JsLocalization;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\JsLocalization\Commands\ExportCommand;
use Javaabu\JsLocalization\Commands\MissingCommand;
use Javaabu\JsLocalization\Http\Controllers\TranslationsController;
use Javaabu\JsLocalization\Http\Middleware\LoadTranslations;
use Javaabu\JsLocalization\Missing\KeyScanner;
use Javaabu\JsLocalization\Missing\MissingTranslations;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;

class JsLocalizationServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    /**
     * The npm version of @javaabu/i18n apps install. A Git dependency until
     * it's on npm.
     */
    public const string I18N_VERSION = 'github:Javaabu/i18n#main';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/js-localization.php', 'js-localization');

        $this->app->singleton(JsLocalization::class, fn ($app) => new JsLocalization(
            $app['translator']->getLoader(),
            $app['files'],
            $app['config'],
            $app['cache'],
        ));

        $this->app->bind(MissingTranslations::class, fn ($app) => new MissingTranslations(
            $app->make(JsLocalization::class),
            $app->make(KeyScanner::class),
            $app['translator'],
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'js-localization');

        $this->registerRoutes();
        $this->registerAssets('js-localization', __DIR__ . '/../resources', fn (Package $package) => $this->defineAssets($package));

        // After every provider has booted, so this runs inside manifest's
        // injection middleware.
        $this->app->booted(fn () => $this->registerMiddleware());

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportCommand::class,
                MissingCommand::class,
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

    protected function defineAssets(Package $package): void
    {
        $package
            ->script('i18n', 'js/blade.ts', attributes: [
                'data-js-localization' => $this->app->make(JsLocalization::class)->url(),
            ])
            ->js()
            ->dist('../dist/browser')
            ->npm(['@javaabu/i18n' => self::I18N_VERSION]);
    }

    protected function registerMiddleware(): void
    {
        if (! $this->app->bound(HttpKernel::class)) {
            return;
        }

        $kernel = $this->app->make(HttpKernel::class);

        if (! $kernel instanceof Kernel) {
            return;
        }

        if ($kernel->hasMiddleware(LoadTranslations::class)) {
            return;
        }

        $kernel->pushMiddleware(LoadTranslations::class);
    }
}
