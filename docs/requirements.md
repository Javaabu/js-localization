---
title: Requirements
sidebar_position: 1.1
---

This package requires:

- PHP 8.4 or higher
- Laravel 12 or 13
- [javaabu/manifest](https://github.com/Javaabu/manifest), installed with it

In the browser, [`@javaabu/i18n`](https://github.com/Javaabu/i18n). Apps that build with Vite install it from npm; apps that don't get it bundled in the package's prebuilt script.

For the Vite plugin:

- Node.js 20.19 or higher
- Vite 6, 7 or 8, with [laravel-vite-plugin](https://github.com/laravel/vite-plugin)

For `php artisan lang:translate`: [laravel/ai](https://github.com/laravel/ai) 1.x, configured with a provider. It's optional; everything else works without it.
