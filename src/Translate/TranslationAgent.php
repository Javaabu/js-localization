<?php

namespace Javaabu\JsLocalization\Translate;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * The laravel/ai agent behind lang:translate. Only loaded when laravel/ai is
 * installed. Fake it in tests with TranslationAgent::fake().
 */
class TranslationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string, string>  $glossary  source term => translation
     */
    public function __construct(
        public string $from,
        public string $to,
        public array $glossary = [],
        public string $notes = '',
    ) {}

    public function instructions(): Stringable|string
    {
        $from = LocaleNames::describe($this->from);
        $to = LocaleNames::describe($this->to);

        $rules = [
            "You translate the user interface strings of a web application from {$from} to {$to}.",
            'You get a JSON list of lines, each with a key and its text. Return a translation for every key, unchanged keys.',
            'Rules:',
            '- Keep every placeholder exactly as written: words starting with a colon, like :name, :Name or :NAME. Never translate, reorder the letters of, or drop them. Move them where the grammar needs them.',
            '- Lines with | are plural forms. Keep the same number of | separated parts, in the same order, and keep any {0}, {1} or [2,*] range at the start of each part exactly as written.',
            '- Keep HTML tags and entities such as <strong>, <a href="..."> and &raquo; as they are; only translate the text between them.',
            '- Keep the tone short and clear, as on buttons, labels and messages in an app. Do not add explanations, quotes or extra punctuation.',
            '- Never translate brand names, code, URLs or email addresses.',
        ];

        if ($this->glossary) {
            $rules[] = 'Use these translations for these terms:';

            foreach ($this->glossary as $term => $translation) {
                $rules[] = "- {$term}: {$translation}";
            }
        }

        if ($this->notes !== '') {
            $rules[] = $this->notes;
        }

        return implode("\n", $rules);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'translations' => $schema->array()
                ->items($schema->object([
                    'key' => $schema->string()->required(),
                    'translation' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }
}
