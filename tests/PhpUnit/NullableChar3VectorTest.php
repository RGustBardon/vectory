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
final class NullableChar3VectorTest extends TestCase
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
        $vector[0] = "\0\0\0";
        $vector[-1];
    }

    public function testThrowsIfIndexIsOutOfRange(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $vector = self::getInstance();
        $vector[0] = "\0\0\0";
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
        $vector[0] = \substr("\0\0\0", 0, -1);
    }

    public function testThrowsIfValueIsTooLong(): void
    {
        $this->expectException(\LengthException::class);
        $vector = self::getInstance();
        $vector[0] = "\0\0\0"."\0";
    }

    public function testArrayAccess(): void
    {
        $vector = self::getInstance();
        self::assertFalse(isset($vector[0]));
        $vector[0] = "\0\0\0";
        self::assertTrue(isset($vector[0]));
        self::assertFalse(isset($vector[1]));
        self::assertSame("\0\0\0", $vector[0]);
        $value = self::getRandomValue();
        $vector[2] = $value;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertTrue(isset($vector[2]));
        self::assertFalse(isset($vector[3]));
        self::assertSame("\0\0\0", $vector[0]);
        self::assertSame("\0\0\0", $vector[1]);
        self::assertSame($value, $vector[2]);
        do {
            $otherValue = self::getRandomValue();
        } while ($value === $otherValue);
        $vector[2] = $otherValue;
        self::assertTrue(isset($vector[0]));
        self::assertTrue(isset($vector[1]));
        self::assertTrue(isset($vector[2]));
        self::assertFalse(isset($vector[3]));
        self::assertSame("\0\0\0", $vector[0]);
        self::assertSame("\0\0\0", $vector[1]);
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
        self::assertSame("\0\0\0", $vector[1]);
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
        self::assertSame("\0\0\0", $vector[0]);
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
        $vector[] = "\0\0\0";
        self::assertCount(3, $vector);
        $vector[3] = null;
        self::assertCount(4, $vector);
    }

    private static function getInstance(): VectorInterface
    {
        return new \Vectory\NullableChar3Vector();
    }

    private static function getRandomValue()
    {
        $value = '';
        for ($i = 0; $i < 3; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }
}
