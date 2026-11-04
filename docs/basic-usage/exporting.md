---
title: Exporting translations
sidebar_position: 4
---

`php artisan lang:js` writes your translations to files, in the shapes `@javaabu/i18n` loads. You only need it for files you serve or import yourself: Blade pages use the [endpoint](../advanced-usage/endpoint), and bundles use the [Vite plugin](./vite).

```bash
php artisan lang:js
```

```
  Written ...................................... resources/js/translations/dv.json
  Written ...................................... resources/js/translations/en.json

   INFO  Exported 2 locales.
```

# What's exported

Exactly what `__()` sees in PHP, read through Laravel's translation loader:

| Source | Key in JavaScript |
| --- | --- |
| `lang/dv.json` and packages' JSON lines | `__('Save')` |
| `lang/dv/messages.php` | `__('messages.welcome')` |
| `lang/dv/forum/thread.php` | `__('forum/thread.title')` |
| A package's `lang/dv/cart.php`, with your `lang/vendor/shop/dv/cart.php` on top | `__('shop::cart.empty')` |
| Laravel's own `validation`, `auth`, `pagination` and `passwords` lines | `__('validation.required', { attribute: 'name' })` |

Each locale file looks like this:

```json
{
  "Save": "ސޭވްކުރޭ",
  "messages": { "welcome": ":app އަށް މަރުޙަބާ" },
  "forum/thread": { "title": "ތްރެޑް" },
  "shop::cart": { "empty": "ތިޔަ ކާޓު ހުސް" }
}
```

Lines are sorted by key, so the files diff cleanly. Everything you export is public: leave out anything private with [`groups` and `except`](../advanced-usage/configuration#what-to-export).

# Where and how

The target decides both:

| Target | Writes |
| --- | --- |
| `resources/js/translations/{locale}.json` (the default) | One JSON file per locale |
| `public/lang/{locale}.json` | The same, served by your web server: `configure({ loader: "/lang/{locale}.json" })` |
| `public/messages.json` | One JSON file with every locale: `{ "dv": { … }, "en": { … } }` |
| `public/messages.js` | A script that adds every locale to `window.Lang`, which `@javaabu/i18n` picks up |

```bash
php artisan lang:js public/lang/{locale}.json
```

Change the default with `js-localization.path`.

# Options

| Option | What it does |
| --- | --- |
| `target` | Where to write (see above). Defaults to `js-localization.path`. |
| `--locale=dv` | Only these locales. Repeat it for more. |
| `--format=json` / `--format=js` | The format, instead of the target's extension. |
| `--pretty` | Indent the output. |
| `--stdout` | Print every locale as JSON instead of writing files. The Vite plugin uses this. |
| `--no-sort` | Keep lines in file order. |
| `--source=themes/default/lang` | Read the app's language files from another folder. Packages' lines are still included. |
| `--json` (`-j`), `--compress` (`-c`) | Kept from Laravel-JS-Localization: `--json` is `--format=json`, and output is always compact. |

# Loading the files

```js
import { configure } from "@javaabu/i18n"

// bundled, one chunk per locale
configure({ loader: (locale) => import(`./translations/${locale}.json`) })

// fetched
configure({ loader: "/lang/{locale}.json" })
```

```html
<!-- without a bundler -->
<script src="/messages.js"></script>
<script src="https://unpkg.com/@javaabu/i18n/dist/i18n.iife.js"></script>
```

Add generated folders to `.gitignore`, and run the command as part of your build or deploy:

```json
"scripts": {
    "build": "php artisan lang:js && vite build"
}
```
