<?php

namespace Javaabu\JsLocalization\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Javaabu\JsLocalization\TranslationWriter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TranslationWriterTest extends TestCase
{
    protected TranslationWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = new TranslationWriter(new Filesystem);
    }

    #[Test]
    public function it_encodes_every_locale_by_locale(): void
    {
        $json = $this->writer->encode(['dv' => ['Save' => 'ސޭވްކުރޭ'], 'en' => ['Save' => 'Save']]);

        $this->assertEquals('{"dv":{"Save":"ސޭވްކުރޭ"},"en":{"Save":"Save"}}' . "\n", $json);
    }

    #[Test]
    public function it_encodes_one_locale_without_the_locale_key(): void
    {
        $json = $this->writer->encode(['dv' => ['messages' => ['hi' => 'ހައި']]], by_locale: false);

        $this->assertEquals('{"messages":{"hi":"ހައި"}}' . "\n", $json);
    }

    #[Test]
    public function it_encodes_empty_locales_as_objects(): void
    {
        $this->assertEquals('{"dv":{}}' . "\n", $this->writer->encode(['dv' => []]));
        $this->assertEquals("{}\n", $this->writer->encode(['dv' => []], by_locale: false));
    }

    #[Test]
    public function it_keeps_slashes_and_html_entities_as_written(): void
    {
        $json = $this->writer->encode(['en' => ['pagination' => ['next' => 'Next &raquo;'], 'forum/thread' => ['a' => 'b']]]);

        $this->assertStringContainsString('"next":"Next &raquo;"', $json);
        $this->assertStringContainsString('"forum/thread"', $json);
    }

    #[Test]
    public function it_indents_pretty_output(): void
    {
        $json = $this->writer->encode(['dv' => ['Save' => 'ސޭވްކުރޭ']], pretty: true);

        $this->assertEquals("{\n    \"dv\": {\n        \"Save\": \"ސޭވްކުރޭ\"\n    }\n}\n", $json);
    }

    #[Test]
    public function it_encodes_a_script_that_merges_into_window_lang(): void
    {
        $script = $this->writer->encode(['dv' => ['Save' => 'ސޭވްކުރޭ']], 'js');

        $this->assertEquals(
            "(function (lang, lines) {\n"
            . "  var messages = lang.messages = lang.messages || {};\n"
            . "  for (var locale in lines) messages[locale] = Object.assign(messages[locale] || {}, lines[locale]);\n"
            . '})(window.Lang = window.Lang || {}, {"dv":{"Save":"ސޭވްކުރޭ"}});' . "\n",
            $script,
        );
    }

    #[Test]
    public function it_picks_the_format_from_the_extension(): void
    {
        $this->assertEquals('json', $this->writer->formatOf('public/lang/{locale}.json'));
        $this->assertEquals('js', $this->writer->formatOf('public/messages.js'));
        $this->assertEquals('js', $this->writer->formatOf('public/messages.mjs'));
        $this->assertEquals('json', $this->writer->formatOf('public/messages'));
    }

    #[Test]
    public function it_rejects_unknown_formats(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->writer->encode(['dv' => []], 'yaml');
    }

    #[Test]
    public function it_writes_one_file_per_locale_or_one_for_all(): void
    {
        $dir = sys_get_temp_dir() . '/js-localization-' . uniqid();
        $translations = ['dv' => ['Save' => 'ސޭވްކުރޭ'], 'en' => ['Save' => 'Save']];

        try {
            $this->assertEquals(
                ["{$dir}/lang/dv.json", "{$dir}/lang/en.json"],
                $this->writer->write("{$dir}/lang/{locale}.json", $translations),
            );

            $this->assertEquals("{\"Save\":\"ސޭވްކުރޭ\"}\n", file_get_contents("{$dir}/lang/dv.json"));

            $this->assertEquals(["{$dir}/all.json"], $this->writer->write("{$dir}/all.json", $translations));
            $this->assertEquals(['dv', 'en'], array_keys(json_decode(file_get_contents("{$dir}/all.json"), true)));
        } finally {
            (new Filesystem)->deleteDirectory($dir);
        }
    }
}
