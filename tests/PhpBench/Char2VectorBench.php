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

namespace Vectory\Tests\PhpBench;

require_once __DIR__.'/../../vendor/autoload.php';

/**
 * @BeforeMethods({"setUp"})
 *
 * @internal
 */
final class Char2VectorBench
{
    private const INVALID_VALUE =
        0
    ;

    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetGetRandomAccess;

    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetSetOverwriting;

    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetSetPushingWithoutGap;

    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetSetPushingWithGap;
    private /* int */ $lastIndexOfArrayAccessOffsetSetPushingWithGap = 0;

    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetUnsetPopping;
    private /* int */ $lastIndexOfArrayAccessOffsetUnsetPopping = 0;

    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetUnsetShifting;

    public function setUp(): void
    {
        \mt_srand(0);

        $this->setUpArrayAccessBenchmark();
        $this->setUpDeleteBenchmark();
        $this->setUpInsertBenchmark();
        $this->setUpIteratorAggregateBenchmark();
        $this->setUpJsonSerializableBenchmark();
        $this->setUpSerializableBenchmark();
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetGetRandomAccess(): void
    {
        $this->vectorForArrayAccessOffsetGetRandomAccess[\mt_rand(0, 9999)];
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetOverwriting(): void
    {
        $this->vectorForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = "\x0\x0";
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithoutGap[] = "\x0\x0";
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithGap[
            $this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100
        ] = "\x0\x0";
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetPopping(): void
    {
        unset($this->vectorForArrayAccessOffsetUnsetPopping[
            $this->lastIndexOfArrayAccessOffsetUnsetPopping--
        ]);
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetShifting(): void
    {
        unset($this->vectorForArrayAccessOffsetUnsetShifting[0]);
    }

    private function setUpArrayAccessBenchmark(): void
    {
        $this->vectorForArrayAccessOffsetGetRandomAccess = self::getInstance();
        $this->vectorForArrayAccessOffsetGetRandomAccess[10000] = "\x0\x0";

        $this->vectorForArrayAccessOffsetSetOverwriting = self::getInstance();
        $this->vectorForArrayAccessOffsetSetOverwriting[10000] = "\x0\x0";

        $this->vectorForArrayAccessOffsetSetPushingWithoutGap = self::getInstance();

        $this->vectorForArrayAccessOffsetSetPushingWithGap = self::getInstance();

        $this->vectorForArrayAccessOffsetUnsetPopping = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetPopping[10000] = "\x0\x0";
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;

        $this->vectorForArrayAccessOffsetUnsetShifting = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetShifting[10000] = "\x0\x0";
    }

    private function setUpDeleteBenchmark(): void
    {
    }

    private function setUpInsertBenchmark(): void
    {
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
    }

    private function setUpJsonSerializableBenchmark(): void
    {
    }

    private function setUpSerializableBenchmark(): void
    {
    }

    private static function getInstance(): \Vectory\VectorInterface
    {
        return new \Vectory\Char2Vector();
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
