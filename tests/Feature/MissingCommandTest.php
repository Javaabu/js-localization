<?php

namespace Javaabu\JsLocalization\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Javaabu\JsLocalization\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MissingCommandTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function report(array $options = []): array
    {
        Artisan::call('lang:missing', ['--json' => true, ...$options]);

        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function it_lists_keys_the_code_uses_that_a_locale_lacks(): void
    {
        $report = $this->report();

        $this->assertEquals('en', $report['base']);
        $this->assertEquals(['dv'], $report['locales']);

        $this->assertEquals(
            ["It's here", 'Only in English', 'Your order is on its way', 'messages.welcom'],
            array_keys($report['used']['dv']),
        );
        $this->assertEquals(['app/Http/Controllers/OrdersController.php:15'], $report['used']['dv']['messages.welcom']);
        $this->assertEquals(['resources/js/components/cart.tsx:11'], $report['used']['dv']['Only in English']);
    }

    #[Test]
    public function it_lists_group_keys_missing_from_the_base_locale_but_not_json_keys(): void
    {
        $this->assertEquals(['messages.welcom'], array_keys($this->report()['used']['en']));
    }

    #[Test]
    public function it_lists_lines_the_base_locale_has_that_others_lack(): void
    {
        $lines = $this->report()['lines']['dv'];

        $this->assertEquals('Only in English', $lines['Only in English']);
        $this->assertEquals('Goodbye', $lines['messages.farewell']);
        $this->assertArrayHasKey('validation.required', $lines);
        $this->assertArrayNotHasKey('messages.welcome', $lines);
        $this->assertArrayNotHasKey('validation.attributes', $lines);
        $this->assertArrayNotHasKey('shop::cart.checkout', $lines);
    }

    #[Test]
    public function it_compares_packages_lines_with_vendor(): void
    {
        $this->assertArrayNotHasKey('shop::cart.empty', $this->report(['--locale' => ['ar']])['lines']['ar']);
        $this->assertEquals('Your cart is empty', $this->report(['--locale' => ['ar'], '--vendor' => true])['lines']['ar']['shop::cart.empty']);
        $this->assertArrayNotHasKey('shop::cart.checkout', $this->report(['--vendor' => true])['lines']['dv']);
    }

    #[Test]
    public function it_lists_keys_javascript_uses_that_are_not_exported(): void
    {
        config(['js-localization.except' => ['auth']]);

        $this->assertEquals(['auth.failed' => ['resources/js/components/cart.tsx:10']], $this->report()['not_exported']);
    }

    #[Test]
    public function it_lists_json_keys_javascript_uses_when_json_is_not_exported(): void
    {
        config(['js-localization.json' => false]);

        $this->assertEquals(['Add to cart', 'Only in English', 'Save'], array_keys($this->report()['not_exported']));
    }

    #[Test]
    public function it_checks_only_the_locales_asked_for(): void
    {
        $report = $this->report(['--locale' => ['ar']]);

        $this->assertEquals(['ar'], $report['locales']);
        $this->assertArrayHasKey('Save', $report['used']['ar']);
        $this->assertArrayHasKey('messages.welcome', $report['lines']['ar']);
    }

    #[Test]
    public function it_scans_the_paths_asked_for(): void
    {
        $report = $this->report(['--path' => [resource_path('js')]]);

        $this->assertEquals(['Only in English'], array_keys($report['used']['dv']));
    }

    #[Test]
    public function it_compares_with_another_base(): void
    {
        $report = $this->report(['--base' => 'dv', '--locale' => ['en']]);

        $this->assertEquals('dv', $report['base']);
        $this->assertEquals([], $report['lines']);
    }

    #[Test]
    public function it_prints_tables_and_fails_when_something_is_missing(): void
    {
        $this->artisan('lang:missing')
            ->expectsOutputToContain('dv is missing 4 key(s) the code uses')
            ->expectsOutputToContain('app/Http/Controllers/OrdersController.php:15')
            ->expectsOutputToContain('dv is missing')
            ->assertFailed();
    }

    #[Test]
    public function it_prints_keys_javascript_uses_that_are_not_exported(): void
    {
        config(['js-localization.except' => ['auth']]);

        $this->artisan('lang:missing', ['--path' => [resource_path('js/components')]])
            ->expectsOutputToContain("JavaScript uses 1 key(s) that lang:js doesn't export")
            ->assertFailed();
    }

    #[Test]
    public function it_succeeds_when_nothing_is_missing(): void
    {
        $this->artisan('lang:missing', ['--base' => 'dv', '--locale' => ['dv'], '--path' => [resource_path('views/page.blade.php')]])
            ->expectsOutputToContain('Nothing is missing in dv, dv.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_speaks_dhivehi(): void
    {
        $this->app->setLocale('dv');

        $this->artisan('lang:missing', ['--path' => [resource_path('js/components')]])
            ->expectsOutputToContain('ކޯޑުގައި ބޭނުންކުރާ 1 ކީ dv ގައި ނެތް')
            ->assertFailed();
    }
}
