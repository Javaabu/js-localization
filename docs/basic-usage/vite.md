---
title: Vite plugin
sidebar_position: 3
---

The Vite plugin bundles your translations into your app's build and keeps them current while you work, so you never run an export by hand. It ships inside the Composer package, at `vendor/javaabu/js-localization/dist/vite.js`. Use it next to [javaabu/manifest](https://github.com/Javaabu/manifest)'s plugin:

```js
// vite.config.js
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"
import jsLocalization from "./vendor/javaabu/js-localization/dist/vite.js"

export default defineConfig({
  plugins: [
    laravel({ input: ["resources/js/app.ts"], refresh: ["resources/views/**", "routes/**"] }),
    manifest(),
    jsLocalization(),
  ],
})
```

# What it does

- **Exports when Vite starts and on every build.** It runs `php artisan lang:js --stdout`, so `npm run build` always ships the lines your PHP has.
- **Bundles one chunk per locale.** `@javaabu/js-localization/translations` exports a `loader` for `@javaabu/i18n`, which imports a locale's chunk the first time the page uses it:

  ```ts
  import { configure } from "@javaabu/i18n"
  import { loader, locales } from "@javaabu/js-localization/translations"

  configure({ loader })
  console.log(locales) // ["dv", "en"]
  ```

- **Updates the page as you edit `lang/`.** In development it watches `lang/` and `config/js-localization.php`, exports again, and sends the changed locales to the page. `@javaabu/i18n` merges them and React and Vue components re-render: no reload, no lost state. Blade pages' script listens too. Adding or removing a locale reloads the page.

If the export fails while the dev server is running (a syntax error in a language file, say), the plugin logs the error and keeps the last good lines. During a build, it stops the build.

# Options

```js
jsLocalization({
  command: "php artisan lang:js --stdout",
  watch: ["lang", "config/js-localization.php"],
  write: false,
  hmr: true,
})
```

| Option | Default | What it does |
| --- | --- | --- |
| `command` | `"php artisan lang:js --stdout"` | Prints every locale as JSON. Run in the Vite root. |
| `watch` | `["lang", "config/js-localization.php"]` | Files and folders, relative to the Vite root, whose changes export again in development. Add a package's `lang` folder if you work on it here. |
| `write` | `false` | Also run `php artisan lang:js` (or this command) on start and on every change, so the files at `js-localization.path` stay current, for code that imports them directly. |
| `hmr` | `true` | Send changed lines to the page. `false` reloads the page instead. |

The plugin object also has `translations()`, which returns the lines it last exported.

# laravel-vite-plugin's refresh

With `refresh: true`, laravel-vite-plugin reloads the page whenever a file in `lang/` changes, before this plugin's update arrives. To keep the page's state while editing translations, list your refresh paths without `lang/`:

```js
laravel({ input: ["resources/js/app.ts"], refresh: ["resources/views/**", "routes/**", "app/Livewire/**"] })
```

# Docker and Sail

When PHP runs in a container, tell the plugin how to run artisan:

```js
jsLocalization({ command: "./vendor/bin/sail artisan lang:js --stdout" })
jsLocalization({ command: "docker compose exec -T app php artisan lang:js --stdout" })
```

# Building without PHP

If your build machine has no PHP, export on a machine that does, and point the command at the file:

```bash
php artisan lang:js storage/app/translations.json
```

```js
jsLocalization({ command: "cat storage/app/translations.json" })
```

# Why a second plugin

javaabu/manifest can't run plugins that packages ship yet ([Javaabu/manifest#14](https://github.com/Javaabu/manifest/issues/14)). Once it can, this package will register its plugin with manifest, and `manifest()` alone will be enough. Your `vite.config.js` will keep working either way.
