<?php

namespace Javaabu\JsLocalization\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Javaabu\JsLocalization\JsLocalization;
use Javaabu\JsLocalization\Translate\AiTranslator;
use Javaabu\JsLocalization\Translate\LangFileWriter;
use Javaabu\JsLocalization\Translate\LineValidator;
use Javaabu\JsLocalization\Translate\MissingLine;
use Javaabu\JsLocalization\Translate\TranslationReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'lang:translate')]
class TranslateCommand extends Command
{
    protected $signature = 'lang:translate
        {--to=* : The locales to fill in}
        {--from= : The locale to translate from. Defaults to js-localization.missing.base, then app.fallback_locale}
        {--driver= : The laravel/ai provider: gemini, openai, anthropic, ... Defaults to laravel/ai\'s default}
        {--model= : The model to use}
        {--group=* : Only these groups (wildcards work; json for JSON lines)}
        {--vendor : Also fill in packages\' lines, in lang/vendor}
        {--force : Translate lines the locale already has too}
        {--batch= : Lines per request}
        {--glossary= : A JSON file of terms: {"dv": {"Invoice": "އިންވޮއިސް"}}}
        {--dry-run : Translate and report, but don\'t write language files}';

    protected $description = 'Fill in missing translations with AI (needs laravel/ai)';

    public function handle(
        AiTranslator $translator,
        JsLocalization $localization,
        LineValidator $validator,
        LangFileWriter $writer,
        Filesystem $files,
    ): int {
        if (! $translator->available()) {
            $this->components->error(__('js-localization::commands.translate.no_ai'));

            return self::FAILURE;
        }

        $targets = $this->option('to');

        if (! $targets) {
            $this->components->error(__('js-localization::commands.translate.no_target'));

            return self::FAILURE;
        }

        $from = $this->option('from') ?: config('js-localization.missing.base') ?: config('app.fallback_locale');
        $glossaries = $this->glossaries($files);

        if ($glossaries === null) {
            return self::FAILURE;
        }

        $failed = false;

        foreach ($targets as $to) {
            $lines = $this->missingLines($localization, $from, $to);

            if (! $lines) {
                $this->components->info(__('js-localization::commands.translate.nothing', ['locale' => $to]));

                continue;
            }

            $this->components->info(__('js-localization::commands.translate.translating', ['count' => count($lines), 'from' => $from, 'to' => $to]));

            $failed = ! $this->translateLines($translator, $validator, $lines, $from, $to, $glossaries[$to] ?? []) || $failed;

            if (! $this->option('dry-run')) {
                $this->write($writer, $lines, $to);
            }

            $report = new TranslationReport($from, $to, $lines, (bool) $this->option('dry-run'));
            $path = $report->save($files, config('js-localization.translate.report_path', storage_path('js-localization/reports')));

            $this->components->twoColumnDetail(__('js-localization::commands.translate.added'), (string) $report->acceptedCount());
            $this->components->twoColumnDetail(__('js-localization::commands.translate.rejected'), (string) $report->rejectedCount());
            $this->components->twoColumnDetail(__('js-localization::commands.translate.report'), $this->relative($path));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, MissingLine>
     */
    protected function missingLines(JsLocalization $localization, string $from, string $to): array
    {
        $existing = $this->option('force') ? [] : $localization->flatLines($to);
        $lines = [];

        foreach ($localization->jsonLines($from) as $key => $source) {
            $lines[] = new MissingLine(null, (string) $key, is_string($source) ? $source : '');
        }

        foreach ($localization->groups($from) as $group) {
            if (! $this->option('vendor') && str_contains($group, '::')) {
                continue;
            }

            foreach ($localization->flatten($localization->loadGroup($from, $group)) as $key => $source) {
                $lines[] = new MissingLine($group, (string) $key, is_string($source) ? $source : '');
            }
        }

        $groups = $this->option('group');

        return array_values(array_filter($lines, function (MissingLine $line) use ($existing, $groups) {
            if (trim($line->source) === '') {
                return false;
            }

            if ($groups && ! Str::is($groups, $line->groupName())) {
                return false;
            }

            $current = $existing[$line->fullKey()] ?? null;

            return ! is_string($current) || $current === '';
        }));
    }

    /**
     * @param  array<int, MissingLine>  $lines
     * @param  array<string, string>  $glossary
     */
    protected function translateLines(
        AiTranslator $translator,
        LineValidator $validator,
        array $lines,
        string $from,
        string $to,
        array $glossary,
    ): bool {
        $size = max(1, (int) ($this->option('batch') ?: config('js-localization.translate.batch', 40)));
        $notes = (string) (config("js-localization.translate.notes.{$to}") ?? config('js-localization.translate.notes.' . explode('_', str_replace('-', '_', $to))[0]) ?? '');
        $ok = true;

        $this->withProgressBar(array_chunk($lines, $size), function (array $batch) use ($translator, $validator, $from, $to, $glossary, $notes, &$ok) {
            $texts = [];

            foreach ($batch as $line) {
                $texts[$line->fullKey()] = $line->source;
            }

            try {
                $translations = $translator->translate(
                    $texts,
                    $from,
                    $to,
                    $this->termsIn($glossary, $texts),
                    $notes,
                    $this->option('driver') ?: config('js-localization.translate.driver'),
                    $this->option('model') ?: config('js-localization.translate.model'),
                );
            } catch (Throwable $exception) {
                $ok = false;

                foreach ($batch as $line) {
                    $line->problem = 'request failed: ' . $exception->getMessage();
                }

                return;
            }

            foreach ($batch as $line) {
                $line->translation = $translations[$line->fullKey()] ?? null;
                $line->problem = $line->translation === null
                    ? 'not returned'
                    : $validator->problem($line->source, $line->translation);
            }
        });

        $this->newLine(2);

        return $ok;
    }

    /**
     * @param  array<int, MissingLine>  $lines
     */
    protected function write(LangFileWriter $writer, array $lines, string $to): void
    {
        $json = [];
        $groups = [];

        foreach ($lines as $line) {
            if (! $line->isAccepted()) {
                continue;
            }

            if ($line->group === null) {
                $json[$line->key] = $line->translation;

                continue;
            }

            $groups[$line->group][$line->key] = $line->translation;
        }

        if ($json) {
            $writer->addJsonLines(lang_path("{$to}.json"), $json);
        }

        foreach ($groups as $group => $group_lines) {
            $writer->addGroupLines($this->groupFile($group, $to), $group_lines);
        }
    }

    protected function groupFile(string $group, string $locale): string
    {
        if (! str_contains($group, '::')) {
            return lang_path("{$locale}/{$group}.php");
        }

        [$namespace, $name] = explode('::', $group, 2);

        return lang_path("vendor/{$namespace}/{$locale}/{$name}.php");
    }

    /**
     * The glossary for each locale, from config and --glossary. Null when the
     * file can't be read.
     *
     * @return array<string, array<string, string>>|null
     */
    protected function glossaries(Filesystem $files): ?array
    {
        $glossaries = config('js-localization.translate.glossary', []);

        if (is_string($glossaries)) {
            $glossaries = $this->readGlossary($files, $glossaries);
        }

        if ($path = $this->option('glossary')) {
            $extra = $this->readGlossary($files, $path);

            if ($extra === null) {
                return null;
            }

            $glossaries = array_replace_recursive($glossaries ?? [], $extra);
        }

        return $glossaries;
    }

    /**
     * @return array<string, array<string, string>>|null
     */
    protected function readGlossary(Filesystem $files, string $path): ?array
    {
        $data = $files->exists($path) ? json_decode($files->get($path), true) : null;

        if (! is_array($data)) {
            $this->components->error(__('js-localization::commands.translate.bad_glossary', ['path' => $path]));

            return null;
        }

        return $data;
    }

    /**
     * Only the terms that appear in these lines, to keep prompts short.
     *
     * @param  array<string, string>  $glossary
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    protected function termsIn(array $glossary, array $texts): array
    {
        $haystack = mb_strtolower(implode("\n", $texts));

        return array_filter(
            $glossary,
            fn (string $term) => str_contains($haystack, mb_strtolower($term)),
            ARRAY_FILTER_USE_KEY,
        );
    }

    protected function relative(string $file): string
    {
        return str_starts_with($file, base_path() . '/')
            ? substr($file, strlen(base_path()) + 1)
            : $file;
    }
}
