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

class NullableUint40Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private $elementCount = 0;
    private $primarySource = '';
    private $nullabilitySource = '';

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
        static $mask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', '�'];
        $byteIndex = $index >> 3;
        $isNull = $this->nullabilitySource[$byteIndex];
        $isNull = "\0" !== ($isNull & $mask[$index & 7]);
        if ($isNull) {
            $value = null;
        } else {
            $value = \substr($this->primarySource, $index * 5, 5);
            // TODO: Unpack signed and unsigned integers.
            $value = 0;
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
            if (!\is_int($value)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'int', ' or null', \gettype($value)));
            }
            if ($value < 0 || $value > 1099511627775) {
                throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$value.', expected '. 0 .' <= x <= '. 1099511627775);
            }
        }
        static $originalMask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', '�'];
        static $invertedMask = ['�', '�', '�', '�', '�', '�', '�', ''];
        if (null === $value) {
            $byteIndex = $index >> 3;
            $unassignedCount = $index - \strlen($this->nullabilitySource) / 1;
            if ($unassignedCount < 0) {
                // Case 1. Overwrite an existing item.
                $this->nullabilitySource[$byteIndex] = ($this->nullabilitySource[$byteIndex] ?? "\0") | $originalMask[$index & 7];
            } elseif (0 === $unassignedCount) {
                // Case 2. Append an element right after the last one.
                $this->nullabilitySource .= ($this->nullabilitySource[$byteIndex] ?? "\0") | $originalMask[$index & 7];
            } else {
                // Case 3. Append to a gap after the last element. Fill the gap with default values.
                $this->nullabilitySource .= \str_repeat(' ', (int) $unassignedCount).(($this->nullabilitySource[$byteIndex] ?? "\0") | $originalMask[$index & 7]);
            }
        } else {
            $byteIndex = $index >> 3;
            $unassignedCount = $index - \strlen($this->nullabilitySource) / 1;
            if ($unassignedCount < 0) {
                // Case 1. Overwrite an existing item.
                $this->nullabilitySource[$byteIndex] = ($this->nullabilitySource[$byteIndex] ?? "\0") & $invertedMask[$index & 7];
            } elseif (0 === $unassignedCount) {
                // Case 2. Append an element right after the last one.
                $this->nullabilitySource .= ($this->nullabilitySource[$byteIndex] ?? "\0") & $invertedMask[$index & 7];
            } else {
                // Case 3. Append to a gap after the last element. Fill the gap with default values.
                $this->nullabilitySource .= \str_repeat(' ', (int) $unassignedCount).(($this->nullabilitySource[$byteIndex] ?? "\0") & $invertedMask[$index & 7]);
            }
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
                    $this->primarySource += \array_fill(0, $this->elementCount, 0);
                }
                \ksort($this->primarySource, \SORT_NUMERIC);
                \array_splice($this->primarySource, $index, 1);
                $this->primarySource = \array_diff($this->primarySource, [0]);
                if (!isset($this->primarySource[$this->elementCount - 2])) {
                    $this->primarySource[$this->elementCount - 2] = 0;
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

    // __countable_methods()
    // __iterator_aggregate_methods()
    // __json_serializable_methods()
    // __serializable_methods()
}
