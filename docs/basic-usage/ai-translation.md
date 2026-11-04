---
title: AI translation
sidebar_position: 6
---

`php artisan lang:translate` fills in the lines a locale is missing, with an AI model, through [laravel/ai](https://github.com/laravel/ai):

```bash
php artisan lang:translate --to=dv
```

```
   INFO  Translating 152 line(s) from en to dv...

 4/4 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

  Added ..................................................................... 150
  Rejected .................................................................... 2
  Report ............................. storage/js-localization/reports/dv-2026-11-04-091532.md
```

It never changes a line the locale already has (unless you pass `--force`), and it checks every translation before writing it. Read the report, then review the changes to your language files before you commit them, as you would a person's work.

# Installing laravel/ai

It's optional, and the command explains how to install it when it's missing:

```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

Configure a provider and its key as laravel/ai's [documentation](https://laravel.com/docs/ai-sdk) describes, for example in `.env`:

```dotenv
GEMINI_API_KEY=...
```

# What it translates

The lines the target locale lacks, compared with the source locale (`--from`, which defaults to `app.fallback_locale`):

- JSON lines, added to `lang/{locale}.json`,
- your language files and Laravel's own (`validation`, `auth`, ...), added to `lang/{locale}/{group}.php`, which is created if needed,
- with `--vendor`, packages' lines, added to `lang/vendor/{package}/{locale}/{group}.php`, where Laravel looks for your overrides.

```bash
php artisan lang:translate --to=dv --group=validation      # one file
php artisan lang:translate --to=dv --group=json            # only JSON lines
php artisan lang:translate --to=dv --group='shop::*' --vendor
```

# Checks

A translation is rejected, and not written, when it:

- changes, drops or adds a `:placeholder` (`:name`, `:Name` and `:NAME` are different placeholders),
- has a different number of `|` plural segments, or changes a `{0}` / `[2,*]` range,
- changes HTML tags,
- is empty, or wasn't returned at all.

Rejected lines are listed in the report with the reason, so you can translate them by hand or run the command again.

The model is told to keep placeholders, plurals, tags and entities, to keep the tone of an app's interface, and not to translate brand names, code or URLs.

# The report

Every run writes two files to `storage/js-localization/reports` (`js-localization.translate.report_path`): a Markdown file to read, and JSON with the same data.

```md
# Translations from en to dv

## Added (150)

| Key | en | dv |
| --- | --- | --- |
| messages.farewell | Goodbye | ވަކިވަނީ |
| validation.required | The :attribute field is required. | :attribute ފުރިހަމަކުރުން ލާޒިމް. |

## Rejected (2)

| Key | en | dv | Problem |
| --- | --- | --- | --- |
| messages.apples | {0} No apples\|{1} One apple\|[2,*] :count apples | އާފަލް :count | plural segments |
```

# Glossary

Give the model your translations of the terms that matter, per locale. Only the terms that appear in a batch are sent:

```php
// config/js-localization.php
'translate' => [
    'glossary' => [
        'dv' => [
            'Invoice' => 'އިންވޮއިސް',
            'Council' => 'ކައުންސިލް',
        ],
    ],
],
```

Or keep them in a JSON file of the same shape, either as the config value or per run:

```bash
php artisan lang:translate --to=dv --glossary=lang/glossary.json
```

# Notes for a locale

`js-localization.translate.notes` adds instructions for a locale. The default for Dhivehi asks for Thaana script and the formal Dhivehi of Maldivian government and business websites:

```php
'notes' => [
    'dv' => 'Write in Thaana script, in the formal Dhivehi used on Maldivian government and business websites. ...',
],
```

# Options

| Option | What it does |
| --- | --- |
| `--to=dv` | The locales to fill in. Required; repeat it for more. |
| `--from=en` | The locale to translate from. |
| `--driver=gemini` | The laravel/ai provider: `gemini`, `openai`, `anthropic`, `mistral`, `ollama`, ... Defaults to `js-localization.translate.driver`, then laravel/ai's default. |
| `--model=` | The model. Defaults to `js-localization.translate.model`, then the provider's default. |
| `--group=messages` | Only these groups. Wildcards work; `json` means JSON lines. |
| `--vendor` | Also fill in packages' lines. |
| `--force` | Translate lines the locale already has too, replacing them. |
| `--batch=40` | Lines per request. |
| `--glossary=file.json` | Extra glossary terms. |
| `--dry-run` | Translate and write the report, but don't touch the language files. |

The command exits with an error if a request fails (the report lists those lines), so you can retry.

# Language files it writes

JSON files keep their order, with new lines at the end. PHP files are rewritten with short arrays, which **removes comments** from those files. Commit before you run it, so you can see and undo the changes.

# Testing your own code

To test code that runs the command without calling a real model, fake the agent with laravel/ai's fakes:

```php
use Javaabu\JsLocalization\Translate\TranslationAgent;

TranslationAgent::fake([
    ['translations' => [['key' => 'messages.farewell', 'translation' => 'ވަކިވަނީ']]],
]);

$this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages']])->assertSuccessful();

TranslationAgent::assertPrompted(fn ($prompt) => $prompt->contains('Goodbye'));
```
