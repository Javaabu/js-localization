---
title: Missing translations
sidebar_position: 5
---

`php artisan lang:missing` finds what each locale is missing:

```bash
php artisan lang:missing
```

```
   WARN  dv is missing 2 key(s) the code uses:

+-----------------+-----------------------------------------------+
| Key             | Used in                                       |
+-----------------+-----------------------------------------------+
| Checkout        | resources/js/components/Cart.tsx:14           |
| messages.welcom | app/Http/Controllers/OrdersController.php:15  |
+-----------------+-----------------------------------------------+

   WARN  dv is missing 1 line(s) that en has:

+-------------------+---------+
| Key               | In en   |
+-------------------+---------+
| messages.farewell | Goodbye |
+-------------------+---------+

   ERROR  3 translations are missing.
```

It checks three things:

1. **Keys the code uses that a locale doesn't translate.** It scans PHP, Blade, JS, TS, TSX and Vue files for keys passed as string literals to `__()`, `trans()`, `trans_choice()`, `transChoice()`, `@lang`, `@choice`, `Lang::get()`, `Lang::choice()` and `Lang::has()`. A typo like `messages.welcom` shows up in every locale, including the base locale. A JSON key such as `'Checkout'` is its own text in the base locale, so it's only reported for the others.
2. **Lines the base locale has that a locale lacks.** Your app's lines and Laravel's own (`validation`, `auth`, ...). Add `--vendor` to compare packages' lines too.
3. **Keys JavaScript uses that aren't exported**, because `js-localization.groups` or `except` leave out their group, or `json` is off. Those keys would show as themselves in the browser.

Keys built at runtime (`__("orders.status.{$status}")`, `__('orders.' . $status)`, ``__(`orders.${status}`)``) can't be checked, and are skipped.

The command exits with an error when anything is missing, so it can run in CI:

```yaml
- run: php artisan lang:missing --locale=dv
```

# Options

| Option | What it does |
| --- | --- |
| `--locale=dv` | Only check these locales. Defaults to every locale except the base. |
| `--base=en` | The locale the others are compared with. Defaults to `js-localization.missing.base`, then `app.fallback_locale`. |
| `--path=resources/js` | Scan these files and folders instead of `js-localization.missing.paths`. |
| `--vendor` | Also compare packages' lines (`shop::cart.empty`). |
| `--json` | Print the report as JSON, for other tools. |

```json
{
    "base": "en",
    "locales": ["dv"],
    "used": { "dv": { "Checkout": ["resources/js/components/Cart.tsx:14"] } },
    "lines": { "dv": { "messages.farewell": "Goodbye" } },
    "not_exported": {}
}
```

# What it scans

By default `app/`, `resources/views/`, `resources/js/` and `routes/`, skipping `node_modules`. Change the folders and the functions in [the config](../advanced-usage/configuration#missing-translations):

```php
'missing' => [
    'paths' => [app_path(), resource_path('views'), resource_path('js'), base_path('routes'), base_path('modules')],
    'functions' => ['__', 'trans', 'trans_choice', 'transChoice', '@lang', '@choice', 'Lang::get', 'Lang::choice', 'Lang::has', 't'],
],
```

Fill in what's missing by hand, or with [AI translation](./ai-translation).
