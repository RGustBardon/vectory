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
    private $splFixedArrayForArrayAccessOffsetGetRandomAccess;
    private $splFixedArrayForArrayAccessOffsetSetOverwriting;
    private $splFixedArrayForArrayAccessOffsetSetPushingWithoutGap;
    private $lastIndexOfArrayAccessOffsetSetPushingWithoutGap = 0;
    private $splFixedArrayForArrayAccessOffsetSetPushingWithGap;
    private $lastIndexOfArrayAccessOffsetSetPushingWithGap = 0;
    private $splFixedArrayForArrayAccessOffsetUnsetPopping;
    private $lastIndexOfArrayAccessOffsetUnsetPopping = 0;
    private $splFixedArrayForArrayAccessOffsetUnsetShifting;
    private $splFixedArrayForDeleteAtHead;
    private $splFixedArrayForDeleteAtTail;
    private $batchForInsert = [];
    private $splFixedArrayForInsertAtHead;
    private $splFixedArrayForInsertAtTail;
    private $splFixedArrayForInsertUnshifting;
    private $splFixedArrayForIteratorAggregate;
    private $splFixedArrayForJsonSerializable;
    private $splFixedArrayForSerializableSerialize;
    private $serializedSplFixedArrayForSerializableUnserialize;
    
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
        $_ = $this->splFixedArrayForArrayAccessOffsetGetRandomAccess[\mt_rand(0, 9999)];
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetOverwriting(): void
    {
        $this->splFixedArrayForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = false;
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->splFixedArrayForArrayAccessOffsetSetPushingWithoutGap->setSize($this->lastIndexOfArrayAccessOffsetSetPushingWithoutGap + 1);
        $this->splFixedArrayForArrayAccessOffsetSetPushingWithoutGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithoutGap++] = false;
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100;
        $this->splFixedArrayForArrayAccessOffsetSetPushingWithGap->setSize($this->lastIndexOfArrayAccessOffsetSetPushingWithGap + 1);
        $this->splFixedArrayForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap] = false;
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetPopping(): void
    {
        $this->splFixedArrayForArrayAccessOffsetUnsetPopping->setSize($this->lastIndexOfArrayAccessOffsetUnsetPopping--);
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetShifting(): void
    {
        $elements = $this->splFixedArrayForArrayAccessOffsetUnsetShifting->toArray();
        unset($elements[0]);
        $this->splFixedArrayForArrayAccessOffsetUnsetShifting = \SplFixedArray::fromArray($elements, false);
    }
    
    /**
     * @Revs(100)
     */
    public function benchDeleteAtHead(): void
    {   
        $this->splFixedArrayForDeleteAtHead =
            \SplFixedArray::fromArray(\array_slice($this->splFixedArrayForDeleteAtHead->toArray(), \mt_rand(1, 100)), false);
    }
    
    /**
     * @Revs(100)
     */
    public function benchDeleteAtTail(): void
    {
        $this->splFixedArrayForDeleteAtTail->setSize(\count($this->splFixedArrayForDeleteAtTail) -\mt_rand(1, 100));
    }
    
    /**
     * @Revs(100)
     */
    public function benchInsertAtHead(): void
    {
        $insertedElements = $this->batchForInsert;
        if (\count($this->splFixedArrayForInsertAtHead) > 0) {
            \array_push($insertedElements, ...$this->splFixedArrayForInsertAtHead->toArray());
        }
        $this->splFixedArrayForInsertAtHead = \SplFixedArray::fromArray($insertedElements, false);
    }
    
    /**
     * @Revs(100)
     */
    public function benchInsertAtTail(): void
    {
        $elementCount = \count($this->splFixedArrayForInsertAtTail);
        $this->splFixedArrayForInsertAtTail->setSize($elementCount + \count($this->batchForInsert));
        foreach ($this->batchForInsert as $element) {
            $this->splFixedArrayForInsertAtTail[$elementCount++] = $element;
        }
    }
    
    /**
     * @Revs(10000)
     */
    public function benchInsertUnshifting(): void
    {
        $elements = $this->splFixedArrayForInsertUnshifting->toArray();
        \array_unshift($elements, false);
        $this->splFixedArrayForInsertUnshifting = \SplFixedArray::fromArray($elements, false);
    }
    
    /**
     * @Iterations(5)
     */
    public function benchIteratorAggregate(): void
    {
        foreach ($this->splFixedArrayForIteratorAggregate as $element) {
        }
    }
    
    /**
     * @Iterations(5)
     */
    public function benchJsonSerializable(): void
    {
        \json_encode($this->splFixedArrayForJsonSerializable);
    }
    
    /**
     * @Iterations(5)
     */
    public function benchSerializableSerialize(): void
    {
        \serialize($this->splFixedArrayForSerializableSerialize);
    }
    
    /**
     * @Iterations(5)
     */
    public function benchSerializableUnserialize(): void
    {
        \unserialize(
            $this->serializedSplFixedArrayForSerializableUnserialize,
            ['allowed_classes' => [\SplFixedArray::class]]
        )->__wakeup();
    }
    
    private function setUpArrayAccessBenchmark(): void
    {
        $this->splFixedArrayForArrayAccessOffsetGetRandomAccess = self::createFilledSplFixedArray();
        $this->splFixedArrayForArrayAccessOffsetSetOverwriting = self::createFilledSplFixedArray();
        $this->splFixedArrayForArrayAccessOffsetSetPushingWithoutGap = new \SplFixedArray();
        $this->splFixedArrayForArrayAccessOffsetSetPushingWithGap = new \SplFixedArray();
        $this->splFixedArrayForArrayAccessOffsetUnsetPopping = self::createFilledSplFixedArray();
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->splFixedArrayForArrayAccessOffsetUnsetShifting = self::createFilledSplFixedArray();
    }
    
    private function setUpDeleteBenchmark(): void
    {
        $this->splFixedArrayForDeleteAtHead = self::createFilledSplFixedArray();
        $this->splFixedArrayForDeleteAtTail = self::createFilledSplFixedArray();
    }
    
    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 50, false);
        $this->splFixedArrayForInsertAtHead = new \SplFixedArray();
        $this->splFixedArrayForInsertAtTail = new \SplFixedArray();
        $this->splFixedArrayForInsertUnshifting = new \SplFixedArray();
    }
    
    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->splFixedArrayForIteratorAggregate = self::createFilledSplFixedArray();
    }
    
    private function setUpJsonSerializableBenchmark(): void
    {
        $this->splFixedArrayForJsonSerializable = self::createFilledSplFixedArray();
    }
    
    private function setUpSerializableBenchmark(): void
    {
        $this->splFixedArrayForSerializableSerialize = self::createFilledSplFixedArray();
        $this->serializedSplFixedArrayForSerializableUnserialize = \serialize(self::createFilledSplFixedArray());
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
