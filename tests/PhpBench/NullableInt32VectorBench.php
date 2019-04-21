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
final class NullableInt32VectorBench
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
        return new \Vectory\NullableInt32Vector();
    }

    private static function getRandomValue()
    {
        $positive = 0 === \mt_rand(0, 1);
        $value = \dechex(\mt_rand(0x0, $positive ? 0x7f : 0x80));

        for ($i = 1; $i < 4; ++$i) {
            $value .= \str_pad(\dechex(\mt_rand(0x0, 0xff)), 2, '0', \STR_PAD_LEFT);
        }
        $value = \hexdec($value);

        return $positive ? $value : -$value;
    }

    private static function assertSequence(array $sequence, \Vectory\VectorInterface $vector): void
    {
        self::assertCount(\count($sequence), $vector);
        $i = 0;
        foreach ($vector as $index => $element) {
            self::assertSame($i, $index);
            self::assertSame(
                $sequence[$index],
                $element,
                'Index: '.$index."\n".
                    \var_export($sequence, true)."\n".
                    self::getVectorDump($vector)
            );
            ++$i;
        }
    }

    private static function getVectorDump(VectorInterface $vector): string
    {
        $dump = "\n";
        $trace = \array_reverse(\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS));
        foreach ($trace as $frame) {
            if (0 === \strpos($frame['class'], 'Vectory')) {
                $frame['class'] = \substr($frame['class'], \strrpos($frame['class'], '\\') + 1);
                $dump .= \sprintf("%s%s%s:%d\n", $frame['class'], $frame['type'], $frame['function'], $frame['line']);
            }
        }

        $vector->__debugInfo();

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
                $dump .= \substr(\strtoupper($sourcePrefix), 0, 1);
                $dump .= \sprintf('% '.\strlen((string) (\strlen($source) / $bytesPerElement)).'d: ', $index);
                foreach (\str_split($element, 2) as $value) {
                    $decimal = (int) \hexdec($value);
                    $binary = \str_pad(\decbin($decimal), 8, '0', \STR_PAD_LEFT);
                    $dump .= \sprintf('h:% 2s d:% 3s b:%04s %04s | ', $value, $decimal, \substr($binary, 0, 4), \substr($binary, 4));
                }
                $dump .= "\n";
            }
        }

        return $dump;
    }

    private static function dumpVector(\Vectory\VectorInterface $vector): void
    {
        echo self::getVectorDump($vector);
    }
}
