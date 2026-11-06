# Changelog

All notable changes to `javaabu/js-localization` are documented in this file.

## Unreleased

Initial development of v1.0, the successor of [Javaabu/Laravel-JS-Localization](https://github.com/Javaabu/Laravel-JS-Localization):

- Translations are read through Laravel's translation loader: app and framework files, nested groups, JSON lines, and packages' lines with `lang/vendor` overrides.
- `php artisan lang:js` writes them for `@javaabu/i18n`: one JSON file per locale, one for all, or a `window.Lang` script. It keeps Laravel-JS-Localization's target argument and `--json`, `--source`, `--no-sort` and `-c`.
- A cached, ETag-ed endpoint serves one locale at `/js-localization/{locale}.json`, with `Route::jsLocalization()` for custom routes.
- Blade pages get `__()`, `trans()`, `trans_choice()` and `I18n` on `window` with no setup, through javaabu/manifest, with a prebuilt script for apps without Vite.
- A Vite plugin bundles the lines, one lazy chunk per locale, and updates them over HMR as `lang/` changes. It's registered with javaabu/manifest, so apps only add `manifest()` to `vite.config.js`; options go in `manifest({ plugins: { "js-localization": { ... } } })`.
- `php artisan lang:missing` lists keys code uses that a locale lacks, lines missing compared with the base locale, and keys JS uses that aren't exported.
- `php artisan lang:translate` fills in missing lines with laravel/ai (optional), with placeholder, plural and tag checks, a glossary, `--dry-run` and a review report.
- English and Dhivehi command output.
