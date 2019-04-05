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
final class NullableChar2VectorBench
{
    private const INVALID_VALUE =
        0
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
        return new \Vectory\NullableChar2Vector();
    }

    private static function getRandomValue()
    {
        $value = '';
        for ($i = 0; $i < 2; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }
}
