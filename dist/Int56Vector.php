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

class Int56Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private $elementCount = 0;
    private $primarySource = '';
    private static $littleEndian;

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
        $packedInteger = \substr($this->primarySource, $index * 7, 7);
        $value = \unpack('P', $packedInteger."\0")[1];
        if ($value > 36028797018963967) {
            $value = 36028797018963967 - $value;
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
        if (!\is_int($value)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'int', '', \gettype($value)));
        }
        if ($value < -36028797018963968 || $value > 36028797018963967) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$value.', expected '.-36028797018963968 .' <= x <= '. 36028797018963967);
        }
        $unpackedInteger = $value;
        $packedInteger = \pack('PX', $unpackedInteger >= 0 ? $unpackedInteger : -$unpackedInteger + 36028797018963967);
        $unassignedCount = $index - \strlen($this->primarySource) / 7;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * 7;
            do {
                $this->primarySource[$byteIndex++] = $packedInteger[$elementIndex++];
            } while ($elementIndex < 7);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $packedInteger;
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat('       ', (int) $unassignedCount).$packedInteger;
        }
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            --$this->elementCount;
            $this->primarySource = \substr_replace($this->primarySource, '', $index * 7, 7);
        }
    }

    public function count(): int
    {
        return $this->elementCount;
    }

    public function getIterator(): \Traversable
    {
        $elementCount = $this->elementCount;
        $clone = clone $this;
        for ($getIteratorIndex = 0; $getIteratorIndex < $elementCount; ++$getIteratorIndex) {
            $packedInteger = \substr($clone->primarySource, $getIteratorIndex * 7, 7);
            $result = \unpack('P', $packedInteger."\0")[1];
            if ($result > 36028797018963967) {
                $result = 36028797018963967 - $result;
            }
            (yield $getIteratorIndex => $result);
        }
    }

    // __json_serializable_methods()
    // __serializable_methods()
}
