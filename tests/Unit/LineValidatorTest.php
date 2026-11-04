<?php

namespace Javaabu\JsLocalization\Tests\Unit;

use Javaabu\JsLocalization\Translate\LineValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LineValidatorTest extends TestCase
{
    #[Test]
    public function it_accepts_a_translation_that_keeps_everything(): void
    {
        $validator = new LineValidator;

        $this->assertNull($validator->problem('Welcome, :name!', 'މަރުޙަބާ، :name!'));
        $this->assertNull($validator->problem(':count apples from :Name', ':Name ގެ :count އާފަލް'));
        $this->assertNull($validator->problem('{0} None|{1} One|[2,*] :count', '{0} ނެތް|{1} އެކެއް|[2, *] :count'));
        $this->assertNull($validator->problem('Read <a href="/terms">the terms</a>', '<a href="/dv/terms">ޝަރުތުތައް</a> ކިޔާލައްވާ'));
        $this->assertNull($validator->problem('Time: 10:30', 'ގަޑި: 10:30'));
    }

    #[Test]
    public function it_rejects_empty_translations(): void
    {
        $this->assertEquals('empty', (new LineValidator)->problem('Save', '  '));
    }

    #[Test]
    public function it_rejects_changed_placeholders(): void
    {
        $validator = new LineValidator;

        $this->assertEquals('placeholders', $validator->problem('Hi :name', 'ހައި'));
        $this->assertEquals('placeholders', $validator->problem('Hi :name', 'ހައި :ނަން'));
        $this->assertEquals('placeholders', $validator->problem('Hi :name', 'ހައި :Name'));
        $this->assertEquals('placeholders', $validator->problem('Hi', 'ހައި :name'));
    }

    #[Test]
    public function it_rejects_changed_plurals(): void
    {
        $validator = new LineValidator;

        $this->assertEquals('plural segments', $validator->problem('apple|apples', 'އާފަލް'));
        $this->assertEquals('plural ranges', $validator->problem('{0} None|[1,*] Some', '{0} ނެތް|[2,*] ބައެއް'));
        $this->assertEquals('plural ranges', $validator->problem('{0} None|[1,*] Some', 'ނެތް|ބައެއް'));
    }

    #[Test]
    public function it_rejects_changed_tags(): void
    {
        $validator = new LineValidator;

        $this->assertEquals('tags', $validator->problem('<strong>Save</strong>', 'ސޭވްކުރޭ'));
        $this->assertEquals('tags', $validator->problem('<strong>Save</strong>', '<b>ސޭވްކުރޭ</b>'));
    }
}
