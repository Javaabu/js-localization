---
title: AI assistants
sidebar_position: 1.35
---

The docs are plain Markdown, written for people and AI coding assistants alike. The package ships them, so they always match the version you have installed.

| File | What |
| --- | --- |
| `vendor/javaabu/js-localization/llms.txt` | The rules for using the package, and every page |
| `vendor/javaabu/js-localization/docs/` | The pages |
| `vendor/javaabu/js-localization/resources/boost/` | The Laravel Boost skill and guideline |

# Laravel Boost

[Laravel Boost](https://github.com/laravel/boost) finds the skill and guideline in the Composer package by itself. Run this after installing or updating:

```bash
php artisan boost:update
```

Choose `javaabu/js-localization` if Boost asks which third-party packages to use.

The skill (`javaabu-js-localization`) covers translating in Blade pages' scripts and in Inertia React and Vue apps, the Vite plugin, and the `lang:js`, `lang:missing` and `lang:translate` commands.

# Other assistants

Point your assistant at the docs from the project's instructions file (`AGENTS.md`, `CLAUDE.md`, `.cursor/rules`, `.github/copilot-instructions.md`):

```md
## Translations in JavaScript

Laravel translations reach JavaScript through javaabu/js-localization and @javaabu/i18n. Before translating text in JS, TS, React or Vue, read vendor/javaabu/js-localization/llms.txt.
```

Or copy `vendor/javaabu/js-localization/resources/boost/skills/javaabu-js-localization` into your assistant's skills folder, such as `.claude/skills/`.
