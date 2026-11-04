<?php

namespace Javaabu\JsLocalization\Translate;

/**
 * A line the target locale lacks: a JSON line (group null) or a line in a
 * group ('messages', 'shop::cart').
 */
class MissingLine
{
    public ?string $translation = null;

    public ?string $problem = null;

    public function __construct(
        public ?string $group,
        public string $key,
        public string $source,
    ) {}

    /**
     * The key you pass to __(): 'Save', 'messages.welcome', 'shop::cart.empty'.
     */
    public function fullKey(): string
    {
        return $this->group === null ? $this->key : "{$this->group}.{$this->key}";
    }

    /**
     * The name --group matches: 'json' for JSON lines.
     */
    public function groupName(): string
    {
        return $this->group ?? 'json';
    }

    public function isAccepted(): bool
    {
        return $this->translation !== null && $this->problem === null;
    }
}
