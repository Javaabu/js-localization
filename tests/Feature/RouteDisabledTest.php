<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Javaabu\JsLocalization\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

class RouteDisabledTest extends TestCase
{
    protected function disableRoute($app): void
    {
        $app['config']->set('js-localization.route.enabled', false);
    }

    protected function useAnotherPrefix($app): void
    {
        $app['config']->set('js-localization.route.prefix', 'i18n');
        $app['config']->set('js-localization.route.middleware', ['throttle:60,1']);
    }

    #[Test]
    #[DefineEnvironment('disableRoute')]
    public function it_does_not_register_the_route_when_disabled(): void
    {
        $this->get('/js-localization/dv.json')->assertNotFound();
    }

    #[Test]
    #[DefineEnvironment('useAnotherPrefix')]
    public function it_uses_the_configured_prefix_and_middleware(): void
    {
        $this->get('/js-localization/dv.json')->assertNotFound();

        $this->get('/i18n/dv.json')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '60');
    }
}
