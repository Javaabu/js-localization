# js-localization: notes for AI coding agents

`javaabu/js-localization` puts a Laravel app's translations in JavaScript for `@javaabu/i18n`: an exporter that reads lines through Laravel's translation loader, `lang:js`, a cached JSON endpoint, a Blade script delivered through `javaabu/manifest`, a Vite plugin (virtual modules + HMR), `lang:missing`, and `lang:translate` (optional `laravel/ai`). It's a Composer package that also ships JS (`dist/`, built from `js/src` and `resources/js`).

## Layout

| Path | What |
| --- | --- |
| `src/JsLocalization.php` | Collects lines through the translation loader: locales, groups, JSON, namespaces, filters, cache, fingerprint |
| `src/TranslationWriter.php` | JSON / `window.Lang` script output for `lang:js` |
| `src/Commands/` | `lang:js`, `lang:missing`, `lang:translate` |
| `src/Http/` | The endpoint's controller, and the middleware that requires the Blade script (runs inside manifest's injection) |
| `src/Missing/` | The key scanner and the missing-lines report |
| `src/Translate/` | laravel/ai agent, validator, language file writer, report. Only `TranslationAgent` references laravel/ai classes |
| `resources/js/blade.ts` | The Blade entry, `js-localization::i18n` |
| `resources/js/index.ts`, `translations.ts` | `@javaabu/js-localization` and `@javaabu/js-localization/translations` (through manifest's alias) |
| `js/src/vite.ts` | The Vite plugin |
| `dist/` | Built JS, **committed**: `vite.js` (plugin) and `browser/` (prebuilt Blade entry with i18n bundled). CI fails if it's stale |
| `tests/Fixtures/app` | The Laravel app tests run in (Testbench base path): `lang/` in en and dv, a package in `vendor/acme/shop`, code for `lang:missing` |
| `tests/Fixtures/parity.json` | Cases PHP's `__()` and `@javaabu/i18n` (loaded with `lang:js` output) must both pass |
| `tests/js` | Vitest: Blade entry, plugin unit tests, `e2e/` real Vite builds and a dev server |
| `docs`, `llms.txt` | Docusaurus pages and the index for assistants |
| `resources/boost` | Laravel Boost guideline and skill |

## Rules

- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, `#[Test]` methods named `it_…`, early returns, no `else`.
- Read lines through the loader (`JsLocalization`), never by walking `lang/` yourself, so JS sees what PHP sees.
- `laravel/ai` is optional: never reference its classes outside `src/Translate/TranslationAgent.php` and `AiTranslator::translate()`. Tests use `TranslationAgent::fake()`; the `ai` test group is excluded in the CI job without it.
- `@javaabu/i18n` isn't on npm yet: it's linked from `../i18n` (`link:../i18n`), and CI clones Javaabu/i18n at a pinned commit (`I18N_REF` in the workflow). Bump both together; rebuild `dist/`.
- After changing `js/src` or `resources/js`, run `pnpm build` and commit `dist/`.
- Every user-facing string goes in `lang/en` and `lang/dv`; tests use Thaana for user-facing behaviour.
- Every change needs tests, its docs page (every config key and command option is checked by `tests/Unit/DocsTest.php`), `llms.txt` if a page is added, the Boost skill if usage changes, and a line in `CHANGELOG.md`.

## Commands

```bash
composer install && pnpm install
composer lint && composer test
pnpm lint && pnpm format:check && pnpm typecheck
pnpm build
pnpm test:coverage                       # needs PHP: runs vendor/bin/testbench
vendor/bin/testbench lang:js --stdout    # artisan in the fixture app
```
