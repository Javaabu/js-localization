<?php

namespace Javaabu\JsLocalization\Translate;

use Illuminate\Filesystem\Filesystem;

/**
 * What lang:translate did, for a person to review: a Markdown table and the
 * same data as JSON, next to each other.
 */
class TranslationReport
{
    /**
     * @param  array<int, MissingLine>  $lines
     */
    public function __construct(
        public string $from,
        public string $to,
        public array $lines,
        public bool $dry_run = false,
    ) {}

    public function acceptedCount(): int
    {
        return count(array_filter($this->lines, fn (MissingLine $line) => $line->isAccepted()));
    }

    public function rejectedCount(): int
    {
        return count($this->lines) - $this->acceptedCount();
    }

    /**
     * Writes {to}-{timestamp}.md and .json to the folder. Returns the
     * Markdown file's path.
     */
    public function save(Filesystem $files, string $folder): string
    {
        $name = $this->to . '-' . now()->format('Y-m-d-His');

        $files->ensureDirectoryExists($folder);
        $files->put("{$folder}/{$name}.json", json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        $files->put("{$folder}/{$name}.md", $this->toMarkdown());

        return "{$folder}/{$name}.md";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'dry_run' => $this->dry_run,
            'lines' => array_map(fn (MissingLine $line) => [
                'key' => $line->fullKey(),
                'source' => $line->source,
                'translation' => $line->translation,
                'status' => $line->isAccepted() ? ($this->dry_run ? 'translated' : 'added') : 'rejected',
                'problem' => $line->problem,
            ], $this->lines),
        ];
    }

    public function toMarkdown(): string
    {
        $accepted = array_filter($this->lines, fn (MissingLine $line) => $line->isAccepted());
        $rejected = array_filter($this->lines, fn (MissingLine $line) => ! $line->isAccepted());

        $markdown = "# Translations from {$this->from} to {$this->to}\n\n";
        $markdown .= $this->dry_run
            ? "A dry run: nothing was written to the language files.\n\n"
            : "Review these lines in the language files before you ship them.\n\n";

        $markdown .= '## ' . ($this->dry_run ? 'Translated' : 'Added') . ' (' . count($accepted) . ")\n\n";
        $markdown .= $this->table(['Key', $this->from, $this->to], array_map(
            fn (MissingLine $line) => [$line->fullKey(), $line->source, (string) $line->translation],
            $accepted,
        ));

        $markdown .= '## Rejected (' . count($rejected) . ")\n\n";
        $markdown .= "These weren't written. Translate them by hand, or run the command again.\n\n";
        $markdown .= $this->table(['Key', $this->from, $this->to, 'Problem'], array_map(
            fn (MissingLine $line) => [$line->fullKey(), $line->source, (string) $line->translation, (string) $line->problem],
            $rejected,
        ));

        return $markdown;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    protected function table(array $headers, array $rows): string
    {
        if (! $rows) {
            return "None.\n\n";
        }

        $escape = fn (string $cell) => str_replace(['|', "\n"], ['\\|', ' '], $cell);

        $table = '| ' . implode(' | ', $headers) . " |\n";
        $table .= '|' . str_repeat(' --- |', count($headers)) . "\n";

        foreach ($rows as $row) {
            $table .= '| ' . implode(' | ', array_map($escape, $row)) . " |\n";
        }

        return $table . "\n";
    }
}
