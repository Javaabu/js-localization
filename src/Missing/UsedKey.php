<?php

namespace Javaabu\JsLocalization\Missing;

/**
 * A translation key found in code, and where.
 */
class UsedKey
{
    /** @var array<int, string> */
    public array $places = [];

    public bool $in_js = false;

    public function __construct(public string $key) {}

    public function addPlace(string $file, int $line, bool $in_js): void
    {
        $this->places[] = "{$file}:{$line}";
        $this->in_js = $this->in_js || $in_js;
    }

    /**
     * The group the key would be in, as Laravel parses it: 'messages' for
     * 'messages.welcome', 'shop::cart' for 'shop::cart.empty', null when
     * there's no dot.
     */
    public function group(): ?string
    {
        $namespace = null;
        $key = $this->key;

        if (str_contains($key, '::')) {
            [$namespace, $key] = explode('::', $key, 2);
        }

        if (! str_contains($key, '.')) {
            return null;
        }

        $group = explode('.', $key, 2)[0];

        return $namespace ? "{$namespace}::{$group}" : $group;
    }
}
