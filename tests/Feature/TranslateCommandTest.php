<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Illuminate\Support\Facades\File;
use Javaabu\JsLocalization\Tests\TestCase;
use Javaabu\JsLocalization\Translate\AiTranslator;
use Javaabu\JsLocalization\Translate\TranslationAgent;
use Laravel\Ai\AiServiceProvider;
use Laravel\Ai\Prompts\AgentPrompt;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

#[Group('ai')]
class TranslateCommandTest extends TestCase
{
    protected string $backup;

    /**
     * What the fake model knows. Anything else comes back with " (dv)" added,
     * which keeps placeholders, plurals and tags.
     *
     * @var array<string, string>
     */
    protected array $dictionary = [
        'Only in English' => 'އިނގިރޭސިން އެކަނި',
        'Goodbye' => 'ވަކިވަނީ',
        'Your cart is empty' => 'ތިޔަ ކާޓު ހުސް',
        'Next &raquo;' => 'ކުރިއަށް &raquo;',
        'The :attribute field is required.' => ':attribute ފުރިހަމަކުރުން ލާޒިމް.',
        'The :attribute field must be accepted.' => 'ފުރިހަމަކުރުން ލާޒިމް.',
    ];

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), AiServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->backup = sys_get_temp_dir() . '/js-localization-lang-' . uniqid();

        File::copyDirectory(lang_path(), $this->backup);

        config(['js-localization.translate.report_path' => storage_path('js-localization/reports')]);

        TranslationAgent::fake(fn (string $prompt) => ['translations' => array_map(
            fn (array $line) => ['key' => $line['key'], 'translation' => $this->dictionary[$line['text']] ?? "{$line['text']} (dv)"],
            json_decode($prompt, true),
        )]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(lang_path());
        File::moveDirectory($this->backup, lang_path());
        File::deleteDirectory(storage_path('js-localization'));

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    protected function phpFile(string $path): array
    {
        return require lang_path($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonFile(string $path): array
    {
        return json_decode(File::get(lang_path($path)), true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lastReport(): array
    {
        $files = File::glob(storage_path('js-localization/reports/*.json'));

        return json_decode(File::get(end($files)), true);
    }

    #[Test]
    public function it_fills_in_missing_json_lines(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['json']])->assertSuccessful();

        $this->assertEquals([
            'Save' => 'ސޭވްކުރޭ',
            'Welcome, :name!' => 'މަރުޙަބާ، :name!',
            'Only in English' => 'އިނގިރޭސިން އެކަނި',
        ], $this->jsonFile('dv.json'));
    }

    #[Test]
    public function it_fills_in_missing_lines_in_groups_without_touching_existing_ones(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages']])->assertSuccessful();

        $messages = $this->phpFile('dv/messages.php');

        $this->assertEquals('ވަކިވަނީ', $messages['farewell']);
        $this->assertEquals(':app އަށް މަރުޙަބާ', $messages['welcome']);
        $this->assertEquals('ތެރޭގައިވާ ސުރުޚީ', $messages['nested']['title']);
    }

    #[Test]
    public function it_creates_group_files_the_locale_does_not_have(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['validation']])->assertSuccessful();

        $validation = $this->phpFile('dv/validation.php');

        $this->assertEquals(':attribute ފުރިހަމަކުރުން ލާޒިމް.', $validation['required']);
        $this->assertEquals('The :attribute field must have between :min and :max items. (dv)', $validation['between']['array']);
    }

    #[Test]
    public function it_writes_php_files_with_short_arrays(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['pagination']])->assertSuccessful();

        $this->assertEquals(
            "<?php\n\nreturn [\n    'previous' => '&laquo; Previous (dv)',\n    'next' => 'ކުރިއަށް &raquo;',\n];\n",
            File::get(lang_path('dv/pagination.php')),
        );
    }

    #[Test]
    public function it_rejects_translations_that_break_placeholders_plurals_or_tags(): void
    {
        $this->dictionary['The :attribute field is required.'] = 'ފުރިހަމަކުރުން ލާޒިމް.';

        $this->artisan('lang:translate', ['--to' => ['ar'], '--group' => ['messages', 'validation']])->assertSuccessful();

        $report = collect($this->lastReport()['lines'])->keyBy('key');

        $this->assertEquals('placeholders', $report['validation.required']['problem']);
        $this->assertEquals('rejected', $report['validation.required']['status']);
        $this->assertEquals('added', $report['messages.apples']['status']);
        $this->assertArrayNotHasKey('required', $this->phpFile('ar/validation.php'));
        $this->assertEquals('{0} No apples|{1} One apple|[2,*] :count apples (dv)', $this->phpFile('ar/messages.php')['apples']);
    }

    #[Test]
    public function it_retranslates_existing_lines_with_force(): void
    {
        $this->dictionary['Save'] = 'ރައްކާކުރޭ';

        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['json'], '--force' => true])->assertSuccessful();

        $this->assertEquals('ރައްކާކުރޭ', $this->jsonFile('dv.json')['Save']);
    }

    #[Test]
    public function it_writes_nothing_on_a_dry_run_but_still_reports(): void
    {
        $before = File::get(lang_path('dv.json'));

        $this->artisan('lang:translate', ['--to' => ['dv'], '--dry-run' => true])->assertSuccessful();

        $this->assertEquals($before, File::get(lang_path('dv.json')));
        $this->assertFileDoesNotExist(lang_path('dv/validation.php'));

        $report = $this->lastReport();

        $this->assertTrue($report['dry_run']);
        $this->assertEquals('translated', collect($report['lines'])->firstWhere('key', 'Only in English')['status']);
        $this->assertStringContainsString('A dry run', File::get(str_replace('.json', '.md', File::glob(storage_path('js-localization/reports/*.json'))[0])));
    }

    #[Test]
    public function it_fills_in_packages_lines_in_lang_vendor_with_vendor(): void
    {
        $this->artisan('lang:translate', ['--to' => ['ar'], '--group' => ['shop::*']])->assertSuccessful();
        $this->assertFileDoesNotExist(lang_path('vendor/shop/ar/cart.php'));

        $this->artisan('lang:translate', ['--to' => ['ar'], '--group' => ['shop::*'], '--vendor' => true])->assertSuccessful();

        $this->assertEquals(['checkout' => 'Checkout (dv)', 'empty' => 'ތިޔަ ކާޓު ހުސް'], $this->phpFile('vendor/shop/ar/cart.php'));
    }

    #[Test]
    public function it_sends_glossary_terms_and_locale_notes_to_the_model(): void
    {
        config(['js-localization.translate.glossary' => ['dv' => ['Goodbye' => 'ދާން', 'Unused' => 'ނުބޭނުންވާ']]]);

        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages']])->assertSuccessful();

        TranslationAgent::assertPrompted(function (AgentPrompt $prompt) {
            $instructions = (string) $prompt->agent->instructions();

            return str_contains($instructions, 'Dhivehi (written in Thaana script) [dv]')
                && str_contains($instructions, '- Goodbye: ދާން')
                && ! str_contains($instructions, 'Unused')
                && str_contains($instructions, 'Write in Thaana script');
        });
    }

    #[Test]
    public function it_reads_a_glossary_file(): void
    {
        $file = storage_path('js-localization/glossary.json');

        File::ensureDirectoryExists(dirname($file));
        File::put($file, json_encode(['dv' => ['Goodbye' => 'ދާން']]));

        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages'], '--glossary' => $file])->assertSuccessful();

        TranslationAgent::assertPrompted(fn (AgentPrompt $prompt) => str_contains((string) $prompt->agent->instructions(), '- Goodbye: ދާން'));
    }

    #[Test]
    public function it_rejects_a_glossary_that_is_not_json(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--glossary' => '/missing.json'])
            ->expectsOutputToContain("isn't a JSON file of terms")
            ->assertFailed();
    }

    #[Test]
    public function it_sends_lines_in_batches(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['json', 'messages'], '--batch' => 1])->assertSuccessful();

        TranslationAgent::assertPromptedTimes(2);
    }

    #[Test]
    public function it_passes_the_driver_and_model(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages'], '--driver' => 'gemini', '--model' => 'gemini-3-flash'])->assertSuccessful();

        TranslationAgent::assertPrompted(fn (AgentPrompt $prompt) => $prompt->model === 'gemini-3-flash' && $prompt->provider->name() === 'gemini');
    }

    #[Test]
    public function it_marks_lines_the_model_left_out(): void
    {
        TranslationAgent::fake([['translations' => [['key' => 'invented', 'translation' => 'x']]]]);

        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages']])->assertSuccessful();

        $this->assertEquals('not returned', $this->lastReport()['lines'][0]['problem']);
    }

    #[Test]
    public function it_reports_a_failed_request_and_fails(): void
    {
        TranslationAgent::fake(fn () => throw new RuntimeException('Quota exceeded'));

        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages']])->assertFailed();

        $this->assertEquals('request failed: Quota exceeded', $this->lastReport()['lines'][0]['problem']);
    }

    #[Test]
    public function it_says_when_there_is_nothing_to_translate(): void
    {
        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['forum/*']])
            ->expectsOutputToContain('Nothing to translate for dv.')
            ->assertSuccessful();

        TranslationAgent::assertNeverPrompted();
    }

    #[Test]
    public function it_needs_a_target_locale(): void
    {
        $this->artisan('lang:translate')
            ->expectsOutputToContain('--to=dv')
            ->assertFailed();
    }

    #[Test]
    public function it_explains_how_to_install_laravel_ai_when_it_is_missing(): void
    {
        $this->app->instance(AiTranslator::class, new class extends AiTranslator
        {
            public function available(): bool
            {
                return false;
            }
        });

        $this->artisan('lang:translate', ['--to' => ['dv']])
            ->expectsOutputToContain('composer require laravel/ai')
            ->assertFailed();
    }

    #[Test]
    public function it_speaks_dhivehi(): void
    {
        $this->app->setLocale('dv');

        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['forum/*']])
            ->expectsOutputToContain('dv އަށް ތަރުޖަމާކުރާނެ އެއްޗެއް ނެތް.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_writes_a_markdown_report_for_review(): void
    {
        $this->dictionary['Goodbye'] = 'ވަކި | ވަނީ';

        $this->artisan('lang:translate', ['--to' => ['dv'], '--group' => ['messages']])
            ->expectsOutputToContain('storage/js-localization/reports/dv-')
            ->assertSuccessful();

        $markdown = File::get(File::glob(storage_path('js-localization/reports/*.md'))[0]);

        $this->assertStringContainsString('# Translations from en to dv', $markdown);
        $this->assertStringContainsString('| messages.farewell | Goodbye | ވަކި \| ވަނީ | plural segments |', $markdown);
        $this->assertStringContainsString("## Added (0)\n\nNone.", $markdown);
    }
}
