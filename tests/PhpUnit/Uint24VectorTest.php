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
final class Uint24VectorTest extends TestCase
{
    private const SEQUENCE_DEFAULT_VALUE = 'DefaultValue';
    private const SEQUENCE_SKIP_VALUE = 'SkipValue';
    private const INVALID_VALUE = '0';

    public static function getRandomValue()
    {
        $value = \dechex(\mt_rand(0x0, 0xff));
        for ($i = 1; $i < 3; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }

        return \hexdec($value);
    }

    public function testThrowsIfIndexRetrievedOfInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        self::getInstance()['foo'];
    }

    public function testThrowsIfIndexRetrievedOfEmptyContainer(): void
    {
        $this->expectException(\OutOfRangeException::class);
        self::getInstance()[0];
    }

    public function testThrowsIfIndexRetrievedIsNegative(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 0;
        $vector[-1];
    }

    public function testThrowsIfIndexSetOfInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        $vector = self::getInstance();
        $vector['foo'] = 0;
    }

    public function testThrowsIfIndexSetIsNegative(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[-1] = 0;
    }

    public function testThrowsIfValueSetOfInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        $vector = self::getInstance();
        $vector[0] = self::INVALID_VALUE;
    }

    public function testThrowsIfValueSetIsLowerThanMinimum(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 0 - 1;
    }

    public function testThrowsIfValueSetIsGreaterThanMaximum(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = 16777215 + 1;
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
        $vector[17] = $value;
        self::assertSame(0, $vector[15]);
        self::assertSame(0, $vector[16]);
        self::assertSame($value, $vector[17]);
        unset($vector[3]);
        unset($vector[2]);
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
        $value3 = self::getRandomValue();
        $vector[2] = $value2;
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
        yield from [[0], [0 + 1], [16777215 - 1], [16777215]];
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

    /**
     * @depends testIteratorAggregate
     */
    public function testIteratorAggregateWithMoreElements(): void
    {
        $vector = self::getInstance();
        $elements = [];
        for ($i = 0; $i < 1031; ++$i) {
            $element = self::getRandomValue();
            $elements[] = $element;
            $vector[$i] = $element;
        }
        self::assertSame($elements, \iterator_to_array($vector));
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

    public static function corruptedSerializationProvider(): \Generator
    {
        yield from [[\UnexpectedValueException::class, '~(?<=\\{)a(?=:)~', 'x'], [\TypeError::class, '~(?<=\\{i:0;)i(?=:[0-9]+)~', 'b'], [\UnexpectedValueException::class, '~(?<=\\{i:0;i:)[0-9]+~', '-1'], [\LengthException::class, '~(?<=s:)0:"(?=";\\}\\}$)~', "1:\"\0"], [\DomainException::class, '~(?<=i:)0(?=;i:[0-9]+;s:0:"";\\}\\}$)~', '-1']];
    }

    /**
     * @dataProvider corruptedSerializationProvider
     */
    public function testThrowsIfUnserializesCorrupted(string $expectedException, string $pattern, string $replacement): void
    {
        $this->expectException($expectedException);
        $serialized = \serialize(self::getInstance());
        $serialized = (string) \preg_replace($pattern, $replacement, $serialized, 1);
        $serialized = self::fixSerializedLength($serialized);
        self::unserializeVector($serialized);
    }

    /**
     * @depends testArrayAccess
     * @depends testCountable
     * @depends testIteratorAggregate
     */
    public function testSerializable(): void
    {
        $vector = self::getInstance();
        self::assertSerialization([], \serialize($vector));
        $value = self::getRandomValue();
        $sequence = [$value, self::getRandomValue(), $value];
        foreach ($sequence as $value) {
            $vector[] = $value;
        }
        $vector[4] = 0;
        \array_push($sequence, 0, 0);
        self::assertSerialization($sequence, \serialize($vector));
    }

    /**
     * @depends testSerializable
     * @depends testThrowsIfUnserializesCorrupted
     */
    public function testSerializableNotCorruptedAfterFailure(): void
    {
        $vector1 = self::getInstance();
        $value1 = self::getRandomValue();
        $vector1[0] = $value1;
        $vector2 = self::getInstance();
        $vector2[0] = self::getRandomValue();
        $vector2[1] = self::getRandomValue();
        $serialized = \serialize($vector2);
        $serialized = \preg_replace_callback('~(?<=s:)([0-9]+)(:")~', static function (array $matches): string {
            return 1 + $matches[1].$matches[2]."\0";
        }, $serialized, 1);
        $serialized = self::fixSerializedLength((string) $serialized);

        try {
            $vector1->unserialize($serialized);
        } catch (\LengthException $e) {
        }
        self::assertCount(1, $vector1);
        self::assertSame($value1, $vector1[0]);
    }

    public static function deletionProvider(): \Generator
    {
        $originalElements = [self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue()];
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

    public function testThrowsIfValueInsertedOfInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        $vector = self::getInstance();
        $vector->insert([self::INVALID_VALUE]);
    }

    public function testThrowsIfValueInsertedIsLowerThanMinimum(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector->insert([0 - 1]);
    }

    public function testThrowsIfValueInsertedIsGreaterThanMaximum(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector->insert([16777215 + 1]);
    }

    public static function insertionProvider(): \Generator
    {
        $originalElements = [self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue(), self::getRandomValue()];
        foreach ([
            // Random test cases.
            [[], [], -3, [self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE]],
            [[], [], -2, [self::SEQUENCE_DEFAULT_VALUE]],
            [[], [], -1, []],
            [[], [], 0, []],
            [[], [], 1, [self::SEQUENCE_DEFAULT_VALUE]],
            [[1], [], -3, [self::SEQUENCE_DEFAULT_VALUE, 1]],
            [[1], [], -2, [1]],
            [[1], [], -1, [1]],
            [[1], [], 0, [1]],
            [[1], [], 1, [1]],
            [[], [1], -3, [1, self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE]],
            [[], [1], -2, [1, self::SEQUENCE_DEFAULT_VALUE]],
            [[], [1], -1, [1]],
            [[], [1], 0, [1]],
            [[], [1], 1, [self::SEQUENCE_DEFAULT_VALUE, 1]],
            [[0, 1, 2, 3, 0, 1, 2], [4, 5], 3, [0, 1, 2, 4, 5, 3, 0, 1, 2]],
            [[0, 1, 2, 3, self::SEQUENCE_SKIP_VALUE, 1, 2], [4, 5], 3, [0, 1, 2, 4, 5, 3, self::SEQUENCE_DEFAULT_VALUE, 1, 2]],
            // `($howManyBitsToInsert & 7) > 0`
            [[0, 1, 2, 3, 0, 1, 2, 3], [0, 1, 2, 3, 0, 1, 2, 3, 0], 0, [0, 1, 2, 3, 0, 1, 2, 3, 0, 0, 1, 2, 3, 0, 1, 2, 3]],
            // Zero or more elements are to be inserted
            // after the existing elements (X?G?N?).
            [[], [], 1, [self::SEQUENCE_DEFAULT_VALUE]],
            // `$howManyBitsToInsert > 0`
            [[], [1], 0, [1]],
            // `$tailRelativeBitIndex > 0`
            [[2], [1], 1, [2, 1]],
            // `$firstIndex < 0`
            [[2, 3], [1], -2, [1, 2, 3]],
            // Keep the indices within the bounds.
            [[2], [1], -2, [1, 2]],
            // Resize the bitmap if the negative first bit
            // index is greater than the new bit count (N?GX?).
            [[], [1], -8, [1, self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE, self::SEQUENCE_DEFAULT_VALUE]],
            // The gap did not end at a full byte,
            // so remove the superfluous bits.
            [[], [1], -2, [1, self::SEQUENCE_DEFAULT_VALUE]],
            // The bits are not to be inserted at the beginning,
            // so splice (XNX).
            [[0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3], [0, 1, 2, 3, 0, 1, 2, 3], 8, [0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3]],
            // The bits are to be inserted at the beginning,
            // so prepend (NX).
            [[0, 1, 2, 3, 0, 1, 2, 3], [0, 1, 2, 3, 0, 1, 2, 3], 0, [0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 2, 3]],
            // `0 === ($firstIndex & 7) && ($howManyBitsToInsert & 7) > 0`
            [[1], [1], 0, [1, 1]],
            // Splice inside a byte (XNX).
            [[0, 1, 2, 3, 0, 1, 2, 3], [0, 1, 2, 3, 0, 1, 2, 3], 1, [0, 0, 1, 2, 3, 0, 1, 2, 3, 1, 2, 3, 0, 1, 2, 3]],
            // The tail did not end at a full byte,
            // so remove the superfluous bits.
            [[0, 1, 2, 3, 0, 1, 2], [0, 1, 2, 3, 0, 1, 2, 3], 1, [0, 0, 1, 2, 3, 0, 1, 2, 3, 1, 2, 3, 0, 1, 2]],
            // `($firstIndex & 7) > 0 && ($howManyBitsToInsert & 7) > 0`
            [[0, 1, 2, 3, 0, 1, 2, 3], [0, 1, 2, 3, 0, 1, 2], 1, [0, 0, 1, 2, 3, 0, 1, 2, 1, 2, 3, 0, 1, 2, 3]],
        ] as [$originalSequence, $inserted, $firstIndex, $expected]) {
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
                $originalVector = clone $vector;
                (yield [$vector, $elements, false, $sequence, $inserted, $firstIndex, $expected]);
            }
        }
        // Repeat the last test using a generator instead of an array.
        (yield [$originalVector, $elements, true, $sequence, $inserted, $firstIndex, $expected]);
    }

    /**
     * @dataProvider insertionProvider
     */
    public function testInsert(VectorInterface $vector, array $elements, bool $useGenerator, array $sequence, array $inserted, int $firstIndex, array $expected): void
    {
        $expectedSequence = [];
        foreach ($expected as $key) {
            if (self::SEQUENCE_DEFAULT_VALUE === $key) {
                $expectedSequence[] = 0;
            } else {
                $expectedSequence[] = $elements[$key];
            }
        }
        $generator = (static function () use ($elements, $inserted) {
            foreach ($inserted as $key) {
                (yield $elements[$key]);
            }
        })();
        $vector->insert($useGenerator ? $generator : \iterator_to_array($generator), $firstIndex);
        self::assertSequence($expectedSequence, $vector);
    }

    private static function getInstance(bool $filled = false): VectorInterface
    {
        $instance = new \Vectory\Uint24Vector();
        if ($filled) {
            $instance[9999] = 0;
        }

        return $instance;
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

    private static function assertSerialization($expected, string $serialized): void
    {
        $actualUnserialized = self::unserializeVector($serialized);
        $actual = [];
        foreach ($actualUnserialized as $index => $element) {
            $actual[$index] = $element;
        }
        self::assertSame($expected, $actual);
    }

    private static function fixSerializedLength(string $serialized): string
    {
        \preg_match('~^(C:[0-9]+:[^:]+:)([0-9]+)(.*)~s', $serialized, $match);

        return $match[1].(\strlen($match[3]) - \strlen(':{}')).$match[3];
    }

    private static function unserializeVector(string $serialized)
    {
        return \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\Uint24Vector', '\\')]]);
    }
}
