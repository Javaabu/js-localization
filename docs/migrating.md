---
title: Migrating from Laravel-JS-Localization
sidebar_position: 1.25
---

This package replaces [Javaabu/Laravel-JS-Localization](https://github.com/Javaabu/Laravel-JS-Localization) (`mariuzzo/laravel-js-localization`). The `php artisan lang:js` command is still there and accepts the old arguments, but the browser side changes from Lang.js to [`@javaabu/i18n`](https://github.com/Javaabu/i18n), and most apps no longer need to generate a file at all.

# 1. Swap the packages

```bash
composer remove mariuzzo/laravel-js-localization
composer require javaabu/js-localization
```

Remove `Mariuzzo\LaravelJsLocalization\LaravelJsLocalizationServiceProvider::class` from `config/app.php` or `bootstrap/providers.php` if you registered it by hand. The new provider is discovered automatically.

# 2. Config

The old `config/localization-js.php` becomes `config/js-localization.php`. Publish it only if you change something:

```bash
php artisan vendor:publish --tag=js-localization-config
rm config/localization-js.php
```

| Old (`localization-js`) | New (`js-localization`) |
| --- | --- |
| `messages => ['validation', 'forum/thread']` (empty: everything) | `groups => ['validation', 'forum/thread']` (default `['*']`). Wildcards work, and `except` leaves files out. |
| `path => public_path('messages.js')` | `path => resource_path('js/translations/{locale}.json')`. Set `public_path('messages.js')` to keep writing the old file name. |

What's exported changed in three ways:

- **Packages' lines** are exported as Laravel loads them (the package's files, with your `lang/vendor` overrides on top), not only the files you published to `lang/vendor`. Leave packages out with `except => ['package::*']`.
- **Laravel's own lines** (`validation`, `auth`, `pagination`, `passwords`) are exported for `en` even without a `lang/en` folder, as in PHP.
- **JSON lines** are at the top of each locale, not in a `strings` group. `@javaabu/i18n` still finds bare keys in a `strings` group, so old files keep working.

# 3. The command

| Old | New |
| --- | --- |
| `php artisan lang:js` | Same. Writes `resources/js/translations/{locale}.json` by default. |
| `php artisan lang:js public/js/messages.js` | Same: a `.js` target writes a script. It sets `window.Lang.messages`, which `@javaabu/i18n` reads, but it no longer bundles Lang.js. |
| `--json` / `-j` | Same, or `--format=json`. |
| `--compress` / `-c` | Accepted; output is always compact. `--pretty` indents it. |
| `--no-lib` / `-nl` | Gone: the library is never bundled now. |
| `--source` / `-s` | Same. |
| `--no-sort` / `-ns` | `--no-sort` (no short form). |

Remove the old build hooks (`webpack-shell-plugin-next`, gulp or Elixir tasks). With Vite, the [Vite plugin](./basic-usage/vite) exports on every build and watches `lang/` while you work. Blade pages don't need a file at all: see below.

# 4. The browser

Lang.js is replaced by `@javaabu/i18n`, whose helpers match Laravel's:

| Lang.js | @javaabu/i18n |
| --- | --- |
| `Lang.get('messages.home')` | `__('messages.home')` or `trans('messages.home')` |
| `Lang.get('messages.welcome', { name: 'Joe' })` | `__('messages.welcome', { name: 'Joe' })` |
| `Lang.get('strings.Save')` | `__('Save')` |
| `Lang.choice('messages.apples', 10, { name: 'Joe' })` | `trans_choice('messages.apples', 10, { name: 'Joe' })` |
| `Lang.has('messages.foo')` | `I18n.has('messages.foo')` (or `has` from the module) |
| `Lang.setLocale('dv')` / `Lang.getLocale()` | `I18n.setLocale('dv')` / `I18n.getLocale()` |
| `Lang.setFallback('en')` | `I18n.setFallback('en')` |

`@javaabu/i18n` reads the locale from `<html lang>`, so you don't need to call `setLocale()` on load. It also follows Laravel exactly where Lang.js didn't: `:Name` and `:NAME` placeholders, `{0}` / `[2,*]` ranges, plural rules per language, and fallback to the fallback locale.

## Blade pages

Remove the old script tag and the generated file:

```diff
- <script src="{{ asset('messages.js') }}"></script>
- <script>Lang.setLocale('{{ app()->getLocale() }}');</script>
```

Every page now gets `__()`, `trans()` and `trans_choice()` on `window` by itself. Code that ran straight away has to wait for the lines, which load in the background now:

```diff
- var title = Lang.get('messages.title');
+ document.addEventListener('DOMContentLoaded', async () => {
+     await I18n.ready();
+     var title = __('messages.title');
+ });
```

See [Blade pages](./basic-usage/blade).

To keep the old setup for now, keep generating the script and add `@javaabu/i18n`'s browser build after it. Calls to `Lang.get()` must still be changed, since Lang.js isn't bundled any more:

```bash
php artisan lang:js public/messages.js
```

```blade
<script src="{{ asset('messages.js') }}"></script>
<script src="https://unpkg.com/@javaabu/i18n/dist/i18n.iife.js"></script>
```

## Bundled apps

```diff
- import Lang from 'lang.js'
- import messages from './messages.json'
- const lang = new Lang({ messages, locale: 'dv' })
- lang.get('messages.title')
+ import { __, configure } from '@javaabu/i18n'
+ import { loader } from '@javaabu/js-localization/translations'
+ configure({ loader })
+ __('messages.title')
```

See [Inertia and bundled apps](./basic-usage/inertia). If you used `@javaabu/js-utilities`' `lang` module, see `@javaabu/i18n`'s [migration guide](https://github.com/Javaabu/i18n/blob/main/docs/migrating-from-js-utilities.md) too.

# 5. Check

```bash
php artisan lang:missing
```

lists keys your PHP, Blade and JavaScript use that a locale lacks, and keys JavaScript uses that you don't export.
