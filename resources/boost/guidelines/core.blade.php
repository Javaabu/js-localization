{{--
    javaabu/js-localization guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

## javaabu/js-localization

- Laravel translations reach JavaScript through `javaabu/js-localization` and `@javaabu/i18n`. Translate JS text with `__()`, `trans()` and `trans_choice()`, using the same keys as PHP; never hard-code user-facing strings or add another i18n library.
- Blade pages already have the helpers on `window` (wait with `await I18n.ready()`); Inertia apps load lines with `configure({ loader })` from `@javaabu/js-localization/translations` and use `useTranslation()` in components.
- Add every new line to each locale in `lang/` (including `dv`, in Thaana) and check with `php artisan lang:missing`.
- Always activate the `javaabu-js-localization` skill when translating text in JS/TS/React/Vue or Blade scripts, editing `lang/` files, the translation plugin `manifest()` runs in `vite.config.js`, or running `lang:js`, `lang:missing` or `lang:translate`.
