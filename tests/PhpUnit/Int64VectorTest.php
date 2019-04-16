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
final class Int64VectorTest extends TestCase
{
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
        $this->expectException(\TypeError::class);
        $vector = self::getInstance();
        $vector[1] = null;
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

    public function testIteratorAggregate(): void
    {
        $vector = self::getInstance();
        self::assertSame([], \iterator_to_array($vector));
        $element = self::getRandomValue();
        $vector[1] = $element;
        self::assertSame([0, $element], \iterator_to_array($vector));
        unset($vector[0]);
        self::assertSame([$element], \iterator_to_array($vector));
    }

    /**
     * @depends testIteratorAggregate
     */
    public function testIteratorAggregateWithModification(): void
    {
        $vector = self::getInstance();
        $elements = [self::getRandomValue(), self::getRandomValue(), self::getRandomValue()];
        $sequence = [$elements[1], $elements[2], $elements[1]];
        foreach ($sequence as $element) {
            $vector[] = $element;
        }
        $iterations = [];
        foreach ($vector as $outerIndex => $outerElement) {
            if (1 === $outerIndex) {
                $vector[] = $elements[2];
            }
            $innerIteration = [];
            foreach ($vector as $innerIndex => $innerElement) {
                if (1 === $innerIndex) {
                    $vector[2] = $elements[0];
                }
                $innerIteration[] = [$innerIndex, $innerElement];
            }
            $iterations[] = $innerIteration;
            $iterations[] = [$outerIndex, $outerElement];
        }
        self::assertSame([[[0, $elements[1]], [1, $elements[2]], [2, $elements[1]]], [0, $elements[1]], [[0, $elements[1]], [1, $elements[2]], [2, $elements[0]], [3, $elements[2]]], [1, $elements[2]], [[0, $elements[1]], [1, $elements[2]], [2, $elements[0]], [3, $elements[2]]], [2, $elements[1]]], $iterations);
    }

    private static function getInstance(): VectorInterface
    {
        return new \Vectory\Int64Vector();
    }

    private static function getRandomValue()
    {
        $positive = 0 === \mt_rand(0, 1);
        $value = \dechex(\mt_rand(0x0, $positive ? 0x7f : 0x80));
        for ($i = 1; $i < 8; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }
        $value = \hexdec($value);

        return $positive ? $value : -$value;
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
        foreach ($sources as $sourcePrefix) {
            $property = new \ReflectionProperty($vector, $sourcePrefix.'Source');
            $property->setAccessible(true);
            $source = $property->getValue($vector);
            $bytesPerElement = 8 ?? 1;
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
