<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Illuminate\Support\Facades\File;
use Javaabu\JsLocalization\Facades\JsLocalization;
use Javaabu\JsLocalization\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class JsLocalizationTest extends TestCase
{
    #[Test]
    public function it_finds_the_locales_the_app_has_language_files_for(): void
    {
        $this->assertEquals(['dv', 'en'], JsLocalization::locales());
    }

    #[Test]
    public function it_includes_the_app_locale_and_fallback_locale(): void
    {
        config(['app.locale' => 'ar', 'app.fallback_locale' => 'en']);

        $this->assertEquals(['ar', 'dv', 'en'], JsLocalization::locales());
    }

    #[Test]
    public function it_uses_the_configured_locales(): void
    {
        config(['js-localization.locales' => ['dv']]);

        $this->assertEquals(['dv'], JsLocalization::locales());
        $this->assertTrue(JsLocalization::hasLocale('dv'));
        $this->assertFalse(JsLocalization::hasLocale('en'));
    }

    #[Test]
    public function it_exports_json_lines_at_the_top_level(): void
    {
        $lines = JsLocalization::forLocale('dv');

        $this->assertEquals('ސޭވްކުރޭ', $lines['Save']);
        $this->assertEquals('މަރުޙަބާ، :name!', $lines['Welcome, :name!']);
    }

    #[Test]
    public function it_merges_json_lines_that_packages_register(): void
    {
        $this->assertEquals('ކާޓަށް އެޅުއްވުމަށް', JsLocalization::forLocale('dv')['Add to cart']);
    }

    #[Test]
    public function it_exports_groups_by_their_laravel_names(): void
    {
        $lines = JsLocalization::forLocale('dv');

        $this->assertEquals(':app އަށް މަރުޙަބާ', $lines['messages']['welcome']);
        $this->assertEquals('ތެރޭގައިވާ ސުރުޚީ', $lines['messages']['nested']['title']);
        $this->assertEquals('ތްރެޑް', $lines['forum/thread']['title']);
    }

    #[Test]
    public function it_exports_package_groups_with_the_apps_overrides(): void
    {
        $lines = JsLocalization::forLocale('dv');

        $this->assertEquals([
            'checkout' => 'ޗެކްއައުޓް',
            'empty' => 'ތިޔަ ކާޓު ހުސް',
        ], $lines['shop::cart']);
    }

    #[Test]
    public function it_exports_the_same_lines_laravel_translates(): void
    {
        $lines = JsLocalization::forLocale('dv');

        $this->assertEquals(trans('shop::cart.checkout', [], 'dv'), $lines['shop::cart']['checkout']);
        $this->assertEquals(trans('forum/thread.title', [], 'dv'), $lines['forum/thread']['title']);
        $this->assertEquals(__('Add to cart', [], 'dv'), $lines['Add to cart']);
    }

    #[Test]
    public function it_exports_the_frameworks_language_files(): void
    {
        $this->assertEquals(trans('validation.required', [], 'en'), JsLocalization::forLocale('en')['validation']['required']);
    }

    #[Test]
    public function it_leaves_out_this_packages_own_lines_by_default(): void
    {
        $lines = JsLocalization::forLocale('en');

        $this->assertArrayNotHasKey('js-localization::commands', $lines);
        $this->assertArrayNotHasKey('manifest::messages', $lines);
    }

    #[Test]
    public function it_only_exports_the_configured_groups(): void
    {
        config(['js-localization.groups' => ['messages', 'forum/*', 'shop::*']]);

        $this->assertEquals(
            ['Add to cart', 'Save', 'Welcome, :name!', 'forum/thread', 'messages', 'shop::cart'],
            array_keys(JsLocalization::forLocale('dv')),
        );
    }

    #[Test]
    public function it_leaves_out_excluded_groups(): void
    {
        config(['js-localization.except' => ['auth', 'shop::*', 'validation', 'pagination', 'passwords', 'js-localization::*', 'manifest::*']]);

        $this->assertEquals(
            ['Add to cart', 'Only in English', 'Save', 'Welcome, :name!', 'forum/thread', 'messages'],
            array_keys(JsLocalization::forLocale('en')),
        );
    }

    #[Test]
    public function it_can_leave_out_json_lines(): void
    {
        config(['js-localization.json' => false]);

        $this->assertEquals(['auth', 'forum/thread', 'messages', 'shop::cart'], array_keys(JsLocalization::forLocale('dv')));
    }

    #[Test]
    public function it_sorts_lines_by_key(): void
    {
        $this->assertEquals(['apples', 'nested', 'welcome'], array_keys(JsLocalization::forLocale('dv')['messages']));
    }

    #[Test]
    public function it_can_keep_lines_in_file_order(): void
    {
        config(['js-localization.sort' => false]);

        $this->assertEquals(['welcome', 'apples', 'nested'], array_keys(JsLocalization::forLocale('dv')['messages']));
    }

    #[Test]
    public function it_returns_every_locale(): void
    {
        $all = JsLocalization::all();

        $this->assertEquals(['dv', 'en'], array_keys($all));
        $this->assertEquals('Save', $all['en']['Save']);
        $this->assertEquals(['en'], array_keys(JsLocalization::all(['en'])));
    }

    #[Test]
    public function it_returns_an_empty_array_for_a_locale_without_lines(): void
    {
        $this->assertEquals([], JsLocalization::forLocale('fr'));
    }

    #[Test]
    public function it_changes_the_fingerprint_when_a_language_file_changes(): void
    {
        $file = lang_path('dv/extra.php');

        $before = JsLocalization::fingerprint();

        try {
            File::put($file, "<?php\n\nreturn ['hello' => 'ހެލޯ'];\n");

            JsLocalization::flush();

            $this->assertNotEquals($before, JsLocalization::fingerprint());
            $this->assertEquals('ހެލޯ', JsLocalization::forLocale('dv')['extra']['hello']);
        } finally {
            File::delete($file);
        }
    }

    #[Test]
    public function it_changes_the_fingerprint_when_the_settings_change(): void
    {
        $before = JsLocalization::fingerprint();

        config(['js-localization.except' => ['auth']]);
        JsLocalization::flush();

        $this->assertNotEquals($before, JsLocalization::fingerprint());
    }

    #[Test]
    public function it_caches_exported_lines_until_a_file_changes(): void
    {
        JsLocalization::forLocale('dv');

        $this->assertTrue(cache()->has('js-localization:dv:' . JsLocalization::fingerprint()));
    }

    #[Test]
    public function it_can_skip_the_cache(): void
    {
        config(['js-localization.cache.enabled' => false]);

        JsLocalization::forLocale('dv');

        $this->assertFalse(cache()->has('js-localization:dv:' . JsLocalization::fingerprint()));
    }

    #[Test]
    public function it_builds_the_endpoint_url(): void
    {
        $this->assertEquals('/js-localization/{locale}.json', JsLocalization::url());
        $this->assertEquals('/js-localization/dv.json', JsLocalization::url('dv'));

        config(['js-localization.route.prefix' => '/assets/lang/']);

        $this->assertEquals('/assets/lang/dv.json', JsLocalization::url('dv'));

        config(['js-localization.route.prefix' => '']);

        $this->assertEquals('/dv.json', JsLocalization::url('dv'));
    }
}
