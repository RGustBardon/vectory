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
final class DsDequeBench
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
        $count = \count($this->instanceForArrayAccessOffsetSetPushingWithGap);
        $this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100;
        $this->instanceForArrayAccessOffsetSetPushingWithGap->allocate($this->lastIndexOfArrayAccessOffsetSetPushingWithGap);
        $elements = \array_fill(0, (int) ($this->lastIndexOfArrayAccessOffsetSetPushingWithGap - $count - 1), false);
        $elements[] = false;
        $this->instanceForArrayAccessOffsetSetPushingWithGap->push(...$elements);
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
        $this->instanceForArrayAccessOffsetUnsetShifting->shift();
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtHead(): void
    {
        $this->instanceForDeleteAtHead = $this->instanceForDeleteAtHead->slice(\mt_rand(1, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtTail(): void
    {
        for ($i = \mt_rand(1, 100); $i > 0; --$i) {
            $this->instanceForDeleteAtTail->pop();
        }
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtHead(): void
    {
        $this->instanceForInsertAtHead->unshift(...$this->batchForInsert);
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtTail(): void
    {
        $this->instanceForInsertAtTail->push(...$this->batchForInsert);
    }

    /**
     * @Revs(10000)
     */
    public function benchInsertUnshifting(): void
    {
        $this->instanceForInsertUnshifting->unshift(false);
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
        \unserialize($this->serializedInstanceForSerializableUnserialize, ['allowed_classes' => ['Ds\\Deque']]);
    }

    private function setUpArrayAccessBenchmark(): void
    {
        $this->instanceForArrayAccessOffsetGetRandomAccess = new \Ds\Deque();
        $this->instanceForArrayAccessOffsetGetRandomAccess->push(...\array_fill(0, 10000, false));
        $this->instanceForArrayAccessOffsetSetOverwriting = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap = new \Ds\Deque();
        $this->instanceForArrayAccessOffsetSetPushingWithGap = new \Ds\Deque();
        $this->instanceForArrayAccessOffsetUnsetPopping = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->instanceForArrayAccessOffsetUnsetShifting = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
    }

    private function setUpDeleteBenchmark(): void
    {
        $this->instanceForDeleteAtHead = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
        $this->instanceForDeleteAtTail = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
    }

    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 50, false);
        $this->instanceForInsertAtHead = new \Ds\Deque();
        $this->instanceForInsertAtTail = new \Ds\Deque();
        $this->instanceForInsertUnshifting = new \Ds\Deque();
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->instanceForIteratorAggregate = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
    }

    private function setUpJsonSerializableBenchmark(): void
    {
        $this->instanceForJsonSerializable = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
    }

    private function setUpSerializableBenchmark(): void
    {
        $this->instanceForSerializableSerialize = clone $this->instanceForArrayAccessOffsetGetRandomAccess;
        $this->serializedInstanceForSerializableUnserialize = \serialize($this->instanceForArrayAccessOffsetGetRandomAccess);
    }

    private static function getRandomValue()
    {
        return [false, true][\mt_rand(0, 1)];
    }
}
