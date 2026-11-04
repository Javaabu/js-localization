# Commands

## php artisan lang:missing

```bash
php artisan lang:missing                 # every locale against app.fallback_locale
php artisan lang:missing --locale=dv     # one locale
php artisan lang:missing --json          # for tools
php artisan lang:missing --vendor        # packages' lines too
```

Exits non-zero when anything is missing; add it to CI. It scans `app/`, `resources/views/`, `resources/js/` and `routes/` (`js-localization.missing.paths`) for string-literal keys passed to `__`, `trans`, `trans_choice`, `transChoice`, `@lang`, `@choice`, `Lang::get`, `Lang::choice`, `Lang::has`. Dynamic keys (`__("status.{$s}")`) are skipped. JSON keys (`'Save'`) aren't reported for the base locale, since the key is the text.

## php artisan lang:translate (optional, needs laravel/ai)

```bash
php artisan lang:translate --to=dv --dry-run       # translate and write the report only
php artisan lang:translate --to=dv                 # add missing lines to lang/
php artisan lang:translate --to=dv --group=validation --driver=gemini
```

- Adds only missing lines (`--force` replaces existing ones). Writes `lang/dv.json`, `lang/dv/{group}.php`, and with `--vendor` `lang/vendor/{pkg}/dv/{group}.php`.
- Rejects translations that change `:placeholders`, plural segments or ranges, or HTML tags; they're listed in the report.
- Report: `storage/js-localization/reports/{locale}-{time}.md` and `.json`. Review it and `git diff lang/` before committing. PHP files are rewritten without comments.
- Glossary per locale in `js-localization.translate.glossary`, or `--glossary=file.json`.
- In tests, never call a real model:

```php
use Javaabu\JsLocalization\Translate\TranslationAgent;

TranslationAgent::fake([['translations' => [['key' => 'cart.empty', 'translation' => 'ކާޓު ހުސް']]]]);
```

## php artisan lang:js

Only for apps that serve or import files themselves:

```bash
php artisan lang:js                              # resources/js/translations/{locale}.json
php artisan lang:js public/lang/{locale}.json    # served: configure({ loader: "/lang/{locale}.json" })
php artisan lang:js public/messages.js           # a script that sets window.Lang
php artisan lang:js --stdout                     # what the Vite plugin reads
```
