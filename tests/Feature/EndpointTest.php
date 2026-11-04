<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Javaabu\JsLocalization\Facades\JsLocalization;
use Javaabu\JsLocalization\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EndpointTest extends TestCase
{
    #[Test]
    public function it_serves_one_locale_as_json(): void
    {
        $this->get('/js-localization/dv.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('Save', 'ސޭވްކުރޭ')
            ->assertJsonPath('messages.nested.title', 'ތެރޭގައިވާ ސުރުޚީ')
            ->assertJsonMissingPath('en');
    }

    #[Test]
    public function it_is_named(): void
    {
        $this->assertEquals(url('/js-localization/dv.json'), route('js-localization.show', 'dv'));
    }

    #[Test]
    public function it_returns_404_for_a_locale_the_app_does_not_have(): void
    {
        $this->get('/js-localization/fr.json')->assertNotFound();
    }

    #[Test]
    public function it_lets_browsers_revalidate_with_an_etag(): void
    {
        $response = $this->get('/js-localization/dv.json');

        $etag = $response->headers->get('ETag');

        $this->assertEquals('"' . JsLocalization::fingerprint() . '"', $etag);
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));

        $this->get('/js-localization/dv.json', ['If-None-Match' => $etag])
            ->assertStatus(304)
            ->assertContent('');
    }

    #[Test]
    public function it_serves_an_empty_object_for_a_configured_locale_without_lines(): void
    {
        config(['js-localization.locales' => ['dv', 'fr']]);

        $this->get('/js-localization/fr.json')
            ->assertOk()
            ->assertContent('{}');
    }

    #[Test]
    public function it_registers_the_route_with_a_macro(): void
    {
        Route::middleware('throttle:60,1')->group(fn () => Route::jsLocalization('assets/lang')->name('lang'));

        $this->get('/assets/lang/dv.json')
            ->assertOk()
            ->assertJsonPath('Save', 'ސޭވްކުރޭ');
    }

    #[Test]
    public function it_only_accepts_locale_like_names(): void
    {
        $this->get('/js-localization/..%2F.env.json')->assertNotFound();
    }
}
