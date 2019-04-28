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
final class NullableBoolVectorBench
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
        $this->vectorForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithoutGap[] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithGap[
            $this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100
        ] = false;
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
        $this->vectorForArrayAccessOffsetGetRandomAccess[10000] = false;

        $this->vectorForArrayAccessOffsetSetOverwriting = self::getInstance();
        $this->vectorForArrayAccessOffsetSetOverwriting[10000] = false;

        $this->vectorForArrayAccessOffsetSetPushingWithoutGap = self::getInstance();

        $this->vectorForArrayAccessOffsetSetPushingWithGap = self::getInstance();

        $this->vectorForArrayAccessOffsetUnsetPopping = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetPopping[10000] = false;
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;

        $this->vectorForArrayAccessOffsetUnsetShifting = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetShifting[10000] = false;
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
        return new \Vectory\NullableBoolVector();
    }

    private static function getRandomValue()
    {
        return [false, true][\mt_rand(0, 1)];
    }
}
