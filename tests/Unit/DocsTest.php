<?php

namespace Javaabu\JsLocalization\Tests\Unit;

use Javaabu\JsLocalization\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DocsTest extends TestCase
{
    protected function root(string $path = ''): string
    {
        return dirname(__DIR__, 2) . ($path ? "/{$path}" : '');
    }

    /**
     * @return array<int, string>
     */
    protected function pages(): array
    {
        $pages = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root('docs'))) as $file) {
            if ($file->getExtension() === 'md') {
                $pages[] = substr($file->getPathname(), strlen($this->root()) + 1);
            }
        }

        sort($pages);

        return $pages;
    }

    #[Test]
    public function it_links_every_docs_page_from_llms_txt(): void
    {
        $llms = file_get_contents($this->root('llms.txt'));

        foreach ($this->pages() as $page) {
            $this->assertStringContainsString("]({$page})", $llms, "{$page} is not linked from llms.txt.");
        }
    }

    #[Test]
    public function it_has_no_broken_relative_links(): void
    {
        $files = [
            ...$this->pages(),
            'llms.txt',
            'README.md',
            'resources/boost/skills/javaabu-js-localization/SKILL.md',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($this->root($file));

            preg_match_all('/\]\(((?!https?:|mailto:|#)[^)\s]+)\)/', $contents, $matches);

            foreach ($matches[1] as $link) {
                $target = preg_replace('/#.*$/', '', $link);
                $base = dirname($this->root($file));

                $candidates = [
                    "{$base}/{$target}",
                    "{$base}/{$target}.md",
                    "{$base}/" . rtrim($target, '/') . '/_category_.json',
                ];

                $exists = array_filter($candidates, fn (string $candidate) => file_exists($candidate));

                $this->assertNotEmpty($exists, "Broken link [{$link}] in {$file}.");
            }
        }
    }

    #[Test]
    public function every_page_has_a_title(): void
    {
        foreach ($this->pages() as $page) {
            $this->assertMatchesRegularExpression('/^---\ntitle: .+\n/', file_get_contents($this->root($page)), "{$page} has no title.");
        }
    }

    #[Test]
    public function every_config_option_is_documented(): void
    {
        $config = config('js-localization');
        $docs = file_get_contents($this->root('docs/advanced-usage/configuration.md'));

        foreach ($this->keys($config) as $key) {
            $this->assertStringContainsString("| `{$key}` |", $docs, "js-localization.{$key} is not documented.");
        }
    }

    #[Test]
    public function every_command_option_is_documented(): void
    {
        $pages = [
            'lang:js' => ['src/Commands/ExportCommand.php', 'docs/basic-usage/exporting.md'],
            'lang:missing' => ['src/Commands/MissingCommand.php', 'docs/basic-usage/missing-translations.md'],
            'lang:translate' => ['src/Commands/TranslateCommand.php', 'docs/basic-usage/ai-translation.md'],
        ];

        foreach ($pages as $command => [$source, $page]) {
            preg_match_all('/\{--(?:\w\|)?([\w-]+)/', file_get_contents($this->root($source)), $matches);

            $docs = file_get_contents($this->root($page));

            foreach ($matches[1] as $option) {
                $this->assertStringContainsString("`--{$option}", $docs, "{$command} --{$option} is not documented in {$page}.");
            }
        }
    }

    #[Test]
    public function it_ships_the_boost_guideline_and_skill(): void
    {
        $this->assertFileExists($this->root('resources/boost/guidelines/core.blade.php'));

        $skill = file_get_contents($this->root('resources/boost/skills/javaabu-js-localization/SKILL.md'));

        $this->assertMatchesRegularExpression('/^---\nname: javaabu-js-localization\ndescription: .+\n/', $skill);
    }

    /**
     * Config keys, dotted, down to the documented level: route.prefix, but
     * not missing.paths.0 or translate.notes.dv.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    protected function keys(array $config, string $prefix = ''): array
    {
        $keys = [];

        foreach ($config as $key => $value) {
            if (is_array($value) && ! array_is_list($value) && $prefix === '' && ! in_array($key, ['glossary', 'notes'], true)) {
                array_push($keys, ...$this->keys($value, "{$key}."));

                continue;
            }

            $keys[] = $prefix . $key;
        }

        return $keys;
    }
}
