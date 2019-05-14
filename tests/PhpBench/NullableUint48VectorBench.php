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
final class NullableUint48VectorBench
{
    private const INVALID_VALUE = '0';
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
        $this->instanceForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = 0;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap[] = 0;
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->instanceForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100] = 0;
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
        $this->instanceForDeleteAtHead->delete(0, \mt_rand(0, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchDeleteAtTail(): void
    {
        $this->instanceForDeleteAtTail->delete(-\mt_rand(0, 100));
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtHead(): void
    {
        $this->instanceForInsertAtHead->insert($this->batchForInsert, 0);
    }

    /**
     * @Revs(100)
     */
    public function benchInsertAtTail(): void
    {
        $this->instanceForInsertAtTail->insert($this->batchForInsert);
    }

    /**
     * @Revs(10000)
     */
    public function benchInsertUnshifting(): void
    {
        $this->instanceForInsertUnshifting->insert([0], 0);
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
        \unserialize($this->serializedInstanceForSerializableUnserialize, ['allowed_classes' => [\ltrim('\\Vectory\\NullableUint48Vector', '\\')]]);
    }

    public static function getInstance(bool $filled = false): \Vectory\VectorInterface
    {
        $instance = new \Vectory\NullableUint48Vector();
        if ($filled) {
            $instance[9999] = 0;
        }

        return $instance;
    }

    public static function getRandomValue()
    {
        $value = \dechex(\mt_rand(0x0, 0xff));
        for ($i = 1; $i < 6; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }

        return \hexdec($value);
    }

    private function setUpArrayAccessBenchmark(): void
    {
        $this->instanceForArrayAccessOffsetGetRandomAccess = self::getInstance(true);
        $this->instanceForArrayAccessOffsetSetOverwriting = self::getInstance(true);
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap = self::getInstance();
        $this->instanceForArrayAccessOffsetSetPushingWithGap = self::getInstance();
        $this->instanceForArrayAccessOffsetUnsetPopping = self::getInstance(true);
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->instanceForArrayAccessOffsetUnsetShifting = self::getInstance(true);
    }

    private function setUpDeleteBenchmark(): void
    {
        $this->instanceForDeleteAtHead = self::getInstance(true);
        $this->instanceForDeleteAtTail = self::getInstance(true);
    }

    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 100 / 2, 0);
        $this->instanceForInsertAtHead = self::getInstance();
        $this->instanceForInsertAtTail = self::getInstance();
        $this->instanceForInsertUnshifting = self::getInstance();
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->instanceForIteratorAggregate = self::getInstance(true);
    }

    private function setUpJsonSerializableBenchmark(): void
    {
        $this->instanceForJsonSerializable = self::getInstance(true);
    }

    private function setUpSerializableBenchmark(): void
    {
        $this->instanceForSerializableSerialize = self::getInstance(true);
        $this->serializedInstanceForSerializableUnserialize = \serialize(self::getInstance(true));
    }
}
