---
title: The endpoint
sidebar_position: 2
---

The package serves each locale's lines as JSON:

```
GET /js-localization/dv.json
```

```json
{"Save":"ސޭވްކުރޭ","messages":{"welcome":":app އަށް މަރުޙަބާ"},"shop::cart":{"empty":"ތިޔަ ކާޓު ހުސް"}}
```

The Blade script loads lines from it, and any `@javaabu/i18n` setup can: `configure({ loader: "/js-localization/{locale}.json" })`. It returns the same lines as [`php artisan lang:js`](../basic-usage/exporting), for locales your app has. Other locales get a 404, which `@javaabu/i18n` treats as "fall back".

# Caching

- On the server, exported lines are cached (`js-localization.cache`) under a fingerprint of every language file and the export settings, so they're only collected again after a file changes. Nothing to clear on deploy.
- In the browser, responses have an `ETag` and `Cache-Control: public, no-cache`. Browsers and CDNs keep the file and check it's current with each page load; while nothing changed, the answer is an empty `304 Not Modified`.

# Changing the URL or middleware

```php
// config/js-localization.php
'route' => [
    'enabled' => true,
    'prefix' => 'assets/lang',        // GET /assets/lang/dv.json
    'middleware' => ['throttle:120,1'],
],
```

The Blade script follows `route.prefix`.

# Registering it yourself

To put the route in a group of your own, turn off the automatic one and use the macro:

```php
// config/js-localization.php
'route' => ['enabled' => false, 'prefix' => 'i18n'],
```

```php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::middleware('cache.headers:public;max_age=60;etag')->group(function () {
    Route::jsLocalization();            // uses route.prefix: /i18n/{locale}.json
});

Route::jsLocalization('admin/lang')->middleware('auth');   // or a prefix of its own
```

The Blade script points at `route.prefix`, so keep it the same as the prefix you register, or set the URL on the page yourself:

```blade
<meta name="js-localization" content="/admin/lang/{locale}.json">
```

The route needs no session, CSRF or authentication. Only add `auth` when the lines themselves are private, and then only on pages for signed-in users.

# Route caching

With `php artisan route:cache`, the automatic route is part of the cache like any other package route. Changing `route.*` needs `route:cache` again.
