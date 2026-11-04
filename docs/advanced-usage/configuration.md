---
title: Configuration
sidebar_position: 1
---

Everything has a default, so most apps never publish the config. To change something:

```bash
php artisan vendor:publish --tag=js-localization-config
```

This creates `config/js-localization.php`.

# What to export

| Key | Default | What it does |
| --- | --- | --- |
| `locales` | `null` | The locales to export. `null` means every locale your app has language files for (folders and JSON files in `lang/`), plus `app.locale` and `app.fallback_locale`. The endpoint returns 404 for any other locale. |
| `groups` | `['*']` | The language files to export, by the name you use in `__()`: `'messages'`, `'forum/thread'`, `'shop::cart'`. Wildcards work: `'forum/*'`, `'shop::*'`. |
| `except` | `['js-localization::*', 'manifest::*']` | Files to leave out, with the same names and wildcards. The default leaves out the command output of this package and manifest. |
| `json` | `true` | Export JSON lines (`lang/{locale}.json` and packages' JSON). |
| `sort` | `true` | Sort lines by key. |

Everything exported is public: anyone can fetch `/js-localization/dv.json`. Leave out anything you don't want visitors to read:

```php
'groups' => ['*'],

'except' => [
    'js-localization::*',
    'manifest::*',
    'admin',            // lang/{locale}/admin.php
    'emails/*',         // lang/{locale}/emails/*.php
    'filament*::*',     // a package's files
],
```

To export only what your JavaScript uses:

```php
'groups' => ['validation', 'messages', 'shop::*'],
```

`php artisan lang:missing` tells you when JavaScript uses a key you don't export.

# Files

| Key | Default | What it does |
| --- | --- | --- |
| `path` | `resource_path('js/translations/{locale}.json')` | Where `php artisan lang:js` writes. With `{locale}`, one file per locale; without, one file for all. `.json` or `.js` picks the format. |
| `pretty` | `false` | Indent the files. |

# The endpoint

| Key | Default | What it does |
| --- | --- | --- |
| `route.enabled` | `true` | Register `GET /{prefix}/{locale}.json`. Turn it off to register it yourself with `Route::jsLocalization()`. |
| `route.prefix` | `'js-localization'` | The URL prefix. The Blade script uses it too. |
| `route.middleware` | `[]` | Middleware for the route. It needs no session, so the default is none. |
| `route.name` | `'js-localization.show'` | The route's name. |

See [The endpoint](./endpoint).

# Blade

| Key | Default | What it does |
| --- | --- | --- |
| `blade.auto` | `true` | Load `@javaabu/i18n` on every full HTML page. Turn it off to load it only with `@manifestScripts('js-localization::i18n')` or `@manifestRequire`. |

See [Blade pages](../basic-usage/blade).

# Cache

| Key | Default | What it does |
| --- | --- | --- |
| `cache.enabled` | `true` | Cache exported lines. The cache key includes a fingerprint of every language file, so a changed file is picked up right away. |
| `cache.store` | `null` | The cache store. `null` is the default store. |

# Missing translations

| Key | Default | What it does |
| --- | --- | --- |
| `missing.paths` | `app/`, `resources/views/`, `resources/js/`, `routes/` | What `php artisan lang:missing` scans. |
| `missing.functions` | `__`, `trans`, `trans_choice`, `transChoice`, `@lang`, `@choice`, `Lang::get`, `Lang::choice`, `Lang::has` | The functions whose first argument is a key. |
| `missing.base` | `null` | The locale others are compared with. `null` is `app.fallback_locale`. `lang:translate` translates from it too. |

See [Missing translations](../basic-usage/missing-translations).

# AI translation

| Key | Default | What it does |
| --- | --- | --- |
| `translate.driver` | `null` | The laravel/ai provider (`gemini`, `openai`, `anthropic`, ...). `null` is laravel/ai's default. |
| `translate.model` | `null` | The model. `null` is the provider's default. |
| `translate.batch` | `40` | Lines per request. |
| `translate.glossary` | `[]` | Terms per locale, `['dv' => ['Invoice' => 'އިންވޮއިސް']]`, or the path of a JSON file of that shape. |
| `translate.notes` | Thaana and formal Dhivehi for `dv` | Extra instructions per locale. |
| `translate.report_path` | `storage_path('js-localization/reports')` | Where reports go. |

See [AI translation](../basic-usage/ai-translation).
