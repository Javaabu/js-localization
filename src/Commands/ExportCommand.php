<?php

namespace Javaabu\JsLocalization\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Javaabu\JsLocalization\JsLocalization;
use Javaabu\JsLocalization\TranslationWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'lang:js')]
class ExportCommand extends Command
{
    protected $signature = 'lang:js
        {target? : Where to write. With {locale} in it, one file per locale; .json or .js picks the format. Defaults to js-localization.path}
        {--locale=* : Only export these locales}
        {--format= : json or js, instead of the target\'s extension}
        {--pretty : Indent the output}
        {--stdout : Print every locale as JSON instead of writing files}
        {--no-sort : Keep lines in file order}
        {--source= : Read language files from this folder instead of the app\'s}
        {--json : Write JSON (same as --format=json)}
        {--c|compress : Kept for Laravel-JS-Localization: output is always compact unless --pretty}';

    protected $description = 'Export the app\'s translations for @javaabu/i18n';

    public function handle(JsLocalization $localization, TranslationWriter $writer, Filesystem $files): int
    {
        if ($this->option('no-sort')) {
            config(['js-localization.sort' => false]);
        }

        if ($source = $this->option('source')) {
            if (! $files->isDirectory($source)) {
                $this->components->error(__('js-localization::commands.source_missing', ['path' => $source]));

                return self::FAILURE;
            }

            $localization = new JsLocalization($this->sourceLoader($files, $source), $files, $this->laravel['config'], $this->laravel['cache']);
        }

        $locales = $this->option('locale') ?: null;
        $translations = $localization->all($locales);
        $pretty = (bool) ($this->option('pretty') ?: config('js-localization.pretty', false));

        if ($this->option('stdout')) {
            $this->output->write($writer->encode($translations, 'json', $pretty), false, OutputInterface::OUTPUT_RAW);

            return self::SUCCESS;
        }

        $format = $this->option('json') ? 'json' : $this->option('format');

        if ($format && ! in_array($format, TranslationWriter::FORMATS, true)) {
            $this->components->error(__('js-localization::commands.unknown_format', ['format' => $format]));

            return self::FAILURE;
        }

        $target = $this->argument('target') ?: config('js-localization.path');

        if ($format && ! $this->argument('target')) {
            $target = preg_replace('/\.(json|js)$/', ".{$format}", $target);
        }

        foreach ($writer->write($target, $translations, $pretty, $format) as $file) {
            $this->components->twoColumnDetail(__('js-localization::commands.written'), $this->relative($file));
        }

        $this->components->info(trans_choice('js-localization::commands.exported', count($translations), ['count' => count($translations)]));

        return self::SUCCESS;
    }

    protected function sourceLoader(Filesystem $files, string $source): Loader
    {
        $loader = new FileLoader($files, [$source]);

        $app_loader = $this->laravel['translator']->getLoader();

        foreach ($app_loader->namespaces() as $namespace => $hint) {
            $loader->addNamespace($namespace, $hint);
        }

        foreach (method_exists($app_loader, 'jsonPaths') ? $app_loader->jsonPaths() : [] as $path) {
            $loader->addJsonPath($path);
        }

        return $loader;
    }

    protected function relative(string $file): string
    {
        return str_starts_with($file, base_path() . '/')
            ? substr($file, strlen(base_path()) + 1)
            : $file;
    }
}
