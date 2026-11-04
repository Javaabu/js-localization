<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Javaabu\JsLocalization\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_merges_the_package_config(): void
    {
        $this->assertIsArray(config('js-localization'));
    }
}
