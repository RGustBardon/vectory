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

class NullableBoolVector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private $elementCount = 0;
    private $primarySource = [];
    private $nullabilitySource = [];

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
        $isNull = $this->nullabilitySource[$index] ?? false;
        if ($isNull) {
            $value = null;
        } else {
            $value = $this->primarySource[$index] ?? false;
        }

        return $value;
    }

    public function offsetSet($index, $value)
    {
        if (null === $index) {
            $index = $this->elementCount;
        } elseif ($index < 0) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Negative index: '.$index);
        }
        if (null !== $value) {
            if (!\is_bool($value)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'bool', ' or null', \gettype($value)));
            }
        }
        if (null === $value) {
            $this->nullabilitySource[$index] = true;
        } else {
            $this->nullabilitySource[$index] = false;
            $this->primarySource[$index] = $value;
        }
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            --$this->elementCount;
            if ($this->elementCount === $index) {
                unset($this->primarySource[$index]);
                unset($this->nullabilitySource[$index]);
            } else {
                if (\count($this->primarySource) !== $this->elementCount) {
                    $this->primarySource += \array_fill(0, $this->elementCount, false);
                }
                \ksort($this->primarySource, \SORT_NUMERIC);
                \array_splice($this->primarySource, $index, 1);
                $this->primarySource = \array_diff($this->primarySource, [false]);
                if (!isset($this->primarySource[$this->elementCount - 1])) {
                    $this->primarySource[$this->elementCount - 1] = false;
                }
                if (\count($this->nullabilitySource) !== $this->elementCount) {
                    $this->nullabilitySource += \array_fill(0, $this->elementCount, false);
                }
                \ksort($this->nullabilitySource, \SORT_NUMERIC);
                \array_splice($this->nullabilitySource, $index, 1);
                $this->nullabilitySource = \array_diff($this->nullabilitySource, [false]);
                if (!isset($this->nullabilitySource[$this->elementCount - 1])) {
                    $this->nullabilitySource[$this->elementCount - 1] = false;
                }
            }
        }
    }
}
