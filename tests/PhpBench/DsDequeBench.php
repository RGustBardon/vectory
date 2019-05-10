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
final class DsDequeBench
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
        \unserialize($this->serializedInstanceForSerializableUnserialize, ['allowed_classes' => [\ltrim('Ds\\Deque', '\\')]]);
    }

    public static function getRandomValue()
    {
        return [false, true][\mt_rand(0, 1)];
        $positive = 0 === \mt_rand(0, 1);
        $value = \dechex(\mt_rand(0x0, 0x7f));
        for ($i = 1; $i < null; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }
        $value = \hexdec($value);

        return $positive ? $value : -$value;
        $value = '';
        for ($i = 0; $i < null; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }

    public static function getRandomSignedInteger(bool $negative): int
    {
        $value = \dechex(\mt_rand(0x0, 0x7f));
        for ($i = 1; $i < null; ++$i) {
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
        while (\strlen($string) < null) {
            $characterMaxLength = \min(4, null - \strlen($string));
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
        $this->batchForInsert = \array_fill(0, 100 / 2, false);
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

    private static function getInstance(bool $filled = false): \Ds\Sequence
    {
        $instance = new \Ds\Deque();
        if ($filled) {
            $instance->push(...\array_fill(0, 10000, false));
        }

        return $instance;
    }
}
