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
final class NullableChar4VectorBench
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
        return new \Vectory\NullableChar4Vector();
    }

    private static function getRandomValue()
    {
        $value = '';
        for ($i = 0; $i < 4; ++$i) {
            $value .= \chr(\mt_rand(0x0, 0xff));
        }

        return $value;
    }

    private static function getRandomUtf8String(): string
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

    private static function dumpVector(\Vectory\VectorInterface $vector): void
    {
        echo "\n";
        $trace = \array_reverse(\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS));
        foreach ($trace as $frame) {
            if (0 === \strpos($frame['class'], 'Vectory')) {
                $frame['class'] = \substr($frame['class'], \strrpos($frame['class'], '\\') + 1);
                \printf("%s%s%s:%d\n", $frame['class'], $frame['type'], $frame['function'], $frame['line']);
            }
        }

        $sources = ['primary'];
        $sources[] = 'nullability';

        foreach ($sources as $sourcePrefix) {
            $property = new \ReflectionProperty($vector, $sourcePrefix.'Source');
            $property->setAccessible(true);
            $source = $property->getValue($vector);
            $bytesPerElement = 4 ?? 1;
            $elements = \str_split(\bin2hex($source), $bytesPerElement * 2);
            \assert(\is_iterable($elements));
            foreach ($elements as $index => $element) {
                echo \substr(\strtoupper($sourcePrefix), 0, 1);
                \printf('% '.\strlen((string) (\strlen($source) / $bytesPerElement)).'d: ', $index);
                foreach (\str_split($element, 2) as $value) {
                    $decimal = (int) \hexdec($value);
                    $binary = \decbin($decimal);
                    \printf('h:% 2s d:% 3s b:%04s %04s | ', $value, $decimal, \substr($binary, 0, 4), \substr($binary, 4));
                }
                echo "\n";
            }
        }
    }
}
