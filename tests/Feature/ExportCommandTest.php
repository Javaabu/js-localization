<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Illuminate\Support\Facades\File;
use Javaabu\JsLocalization\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExportCommandTest extends TestCase
{
    protected string $output_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output_dir = storage_path('js-localization');

        File::deleteDirectory($this->output_dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->output_dir);
        File::deleteDirectory(resource_path('js/translations'));

        parent::tearDown();
    }

    #[Test]
    public function it_writes_one_json_file_per_locale_by_default(): void
    {
        $this->artisan('lang:js')->assertSuccessful();

        $dv = $this->readJson(resource_path('js/translations/dv.json'));
        $en = $this->readJson(resource_path('js/translations/en.json'));

        $this->assertEquals('ސޭވްކުރޭ', $dv['Save']);
        $this->assertEquals('Save', $en['Save']);
        $this->assertArrayNotHasKey('dv', $dv);
    }

    #[Test]
    public function it_writes_unescaped_thaana(): void
    {
        $this->artisan('lang:js')->assertSuccessful();

        $this->assertStringContainsString('"Save":"ސޭވްކުރޭ"', File::get(resource_path('js/translations/dv.json')));
    }

    #[Test]
    public function it_writes_one_file_with_every_locale_when_the_target_has_no_locale(): void
    {
        $this->artisan('lang:js', ['target' => "{$this->output_dir}/messages.json"])->assertSuccessful();

        $all = $this->readJson("{$this->output_dir}/messages.json");

        $this->assertEquals(['dv', 'en'], array_keys($all));
        $this->assertEquals('ސޭވްކުރޭ', $all['dv']['Save']);
    }

    #[Test]
    public function it_writes_to_the_configured_path(): void
    {
        config(['js-localization.path' => "{$this->output_dir}/lang/{locale}.json"]);

        $this->artisan('lang:js')
            ->expectsOutputToContain('lang/dv.json')
            ->assertSuccessful();

        $this->assertFileExists("{$this->output_dir}/lang/dv.json");
        $this->assertFileExists("{$this->output_dir}/lang/en.json");
    }

    #[Test]
    public function it_exports_only_the_locales_asked_for(): void
    {
        $this->artisan('lang:js', ['target' => "{$this->output_dir}/{locale}.json", '--locale' => ['dv']])->assertSuccessful();

        $this->assertFileExists("{$this->output_dir}/dv.json");
        $this->assertFileDoesNotExist("{$this->output_dir}/en.json");
    }

    #[Test]
    public function it_writes_a_script_for_a_js_target(): void
    {
        $this->artisan('lang:js', ['target' => "{$this->output_dir}/messages.js"])->assertSuccessful();

        $script = File::get("{$this->output_dir}/messages.js");

        $this->assertStringStartsWith('(function (lang, lines) {', $script);
        $this->assertStringContainsString('window.Lang = window.Lang || {}', $script);
        $this->assertStringContainsString('"dv":{', $script);
    }

    #[Test]
    public function it_switches_the_format_of_the_default_path(): void
    {
        $this->artisan('lang:js', ['--format' => 'js'])->assertSuccessful();

        $this->assertFileExists(resource_path('js/translations/dv.js'));
        $this->assertFileDoesNotExist(resource_path('js/translations/dv.json'));
    }

    #[Test]
    public function it_takes_the_json_flag_of_laravel_js_localization(): void
    {
        $this->artisan('lang:js', ['target' => "{$this->output_dir}/messages.js", '--json' => true, '-c' => true])->assertSuccessful();

        $this->assertIsArray($this->readJson("{$this->output_dir}/messages.js"));
    }

    #[Test]
    public function it_rejects_an_unknown_format(): void
    {
        $this->artisan('lang:js', ['--format' => 'yaml'])
            ->expectsOutputToContain('Unknown format [yaml]')
            ->assertFailed();
    }

    #[Test]
    public function it_speaks_dhivehi(): void
    {
        $this->app->setLocale('dv');

        $this->artisan('lang:js', ['--format' => 'yaml'])
            ->expectsOutputToContain('[yaml] އަކީ ނޭނގޭ ފޯމެޓެކެވެ.')
            ->assertFailed();
    }

    #[Test]
    public function it_prints_json_to_stdout(): void
    {
        $this->artisan('lang:js', ['--stdout' => true, '--locale' => ['dv']])
            ->expectsOutputToContain('{"dv":{"Add to cart":"ކާޓަށް އެޅުއްވުމަށް"')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist(resource_path('js/translations'));
    }

    #[Test]
    public function it_indents_the_output_when_asked(): void
    {
        $this->artisan('lang:js', ['target' => "{$this->output_dir}/{locale}.json", '--pretty' => true])->assertSuccessful();

        $this->assertStringStartsWith("{\n    \"Add to cart\"", File::get("{$this->output_dir}/dv.json"));
    }

    #[Test]
    public function it_keeps_file_order_with_no_sort(): void
    {
        $this->artisan('lang:js', ['target' => "{$this->output_dir}/{locale}.json", '--no-sort' => true])->assertSuccessful();

        $this->assertEquals(['welcome', 'apples', 'nested'], array_keys($this->readJson("{$this->output_dir}/dv.json")['messages']));
    }

    #[Test]
    public function it_reads_another_folder_with_source(): void
    {
        File::ensureDirectoryExists("{$this->output_dir}/source/dv");
        File::put("{$this->output_dir}/source/dv/theme.php", "<?php\n\nreturn ['title' => 'ތީމް'];\n");

        $this->artisan('lang:js', [
            'target' => "{$this->output_dir}/out/{locale}.json",
            '--source' => "{$this->output_dir}/source",
            '--locale' => ['dv'],
        ])->assertSuccessful();

        $dv = $this->readJson("{$this->output_dir}/out/dv.json");

        $this->assertEquals('ތީމް', $dv['theme']['title']);
        $this->assertArrayNotHasKey('messages', $dv);
        $this->assertEquals('ތިޔަ ކާޓު ހުސް', $dv['shop::cart']['empty']);
        $this->assertEquals('ކާޓަށް އެޅުއްވުމަށް', $dv['Add to cart']);
    }

    #[Test]
    public function it_fails_when_the_source_folder_is_missing(): void
    {
        $this->artisan('lang:js', ['--source' => "{$this->output_dir}/missing"])
            ->expectsOutputToContain("doesn't exist")
            ->assertFailed();
    }

    #[Test]
    public function it_writes_an_empty_locale_as_an_object(): void
    {
        $this->artisan('lang:js', ['target' => "{$this->output_dir}/{locale}.json", '--locale' => ['fr']])->assertSuccessful();

        $this->assertEquals("{}\n", File::get("{$this->output_dir}/fr.json"));
    }

    /**
     * @return array<string, mixed>
     */
    protected function readJson(string $file): array
    {
        return json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);
    }
}
