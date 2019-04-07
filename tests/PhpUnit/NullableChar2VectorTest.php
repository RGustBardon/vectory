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
final class NullableChar2VectorTest extends TestCase
{
    private const INVALID_VALUE = 0;

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
        $vector[0] = "\0\0";
        $vector[-1];
    }

    public function testThrowsIfIndexIsOutOfRange(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = "\0\0";
        $vector[1];
    }

    public function testThrowsIfValueOfInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        $vector = self::getInstance();
        $vector[0] = self::INVALID_VALUE;
    }

    public function testThrowsIfValueIsTooShort(): void
    {
        $this->expectException(\LengthException::class);
        $vector = self::getInstance();
        $vector[0] = \substr("\0\0", 0, -1);
    }

    public function testThrowsIfValueIsTooLong(): void
    {
        $this->expectException(\LengthException::class);
        $vector = self::getInstance();
        $vector[0] = "\0\0"."\0";
    }

    public function testArrayAccess(): void
    {
        $vector = self::getInstance();
        self::assertFalse(isset($vector[0]));
        $vector[0] = "\0\0";
        self::assertTrue(isset($vector[0]));
        self::assertFalse(isset($vector[1]));
        self::assertSame("\0\0", $vector[0]);
        $value = self::getRandomValue();
        $vector[2] = $value;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertTrue(isset($vector[2]));
        self::assertFalse(isset($vector[3]));
        self::assertSame("\0\0", $vector[0]);
        self::assertSame("\0\0", $vector[1]);
        self::assertSame($value, $vector[2]);
        do {
            $otherValue = self::getRandomValue();
        } while ($value === $otherValue);
        $vector[2] = $otherValue;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertTrue(isset($vector[2]));
        self::assertFalse(isset($vector[3]));
        self::assertSame("\0\0", $vector[0]);
        self::assertSame("\0\0", $vector[1]);
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
        self::assertSame("\0\0", $vector[1]);
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
        self::assertSame("\0\0", $vector[0]);
        self::assertNull($vector[1]);
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
        $vector[2] = "\0\0";
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

    public function testIteratorAggregate(): void
    {
        $vector = self::getInstance();
        self::assertSame([], \iterator_to_array($vector));
        $element = self::getRandomValue();
        $vector[1] = $element;
        self::assertSame(["\0\0", $element], \iterator_to_array($vector));
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

    public function testIteratorAggregateWithNullValue(): void
    {
        $vector = self::getInstance();
        $vector[0] = null;
        self::assertSame([null], \iterator_to_array($vector));
        $vector[2] = null;
        self::assertSame([null, "\0\0", null], \iterator_to_array($vector));
    }

    public function testJsonSerializable(): void
    {
        $vector = self::getInstance();
        self::assertNativeJson([], $vector);
        $value = self::getRandomUtf8String();
        $sequence = [$value, self::getRandomUtf8String(), $value];
        foreach ($sequence as $value) {
            $vector[] = $value;
        }
        $vector[4] = "\0\0";
        \array_push($sequence, "\0\0", "\0\0");
        self::assertNativeJson($sequence, $vector);
    }

    public function testJsonSerializableWithNullValue(): void
    {
        $vector = self::getInstance();
        $vector[0] = null;
        self::assertNativeJson([null], $vector);
        $vector[2] = null;
        self::assertNativeJson([null, "\0\0", null], $vector);
    }

    private static function assertNativeJson($expected, $actual): void
    {
        $expectedJson = \json_encode($expected);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());
        $actualJson = \json_encode($actual);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());
        self::assertSame($expectedJson, $actualJson);
    }

    private static function getInstance(): VectorInterface
    {
        return new \Vectory\NullableChar2Vector();
    }

    private static function getRandomValue()
    {
        $value = '';
        for ($i = 0; $i < 2; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }

    private static function getRandomUtf8String(): string
    {
        \assert(0x10ffff <= \mt_getrandmax());
        $string = '';
        while (\strlen($string) < 2) {
            $characterMaxLength = \min(4, 2 - \strlen($string));
            $character = '';
            switch (\mt_rand(1, $characterMaxLength)) {
                case 1:
                    $character = \mb_chr(\mt_rand(0x0, 0x7f));

                    break;
                case 2:
                    $character = \mb_chr(\mt_rand(0x80, 0x7ff));

                    break;
                case 3:
                    $character = \mb_chr(\mt_rand(0x800, 0xffff));

                    break;
                case 4:
                    $character = \mb_chr(\mt_rand(0x10000, 0x10ffff));

                    break;
            }
            $string .= $character;
        }

        return $string;
    }
}
