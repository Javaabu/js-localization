<?php

namespace Javaabu\JsLocalization\Tests;

use Javaabu\JsLocalization\JsLocalizationServiceProvider;
use Javaabu\Manifest\ManifestServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * A small Laravel app in tests/Fixtures/app with English and Dhivehi
     * language files, and a fixture package in its vendor folder.
     */
    public static function applicationBasePath(): string
    {
        return __DIR__ . '/Fixtures/app';
    }

    protected function getPackageProviders($app): array
    {
        return [
            ManifestServiceProvider::class,
            JsLocalizationServiceProvider::class,
        ];
    }
}
