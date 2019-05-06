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
final class Uint8VectorBench
{
    private const INVALID_VALUE = '0';
    private $vectorForArrayAccessOffsetGetRandomAccess;
    private $vectorForArrayAccessOffsetSetOverwriting;
    private $vectorForArrayAccessOffsetSetPushingWithoutGap;
    private $vectorForArrayAccessOffsetSetPushingWithGap;
    private $lastIndexOfArrayAccessOffsetSetPushingWithGap = 0;
    private $vectorForArrayAccessOffsetUnsetPopping;
    private $lastIndexOfArrayAccessOffsetUnsetPopping = 0;
    private $vectorForArrayAccessOffsetUnsetShifting;
    private $vectorForDeleteAtHead;
    private $vectorForDeleteAtTail;
    private $batchForInsert = [];
    private $vectorForInsertAtHead;
    private $vectorForInsertAtTail;
    private $vectorForInsertUnshifting;
    private $vectorForIteratorAggregate;
    private $vectorForJsonSerializable;
    private $vectorForSerializableSerialize;
    private $serializedVectorForSerializableUnserialize;

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
        $_ = $this->vectorForArrayAccessOffsetGetRandomAccess[\mt_rand(0, 9999)];
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetOverwriting(): void
    {
        $this->vectorForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = 0;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithoutGap[] = 0;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100] = 0;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetPopping(): void
    {
        unset($this->vectorForArrayAccessOffsetUnsetPopping[$this->lastIndexOfArrayAccessOffsetUnsetPopping--]);
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetShifting(): void
    {
        unset($this->vectorForArrayAccessOffsetUnsetShifting[0]);
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtHead(): void
    {
        $this->vectorForDeleteAtHead->delete(0, \mt_rand(0, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtTail(): void
    {
        $this->vectorForDeleteAtHead->delete(-\mt_rand(0, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtHead(): void
    {
        $this->vectorForInsertAtHead->insert($this->batchForInsert, 0);
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtTail(): void
    {
        $this->vectorForInsertAtTail->insert($this->batchForInsert);
    }

    /**
     * @Revs(10000)
     */
    public function benchInsertUnshifting(): void
    {
        $this->vectorForInsertUnshifting->insert([0], 0);
    }

    /**
     * @Iterations(5)
     */
    public function benchIteratorAggregate(): void
    {
        foreach ($this->vectorForIteratorAggregate as $element) {
        }
    }

    /**
     * @Iterations(5)
     */
    public function benchJsonSerializable(): void
    {
        \json_encode($this->vectorForJsonSerializable);
    }

    /**
     * @Iterations(5)
     */
    public function benchSerializableSerialize(): void
    {
        \serialize($this->vectorForSerializableSerialize);
    }

    /**
     * @Iterations(5)
     */
    public function benchSerializableUnserialize(): void
    {
        \unserialize($this->serializedVectorForSerializableUnserialize, ['allowed_classes' => [\ltrim('\\Vectory\\Uint8Vector', '\\')]]);
    }

    public static function getRandomValue()
    {
        $value = \dechex(\mt_rand(0x0, 0xff));
        for ($i = 1; $i < 1; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }

        return \hexdec($value);
    }

    private function setUpArrayAccessBenchmark(): void
    {
        $this->vectorForArrayAccessOffsetGetRandomAccess = self::getInstance();
        $this->vectorForArrayAccessOffsetGetRandomAccess[9999] = 0;
        $this->vectorForArrayAccessOffsetSetOverwriting = self::getInstance();
        $this->vectorForArrayAccessOffsetSetOverwriting[9999] = 0;
        $this->vectorForArrayAccessOffsetSetPushingWithoutGap = self::getInstance();
        $this->vectorForArrayAccessOffsetSetPushingWithGap = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetPopping = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetPopping[9999] = 0;
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->vectorForArrayAccessOffsetUnsetShifting = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetShifting[9999] = 0;
    }

    private function setUpDeleteBenchmark(): void
    {
        $this->vectorForDeleteAtHead = self::getInstance();
        $this->vectorForDeleteAtHead[10000] = 0;
        $this->vectorForDeleteAtTail = self::getInstance();
        $this->vectorForDeleteAtTail[10000] = 0;
    }

    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 50, 0);
        $this->vectorForInsertAtHead = self::getInstance();
        $this->vectorForInsertAtTail = self::getInstance();
        $this->vectorForInsertUnshifting = self::getInstance();
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->vectorForIteratorAggregate = self::getInstance();
        $this->vectorForIteratorAggregate[10000] = 0;
    }

    private function setUpJsonSerializableBenchmark(): void
    {
        $this->vectorForJsonSerializable = self::getInstance();
        $this->vectorForJsonSerializable[10000] = 0;
    }

    private function setUpSerializableBenchmark(): void
    {
        $this->vectorForSerializableSerialize = self::getInstance();
        $this->vectorForSerializableSerialize[10000] = 0;
        $vector = self::getInstance();
        $vector[10000] = 0;
        $this->serializedVectorForSerializableUnserialize = \serialize($vector);
    }

    private static function getInstance(): \Vectory\VectorInterface
    {
        return new \Vectory\Uint8Vector();
    }
}
