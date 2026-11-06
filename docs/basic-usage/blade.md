---
title: Blade pages
sidebar_position: 1
---

On every full HTML page your app renders, the package adds one script, `js-localization::i18n`, through [javaabu/manifest](https://github.com/Javaabu/manifest). It:

- puts `__()`, `trans()` and `trans_choice()` on `window`, and the rest of [`@javaabu/i18n`](https://github.com/Javaabu/i18n) on `window.I18n`,
- loads the page's locale (from `<html lang>`) and the fallback locale from the [endpoint](../advanced-usage/endpoint), `/js-localization/{locale}.json`,
- fetches another locale when you call `I18n.setLocale()`.

Nothing to install or configure, and no files to generate.

# Translating in scripts

The script is a module, so it runs after the page has parsed, and the lines arrive a moment later. Wait for both:

```blade
<p id="cart-count"></p>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        await I18n.ready();

        document.querySelector('#cart-count').textContent = trans_choice('cart.items', 3);
    });
</script>
```

Code in your own Vite entries can import the helpers instead. With manifest's Vite plugin in your `vite.config.js`, Vite builds the page's script too, so both share one translator:

```js
// resources/js/cart.js
import { __, ready } from "@javaabu/i18n"

await ready()

document.querySelector("#checkout").textContent = __("Checkout")
```

Without manifest's plugin, the page gets the package's prebuilt script, which has its own copy of `@javaabu/i18n`: use the `window` helpers then.

Everything works as in Laravel: placeholders (`__('Welcome, :name!', { name: 'އަލީ' })`), pluralization (`trans_choice('messages.apples', 5)`), package lines (`__('shop::cart.empty')`) and falling back to the fallback locale. See `@javaabu/i18n`'s [Translating text](https://github.com/Javaabu/i18n/blob/main/docs/basic-usage/translating.md).

# Translating markup

`@javaabu/i18n` can fill in elements marked with `data-trans` attributes:

```blade
<button data-trans="Save"></button>
<input data-trans-attr-placeholder="Search">

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        await I18n.ready();

        I18n.translatePage();
    });
</script>
```

# Switching language without a reload

```js
I18n.setLocale("dv")
await I18n.ready()
I18n.syncDocument() // sets <html lang> and dir="rtl"
```

# Which pages get the script

Full HTML pages: responses with a `</body>`, served as `text/html`. It's left out of:

- JSON, downloads and redirects,
- HTML fragments without `</body>`, such as responses to `fetch()` or htmx,
- Inertia responses, and Inertia's root view (anything with `data-page=`): set up Inertia apps in your bundle as shown in [Inertia and bundled apps](./inertia).

The tag is placed before `</body>`, in order with the other assets manifest injects. Since it goes through Laravel's `Vite`, the dev server, your build, `Vite::useCspNonce()` and integrity hashes all apply.

# Only on some pages

Turn off the automatic script:

```php
// config/js-localization.php
'blade' => [
    'auto' => false,
],
```

and place it where you need it, with manifest's directive:

```blade
@manifestScripts('js-localization::i18n')
```

or require it from a view or a Blade component, and let manifest inject it:

```blade
@manifestRequire('js-localization::i18n')
```

```php
#[RequiresAssets('js-localization::i18n')]
class DatePicker extends Component {}
```

# Without Vite

Apps that don't build with Vite get the package's prebuilt script, with `@javaabu/i18n` bundled in, from `/vendor/manifest/js-localization/...`. Publish it as part of your deploy so your web server serves it directly:

```bash
php artisan vendor:publish --tag=manifest-dist-js-localization --force
```

See manifest's [Prebuilt assets](https://github.com/Javaabu/manifest/blob/main/docs/advanced-usage/prebuilt-assets.md).

# Development

With Vite's dev server running and `manifest()` in `vite.config.js` (which runs the [Vite plugin](./vite)), editing a language file updates the lines on the page without a reload.
