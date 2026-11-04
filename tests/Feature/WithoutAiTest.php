<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Javaabu\JsLocalization\Tests\TestCase;
use Javaabu\JsLocalization\Translate\AiTranslator;
use Laravel\Ai\Contracts\Agent;
use PHPUnit\Framework\Attributes\Test;

/**
 * Runs with and without laravel/ai installed: CI has a job that removes it.
 */
class WithoutAiTest extends TestCase
{
    #[Test]
    public function it_detects_whether_laravel_ai_is_installed(): void
    {
        $this->assertEquals(interface_exists(Agent::class), app(AiTranslator::class)->available());
    }

    #[Test]
    public function it_works_without_laravel_ai_except_for_lang_translate(): void
    {
        if (interface_exists(Agent::class)) {
            $this->markTestSkipped('laravel/ai is installed; the CI job without it runs this.');
        }

        $this->artisan('lang:js', ['--stdout' => true])->assertSuccessful();

        $this->artisan('lang:translate', ['--to' => ['dv']])
            ->expectsOutputToContain('composer require laravel/ai')
            ->assertFailed();
    }
}
