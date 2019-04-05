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
final class NullableUint16VectorBench
{
    private const INVALID_VALUE = '0'

    ;
    private $value;
    private /* \Vectory\Interface */ $vector;

    public function setUp(): void
    {
        \mt_srand(0);

        $this->value = self::getRandomValue();
    }

    /**
     * @Revs(10000)
     */
    public function benchPushing(): void
    {
        $this->vector[] = $this->value;
    }

    private static function getInstance(): \Vectory\VectorInterface
    {
        return new \Vectory\NullableUint16Vector();
    }

    private static function getRandomValue()
    {
        $value = \dechex(\mt_rand(0x0, 0xff));

        for ($i = 1; $i < 2; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }

        return \hexdec($value);
    }
}
