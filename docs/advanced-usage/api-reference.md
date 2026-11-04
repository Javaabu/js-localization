---
title: API reference
sidebar_position: 3
---

# PHP

## `JsLocalization` facade

`Javaabu\JsLocalization\Facades\JsLocalization`, a facade for the `Javaabu\JsLocalization\JsLocalization` singleton.

| Method | Returns |
| --- | --- |
| `locales(): array` | The exported locales: `js-localization.locales`, or every locale the app has files for plus `app.locale` and `app.fallback_locale`. Sorted. |
| `hasLocale(string $locale): bool` | Whether a locale is exported. |
| `forLocale(string $locale): array` | One locale's exported lines, as `@javaabu/i18n` loads them: JSON lines at the top, then groups by name (`'messages'`, `'forum/thread'`, `'shop::cart'`). Cached. |
| `all(?array $locales = null): array` | Every locale's lines, keyed by locale. |
| `url(?string $locale = null): string` | The endpoint's path for a locale, or its template: `/js-localization/{locale}.json`. |
| `fingerprint(): string` | A hash of every language file and the export settings. It's the endpoint's ETag. |
| `flush(): void` | Forget lines collected in this process, after language files change in a long-running process (Octane, queue workers). |
| `sourcePaths(): array` | Every folder language files are read from. |
| `groups(string $locale, bool $exported_only = false): array` | The groups a locale has files for. |
| `exports(string $group): bool` | Whether `groups` and `except` export a group. |
| `isGroup(string $group): bool` | Whether any locale has a file for a group. |
| `jsonLines(string $locale): array` | A locale's JSON lines, packages' included. |
| `loadGroup(string $locale, string $group): array` | One group's lines, with `lang/vendor` overrides for packages. |
| `flatLines(string $locale, bool $namespaced = true): array` | Every line, exported or not, by the key you pass to `__()`: `'messages.nested.title' => '...'`. |

```php
use Javaabu\JsLocalization\Facades\JsLocalization;

JsLocalization::forLocale('dv')['messages']['welcome']; // ':app އަށް މަރުޙަބާ'
JsLocalization::url('dv');                              // '/js-localization/dv.json'
```

## Route macro

`Route::jsLocalization(?string $prefix = null): Illuminate\Routing\Route` registers `GET {prefix}/{locale}.json`, named `js-localization.show` (`route.name`). See [The endpoint](./endpoint).

## Commands

| Command | Page |
| --- | --- |
| `php artisan lang:js [target] [--locale=] [--format=] [--pretty] [--stdout] [--no-sort] [--source=]` | [Exporting translations](../basic-usage/exporting) |
| `php artisan lang:missing [--locale=] [--base=] [--path=] [--vendor] [--json]` | [Missing translations](../basic-usage/missing-translations) |
| `php artisan lang:translate --to= [--from=] [--driver=] [--model=] [--group=] [--vendor] [--force] [--batch=] [--glossary=] [--dry-run]` | [AI translation](../basic-usage/ai-translation) |

## Manifest asset

| Asset | What it is |
| --- | --- |
| `js-localization::i18n` | The Blade script (`resources/js/blade.ts`). Its tag carries the endpoint in `data-js-localization`. |

The package also registers `resources/js` as `@javaabu/js-localization` and asks apps to install `@javaabu/i18n` (`php artisan manifest:check`).

## Publishing

| Tag | What |
| --- | --- |
| `js-localization-config` | `config/js-localization.php` |
| `js-localization-translations` | The package's own command output, to `lang/vendor/js-localization` |
| `manifest-dist-js-localization` | The prebuilt Blade script, for apps without Vite (manifest's tag) |

## AI translation classes

| Class | What it is |
| --- | --- |
| `Javaabu\JsLocalization\Translate\TranslationAgent` | The laravel/ai agent. `TranslationAgent::fake()` and its assertions work as for any laravel/ai agent. |
| `Javaabu\JsLocalization\Translate\AiTranslator` | Sends a batch and returns translations by key. `available()` says whether laravel/ai is installed. |
| `Javaabu\JsLocalization\Translate\LineValidator` | `problem($source, $translation)`: `null`, or why a translation breaks placeholders, plurals or tags. |

# JavaScript

## `@javaabu/js-localization/translations`

Needs the [Vite plugin](../basic-usage/vite).

| Export | What it is |
| --- | --- |
| `loader(locale): Promise<Messages>` | `@javaabu/i18n`'s `loader` option: imports a locale's chunk. Unknown locales resolve to `{}`. Also the default export. |
| `locales: string[]` | Every exported locale. |

Importing it also listens for the plugin's updates in development.

## `@javaabu/js-localization`

| Export | What it is |
| --- | --- |
| `useEndpoint(url?)` | `configure({ loader: url })`, with the page's endpoint by default. |
| `endpoint(root = document): string` | The page's endpoint: `<meta name="js-localization">`, then the Blade script's `data-js-localization`, then `/js-localization/{locale}.json`. |
| `listenForUpdates(import.meta.hot, translator?)` | Applies the Vite plugin's updates to a translator (the shared one by default). |
| `applyUpdate({ locale, messages }, translator?)` | Merges one update. |
| `DEFAULT_ENDPOINT`, `ENDPOINT_ATTRIBUTE`, `UPDATE_EVENT` | `"/js-localization/{locale}.json"`, `"data-js-localization"`, `"js-localization:update"` |

## Vite plugin

`vendor/javaabu/js-localization/dist/vite.js`. The default export is `jsLocalization(options)`; see [Vite plugin](../basic-usage/vite) for the options. It also exports `exportTranslations(root, command)`, `VIRTUAL_MODULE_ID` (`"virtual:js-localization"`) and `UPDATE_EVENT`.
