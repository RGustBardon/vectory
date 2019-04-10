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

namespace Vectory;

class Uint32Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private $elementCount = 0;
    private $primarySource = '';

    public function offsetExists($index)
    {
        return \is_int($index) && $index >= 0 && $index < $this->elementCount;
    }

    public function offsetGet($index)
    {
        if (null === $index) {
            $index = $this->elementCount;
        } else {
            if (!\is_int($index)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($index).' given');
            }
        }
        if (0 === $this->elementCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'The container is empty, so index '.$index.' does not exist');
        }
        if ($index < 0 || $index >= $this->elementCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Index out of range: '.$index.', expected 0 <= x <= '.($this->elementCount - 1));
        }

        return $this->primarySource[$index] ?? 0;
    }

    public function offsetSet($index, $value)
    {
        if (null === $index) {
            $index = $this->elementCount;
        } elseif ($index < 0) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Negative index: '.$index);
        }
        if (!\is_int($value)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'int', '', \gettype($value)));
        }
        if ($value < 0 || $value > 4294967295) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$value.', expected '. 0 .' <= x <= '. 4294967295);
        }
        $this->primarySource[$index] = $value;
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            if ($this->elementCount === $index) {
                --$this->elementCount;
                unset($this->primarySource[$index]);
            } else {
                if (\count($this->primarySource) !== $this->elementCount) {
                    $this->primarySource += \array_fill(0, $this->elementCount, 0);
                }
                \ksort($this->primarySource, \SORT_NUMERIC);
                \array_splice($this->primarySource, $index, 1);
                $this->primarySource = \array_diff($this->primarySource, [0]);
                if (!isset($this->primarySource[$this->elementCount - 2])) {
                    $this->primarySource[$this->elementCount - 2] = 0;
                }
                --$this->elementCount;
            }
        }
    }

    // __countable_methods()
    // __iterator_aggregate_methods()
    // __json_serializable_methods()
    // __serializable_methods()
}
