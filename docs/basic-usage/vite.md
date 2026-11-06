---
title: Vite plugin
sidebar_position: 3
---

The Vite plugin bundles your translations into your app's build and keeps them current while you work, so you never run an export by hand. The package registers it with [javaabu/manifest](https://github.com/Javaabu/manifest), so manifest's plugin runs it, and there's nothing to add for it:

```js
// vite.config.js
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
  plugins: [laravel({ input: ["resources/js/app.ts"], refresh: ["resources/views/**", "routes/**"] }), manifest()],
})
```

Run Vite from your app's root (as `npm run dev` and `npm run build` do): manifest finds packages' plugins from there.

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

Pass options through manifest, under the package's name:

```js
manifest({
  plugins: {
    "js-localization": {
      command: "php artisan lang:js --stdout",
      watch: ["lang", "config/js-localization.php"],
      write: false,
      hmr: true,
    },
  },
})
```

| Option | Default | What it does |
| --- | --- | --- |
| `command` | `"php artisan lang:js --stdout"` | Prints every locale as JSON. Run in the Vite root. |
| `watch` | `["lang", "config/js-localization.php"]` | Files and folders, relative to the Vite root, whose changes export again in development. Add a package's `lang` folder if you work on it here. |
| `write` | `false` | Also run `php artisan lang:js` (or this command) on start and on every change, so the files at `js-localization.path` stay current, for code that imports them directly. |
| `hmr` | `true` | Send changed lines to the page. `false` reloads the page instead. |

To turn the plugin off, for example in an app that only uses Blade pages and the endpoint:

```js
manifest({ plugins: { "js-localization": false } })
```

# laravel-vite-plugin's refresh

With `refresh: true`, laravel-vite-plugin reloads the page whenever a file in `lang/` changes, before this plugin's update arrives. To keep the page's state while editing translations, list your refresh paths without `lang/`:

```js
laravel({ input: ["resources/js/app.ts"], refresh: ["resources/views/**", "routes/**", "app/Livewire/**"] })
```

# Docker and Sail

When PHP runs in a container, tell the plugin how to run artisan:

```js
manifest({
  discover: "./vendor/bin/sail artisan manifest:discover",
  plugins: { "js-localization": { command: "./vendor/bin/sail artisan lang:js --stdout" } },
})
```

```js
manifest({
  discover: "docker compose exec -T app php artisan manifest:discover",
  plugins: { "js-localization": { command: "docker compose exec -T app php artisan lang:js --stdout" } },
})
```

# Building without PHP

If your build machine has no PHP, export on a machine that does, and point the command at the file:

```bash
php artisan lang:js storage/app/translations.json
```

```js
manifest({ discover: false, plugins: { "js-localization": { command: "cat storage/app/translations.json" } } })
```

With `discover: false`, commit or copy `bootstrap/cache/manifest.json` too (see manifest's [Vite](https://github.com/Javaabu/manifest/blob/main/docs/basic-usage/vite.md) page).

# Adding the plugin yourself

Apps set up before manifest ran packages' plugins have this in `vite.config.js`:

```js
import jsLocalization from "./vendor/javaabu/js-localization/dist/vite.js"

plugins: [laravel({ input: ["resources/js/app.ts"] }), manifest(), jsLocalization()]
```

It still works, but the plugin then runs twice, and manifest warns that `javaabu-js-localization` is in `vite.config.js` too. Remove the import and `jsLocalization()` to fix it, and move any options into `manifest({ plugins: { "js-localization": { ... } } })`.

To keep adding it by hand (to pass options manifest can't, such as functions), turn off the registered one:

```js
plugins: [
  laravel({ input: ["resources/js/app.ts"] }),
  manifest({ plugins: { "js-localization": false } }),
  jsLocalization({ command: "php artisan lang:js --stdout" }),
]
```

The plugin object `jsLocalization()` returns also has `translations()`, the lines it last exported.
