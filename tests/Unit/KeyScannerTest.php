<?php

namespace Javaabu\JsLocalization\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Javaabu\JsLocalization\Missing\KeyScanner;
use Javaabu\JsLocalization\Missing\UsedKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class KeyScannerTest extends TestCase
{
    protected KeyScanner $scanner;

    protected string $pattern;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanner = new KeyScanner(new Filesystem);
        $this->pattern = $this->scanner->pattern(['__', 'trans', 'trans_choice', 'transChoice', '@lang', '@choice', 'Lang::get']);
    }

    /**
     * @return array<int, string>
     */
    protected function keys(string $code, bool $in_js = false): array
    {
        return array_column($this->scanner->keysIn($code, $this->pattern, $in_js), 0);
    }

    #[Test]
    public function it_finds_keys_passed_as_string_literals(): void
    {
        $this->assertEquals(
            ['messages.welcome', 'Save', 'messages.apples', 'auth.failed', 'shop::cart.empty', 'Cancel'],
            $this->keys("__('messages.welcome', ['app' => 'x']); trans(\"Save\"); trans_choice('messages.apples', 3); @lang('auth.failed') Lang::get('shop::cart.empty'); __( 'Cancel' )"),
        );
    }

    #[Test]
    public function it_finds_keys_in_javascript(): void
    {
        $this->assertEquals(
            ['ސޭވްކުރޭ', 'Template', 'messages.apples', 'jquery'],
            $this->keys('__("ސޭވްކުރޭ"); __(`Template`); transChoice("messages.apples", n); $.__("jquery")', in_js: true),
        );
    }

    #[Test]
    public function it_skips_keys_that_are_not_fixed(): void
    {
        $this->assertEquals([], $this->keys('__("orders.{$status}"); __("orders.$status"); __(\'orders.\' . $status); __($key); __(`orders.${status}`); __(\'\')'));
    }

    #[Test]
    public function it_keeps_dollar_signs_in_javascript_strings(): void
    {
        $this->assertEquals(['Pay $10'], $this->keys('__("Pay $10")', in_js: true));
    }

    #[Test]
    public function it_skips_methods_and_longer_names(): void
    {
        $this->assertEquals([], $this->keys("\$this->trans('a.b'); my__('a.b'); \$__('a.b'); untrans('a.b')"));
    }

    #[Test]
    public function it_unescapes_quotes(): void
    {
        $this->assertEquals(["It's here", 'Say "hi"'], $this->keys("__('It\\'s here'); __(\"Say \\\"hi\\\"\")"));
    }

    #[Test]
    public function it_reports_line_numbers(): void
    {
        $this->assertEquals(
            [['a.b', 1], ['c.d', 3]],
            $this->scanner->keysIn("__('a.b')\n\n__('c.d')", $this->pattern),
        );
    }

    #[Test]
    public function it_scans_files_and_folders(): void
    {
        $dir = sys_get_temp_dir() . '/js-localization-scan-' . uniqid();
        $files = new Filesystem;

        $files->ensureDirectoryExists("{$dir}/js/node_modules/lib");
        $files->put("{$dir}/js/app.tsx", "__('Save')\n__('messages.welcome')");
        $files->put("{$dir}/js/node_modules/lib/index.js", "__('Ignored')");
        $files->put("{$dir}/js/notes.md", "__('Not code')");
        $files->put("{$dir}/view.blade.php", "{{ __('Save') }}");

        try {
            $keys = $this->scanner->scan(["{$dir}/js", "{$dir}/view.blade.php", "{$dir}/missing"], ['__']);

            $this->assertEquals(['Save', 'messages.welcome'], array_keys($keys));
            $this->assertEquals(["{$dir}/js/app.tsx:1", "{$dir}/view.blade.php:1"], $keys['Save']->places);
            $this->assertTrue($keys['Save']->in_js);
        } finally {
            $files->deleteDirectory($dir);
        }
    }

    #[Test]
    public function it_parses_the_group_of_a_key_as_laravel_does(): void
    {
        $this->assertEquals('messages', (new UsedKey('messages.welcome'))->group());
        $this->assertEquals('forum/thread', (new UsedKey('forum/thread.title'))->group());
        $this->assertEquals('shop::cart', (new UsedKey('shop::cart.empty'))->group());
        $this->assertEquals('Hello', (new UsedKey('Hello. Bye.'))->group());
        $this->assertNull((new UsedKey('Save'))->group());
        $this->assertNull((new UsedKey('shop::cart'))->group());
    }
}
