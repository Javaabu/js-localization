---
name: javaabu-js-localization
description: Translate text in JavaScript with a Laravel app's own translations, through javaabu/js-localization and @javaabu/i18n. Use when adding or changing user-facing text in JS, TS, React, Vue or inline Blade scripts (__(), trans(), trans_choice(), useTranslation), when setting up translations for Blade pages or Inertia apps, editing lang/ files, configuring the Vite plugin manifest() runs for it (manifest({ plugins: { "js-localization": ... } })) or the js-localization config, or running php artisan lang:js, lang:missing or lang:translate.
license: MIT
metadata:
  author: Javaabu
---

# Translations in JavaScript with javaabu/js-localization

The docs ship with the package: `vendor/javaabu/js-localization/llms.txt` lists every page in `vendor/javaabu/js-localization/docs/`. `@javaabu/i18n`'s own docs are in `node_modules/@javaabu/i18n/llms.txt`.

JS uses the **same keys and lines as PHP**. `__('Save')`, `__('messages.welcome', { app })`, `trans_choice('cart.items', n)`, `__('shop::cart.empty')` and `__('forum/thread.title')` behave exactly like Laravel's.

## Where is the code?

| Code | How it gets translations | Read |
| --- | --- | --- |
| Inline `<script>` in a Blade view, or a Blade page's Vite entry | Automatic: helpers on `window`, lines from `/js-localization/{locale}.json` | Below |
| Inertia React/Vue, or a JS bundle | `configure({ loader })` once in the entry, `useTranslation()` in components | Below |
| Adding lines, finding gaps, AI | `lang/` files, `lang:missing`, `lang:translate` | [references/commands.md](references/commands.md) |
| Something doesn't translate | | [references/troubleshooting.md](references/troubleshooting.md) |

## Blade pages

Nothing to set up. Lines load in the background, so wait for them:

```blade
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        await I18n.ready();

        document.querySelector('#status').textContent = __('Saved, :name!', { name: @js($user->name) });
    });
</script>
```

In a Vite entry: `import { __, ready } from "@javaabu/i18n"`, then `await ready()`.

## Inertia, React, Vue

```js
// vite.config.js: manifest() also runs js-localization's Vite plugin. Don't add jsLocalization() too.
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

plugins: [laravel({ input: ["resources/js/app.tsx"], refresh: ["resources/views/**", "routes/**"] }), manifest(), react()]

// options, such as PHP in Docker:
manifest({ plugins: { "js-localization": { command: "./vendor/bin/sail artisan lang:js --stdout" } } })
```

```tsx
// resources/js/app.tsx, once
import { configure } from "@javaabu/i18n"
import { loader } from "@javaabu/js-localization/translations"

configure({ loader })
```

```tsx
// any component
import { useTranslation } from "@javaabu/i18n/react"

export function Cart({ count }: { count: number }) {
  const { __, trans_choice, direction } = useTranslation()

  return (
    <section dir={direction}>
      <h2>{__("Your cart")}</h2>
      <p>{trans_choice("cart.items", count, { count })}</p>
    </section>
  )
}
```

Vue: `app.use(i18n)` from `@javaabu/i18n/vue`, then `{{ __('Save') }}` in templates and `useTranslation()` in `<script setup>`.

## Adding text

1. Pick the key: English text for short UI strings (`lang/en.json` + `lang/dv.json`), or a group key for structured text (`lang/en/cart.php` + `lang/dv/cart.php`).
2. Add the line to **every locale** in `lang/`. Dhivehi is written in Thaana; keep `:placeholders`, `|` plural parts and `{0}`/`[2,*]` ranges exactly.
3. Use it: `__('Your cart')`, `trans_choice('cart.items', count, { count })`.
4. Run `php artisan lang:missing`. It lists keys code uses that a locale lacks (with file:line), lines missing compared with the base locale, and keys JS uses that aren't exported.

No export step: the Vite plugin and the endpoint read `lang/` directly. Development updates the page as you save.

## Rules

- Never hard-code user-facing text in JS, and never build sentences by joining strings: `__('Hello, :name', { name })`.
- Don't add i18next, vue-i18n, Lang.js, a custom `t()`, or a `<script src="messages.js">`.
- Don't commit generated translation files (`resources/js/translations/`) unless the app deliberately serves them.
- Plurals in Dhivehi: Laravel has no plural rule for `dv`, so write ranges: `{1} އެއް ތަކެތި|[2,*] :count ތަކެތި`.
- Everything exported is public. Private text goes in a group listed in `js-localization.except`.
- `@javaabu/i18n`'s result isn't escaped. Escape before using it as HTML (`textContent`, not `innerHTML`).
- In PHP examples and code, follow Javaabu style: `__()` over `@lang`, snake_case variables.
