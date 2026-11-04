<?php

namespace Javaabu\JsLocalization\Commands;

use Illuminate\Console\Command;
use Javaabu\JsLocalization\JsLocalization;
use Javaabu\JsLocalization\Missing\MissingReport;
use Javaabu\JsLocalization\Missing\MissingTranslations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'lang:missing')]
class MissingCommand extends Command
{
    protected $signature = 'lang:missing
        {--locale=* : Only check these locales}
        {--base= : The locale the others are compared with. Defaults to js-localization.missing.base, then app.fallback_locale}
        {--path=* : Scan these files and folders instead of js-localization.missing.paths}
        {--vendor : Also compare packages\' lines (shop::cart.empty)}
        {--json : Print the report as JSON}';

    protected $description = 'List translation keys and lines missing for a locale';

    public function handle(JsLocalization $localization, MissingTranslations $missing): int
    {
        $base = $this->option('base') ?: config('js-localization.missing.base') ?: config('app.fallback_locale');
        $locales = $this->option('locale') ?: array_values(array_diff($localization->locales(), [$base]));

        $report = $missing->report(
            locales: $locales,
            base: $base,
            paths: $this->option('path') ?: config('js-localization.missing.paths', []),
            functions: config('js-localization.missing.functions', []),
            namespaced: (bool) $this->option('vendor'),
        );

        if ($this->option('json')) {
            $this->output->writeln(json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), OutputInterface::OUTPUT_RAW);

            return $report->isEmpty() ? self::SUCCESS : self::FAILURE;
        }

        if ($report->isEmpty()) {
            $this->components->info(__('js-localization::commands.missing.none', ['locales' => implode(', ', [$base, ...$locales])]));

            return self::SUCCESS;
        }

        $this->printUsed($report);
        $this->printLines($report);
        $this->printNotExported($report);

        $this->components->error(trans_choice('js-localization::commands.missing.total', $report->count(), ['count' => $report->count()]));

        return self::FAILURE;
    }

    protected function printUsed(MissingReport $report): void
    {
        foreach ($report->used as $locale => $keys) {
            $this->components->warn(__('js-localization::commands.missing.used', ['locale' => $locale, 'count' => count($keys)]));

            $this->table(
                [__('js-localization::commands.missing.key'), __('js-localization::commands.missing.used_in')],
                array_map(fn (string $key, array $places) => [$key, $this->places($places)], array_keys($keys), $keys),
            );
        }
    }

    protected function printLines(MissingReport $report): void
    {
        foreach ($report->lines as $locale => $lines) {
            $this->components->warn(__('js-localization::commands.missing.lines', ['locale' => $locale, 'base' => $report->base, 'count' => count($lines)]));

            $this->table(
                [__('js-localization::commands.missing.key'), __('js-localization::commands.missing.base_line', ['base' => $report->base])],
                array_map(fn (string $key, string $line) => [$key, $line], array_keys($lines), $lines),
            );
        }
    }

    protected function printNotExported(MissingReport $report): void
    {
        if (! $report->not_exported) {
            return;
        }

        $this->components->warn(__('js-localization::commands.missing.not_exported', ['count' => count($report->not_exported)]));

        $this->table(
            [__('js-localization::commands.missing.key'), __('js-localization::commands.missing.used_in')],
            array_map(fn (string $key, array $places) => [$key, $this->places($places)], array_keys($report->not_exported), $report->not_exported),
        );
    }

    /**
     * @param  array<int, string>  $places
     */
    protected function places(array $places): string
    {
        return implode("\n", $places);
    }
}
