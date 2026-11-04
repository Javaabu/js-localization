<?php

namespace Javaabu\JsLocalization;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

/**
 * Writes exported lines as JSON for @javaabu/i18n, or as a script that sets
 * window.Lang, which @javaabu/i18n picks up on any page.
 */
class TranslationWriter
{
    public const array FORMATS = ['json', 'js'];

    public function __construct(protected Filesystem $files) {}

    /**
     * Writes every locale to $path: one file per locale when the path has
     * {locale} in it, otherwise one file for all of them. Returns the files
     * written.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<int, string>
     */
    public function write(string $path, array $translations, bool $pretty = false, ?string $format = null): array
    {
        $format ??= $this->formatOf($path);

        if (! str_contains($path, '{locale}')) {
            $this->put($path, $this->encode($translations, $format, $pretty, by_locale: true));

            return [$path];
        }

        $written = [];

        foreach ($translations as $locale => $lines) {
            $file = str_replace('{locale}', $locale, $path);

            $this->put($file, $this->encode([$locale => $lines], $format, $pretty, by_locale: false));

            $written[] = $file;
        }

        return $written;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function encode(array $translations, string $format = 'json', bool $pretty = false, bool $by_locale = true): string
    {
        $this->ensureFormat($format);

        if ($format === 'js') {
            return $this->script($translations, $pretty);
        }

        $data = $by_locale
            ? $this->objects($translations)
            : (object) (reset($translations) ?: []);

        return $this->json($data, $pretty) . "\n";
    }

    public function formatOf(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['js', 'mjs', 'cjs'], true) ? 'js' : 'json';
    }

    public function ensureFormat(string $format): void
    {
        if (in_array($format, self::FORMATS, true)) {
            return;
        }

        throw new InvalidArgumentException("Unknown format [{$format}]. Use json or js.");
    }

    /**
     * A script that merges the lines into window.Lang.messages, whatever
     * order the scripts load in.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function script(array $translations, bool $pretty): string
    {
        $json = $this->json($this->objects($translations), $pretty);

        return '(function (lang, lines) {' . "\n"
            . '  var messages = lang.messages = lang.messages || {};' . "\n"
            . '  for (var locale in lines) messages[locale] = Object.assign(messages[locale] || {}, lines[locale]);' . "\n"
            . '})(window.Lang = window.Lang || {}, ' . $json . ');' . "\n";
    }

    /**
     * Empty locales become {} rather than [] in JSON.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function objects(array $translations): object
    {
        return (object) array_map(fn (array $lines) => (object) $lines, $translations);
    }

    protected function json(mixed $data, bool $pretty): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $flags);
    }

    protected function put(string $file, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($file));

        $this->files->put($file, $contents);
    }
}
