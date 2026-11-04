<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Javaabu\JsLocalization\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * tests/Fixtures/parity.json is what Laravel translates. tests/js/parity.test.ts
 * checks that @javaabu/i18n, loaded with lang:js's output, translates the same.
 */
class ParityTest extends TestCase
{
    #[Test]
    public function it_matches_what_laravel_translates(): void
    {
        $cases = json_decode(file_get_contents(__DIR__ . '/../Fixtures/parity.json'), true);

        foreach ($cases as $case) {
            $actual = isset($case['count'])
                ? trans_choice($case['key'], $case['count'], $case['replace'] ?? [], $case['locale'])
                : __($case['key'], $case['replace'] ?? [], $case['locale']);

            $this->assertEquals($case['expected'], $actual, "{$case['locale']}: {$case['key']}");
        }
    }
}
