<?php

namespace Javaabu\JsLocalization\Missing;

use Illuminate\Filesystem\Filesystem;
use SplFileInfo;

/**
 * Finds the translation keys code passes as string literals to __(),
 * trans(), trans_choice(), @lang and the like, in PHP, Blade, JS, TS and Vue.
 */
class KeyScanner
{
    public const array EXTENSIONS = ['php', 'js', 'mjs', 'cjs', 'jsx', 'ts', 'mts', 'tsx', 'vue', 'svelte'];

    public const array JS_EXTENSIONS = ['js', 'mjs', 'cjs', 'jsx', 'ts', 'mts', 'tsx', 'vue', 'svelte'];

    public function __construct(protected Filesystem $files) {}

    /**
     * @param  array<int, string>  $paths  files and folders
     * @param  array<int, string>  $functions
     * @return array<string, UsedKey>
     */
    public function scan(array $paths, array $functions): array
    {
        $pattern = $this->pattern($functions);
        $keys = [];

        foreach ($this->filesIn($paths) as $file) {
            $path = $file->getPathname();
            $contents = $this->files->get($path);
            $in_js = in_array($this->extensionOf($path), self::JS_EXTENSIONS, true);

            foreach ($this->keysIn($contents, $pattern, $in_js) as [$key, $line]) {
                $keys[$key] ??= new UsedKey($key);
                $keys[$key]->addPlace($path, $line, $in_js);
            }
        }

        ksort($keys, SORT_STRING);

        return $keys;
    }

    /**
     * @param  array<int, string>  $functions
     */
    public function pattern(array $functions): string
    {
        usort($functions, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        $names = implode('|', array_map(fn (string $function) => preg_quote($function, '/'), $functions));

        return '/(?<![\w$>])(?:' . $names . ')\s*\(\s*'
            . '(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)"|`((?:[^`\\\\$]|\\\\.|\$(?!\{))*)`)'
            . '(?=\s*[,)])/s';
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    public function keysIn(string $contents, string $pattern, bool $in_js = false): array
    {
        if (! preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL)) {
            return [];
        }

        $found = [];

        foreach ($matches as $match) {
            [$key, $offset, $quote] = $this->literal($match);

            if ($key === '') {
                continue;
            }

            // A PHP double-quoted string with a variable in it isn't a fixed key.
            if ($quote === '"' && ! $in_js && str_contains($key, '$')) {
                continue;
            }

            $found[] = [$this->unescape($key), substr_count($contents, "\n", 0, $offset) + 1];
        }

        return $found;
    }

    /**
     * @param  array<int, array{0: ?string, 1: int}>  $match
     * @return array{0: string, 1: int, 2: string}
     */
    protected function literal(array $match): array
    {
        foreach ([1 => "'", 2 => '"', 3 => '`'] as $group => $quote) {
            if (isset($match[$group][0])) {
                return [$match[$group][0], $match[0][1], $quote];
            }
        }

        return ['', 0, ''];
    }

    protected function unescape(string $key): string
    {
        return preg_replace('/\\\\(.)/s', '$1', $key);
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, SplFileInfo>
     */
    protected function filesIn(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if ($this->files->isFile($path)) {
                $files[] = new SplFileInfo($path);

                continue;
            }

            if (! $this->files->isDirectory($path)) {
                continue;
            }

            foreach ($this->files->allFiles($path) as $file) {
                if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                if (! in_array($this->extensionOf($file->getPathname()), self::EXTENSIONS, true)) {
                    continue;
                }

                $files[] = $file;
            }
        }

        usort($files, fn (SplFileInfo $a, SplFileInfo $b) => strcmp($a->getPathname(), $b->getPathname()));

        return $files;
    }

    protected function extensionOf(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}
