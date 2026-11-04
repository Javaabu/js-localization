<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The locales to export. null exports every locale your app has language
    | files for (folders and JSON files in lang/), plus app.locale and
    | app.fallback_locale.
    |
    | 'locales' => ['en', 'dv'],
    |
    */

    'locales' => null,

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    |
    | Which language files to export, by the name you use in __(): 'messages'
    | for lang/{locale}/messages.php, 'forum/thread' for a nested file, and
    | 'shop::cart' for a package's file. Wildcards work: 'forum/*', 'shop::*'.
    | Everything you export is public, so leave out anything private. The
    | default leaves out the command output of this package and manifest.
    |
    */

    'groups' => ['*'],

    'except' => [
        'js-localization::*',
        'manifest::*',
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON lines
    |--------------------------------------------------------------------------
    |
    | Export lang/{locale}.json, merged with the JSON lines packages register,
    | exactly as Laravel's __() sees them.
    |
    */

    'json' => true,

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Where php artisan lang:js writes. With {locale} in the path, each locale
    | gets its own file; without it, one file holds every locale. The
    | extension picks the format: .json for @javaabu/i18n's loaders and
    | imports, .js for a <script> that sets window.Lang.
    |
    */

    'path' => resource_path('js/translations/{locale}.json'),

    'sort' => true,

    'pretty' => false,

    /*
    |--------------------------------------------------------------------------
    | Endpoint
    |--------------------------------------------------------------------------
    |
    | GET /{prefix}/{locale}.json returns one locale's lines, so pages can load
    | translations without a build step. Turn it off and call
    | Route::jsLocalization() in your routes to choose the middleware yourself.
    |
    */

    'route' => [
        'enabled' => true,
        'prefix' => 'js-localization',
        'middleware' => [],
        'name' => 'js-localization.show',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blade
    |--------------------------------------------------------------------------
    |
    | Load @javaabu/i18n on every Blade page, so __(), trans() and
    | trans_choice() work in scripts without any setup. Turn it off to load it
    | only where you ask for it: @manifestScripts('js-localization').
    |
    */

    'blade' => [
        'auto' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Exported lines are cached until a language file changes. null uses the
    | default cache store.
    |
    */

    'cache' => [
        'enabled' => true,
        'store' => null,
    ],

];
