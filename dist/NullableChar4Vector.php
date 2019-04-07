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

class NullableChar4Vector implements VectorInterface
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
            $value = $this->primarySource[$index] ?? "\0\0\0\0";
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
            if (!\is_string($value)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'string', ' or null', \gettype($value)));
            }
            if (4 !== \strlen($value)) {
                throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Value must be exactly %d bytes, %d given', 4, \strlen($value)));
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
            if ($this->elementCount === $index) {
                --$this->elementCount;
                unset($this->primarySource[$index]);
                unset($this->nullabilitySource[$index]);
            } else {
                if (\count($this->primarySource) !== $this->elementCount) {
                    $this->primarySource += \array_fill(0, $this->elementCount, "\0\0\0\0");
                }
                \ksort($this->primarySource, \SORT_NUMERIC);
                \array_splice($this->primarySource, $index, 1);
                $this->primarySource = \array_diff($this->primarySource, ["\0\0\0\0"]);
                if (!isset($this->primarySource[$this->elementCount - 2])) {
                    $this->primarySource[$this->elementCount - 2] = "\0\0\0\0";
                }
                if (\count($this->nullabilitySource) !== $this->elementCount) {
                    $this->nullabilitySource += \array_fill(0, $this->elementCount, false);
                }
                \ksort($this->nullabilitySource, \SORT_NUMERIC);
                \array_splice($this->nullabilitySource, $index, 1);
                $this->nullabilitySource = \array_diff($this->nullabilitySource, [false]);
                if (!isset($this->nullabilitySource[$this->elementCount - 2])) {
                    $this->nullabilitySource[$this->elementCount - 2] = false;
                }
                --$this->elementCount;
            }
        }
    }

    public function count(): int
    {
        return $this->elementCount;
    }

    public function getIterator(): \Traversable
    {
        $elementCount = $this->elementCount;
        $primarySource = $this->primarySource;
        $nullabilitySource = $this->nullabilitySource;
        for ($index = 0; $index < $elementCount; ++$index) {
            if ($nullabilitySource[$index] ?? false) {
                (yield $index => null);
            } else {
                (yield $index => $primarySource[$index] ?? "\0\0\0\0");
            }
        }
    }
}
