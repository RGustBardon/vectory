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

class Char2Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private const SERIALIZATION_FORMAT_VERSION = 'v1.0.0';
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

        return \substr($this->primarySource, $index * 2, 2);
    }

    public function offsetSet($index, $value)
    {
        if (null === $index) {
            $index = $this->elementCount;
        } elseif ($index < 0) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Negative index: '.$index);
        }
        if (!\is_string($value)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'string', '', \gettype($value)));
        }
        if (2 !== \strlen($value)) {
            throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Value must be exactly %d bytes, %d given', 2, \strlen($value)));
        }
        $unassignedCount = $index - \strlen($this->primarySource) / 2;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * 2;
            do {
                $this->primarySource[$byteIndex++] = $value[$elementIndex++];
            } while ($elementIndex < 2);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $value;
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat('  ', (int) $unassignedCount).$value;
        }
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            --$this->elementCount;
            $this->primarySource = \substr_replace($this->primarySource, '', $index * 2, 2);
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
            $result = \substr($clone->primarySource, $getIteratorIndex * 2, 2);
            (yield $getIteratorIndex => $result);
        }
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $elementCount = $this->elementCount;
        for ($getIteratorIndex = 0; $getIteratorIndex < $elementCount; ++$getIteratorIndex) {
            $element = \substr($this->primarySource, $getIteratorIndex * 2, 2);
            $result[] = $element;
        }

        return $result;
    }

    public function serialize(): string
    {
        return \serialize([$this->elementCount, $this->primarySource]);
    }

    public function unserialize($serialized)
    {
        $errorMessage = 'Details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $newValues = \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\Char2Vector', '\\')]]);
        \restore_error_handler();
        if (false === $newValues) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedTypes = ['integer', 'string'];
        if (!\is_array($newValues) || \array_keys($newValues) !== \array_keys($expectedTypes) || \array_map('gettype', $newValues) !== $expectedTypes) {
            $errorMessage = 'Expected an array of '.\implode(', ', $expectedTypes);

            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $previousValues = [$this->elementCount, $this->primarySource];
        [$this->elementCount, $this->primarySource] = $newValues;

        try {
            if ($this->elementCount < 0) {
                $errorMessage = 'The element count must not be negative';

                throw new \DomainException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
            }
        } catch (\Throwable $e) {
            [$this->elementCount, $this->primarySource] = $previousValues;

            throw $e;
        }
    }
}
