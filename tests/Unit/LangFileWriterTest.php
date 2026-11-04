<?php

namespace Javaabu\JsLocalization\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Javaabu\JsLocalization\Translate\LangFileWriter;
use Javaabu\JsLocalization\Translate\LocaleNames;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LangFileWriterTest extends TestCase
{
    protected string $dir;

    protected LangFileWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/js-localization-writer-' . uniqid();
        $this->writer = new LangFileWriter(new Filesystem);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->dir);

        parent::tearDown();
    }

    #[Test]
    public function it_adds_json_lines_keeping_the_existing_ones(): void
    {
        $file = "{$this->dir}/dv.json";

        $this->writer->addJsonLines($file, ['Save' => 'ސޭވް']);
        $this->writer->addJsonLines($file, ['Cancel' => 'ކެންސަލް', 'Save' => 'ސޭވްކުރޭ']);

        $this->assertEquals("{\n    \"Save\": \"ސޭވްކުރޭ\",\n    \"Cancel\": \"ކެންސަލް\"\n}\n", file_get_contents($file));
    }

    #[Test]
    public function it_adds_nested_group_lines(): void
    {
        $file = "{$this->dir}/dv/messages.php";

        $this->writer->addGroupLines($file, ['welcome' => "It's :app", 'nested.title' => 'ސުރުޚީ', 'path' => 'C:\\dv']);
        $this->writer->addGroupLines($file, ['nested.body' => 'ބޮޑީ']);

        $this->assertEquals(
            ['welcome' => "It's :app", 'nested' => ['title' => 'ސުރުޚީ', 'body' => 'ބޮޑީ'], 'path' => 'C:\\dv'],
            require $file,
        );
    }

    #[Test]
    public function it_exports_short_arrays(): void
    {
        $this->assertEquals(
            "[\n    'a' => [\n        'x',\n        2,\n        true,\n        null,\n    ],\n    'b' => [],\n]",
            $this->writer->export(['a' => ['x', 2, true, null], 'b' => []]),
        );
    }

    #[Test]
    public function it_names_locales_for_the_model(): void
    {
        $this->assertEquals('Dhivehi (written in Thaana script) [dv]', LocaleNames::describe('dv'));
        $this->assertEquals('Dhivehi (written in Thaana script) [dv-MV]', LocaleNames::describe('dv-MV'));
        $this->assertEquals('English [en]', LocaleNames::describe('en'));
        $this->assertStringStartsWith('French', LocaleNames::describe('fr'));
        $this->assertEquals('zz', LocaleNames::describe('zz'));
    }
}
