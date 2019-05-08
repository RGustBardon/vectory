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
final class Char4VectorBench
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
        $this->instanceForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = "\0\0\0\0";
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap[] = "\0\0\0\0";
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->instanceForArrayAccessOffsetSetPushingWithGap[$this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100] = "\0\0\0\0";
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
        $this->instanceForDeleteAtHead->delete(-\mt_rand(0, 100));
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
        $this->instanceForInsertUnshifting->insert(["\0\0\0\0"], 0);
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
        \unserialize($this->serializedInstanceForSerializableUnserialize, ['allowed_classes' => [\ltrim('\\Vectory\\Char4Vector', '\\')]]);
    }

    public static function getRandomValue()
    {
        $value = '';
        for ($i = 0; $i < 4; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }

    public static function getRandomUtf8String(): string
    {
        \assert(0x10ffff <= \mt_getrandmax());
        $string = '';
        while (\strlen($string) < 4) {
            $characterMaxLength = \min(4, 4 - \strlen($string));
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
        $this->instanceForArrayAccessOffsetGetRandomAccess = self::getInstance();
        $this->instanceForArrayAccessOffsetGetRandomAccess[9999] = "\0\0\0\0";
        $this->instanceForArrayAccessOffsetSetOverwriting = self::getInstance();
        $this->instanceForArrayAccessOffsetSetOverwriting[9999] = "\0\0\0\0";
        $this->instanceForArrayAccessOffsetSetPushingWithoutGap = self::getInstance();
        $this->instanceForArrayAccessOffsetSetPushingWithGap = self::getInstance();
        $this->instanceForArrayAccessOffsetUnsetPopping = self::getInstance();
        $this->instanceForArrayAccessOffsetUnsetPopping[9999] = "\0\0\0\0";
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        $this->instanceForArrayAccessOffsetUnsetShifting = self::getInstance();
        $this->instanceForArrayAccessOffsetUnsetShifting[9999] = "\0\0\0\0";
    }

    private function setUpDeleteBenchmark(): void
    {
        $this->instanceForDeleteAtHead = self::getInstance();
        $this->instanceForDeleteAtHead[10000] = "\0\0\0\0";
        $this->instanceForDeleteAtTail = self::getInstance();
        $this->instanceForDeleteAtTail[10000] = "\0\0\0\0";
    }

    private function setUpInsertBenchmark(): void
    {
        $this->batchForInsert = \array_fill(0, 50, "\0\0\0\0");
        $this->instanceForInsertAtHead = self::getInstance();
        $this->instanceForInsertAtTail = self::getInstance();
        $this->instanceForInsertUnshifting = self::getInstance();
    }

    private function setUpIteratorAggregateBenchmark(): void
    {
        $this->instanceForIteratorAggregate = self::getInstance();
        $this->instanceForIteratorAggregate[10000] = "\0\0\0\0";
    }

    private function setUpJsonSerializableBenchmark(): void
    {
        $this->instanceForJsonSerializable = self::getInstance();
        $this->instanceForJsonSerializable[10000] = "\0\0\0\0";
    }

    private function setUpSerializableBenchmark(): void
    {
        $this->instanceForSerializableSerialize = self::getInstance();
        $this->instanceForSerializableSerialize[10000] = "\0\0\0\0";
        $vector = self::getInstance();
        $vector[10000] = "\0\0\0\0";
        $this->serializedInstanceForSerializableUnserialize = \serialize($vector);
    }

    private static function getInstance(): \Vectory\VectorInterface
    {
        return new \Vectory\Char4Vector();
    }
}
