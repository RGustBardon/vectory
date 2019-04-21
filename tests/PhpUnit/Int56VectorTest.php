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
final class Int56VectorTest extends TestCase
{
    private const INVALID_VALUE = '0';
    private const SEQUENCE_DEFAULT_VALUE = 'DefaultValue';
    private const SEQUENCE_SKIP_VALUE = 'SkipValue';

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
        $vector[0] = -36028797018963968 - 1;
    }

    public function testThrowsIfValueIsGreaterThanMaximum(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 36028797018963967 + 1;
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

    public static function extremumProvider(): \Generator
    {
        yield from [[-36028797018963968], [-36028797018963968 + 1], [36028797018963967 - 1], [36028797018963967]];
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

    public function testJsonSerializable(): void
    {
        $vector = self::getInstance();
        self::assertNativeJson([], $vector);
        $value = self::getRandomValue();
        $sequence = [$value, self::getRandomValue(), $value];
        foreach ($sequence as $value) {
            $vector[] = $value;
        }
        $vector[4] = 0;
        \array_push($sequence, 0, 0);
        self::assertNativeJson($sequence, $vector);
    }

    public function testSerializable(): void
    {
        $vector = self::getInstance();
        self::assertSerialization([], $vector);
        $value = self::getRandomValue();
        $sequence = [$value, self::getRandomValue(), $value];
        foreach ($sequence as $value) {
            $vector[] = $value;
        }
        $vector[4] = 0;
        \array_push($sequence, 0, 0);
        self::assertSerialization($sequence, $vector);
    }

    public static function deletionProvider(): \Generator
    {
        foreach ([[self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue()]] as $originalElements) {
            foreach ([
                // Random test cases.
                [[], -1, 0, []],
                [[], 0, 0, []],
                [[], 1, 0, []],
                [[], -1, 1, []],
                [[], 0, 1, []],
                [[], 1, 1, []],
                [[0], -1, 0, [0]],
                [[0], -1, 1, []],
                [[0], -1, 2, []],
                [[0], 0, 0, [0]],
                [[0], 0, 1, []],
                [[0], 0, 2, []],
                [[0], 1, 0, [0]],
                [[0], 1, 1, [0]],
                [[0], 1, 2, [0]],
                [[0, 1], -2, 1, [1]],
                [[0, 1], -2, 2, []],
                [[0, 1], -1, 1, [0]],
                [[0, 1], -1, 2, [0]],
                [[0, 1], 0, 1, [1]],
                [[0, 1], 0, 2, []],
                [[0, 1], 1, 1, [0]],
                [[0, 1], 1, 2, [0]],
                [[0, 1, 2, 3, 4, 5], 0, 3, [3, 4, 5]],
                [[0, 1, 2, 3, 4, 5], 1, 1, [0, 2, 3, 4, 5]],
                [[0, 1, 2, 3, 4, 5], 1, 3, [0, 4, 5]],
                [[0, 1, 2, 3, 4, 5], 2, 3, [0, 1, 5]],
                [[0, 1, 2, 3, 4, 5], 3, 3, [0, 1, 2]],
                [[0, 1, 2, 3, 4, 5], 4, 3, [0, 1, 2, 3]],
                [[0, 1, 2, 3, 4, 5], 5, 3, [0, 1, 2, 3, 4]],
                [[0, 1, 2, 3, 4, 5], -1, 3, [0, 1, 2, 3, 4]],
                [[0, 1, 2, 3, 4, 5], -2, 3, [0, 1, 2, 3]],
                [[0, 1, 2, 3, 4, 5], -3, 3, [0, 1, 2]],
                [[0, 1, 2, 3, 4, 5], -4, 3, [0, 1, 5]],
                [[0, 1, 2, 3, 4, 5], -5, 3, [0, 4, 5]],
                [[self::SEQUENCE_SKIP_VALUE, 1, 2, 3, 4, 5], -5, 3, [self::SEQUENCE_DEFAULT_VALUE, 4, 5]],
                [[0, 1, 2, 3, 4, 5], -6, 3, [3, 4, 5]],
                [[0, 1, 2, 3, 4, 5], -7, 3, [2, 3, 4, 5]],
                [[1, 2, 1, 2, 1, 2], 2, 3, [1, 2, 2]],
                [[1, self::SEQUENCE_SKIP_VALUE, 1, 2, 1, 0], 2, 3, [1, self::SEQUENCE_DEFAULT_VALUE, 0]],
                // Calculate the positive index corresponding to the negative one.
                [[0, 1, 2], -1, 1, [0, 1]],
                // If we still end up with a negative index, decrease `$howMany`.
                [[0, 1, 2], -4, 3, [2]],
                // Check if there is anything to delete or
                // if the positive index is out of bounds.
                [[0], 0, 0, [0]],
                [[], 0, 1, []],
                [[0], 1, 1, [0]],
                // If the first index conceptually begins a byte
                // and everything to its right is to be deleted,
                // no bit-shifting is necessary.
                [[0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3], 8, 8, [0, 1, 2, 3, 0, 1, 2, 3]],
                // `$howManyFullBytes > 0` and then `0 === $howMany`
                [[0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0], 8, 8, [0, 1, 2, 3, 0, 1, 2, 3, 0]],
                // There are not enough bits in the assembled byte,
                // so augment it with the next source byte.
                [[0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0], 8, 17, [0, 1, 2, 3, 0, 1, 2, 3, 1, 2, 3, 0, 1, 2, 3, 0]],
                // Some of the bits of the target byte need to be preserved,
                // so augment the assembled byte.
                [[0, 1, 2], 1, 1, [0, 2]],
            ] as [$originalSequence, $firstIndex, $howMany, $expected]) {
                $batch = [$originalElements];
                foreach ($batch as $elements) {
                    $vector = self::getInstance();
                    $sequence = $originalSequence;
                    foreach ($sequence as $index => $key) {
                        if (self::SEQUENCE_SKIP_VALUE === $key) {
                            $sequence[$index] = 0;
                        } else {
                            $vector[$index] = $elements[$key];
                        }
                    }
                    (yield [$vector, $elements, $sequence, $firstIndex, $howMany, $expected]);
                }
            }
        }
    }

    /**
     * @dataProvider deletionProvider
     */
    public function testDelete(VectorInterface $vector, array $elements, array $sequence, int $firstIndex, int $howMany, array $expected): void
    {
        $expectedSequence = [];
        foreach ($expected as $key) {
            if (self::SEQUENCE_DEFAULT_VALUE === $key) {
                $expectedSequence[] = 0;
            } else {
                $expectedSequence[] = $elements[$key];
            }
        }
        $vector->delete($firstIndex, $howMany);
        self::assertSequence($expectedSequence, $vector);
    }

    private static function getInstance(): VectorInterface
    {
        return new \Vectory\Int56Vector();
    }

    private static function getRandomValue()
    {
        $positive = 0 === \mt_rand(0, 1);
        $value = \dechex(\mt_rand(0x0, $positive ? 0x7f : 0x80));
        for ($i = 1; $i < 7; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }
        $value = \hexdec($value);

        return $positive ? $value : -$value;
    }

    private static function assertSequence(array $sequence, VectorInterface $vector): void
    {
        self::assertCount(\count($sequence), $vector);
        $i = 0;
        foreach ($vector as $index => $element) {
            self::assertSame($i, $index);
            self::assertSame($sequence[$index], $element, 'Index: '.$index."\n".\var_export($sequence, true)."\n".self::getVectorDump($vector));
            ++$i;
        }
    }

    private static function getVectorDump(VectorInterface $vector): string
    {
        $dump = "\n";
        $trace = \array_reverse(\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS));
        foreach ($trace as $frame) {
            if (0 === \strpos($frame['class'], 'Vectory')) {
                $frame['class'] = \substr($frame['class'], \strrpos($frame['class'], '\\') + 1);
                $dump .= \sprintf("%s%s%s:%d\n", $frame['class'], $frame['type'], $frame['function'], $frame['line']);
            }
        }
        \ob_start();
        \var_dump($vector);
        $dump .= \ob_get_clean();

        return $dump;
    }

    private static function assertNativeJson($expected, $vector): void
    {
        $expectedJson = \json_encode($expected);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());
        $actualJson = \json_encode($vector);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());
        self::assertSame($expectedJson, $actualJson);
    }

    private static function assertSerialization($expected, $vector)
    {
        $actualSerialized = \serialize($vector);
        $actualUnserialized = \unserialize($actualSerialized, ['allowed_classes' => [\ltrim('\\Vectory\\Int56Vector', '\\')]]);
        $actual = [];
        foreach ($actualUnserialized as $index => $element) {
            $actual[$index] = $element;
        }
        self::assertSame($expected, $actual);
    }
}
