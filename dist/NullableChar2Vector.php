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

class NullableChar2Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private $elementCount = 0;
    private $source = [];

    public function offsetExists($index)
    {
        return \is_int($index) && $index >= 0 && $index < $this->elementCount;
    }

    public function offsetGet($index)
    {
        // region __ensure_index
        if (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($index).' given');
        }
        if (0 === $this->elementCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'The container is empty, so index '.$index.' does not exist');
        }
        if ($index < 0 || $index >= $this->elementCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Index out of range: '.$index.', expected 0 <= x <= '.($this->elementCount - 1));
        }
        // endregion
        return $this->source[$index] ?? '\\u0000\\u0000';
    }

    public function offsetSet($index, $value)
    {
        // region __ensure_index
        if (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($index).' given');
        }
        if (0 === $this->elementCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'The container is empty, so index '.$index.' does not exist');
        }
        if ($index < 0 || $index >= $this->elementCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Index out of range: '.$index.', expected 0 <= x <= '.($this->elementCount - 1));
        }
        // endregion
        // region __ensure_value
        if (null !== $value) {
            if (!\is_string($value)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'string', ' or null', \gettype($value)));
            }
            if (2 !== \strlen($value)) {
                throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Value must be exactly %d bytes, %d given', 2, \strlen($value)));
            }
        }
        // endregion
        $this->source[$index] = $value;
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            unset($this->source[$index]);
        }
    }
}
