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
final class NullableChar1VectorBench
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

    /**
     * @Revs(10000)
     */
    public function benchPushing(): void
    {
        $this->vector[] = $this->value;
    }

    private static function getInstance(): \Vectory\VectorInterface
    {
        return new \Vectory\NullableChar1Vector();
    }

    private static function getRandomValue()
    {
        $value = '';
        for ($i = 0; $i < 1; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }

    private static function getRandomUtf8String(): string
    {
        \assert(0x10ffff <= \mt_getrandmax());
        $string = '';
        while (\strlen($string) < 1) {
            $characterMaxLength = \min(4, 1 - \strlen($string));
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
}
