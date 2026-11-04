<?php

namespace Javaabu\JsLocalization\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, string> locales()
 * @method static bool hasLocale(string $locale)
 * @method static array<string, mixed> forLocale(string $locale)
 * @method static array<string, array<string, mixed>> all(array<int, string>|null $locales = null)
 * @method static string fingerprint()
 * @method static string url(?string $locale = null)
 * @method static void flush()
 *
 * @see \Javaabu\JsLocalization\JsLocalization
 */
class JsLocalization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Javaabu\JsLocalization\JsLocalization::class;
    }
}
