<?php

namespace Javaabu\JsLocalization\Translate;

/**
 * Checks that a translated line keeps what Laravel and @javaabu/i18n need
 * from the source: :placeholders, pluralization segments and their ranges,
 * and HTML tags.
 */
class LineValidator
{
    /**
     * Why a translation can't be used, or null when it can.
     */
    public function problem(string $source, string $translation): ?string
    {
        if (trim($translation) === '') {
            return 'empty';
        }

        if ($this->placeholders($source) !== $this->placeholders($translation)) {
            return 'placeholders';
        }

        if (count($this->segments($source)) !== count($this->segments($translation))) {
            return 'plural segments';
        }

        if ($this->ranges($source) !== $this->ranges($translation)) {
            return 'plural ranges';
        }

        if ($this->tags($source) !== $this->tags($translation)) {
            return 'tags';
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function placeholders(string $line): array
    {
        preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $line, $matches);

        $names = $matches[1];

        sort($names);

        return $names;
    }

    /**
     * @return array<int, string>
     */
    public function segments(string $line): array
    {
        return explode('|', $line);
    }

    /**
     * The {0} / [2,*] prefix of each plural segment.
     *
     * @return array<int, string>
     */
    public function ranges(string $line): array
    {
        return array_map(function (string $segment) {
            preg_match('/^\s*(\{[^}]*\}|\[[^\]]*\])/', $segment, $match);

            return str_replace(' ', '', $match[1] ?? '');
        }, $this->segments($line));
    }

    /**
     * @return array<int, string>
     */
    public function tags(string $line): array
    {
        preg_match_all('/<\/?([A-Za-z][\w-]*)[^>]*>/', $line, $matches);

        $tags = array_map('strtolower', $matches[0] ? array_map(
            fn (string $tag, string $name) => (str_starts_with($tag, '</') ? '/' : '') . $name,
            $matches[0],
            $matches[1],
        ) : []);

        sort($tags);

        return $tags;
    }
}
