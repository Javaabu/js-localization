<?php

namespace Javaabu\JsLocalization\Missing;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\Translator;
use Javaabu\JsLocalization\JsLocalization;

/**
 * Finds keys code uses that a locale doesn't translate, lines the base
 * locale has that others don't, and keys JS uses that lang:js doesn't
 * export.
 */
class MissingTranslations
{
    public function __construct(
        protected JsLocalization $localization,
        protected KeyScanner $scanner,
        protected Translator $translator,
    ) {}

    /**
     * @param  array<int, string>  $locales
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $functions
     */
    public function report(array $locales, string $base, array $paths, array $functions, bool $namespaced = false): MissingReport
    {
        $report = new MissingReport($base, $locales);
        $used = $this->scanner->scan($paths, $functions);

        foreach ($used as $key) {
            $key->places = array_map(fn (string $place) => $this->relative($place), $key->places);

            $group = $key->group();
            $is_group_key = $group !== null && $this->localization->isGroup($group);

            if ($key->in_js && ! $this->isExported($key, $is_group_key)) {
                $report->not_exported[$key->key] = $key->places;
            }

            foreach ([$base, ...$locales] as $locale) {
                if ($this->has($key->key, $locale, $is_group_key, $base)) {
                    continue;
                }

                $report->used[$locale][$key->key] = $key->places;
            }
        }

        $base_lines = $this->localization->flatLines($base, $namespaced);

        foreach ($locales as $locale) {
            $lines = $this->localization->flatLines($locale, $namespaced);

            foreach ($base_lines as $key => $line) {
                if (array_key_exists($key, $lines)) {
                    continue;
                }

                $report->lines[$locale][$key] = is_scalar($line) ? (string) $line : '';
            }
        }

        return $report;
    }

    protected function has(string $key, string $locale, bool $is_group_key, string $base): bool
    {
        if ($is_group_key) {
            return $this->translator->hasForLocale($key, $locale);
        }

        // A JSON key is its own text in the base locale, as __() returns it.
        if ($locale === $base) {
            return true;
        }

        return array_key_exists($key, $this->loader()->load($locale, '*', '*'));
    }

    protected function isExported(UsedKey $key, bool $is_group_key): bool
    {
        if ($is_group_key) {
            return $this->localization->exports((string) $key->group());
        }

        return (bool) config('js-localization.json', true);
    }

    protected function relative(string $place): string
    {
        $base = base_path() . DIRECTORY_SEPARATOR;

        return str_starts_with($place, $base) ? substr($place, strlen($base)) : $place;
    }

    protected function loader(): Loader
    {
        return $this->translator->getLoader();
    }
}
