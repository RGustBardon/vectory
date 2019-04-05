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
 * @OutputTimeUnit("seconds")
 * @OutputMode("throughput")
 *
 * @internal
 */
final class Uint16VectorBench
{
    private const INVALID_VALUE = '0'

    ;

    private function setUp(): void
    {
        \mt_srand(0);
    }

    public function provideVectors(): \Generator
    {
        yield [self::getInstance(), self::getRandomValue()];
    }

    /**
     * @ParamProviders({"provideVectors"})
     */
    public function benchPushing(array $params): void
    {
        $params[0][] = $params[1];
    }

    private static function getInstance(): \Vectory\VectorInterface
    {
        return new \Vectory\Uint16Vector();
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
