# JS Localization

[![Tests](https://github.com/Javaabu/js-localization/actions/workflows/tests.yml/badge.svg)](https://github.com/Javaabu/js-localization/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/Javaabu/js-localization/branch/main/graph/badge.svg)](https://codecov.io/gh/Javaabu/js-localization)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/js-localization.svg)](https://packagist.org/packages/javaabu/js-localization)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Your Laravel translations in JavaScript, with [`@javaabu/i18n`](https://github.com/Javaabu/i18n)'s `__()`, `trans()` and `trans_choice()`: the same keys, arguments and output as in PHP. The successor of [Laravel-JS-Localization](https://github.com/Javaabu/Laravel-JS-Localization).

- **Blade pages need no setup**: the helpers are on `window`, with the page's locale loaded from a cached endpoint, through [javaabu/manifest](https://github.com/Javaabu/manifest).
- **Inertia and SPA bundles** load lines from a Vite plugin, one lazy chunk per locale, updated live as you edit `lang/`.
- **`php artisan lang:missing`** lists keys your PHP, Blade, JS and TSX use that a locale lacks. **`php artisan lang:translate --to=dv`** fills them in with AI through [laravel/ai](https://github.com/laravel/ai), keeping placeholders and plurals intact.

```blade
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        await I18n.ready();

        alert(__('Welcome, :name!', { name: 'އަލީ' }));
    });
</script>
```

```tsx
const { __, trans_choice } = useTranslation()

return <p>{trans_choice("messages.apples", count)}</p>
```

## Documentation

You'll find the documentation on [https://docs.javaabu.com/js-localization](https://docs.javaabu.com/js-localization), and in the [`docs`](docs) folder.

AI coding assistants can start from [`llms.txt`](llms.txt). The package ships a [Laravel Boost](https://github.com/laravel/boost) skill and guideline, which Boost finds by itself. See [AI assistants](docs/ai-assistants.md).

## Quick start

```bash
composer require javaabu/js-localization
php artisan manifest:check   # installs @javaabu/i18n from npm
```

Blade pages work now. For Inertia, React or Vue:

```js
// vite.config.js: manifest() also runs this package's Vite plugin
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
  plugins: [laravel({ input: ["resources/js/app.tsx"] }), manifest(), react()],
})
```

```ts
// resources/js/app.tsx
import { configure } from "@javaabu/i18n"
import { loader } from "@javaabu/js-localization/translations"

configure({ loader })
```

| Using | Read |
| --- | --- |
| Blade | [Blade pages](docs/basic-usage/blade.md) |
| Inertia, React, Vue | [Inertia and bundled apps](docs/basic-usage/inertia.md), [Vite plugin](docs/basic-usage/vite.md) |
| Laravel-JS-Localization today | [Migrating](docs/migrating.md) |

## Testing

```bash
composer install && pnpm install   # @javaabu/i18n is linked from ../i18n until it's on npm
composer test                      # PHPUnit
composer lint                      # Pint
pnpm lint && pnpm format:check && pnpm typecheck
pnpm build                         # dist/, committed
pnpm test:coverage                 # Vitest: the Blade script, the Vite plugin, real Vite builds and a dev server
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## License

MIT. See [LICENSE.md](LICENSE.md).
