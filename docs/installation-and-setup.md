---
title: Installation & Setup
sidebar_position: 1.2
---

# Installation

Until it's on Packagist, add the repositories of this package and javaabu/manifest to your app's `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/Javaabu/js-localization" },
    { "type": "vcs", "url": "https://github.com/Javaabu/manifest" }
]
```

```bash
composer require javaabu/js-localization
```

The service provider is discovered automatically. There's no config to publish for the common case.

Then install `@javaabu/i18n` from npm, if your app builds with Vite. `php artisan manifest:check` lists it, along with anything else your packages need:

```bash
php artisan manifest:check
npm install @javaabu/i18n   # github:Javaabu/i18n#main until it's on npm
```

# Blade pages: done

Every full HTML page now loads `@javaabu/i18n`, puts `__()`, `trans()` and `trans_choice()` on `window`, and fetches the page's locale from `/js-localization/{locale}.json`. The locale comes from `<html lang>`, which Laravel's default layout sets:

```blade
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
```

```blade
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        await I18n.ready();

        document.querySelector('#status').textContent = __('Saved');
    });
</script>
```

See [Blade pages](./basic-usage/blade).

If your app builds with Vite, add javaabu/manifest's plugin, so the script is part of your build (otherwise the package's prebuilt copy is served). It also runs this package's [Vite plugin](./basic-usage/vite), so there's nothing else to add:

```js
// vite.config.js
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
  plugins: [laravel({ input: ["resources/js/app.js"], refresh: true }), manifest()],
})
```

# Inertia, React and Vue

With `manifest()` in `vite.config.js` as above, load the lines in your entry:

```ts
// resources/js/app.tsx
import { configure } from "@javaabu/i18n"
import { loader } from "@javaabu/js-localization/translations"

configure({ loader })
```

Use `useTranslation()` from `@javaabu/i18n/react` (or `/vue`) in components. See [Inertia and bundled apps](./basic-usage/inertia).

# Publishing the config

To change what's exported, the endpoint, or the AI settings:

```bash
php artisan vendor:publish --tag=js-localization-config
```

See [Configuration](./advanced-usage/configuration) for every option.
