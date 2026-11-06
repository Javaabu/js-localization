---
title: Introduction
sidebar_position: 1.0
---

# JS Localization

[JS Localization](https://github.com/Javaabu/js-localization) puts your Laravel translations in JavaScript, with [`@javaabu/i18n`](https://github.com/Javaabu/i18n)'s `__()`, `trans()` and `trans_choice()`. They take the same arguments and give the same output as Laravel's:

```blade
<button onclick="alert(__('Saved, :name!', { name: 'އަލީ' }))">{{ __('Save') }}</button>
```

```tsx
const { __, trans_choice } = useTranslation()

return <p>{trans_choice("messages.apples", count)}</p>
```

It is the successor of [Laravel-JS-Localization](https://github.com/Javaabu/Laravel-JS-Localization), and does more than export files:

- **Blade pages need no setup.** Every HTML page gets `__()`, `trans()` and `trans_choice()` on `window`, loading the page's locale from a cached endpoint. Delivered through [javaabu/manifest](https://github.com/Javaabu/manifest), so it works with or without Vite.
- **Inertia and SPA bundles** get the lines from a Vite plugin that manifest's `manifest()` runs, one lazy chunk per locale. In development, editing `lang/` updates the page without a reload.
- **The same lines PHP sees.** Lines are read through Laravel's translation loader: `lang/{locale}/*.php`, nested files like `forum/thread`, `lang/{locale}.json`, packages' JSON lines and namespaced files (`shop::cart`), with your `lang/vendor` overrides.
- **`php artisan lang:js`** writes them as JSON for `@javaabu/i18n`'s loaders, or as a `<script>` that sets `window.Lang`.
- **`php artisan lang:missing`** lists keys your PHP, Blade, JS and TSX use that a locale doesn't translate, and lines your base locale has that others lack. It fails in CI when something is missing.
- **`php artisan lang:translate --to=dv`** fills in missing lines with AI (Gemini, OpenAI, Anthropic and others through [laravel/ai](https://github.com/laravel/ai)), keeping `:placeholders`, plurals and tags intact, and writes a report to review.

| You want to | Read |
| --- | --- |
| Set it up | [Installation & Setup](./installation-and-setup) |
| Translate in Blade pages' scripts | [Blade pages](./basic-usage/blade) |
| Translate in React or Vue with Inertia | [Inertia and bundled apps](./basic-usage/inertia) |
| Move from Laravel-JS-Localization | [Migrating](./migrating) |

Using an AI coding assistant? See [AI assistants](./ai-assistants).
