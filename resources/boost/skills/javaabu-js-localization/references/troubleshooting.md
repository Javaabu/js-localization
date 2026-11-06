# Troubleshooting

| Symptom | Cause and fix |
| --- | --- |
| `__('messages.title')` returns the key in the browser but not in PHP | The group isn't exported: check `js-localization.groups` / `except`. `php artisan lang:missing` lists these under "not exported". |
| `__` is undefined in an inline Blade script | The script runs before the module loads. Wrap it in `document.addEventListener('DOMContentLoaded', async () => { await I18n.ready(); ... })`. |
| Lines show as keys right after load, then translate | Code ran before the lines arrived. `await I18n.ready()` (or `ready()` from `@javaabu/i18n`) first. React and Vue components re-render by themselves. |
| No `data-js-localization` script on a page | It's left out of Inertia root views (`data-page=`), fragments without `</body>`, JSON, and when `js-localization.blade.auto` is false. Place it with `@manifestScripts('js-localization::i18n')`. |
| The wrong language | The locale comes from `<html lang>`. Set it in the layout: `lang="{{ str_replace('_', '-', app()->getLocale()) }}"`. |
| `Failed to resolve import "virtual:js-localization"` / `@javaabu/js-localization/translations` | Add `manifest()` to `vite.config.js` (it runs this package's plugin), run Vite from the app root, and check `manifest({ plugins: { "js-localization": false } })` isn't turning it off. |
| manifest warns that `javaabu-js-localization` runs twice | `vite.config.js` still has `jsLocalization()` from before manifest ran package plugins. Remove that line and its import. |
| `"php artisan lang:js --stdout" failed` in Vite | PHP isn't on the PATH Vite sees, or runs in Docker: `manifest({ plugins: { "js-localization": { command: "./vendor/bin/sail artisan lang:js --stdout" } } })`. |
| Editing `lang/` reloads the page instead of updating it | laravel-vite-plugin's `refresh: true` watches `lang/`. List refresh paths without it. |
| `Cannot find module '@javaabu/i18n'` | Install it: `php artisan manifest:check` prints the command. |
| A Dhivehi plural always picks the first form | Laravel has no plural rule for `dv`. Use ranges: `{1} …|[2,*] …`. |
| `lang:translate` says it needs laravel/ai | `composer require laravel/ai`, then configure a provider key. |
