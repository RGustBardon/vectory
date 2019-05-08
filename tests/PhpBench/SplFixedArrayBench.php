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
final class SplFixedArrayBench
{
    private const INVALID_VALUE = 0;
    private $instanceForArrayAccessOffsetGetRandomAccess;
    private $instanceForArrayAccessOffsetSetOverwriting;
    private $instanceForArrayAccessOffsetSetPushingWithoutGap;
    private $lastIndexOfArrayAccessOffsetSetPushingWithoutGap = 0;
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
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap->setSize($this->lastIndexOfArrayAccessOffsetSetPushingWithoutGap + 1);
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithoutGap++] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100;
        $this->instanceForArrayAccessOffsetSetPushingWithGap->setSize($this->lastIndexOfArrayAccessOffsetSetPushingWithGap + 1);
        $this->instanceForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap] = false;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetPopping(): void
    {
        $this->instanceForArrayAccessOffsetUnsetPopping->setSize($this->lastIndexOfArrayAccessOffsetUnsetPopping--);
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetShifting(): void
    {
        $elements = $this->instanceForArrayAccessOffsetUnsetShifting->toArray();
        unset($elements[0]);
        $this->instanceForArrayAccessOffsetUnsetShifting = \SplFixedArray::fromArray($elements, false);
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtHead(): void
    {
        $this->instanceForDeleteAtHead = \SplFixedArray::fromArray(\array_slice($this->instanceForDeleteAtHead->toArray(), \mt_rand(1, 100)), false);
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtTail(): void
    {
        $this->instanceForDeleteAtTail->setSize(\count($this->instanceForDeleteAtTail) - \mt_rand(1, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtHead(): void
    {
        $insertedElements = $this->batchForInsert;
        if (\count($this->instanceForInsertAtHead) > 0) {
            \array_push($insertedElements, ...$this->instanceForInsertAtHead->toArray());
        }
        $this->instanceForInsertAtHead = \SplFixedArray::fromArray($insertedElements, false);
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtTail(): void
    {
        $elementCount = \count($this->instanceForInsertAtTail);
        $this->instanceForInsertAtTail->setSize($elementCount + \count($this->batchForInsert));
        foreach ($this->batchForInsert as $element) {
            $this->instanceForInsertAtTail[$elementCount++] = $element;
        }
    }

    /**
     * @Revs(10000)
     */
    public function benchInsertUnshifting(): void
    {
        $elements = $this->instanceForInsertUnshifting->toArray();
        \array_unshift($elements, false);
        $this->instanceForInsertUnshifting = \SplFixedArray::fromArray($elements, false);
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
        \unserialize($this->serializedInstanceForSerializableUnserialize, ['allowed_classes' => [\SplFixedArray::class]])->__wakeup();
    }

    private function setUpArrayAccessBenchmark(): void
    {
        $this->instanceForArrayAccessOffsetGetRandomAccess = self::createFilledSplFixedArray();
        $this->instanceForArrayAccessOffsetSetOverwriting = self::createFilledSplFixedArray();
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap = new \SplFixedArray();
        $this->instanceForArrayAccessOffsetSetPushingWithGap = new \SplFixedArray();
        $this->instanceForArrayAccessOffsetUnsetPopping = self::createFilledSplFixedArray();
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->instanceForArrayAccessOffsetUnsetShifting = self::createFilledSplFixedArray();
    }

    private function setUpDeleteBenchmark(): void
    {
        $this->instanceForDeleteAtHead = self::createFilledSplFixedArray();
        $this->instanceForDeleteAtTail = self::createFilledSplFixedArray();
    }

    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 50, false);
        $this->instanceForInsertAtHead = new \SplFixedArray();
        $this->instanceForInsertAtTail = new \SplFixedArray();
        $this->instanceForInsertUnshifting = new \SplFixedArray();
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->instanceForIteratorAggregate = self::createFilledSplFixedArray();
    }

    private function setUpJsonSerializableBenchmark(): void
    {
        $this->instanceForJsonSerializable = self::createFilledSplFixedArray();
    }

    private function setUpSerializableBenchmark(): void
    {
        $this->instanceForSerializableSerialize = self::createFilledSplFixedArray();
        $this->serializedInstanceForSerializableUnserialize = \serialize(self::createFilledSplFixedArray());
    }

    private static function createFilledSplFixedArray(): \SplFixedArray
    {
        $splFixedArray = new \SplFixedArray();
        $splFixedArray->setSize(10000);
        for ($i = 0; $i < 10000; ++$i) {
            $splFixedArray[$i] = false;
        }

        return $splFixedArray;
    }

    private static function getRandomValue()
    {
        return [false, true][\mt_rand(0, 1)];
    }
}
