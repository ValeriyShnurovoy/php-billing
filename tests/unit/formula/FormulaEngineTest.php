<?php
/**
 * PHP Billing Library
 *
 * @link      https://github.com/hiqdev/php-billing
 * @package   php-billing
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017-2020, HiQDev (http://hiqdev.com/)
 */

namespace hiqdev\php\billing\tests\unit\formula;

use Cache\Adapter\PHPArray\ArrayCachePool;
use DateTimeImmutable;
use hiqdev\php\billing\charge\modifiers\addons\MonthPeriod;
use hiqdev\php\billing\charge\modifiers\addons\Reason;
use hiqdev\php\billing\charge\modifiers\addons\Since;
use hiqdev\php\billing\charge\modifiers\FixedDiscount;
use hiqdev\php\billing\charge\modifiers\Installment;
use hiqdev\php\billing\formula\FormulaEngine;
use PHPUnit\Framework\TestCase;

/**
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class FormulaEngineTest extends TestCase
{
    /**
     * @var FormulaEngine
     */
    protected $engine;

    public function setUp(): void
    {
        $this->engine = new FormulaEngine(new ArrayCachePool());
    }

    public function testSimpleDiscount()
    {
        $date = '2018-08-01';
        $rate = '2';
        $reason = 'test reason';
        $formula = $this->engine->build("discount.fixed('$rate%').since('$date').reason('$reason')");

        $this->assertInstanceOf(FixedDiscount::class, $formula);
        $this->assertSame($rate, $formula->getValue()->getValue());
        $this->assertTrue($formula->isRelative());
        $this->assertInstanceOf(Since::class, $formula->getSince());
        $this->assertEquals(new DateTimeImmutable($date), $formula->getSince()->getValue());
        $this->assertInstanceOf(Reason::class, $formula->getReason());
        $this->assertSame($reason, $formula->getReason()->getValue());
        $this->assertNull($formula->getTill());
    }

    public function testSimpleInstallment()
    {
        $this->checkSimpleInstallment('2024-08-01', 2, 'test reason');
        $this->checkSimpleInstallment('2024-09-01', 3, 'test reason');
    }

    protected function checkSimpleInstallment($date, $num, $reason)
    {
        $formula = $this->engine->build("installment.since('$date').lasts('$num months').reason('$reason')");

        $this->assertInstanceOf(Installment::class, $formula);
        $this->assertInstanceOf(MonthPeriod::class, $formula->getTerm());
        $this->assertSame($num, $formula->getTerm()->getValue());
        $this->assertInstanceOf(Since::class, $formula->getSince());
        $this->assertEquals(new DateTimeImmutable($date), $formula->getSince()->getValue());
        $this->assertInstanceOf(Reason::class, $formula->getReason());
        $this->assertSame($reason, $formula->getReason()->getValue());
        $this->assertNull($formula->getTill());
    }

    public function normalizeDataProvider()
    {
        return [
            ["ab\ncd", "ab\ncd"],
            [" ab  \n  \n cd", "ab\ncd"],
            ['', null],
            [' ', null],
            ["\n\n\n", null],
            ['ab', 'ab'],
            ["ab\ncd", "ab\ncd"],
            [true, '1'],
        ];
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($formula, $expected)
    {
        return $this->assertSame($expected, $this->engine->normalize($formula));
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate($formula, $error)
    {
        return $this->assertSame($error, $this->engine->validate($formula));
    }

    public function validateDataProvider()
    {
        return [
            ['', "Unexpected token \"EOF\" (EOF) at line 1 and column 1:\n\n↑ : "],
            //['', 'Failed to interpret formula : '],
            ['true', 'Formula run returned unexpected result : true'],
            ['discount.fixed("50%")', null],
            ["discount.fixed(\"50%\")\ndiscount.fixed(\"5 USD\")", null],
        ];
    }
}
