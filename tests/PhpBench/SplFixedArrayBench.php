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
final class SplFixedArrayBench
{
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
        $this->instanceForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = "\0\0";
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap->setSize($this->lastIndexOfArrayAccessOffsetSetPushingWithoutGap + 1);
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithoutGap++] = "\0\0";
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100;
        $this->instanceForArrayAccessOffsetSetPushingWithGap->setSize($this->lastIndexOfArrayAccessOffsetSetPushingWithGap + 1);
        $this->instanceForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap] = "\0\0";
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
        \array_unshift($elements, "\0\0");
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
        \unserialize($this->serializedInstanceForSerializableUnserialize, ['allowed_classes' => [\ltrim('SplFixedArray', '\\')]]);
    }

    public static function getInstance(bool $filled = false): \SplFixedArray
    {
        $instance = new \SplFixedArray();
        if ($filled) {
            $instance->setSize(10000);
            for ($i = 0; $i < 10000; ++$i) {
                $instance[$i] = false;
            }
        }

        return $instance;
    }

    public static function getRandomValue()
    {
        return [false, true][\mt_rand(0, 1)];
        $positive = 0 === \mt_rand(0, 1);
        $value = \dechex(\mt_rand(0x0, 0x7f));
        for ($i = 1; $i < 2; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }
        $value = \hexdec($value);

        return $positive ? $value : -$value;
        $value = '';
        for ($i = 0; $i < 2; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }

    public static function getRandomSignedInteger(bool $negative): int
    {
        $value = \dechex(\mt_rand(0x0, 0x7f));
        for ($i = 1; $i < 2; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }
        $value = \hexdec($value);
        $value = $negative ? $value < 0 ? -$value : -9.223372036854776E+18 : $value;

        return (int) $value;
    }

    public static function getRandomUtf8String(): string
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
        $this->batchForInsert = \array_fill(0, 100 / 2, "\0\0");
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
