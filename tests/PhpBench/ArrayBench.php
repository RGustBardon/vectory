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

/**
 * @BeforeMethods({"setUp"})
 *
 * @internal
 */
final class ArrayBench
{
    private const INVALID_VALUE = 0;
    private $arrayForArrayAccessOffsetGetRandomAccess;
    private $arrayForArrayAccessOffsetSetOverwriting;
    private $arrayForArrayAccessOffsetSetPushingWithoutGap;
    private $arrayForArrayAccessOffsetSetPushingWithGap;
    private $lastIndexOfArrayAccessOffsetSetPushingWithGap = 0;
    private $arrayForArrayAccessOffsetUnsetPopping;
    private $lastIndexOfArrayAccessOffsetUnsetPopping = 0;
    private $arrayForArrayAccessOffsetUnsetShifting;
    private $arrayForDeleteAtHead;
    private $arrayForDeleteAtTail;
    private $batchForInsert = [];
    private $arrayForInsertAtHead;
    private $arrayForInsertAtTail;
    private $arrayForInsertUnshifting;
    private $arrayForIteratorAggregate;
    private $arrayForJsonSerializable;
    private $arrayForSerializableSerialize;
    private $serializedarrayForSerializableUnserialize;

    public function setUp(): void
    {
        \error_reporting(\E_ALL);
        \ini_set('precision', '14');
        \ini_set('serialize_precision', '14');
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
        static $_;
        $_ = $this->arrayForArrayAccessOffsetGetRandomAccess[\mt_rand(0, 9999)];
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetOverwriting(): void
    {
        $this->arrayForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->arrayForArrayAccessOffsetSetPushingWithoutGap[] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->arrayForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetPopping(): void
    {
        unset($this->arrayForArrayAccessOffsetUnsetPopping[$this->lastIndexOfArrayAccessOffsetUnsetPopping--]);
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetShifting(): void
    {
        unset($this->arrayForArrayAccessOffsetUnsetShifting[0]);
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtHead(): void
    {
        \array_splice($this->arrayForDeleteAtHead, 0, \mt_rand(1, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtTail(): void
    {
        \array_splice($this->arrayForDeleteAtTail, -\mt_rand(1, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtHead(): void
    {
        \array_unshift($this->arrayForInsertAtHead, ...$this->batchForInsert);
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtTail(): void
    {
        \array_push($this->arrayForInsertAtTail, ...$this->batchForInsert);
    }

    /**
     * @Revs(10000)
     */
    public function benchInsertUnshifting(): void
    {
        \array_unshift($this->arrayForInsertUnshifting, false);
    }

    /**
     * @Iterations(5)
     */
    public function benchIteratorAggregate(): void
    {
        foreach ($this->arrayForIteratorAggregate as $element) {
        }
    }

    /**
     * @Iterations(5)
     */
    public function benchJsonSerializable(): void
    {
        \json_encode($this->arrayForJsonSerializable);
    }

    /**
     * @Iterations(5)
     */
    public function benchSerializableSerialize(): void
    {
        \serialize($this->arrayForSerializableSerialize);
    }

    /**
     * @Iterations(5)
     */
    public function benchSerializableUnserialize(): void
    {
        \unserialize($this->serializedarrayForSerializableUnserialize, ['allowed_classes' => false]);
    }

    private function setUpArrayAccessBenchmark(): void
    {
        $this->arrayForArrayAccessOffsetGetRandomAccess = \array_fill(0, 10000, false);
        $this->arrayForArrayAccessOffsetSetOverwriting = \array_fill(0, 10000, false);
        $this->arrayForArrayAccessOffsetSetPushingWithoutGap = [];
        $this->arrayForArrayAccessOffsetSetPushingWithGap = [];
        $this->arrayForArrayAccessOffsetUnsetPopping = \array_fill(0, 10000, false);
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->arrayForArrayAccessOffsetUnsetShifting = \array_fill(0, 10000, false);
    }

    private function setUpDeleteBenchmark(): void
    {
        $this->arrayForDeleteAtHead = \array_fill(0, 10000, false);
        $this->arrayForDeleteAtTail = \array_fill(0, 10000, false);
    }

    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 50, false);
        $this->arrayForInsertAtHead = [];
        $this->arrayForInsertAtTail = [];
        $this->arrayForInsertUnshifting = [];
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->arrayForIteratorAggregate = \array_fill(0, 10000, false);
    }

    private function setUpJsonSerializableBenchmark(): void
    {
        $this->arrayForJsonSerializable = \array_fill(0, 10000, false);
    }

    private function setUpSerializableBenchmark(): void
    {
        $this->arrayForSerializableSerialize = \array_fill(0, 10000, false);
        $this->serializedarrayForSerializableUnserialize = \serialize(\array_fill(0, 10000, false));
    }

    private static function getRandomValue()
    {
        return [false, true][\mt_rand(0, 1)];
    }
}
