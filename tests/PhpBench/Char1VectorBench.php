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
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 *
 * @internal
 */
final class Char1VectorBench
{
    private const INVALID_VALUE =
        0
    ;
    private $value;
    private /* \Vectory\Interface */ $vector;

    public function setUp(): void
    {
        \mt_srand(0);

        $this->value = self::getRandomValue();
    }

    public function benchPushing(): void
    {
        $this->vector[] = $this->value;
    }

    private static function getInstance(): \Vectory\VectorInterface
    {
        return new \Vectory\Char1Vector();
    }

    private static function getRandomValue()
    {
        $value = '';
        for ($i = 0; $i < 1; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }
}
