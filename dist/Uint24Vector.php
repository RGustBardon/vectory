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

class Uint24Vector implements VectorInterface
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
        $packedInteger = \substr($this->primarySource, $index * 3, 3);

        return \unpack('V', $packedInteger."\0")[1];
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
        if ($value < 0 || $value > 16777215) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$value.', expected '. 0 .' <= x <= '. 16777215);
        }
        $unpackedInteger = $value;
        $packedInteger = \pack('VX', $unpackedInteger);
        $unassignedCount = $index - \strlen($this->primarySource) / 3;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * 3;
            do {
                $this->primarySource[$byteIndex++] = $packedInteger[$elementIndex++];
            } while ($elementIndex < 3);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $packedInteger;
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat('   ', (int) $unassignedCount).$packedInteger;
        }
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            --$this->elementCount;
            $this->primarySource = \substr_replace($this->primarySource, '', $index * 3, 3);
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
            $packedInteger = \substr($clone->primarySource, $getIteratorIndex * 3, 3);
            $result = \unpack('V', $packedInteger."\0")[1];
            (yield $getIteratorIndex => $result);
        }
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $elementCount = $this->elementCount;
        for ($getIteratorIndex = 0; $getIteratorIndex < $elementCount; ++$getIteratorIndex) {
            $packedInteger = \substr($this->primarySource, $getIteratorIndex * 3, 3);
            $element = \unpack('V', $packedInteger."\0")[1];
            $result[] = $element;
        }

        return $result;
    }

    // __serializable_methods()
}
