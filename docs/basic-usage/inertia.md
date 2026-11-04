---
title: Inertia and bundled apps
sidebar_position: 2
---

In an Inertia app, or any app whose JavaScript is a Vite bundle, load the lines in your entry and translate in components with `@javaabu/i18n`. There are three ways to get the lines to the browser. The first suits most apps.

# From the Vite plugin (recommended)

Add the [Vite plugin](./vite) next to manifest's:

```js
// vite.config.js
import react from "@vitejs/plugin-react"
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"
import jsLocalization from "./vendor/javaabu/js-localization/dist/vite.js"

export default defineConfig({
  plugins: [
    laravel({ input: ["resources/js/app.tsx"], refresh: ["resources/views/**", "routes/**"] }),
    manifest(),
    jsLocalization(),
    react(),
  ],
})
```

```tsx
// resources/js/app.tsx
import { createInertiaApp } from "@inertiajs/react"
import { configure } from "@javaabu/i18n"
import { resolvePage } from "@javaabu/manifest/inertia"
import { loader } from "@javaabu/js-localization/translations"
import { createRoot } from "react-dom/client"

configure({ loader })

createInertiaApp({
  resolve: (name) => resolvePage(name, import.meta.glob("./pages/**/*.tsx")),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

Every locale is its own chunk, fetched only when the page uses it. The locale comes from `<html lang>` in your root view. In development, editing `lang/` updates the page without a reload.

`@javaabu/js-localization/translations` resolves through manifest's plugin to this package's `resources/js/translations.ts`. For TypeScript, run `php artisan manifest:types` once, which adds the path to your `tsconfig.json`.

# React

```tsx
import { useTranslation } from "@javaabu/i18n/react"

export default function Cart({ count }: { count: number }) {
  const { __, trans_choice, direction } = useTranslation()

  return (
    <section dir={direction}>
      <h1>{__("shop::cart.title")}</h1>
      <p>{trans_choice("messages.apples", count)}</p>
      <button>{__("Checkout")}</button>
    </section>
  )
}
```

Components re-render when the locale or the lines change.

# Vue

```ts
// resources/js/app.ts
import { createInertiaApp } from "@inertiajs/vue3"
import { configure } from "@javaabu/i18n"
import { i18n } from "@javaabu/i18n/vue"
import { resolvePage } from "@javaabu/manifest/inertia"
import { loader } from "@javaabu/js-localization/translations"
import { createApp, h } from "vue"

configure({ loader })

createInertiaApp({
  resolve: (name) => resolvePage(name, import.meta.glob("./pages/**/*.vue")),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) }).use(plugin).use(i18n).mount(el)
  },
})
```

```vue
<template>
  <button>{{ __("Save") }}</button>
</template>
```

See `@javaabu/i18n`'s [React](https://github.com/Javaabu/i18n/blob/main/docs/frameworks/react.md) and [Vue](https://github.com/Javaabu/i18n/blob/main/docs/frameworks/vue.md) pages for the rest of the API.

# From the endpoint, without the Vite plugin

The package serves each locale at `/js-localization/{locale}.json`. Point `@javaabu/i18n` at it:

```ts
import { configure } from "@javaabu/i18n"

configure({ loader: "/js-localization/{locale}.json" })
```

The lines aren't in your bundle, so they're always current, at the cost of one request per locale. See [The endpoint](../advanced-usage/endpoint).

# As shared props

To have the lines with the first render (useful with server-side rendering), share them from your Inertia middleware:

```php
use Javaabu\JsLocalization\Facades\JsLocalization;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'translations' => fn () => JsLocalization::forLocale(app()->getLocale()),
        ];
    }
}
```

```tsx
import { load, setLocale } from "@javaabu/i18n"
import { usePage } from "@inertiajs/react"
import { useEffect } from "react"

const { locale, translations } = usePage().props

useEffect(() => {
  load(translations, { locale })
  setLocale(locale)
}, [locale, translations])
```

This sends the lines with every Inertia response, so prefer the other two ways when you have many lines.

# Server-side rendering

Give each request its own translator, as `@javaabu/i18n`'s [server rendering](https://github.com/Javaabu/i18n/blob/main/docs/frameworks/react.md#server-rendering) section shows, with the lines from shared props or from `loader(locale)`.
