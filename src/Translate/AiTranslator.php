<?php

namespace Javaabu\JsLocalization\Translate;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Sends lines to an AI model through laravel/ai, an optional dependency.
 */
class AiTranslator
{
    public function available(): bool
    {
        return interface_exists(Agent::class) && trait_exists(Promptable::class);
    }

    /**
     * Translations of the lines, by key, as the model returned them. Keys
     * the model left out or invented are dropped; nothing is validated here.
     *
     * @param  array<string, string>  $lines  key => text
     * @param  array<string, string>  $glossary
     * @return array<string, string>
     */
    public function translate(
        array $lines,
        string $from,
        string $to,
        array $glossary = [],
        string $notes = '',
        ?string $provider = null,
        ?string $model = null,
    ): array {
        $agent = new TranslationAgent($from, $to, $glossary, $notes);

        $input = array_map(
            fn (string $key, string $text) => ['key' => $key, 'text' => $text],
            array_keys($lines),
            $lines,
        );

        $response = $agent->prompt(
            json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            provider: $provider,
            model: $model,
        );

        $translations = [];

        foreach ($response['translations'] ?? [] as $item) {
            $key = $item['key'] ?? null;
            $translation = $item['translation'] ?? null;

            if (! is_string($key) || ! is_string($translation) || ! array_key_exists($key, $lines)) {
                continue;
            }

            $translations[$key] = $translation;
        }

        return $translations;
    }
}
