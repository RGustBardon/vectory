<?php

declare(strict_types=1);

/*
 * This file is part of the Vectory package.
 *
 * (c) Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vectory\Tests\PhpUnit;

use PHPUnit\Framework\TestCase;
use Vectory\VectorInterface;

/**
 * @internal
 */
final class NullableUint16VectorTest extends TestCase
{
    // __iterator_aggregate_methods_test()
    // __json_serializable_methods_test()
    // __serializable_methods_test()
    private const INVALID_VALUE = '0';

    protected function setUp(): void
    {
        \mt_srand(0);
    }

    public function testThrowsIfIndexOfInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        self::getInstance()[false];
    }

    public function testThrowsIfIndexOfEmptyContainer(): void
    {
        $this->expectException(\OutOfRangeException::class);
        self::getInstance()[0];
    }

    public function testThrowsIfIndexIsNegative(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 0;
        $vector[-1];
    }

    public function testThrowsIfIndexIsOutOfRange(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 0;
        $vector[1];
    }

    public function testThrowsIfValueOfInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        $vector = self::getInstance();
        $vector[0] = self::INVALID_VALUE;
    }

    public function testThrowsIfValueIsLowerThanMinimum(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 0 - 1;
    }

    public function testThrowsIfValueIsGreaterThanMaximum(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 65535 + 1;
    }

    public function testArrayAccess(): void
    {
        $vector = self::getInstance();
        self::assertFalse(isset($vector[0]));
        $vector[0] = 0;
        self::assertTrue(isset($vector[0]));
        self::assertFalse(isset($vector[1]));
        self::assertSame(0, $vector[0]);
        $value = self::getRandomValue();
        $vector[2] = $value;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertTrue(isset($vector[2]));
        self::assertFalse(isset($vector[3]));
        self::assertSame(0, $vector[0]);
        self::assertSame(0, $vector[1]);
        self::assertSame($value, $vector[2]);
        do {
            $otherValue = self::getRandomValue();
        } while ($value === $otherValue);
        $vector[2] = $otherValue;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertTrue(isset($vector[2]));
        self::assertFalse(isset($vector[3]));
        self::assertSame(0, $vector[0]);
        self::assertSame(0, $vector[1]);
        self::assertSame($otherValue, $vector[2]);
        $vector[0] = $value;
        unset($vector[1]);
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertFalse(isset($vector[2]));
        self::assertSame($value, $vector[0]);
        self::assertSame($otherValue, $vector[1]);
    }

    /**
     * @depends testArrayAccess
     */
    public function testOffsetSetWithNullIndex(): void
    {
        $vector = self::getInstance();
        $value0 = self::getRandomValue();
        $vector[] = $value0;
        $value2 = self::getRandomValue();
        $vector[2] = $value2;
        $value3 = self::getRandomValue();
        $vector[] = $value3;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertTrue(isset($vector[2]));
        self::assertTrue(isset($vector[3]));
        self::assertFalse(isset($vector[4]));
        self::assertSame($value0, $vector[0]);
        self::assertSame(0, $vector[1]);
        self::assertSame($value2, $vector[2]);
        self::assertSame($value3, $vector[3]);
    }

    /**
     * @depends testArrayAccess
     */
    public function testOffsetSetWithNullValue(): void
    {
        $vector = self::getInstance();
        $vector[1] = null;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertFalse(isset($vector[2]));
        self::assertSame(0, $vector[0]);
        self::assertNull($vector[1]);
    }

    public static function extremumProvider(): \Generator
    {
        yield from [[0], [0 + 1], [65535 - 1], [65535]];
    }

    /**
     * @dataProvider extremumProvider
     * @depends testArrayAccess
     */
    public function testOffsetSetWithExtremum(int $extremum): void
    {
        $vector = self::getInstance();
        $vector[0] = $extremum;
        self::assertSame($extremum, $vector[0]);
    }

    public function testCountable(): void
    {
        $vector = self::getInstance();
        self::assertCount(0, $vector);
        $vector[1] = self::getRandomValue();
        self::assertCount(2, $vector);
        $vector[2] = $vector[1];
        self::assertCount(3, $vector);
        $vector[2] = self::getRandomValue();
        self::assertCount(3, $vector);
        unset($vector[0]);
        self::assertCount(2, $vector);
        unset($vector[2]);
        self::assertCount(2, $vector);
        $vector[2] = 0;
        self::assertCount(3, $vector);
    }

    public function testCountableWithNullValue(): void
    {
        $vector = self::getInstance();
        $vector[0] = null;
        self::assertCount(1, $vector);
        $vector[2] = null;
        self::assertCount(3, $vector);
    }

    private static function getInstance(): VectorInterface
    {
        return new \Vectory\NullableUint16Vector();
    }

    private static function getRandomValue()
    {
        $value = \dechex(\mt_rand(0x0, 0xff));
        for ($i = 1; $i < 2; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }

        return \hexdec($value);
    }

    private static function dumpVector(VectorInterface $vector): void
    {
        echo "\n";
        $trace = \array_reverse(\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS));
        foreach ($trace as $frame) {
            if (0 === \strpos($frame['class'], 'Vectory')) {
                $frame['class'] = \substr($frame['class'], \strrpos($frame['class'], '\\') + 1);
                \printf("%s%s%s:%d\n", $frame['class'], $frame['type'], $frame['function'], $frame['line']);
            }
        }
        $sources = ['primary'];
        $sources[] = 'nullability';
        foreach ($sources as $sourcePrefix) {
            $property = new \ReflectionProperty($vector, $sourcePrefix.'Source');
            $property->setAccessible(true);
            $source = $property->getValue($vector);
            $bytesPerElement = 2 ?? 1;
            $elements = \str_split(\bin2hex($source), $bytesPerElement * 2);
            \assert(\is_iterable($elements));
            foreach ($elements as $index => $element) {
                echo \substr(\strtoupper($sourcePrefix), 0, 1);
                \printf('% '.\strlen((string) (\strlen($source) / $bytesPerElement)).'d: ', $index);
                foreach (\str_split($element, 2) as $value) {
                    $decimal = (int) \hexdec($value);
                    $binary = \decbin($decimal);
                    \printf('h:% 2s d:% 3s b:%04s %04s | ', $value, $decimal, \substr($binary, 0, 4), \substr($binary, 4));
                }
                echo "\n";
            }
        }
    }
}
