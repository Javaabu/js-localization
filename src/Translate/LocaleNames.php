<?php

namespace Javaabu\JsLocalization\Translate;

use Locale;

class LocaleNames
{
    /** @var array<string, string> */
    public const array KNOWN = [
        'dv' => 'Dhivehi (written in Thaana script)',
        'en' => 'English',
        'ar' => 'Arabic',
    ];

    /**
     * A name an AI model understands: 'Dhivehi (written in Thaana script) [dv]'.
     */
    public static function describe(string $locale): string
    {
        $base = strtolower(str_replace('-', '_', $locale));
        $name = self::KNOWN[$base] ?? self::KNOWN[explode('_', $base)[0]] ?? null;

        if (! $name && class_exists(Locale::class)) {
            $display = Locale::getDisplayName($locale, 'en');
            $name = $display && $display !== $locale ? $display : null;
        }

        return $name ? "{$name} [{$locale}]" : $locale;
    }
}
