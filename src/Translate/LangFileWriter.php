<?php

namespace Javaabu\JsLocalization\Translate;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

/**
 * Adds lines to the app's own language files: lang/{locale}.json,
 * lang/{locale}/{group}.php and lang/vendor/{namespace}/{locale}/{group}.php.
 * PHP files are rewritten with short arrays, so comments in them are lost.
 */
class LangFileWriter
{
    public function __construct(protected Filesystem $files) {}

    /**
     * @param  array<string, string>  $lines  key => line
     */
    public function addJsonLines(string $file, array $lines): void
    {
        $existing = $this->files->exists($file)
            ? json_decode($this->files->get($file), true, flags: JSON_THROW_ON_ERROR)
            : [];

        $this->put($file, json_encode(
            array_replace($existing, $lines),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n");
    }

    /**
     * @param  array<string, string>  $lines  dotted key within the group => line
     */
    public function addGroupLines(string $file, array $lines): void
    {
        $existing = $this->files->exists($file) ? $this->files->getRequire($file) : [];

        if (! is_array($existing)) {
            $existing = [];
        }

        foreach ($lines as $key => $line) {
            Arr::set($existing, $key, $line);
        }

        $this->put($file, "<?php\n\nreturn " . $this->export($existing) . ";\n");
    }

    public function export(mixed $value, int $depth = 0): string
    {
        if (is_array($value)) {
            return $this->exportArray($value, $depth);
        }

        if (is_string($value)) {
            return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
        }

        if ($value === null) {
            return 'null';
        }

        return var_export($value, true);
    }

    /**
     * @param  array<mixed>  $array
     */
    protected function exportArray(array $array, int $depth): string
    {
        if ($array === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth + 1);
        $is_list = array_is_list($array);
        $lines = [];

        foreach ($array as $key => $value) {
            $prefix = $is_list ? '' : $this->export($key) . ' => ';

            $lines[] = $indent . $prefix . $this->export($value, $depth + 1) . ',';
        }

        return "[\n" . implode("\n", $lines) . "\n" . str_repeat('    ', $depth) . ']';
    }

    protected function put(string $file, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($file));

        $this->files->put($file, $contents);
    }
}
