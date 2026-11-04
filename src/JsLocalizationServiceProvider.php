<?php

namespace Javaabu\JsLocalization;

use Illuminate\Support\ServiceProvider;

class JsLocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/js-localization.php', 'js-localization');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'js-localization');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/js-localization.php' => config_path('js-localization.php'),
            ], 'js-localization-config');

            $this->publishes([
                __DIR__ . '/../lang' => $this->app->langPath('vendor/js-localization'),
            ], 'js-localization-translations');
        }
    }
}
