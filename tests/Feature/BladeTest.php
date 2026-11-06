<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Javaabu\JsLocalization\Tests\TestCase;
use Javaabu\Manifest\Facades\Manifest;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

class BladeTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/page', fn () => view('page'));
        $router->get('/inertia', fn () => view('inertia'));
        $router->get('/fragment', fn () => '<p>' . __('Save') . '</p>');
        $router->get('/api', fn () => ['message' => __('Save')]);
    }

    #[Test]
    public function it_registers_the_blade_entry_with_manifest(): void
    {
        $asset = Manifest::find('js-localization::i18n');

        $this->assertEquals('js/blade.ts', $asset->entry);
        $this->assertEquals('/js-localization/{locale}.json', $asset->attributes['data-js-localization']);
    }

    #[Test]
    public function it_asks_apps_to_install_javaabu_i18n(): void
    {
        $package = Manifest::package('js-localization');

        $this->assertArrayHasKey('@javaabu/i18n', $package->npmDependencies());
        $this->assertEquals('@javaabu/js-localization', $package->importAlias());
    }

    #[Test]
    public function it_registers_its_vite_plugin_with_manifest(): void
    {
        $this->artisan('manifest:discover')->assertSuccessful();

        $discovery = json_decode(file_get_contents(base_path('bootstrap/cache/manifest.json')), true);

        $this->assertEquals(
            [['module' => '../dist/vite.js', 'options' => []]],
            $discovery['packages']['js-localization']['vitePlugins'],
        );
        $this->assertFileExists(dirname(__DIR__, 2) . '/dist/vite.js');
    }

    #[Test]
    public function it_loads_the_blade_entry_on_html_pages(): void
    {
        $this->app->setLocale('dv');

        $content = $this->get('/page')->assertOk()->getContent();

        $this->assertStringContainsString('<h1>ޖާވާބު އަށް މަރުޙަބާ</h1>', $content);
        $this->assertMatchesRegularExpression(
            '#<script type="module" src="[^"]*/vendor/manifest/js-localization/assets/blade-[\w-]+\.js" data-js-localization="/js-localization/\{locale\}\.json"></script>\s*</body>#',
            $content,
        );
    }

    protected function useAnotherPrefix($app): void
    {
        $app['config']->set('js-localization.route.prefix', 'i18n');
    }

    #[Test]
    #[DefineEnvironment('useAnotherPrefix')]
    public function it_points_the_entry_at_the_configured_prefix(): void
    {
        $this->assertStringContainsString('data-js-localization="/i18n/{locale}.json"', $this->get('/page')->getContent());
    }

    #[Test]
    public function it_leaves_out_fragments_and_json(): void
    {
        $this->assertStringNotContainsString('js-localization', $this->get('/fragment')->getContent());
        $this->assertStringNotContainsString('js-localization', $this->get('/api')->getContent());
    }

    #[Test]
    public function it_leaves_out_inertia_pages(): void
    {
        $this->assertStringNotContainsString('data-js-localization', $this->get('/inertia')->getContent());
        $this->assertStringNotContainsString('data-js-localization', $this->get('/page', ['X-Inertia' => 'true'])->getContent());
    }

    #[Test]
    public function it_can_be_turned_off(): void
    {
        config(['js-localization.blade.auto' => false]);

        $this->assertStringNotContainsString('data-js-localization', $this->get('/page')->getContent());
    }

    #[Test]
    public function it_can_still_be_placed_with_a_directive_when_turned_off(): void
    {
        config(['js-localization.blade.auto' => false]);

        Route::get('/manual', fn () => view('manual'));

        $this->assertStringContainsString('data-js-localization', $this->get('/manual')->getContent());
    }
}
