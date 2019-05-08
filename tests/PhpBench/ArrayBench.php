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
    private $instanceForArrayAccessOffsetGetRandomAccess;
    private $instanceForArrayAccessOffsetSetOverwriting;
    private $instanceForArrayAccessOffsetSetPushingWithoutGap;
    private $instanceForArrayAccessOffsetSetPushingWithGap;
    private $lastIndexOfArrayAccessOffsetSetPushingWithGap = 0;
    private $instanceForArrayAccessOffsetUnsetPopping;
    private $lastIndexOfArrayAccessOffsetUnsetPopping = 0;
    private $instanceForArrayAccessOffsetUnsetShifting;
    private $instanceForDeleteAtHead;
    private $instanceForDeleteAtTail;
    private $batchForInsert = [];
    private $instanceForInsertAtHead;
    private $instanceForInsertAtTail;
    private $instanceForInsertUnshifting;
    private $instanceForIteratorAggregate;
    private $instanceForJsonSerializable;
    private $instanceForSerializableSerialize;
    private $serializedInstanceForSerializableUnserialize;

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
        $_ = $this->instanceForArrayAccessOffsetGetRandomAccess[\mt_rand(0, 9999)];
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetOverwriting(): void
    {
        $this->instanceForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap[] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->instanceForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetPopping(): void
    {
        unset($this->instanceForArrayAccessOffsetUnsetPopping[$this->lastIndexOfArrayAccessOffsetUnsetPopping--]);
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetShifting(): void
    {
        unset($this->instanceForArrayAccessOffsetUnsetShifting[0]);
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtHead(): void
    {
        \array_splice($this->instanceForDeleteAtHead, 0, \mt_rand(1, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtTail(): void
    {
        \array_splice($this->instanceForDeleteAtTail, -\mt_rand(1, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtHead(): void
    {
        \array_unshift($this->instanceForInsertAtHead, ...$this->batchForInsert);
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtTail(): void
    {
        \array_push($this->instanceForInsertAtTail, ...$this->batchForInsert);
    }

    /**
     * @Revs(10000)
     */
    public function benchInsertUnshifting(): void
    {
        \array_unshift($this->instanceForInsertUnshifting, false);
    }

    /**
     * @Iterations(5)
     */
    public function benchIteratorAggregate(): void
    {
        foreach ($this->instanceForIteratorAggregate as $element) {
        }
    }

    /**
     * @Iterations(5)
     */
    public function benchJsonSerializable(): void
    {
        \json_encode($this->instanceForJsonSerializable);
    }

    /**
     * @Iterations(5)
     */
    public function benchSerializableSerialize(): void
    {
        \serialize($this->instanceForSerializableSerialize);
    }

    /**
     * @Iterations(5)
     */
    public function benchSerializableUnserialize(): void
    {
        \unserialize($this->serializedInstanceForSerializableUnserialize, ['allowed_classes' => false]);
    }

    private function setUpArrayAccessBenchmark(): void
    {
        $this->instanceForArrayAccessOffsetGetRandomAccess = \array_fill(0, 10000, false);
        $this->instanceForArrayAccessOffsetSetOverwriting = \array_fill(0, 10000, false);
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap = [];
        $this->instanceForArrayAccessOffsetSetPushingWithGap = [];
        $this->instanceForArrayAccessOffsetUnsetPopping = \array_fill(0, 10000, false);
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->instanceForArrayAccessOffsetUnsetShifting = \array_fill(0, 10000, false);
    }

    private function setUpDeleteBenchmark(): void
    {
        $this->instanceForDeleteAtHead = \array_fill(0, 10000, false);
        $this->instanceForDeleteAtTail = \array_fill(0, 10000, false);
    }

    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 50, false);
        $this->instanceForInsertAtHead = [];
        $this->instanceForInsertAtTail = [];
        $this->instanceForInsertUnshifting = [];
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->instanceForIteratorAggregate = \array_fill(0, 10000, false);
    }

    private function setUpJsonSerializableBenchmark(): void
    {
        $this->instanceForJsonSerializable = \array_fill(0, 10000, false);
    }

    private function setUpSerializableBenchmark(): void
    {
        $this->instanceForSerializableSerialize = \array_fill(0, 10000, false);
        $this->serializedInstanceForSerializableUnserialize = \serialize(\array_fill(0, 10000, false));
    }

    private static function getRandomValue()
    {
        return [false, true][\mt_rand(0, 1)];
    }
}
