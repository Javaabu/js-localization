<?php

namespace Javaabu\JsLocalization\Missing;

class MissingReport
{
    /**
     * Keys code uses that a locale doesn't translate: locale => key => places.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    public array $used = [];

    /**
     * Lines the base locale has that a locale doesn't: locale => key => base line.
     *
     * @var array<string, array<string, string>>
     */
    public array $lines = [];

    /**
     * Keys JS uses that lang:js doesn't export: key => places.
     *
     * @var array<string, array<int, string>>
     */
    public array $not_exported = [];

    /**
     * @param  array<int, string>  $locales
     */
    public function __construct(public string $base, public array $locales) {}

    public function isEmpty(): bool
    {
        return $this->used === [] && $this->lines === [] && $this->not_exported === [];
    }

    public function count(): int
    {
        return array_sum(array_map('count', $this->used))
            + array_sum(array_map('count', $this->lines))
            + count($this->not_exported);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base' => $this->base,
            'locales' => $this->locales,
            'used' => (object) $this->used,
            'lines' => (object) $this->lines,
            'not_exported' => (object) $this->not_exported,
        ];
    }
}
