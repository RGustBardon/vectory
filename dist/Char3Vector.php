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

class Char3Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private const SERIALIZATION_FORMAT_VERSION = 1;
    private const SUPPORTED_SERIALIZATION_FORMAT_VERSIONS = [self::SERIALIZATION_FORMAT_VERSION];
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

        return \substr($this->primarySource, $index * 3, 3);
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
        if (3 !== \strlen($value)) {
            throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Value must be exactly %d bytes, %d given', 3, \strlen($value)));
        }
        $unassignedCount = $index - \strlen($this->primarySource) / 3;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * 3;
            do {
                $this->primarySource[$byteIndex++] = $value[$elementIndex++];
            } while ($elementIndex < 3);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $value;
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat('   ', (int) $unassignedCount).$value;
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
            $result = \substr($clone->primarySource, $getIteratorIndex * 3, 3);
            (yield $getIteratorIndex => $result);
        }
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $elementCount = $this->elementCount;
        for ($getIteratorIndex = 0; $getIteratorIndex < $elementCount; ++$getIteratorIndex) {
            $element = \substr($this->primarySource, $getIteratorIndex * 3, 3);
            $result[] = $element;
        }

        return $result;
    }

    public function serialize(): string
    {
        return \serialize([self::SERIALIZATION_FORMAT_VERSION, $this->elementCount, $this->primarySource]);
    }

    public function unserialize($serialized)
    {
        $errorMessage = 'Details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $newValues = \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\Char3Vector', '\\')]]);
        \restore_error_handler();
        if (false === $newValues) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedTypes = ['integer', 'integer', 'string'];
        if (!\is_array($newValues) || \array_keys($newValues) !== \array_keys($expectedTypes) || \array_map('gettype', $newValues) !== $expectedTypes) {
            $errorMessage = 'Expected an array of '.\implode(', ', $expectedTypes);

            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $previousValues = [$this->elementCount, $this->primarySource];
        [$version, $this->elementCount, $this->primarySource] = $newValues;
        if (!\in_array($version, self::SUPPORTED_SERIALIZATION_FORMAT_VERSIONS, true)) {
            $errorMessage = 'Unsupported version: '.$version;

            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }

        try {
            $expectedLength = $this->elementCount * 3;
            if (\strlen($this->primarySource) !== $expectedLength) {
                $errorMessage = \sprintf('Unexpected length of the primary source: expected %d bytes, found %d instead', $expectedLength, \strlen($this->primarySource));

                throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
            }
            if ($this->elementCount < 0) {
                $errorMessage = 'The element count must not be negative';

                throw new \DomainException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
            }
        } catch (\Throwable $e) {
            [$this->elementCount, $this->primarySource] = $previousValues;

            throw $e;
        }
    }

    public function delete(int $firstIndex = -1, int $howMany = \PHP_INT_MAX): void
    {
        $elementCount = $this->elementCount;
        // Calculate the positive index corresponding to the negative one.
        if ($firstIndex < 0) {
            $firstIndex += $elementCount;
        }
        // If we still end up with a negative index, decrease `$howMany`.
        if ($firstIndex < 0) {
            $howMany += $firstIndex;
            $firstIndex = 0;
        }
        // Check if there is anything to delete or if the positive index is out of bounds.
        if ($howMany < 1 || 0 === $elementCount || $firstIndex >= $elementCount) {
            return;
        }
        // Delete elements.
        $this->deleteBytes(true, $firstIndex, $howMany, $elementCount);
    }

    private function deleteBits(bool $primarySource, int $firstIndex, int $howMany, int $elementCount): void
    {
        // Bit-shifting a substring is expensive, so delete all the full bytes in the range, for example:
        //
        // Indices:            0123|4567 89ab|cdef 0123|4567 89ab|cdef 0123|4567 89ab|cdef
        // To be deleted:             XX XXXX|XXXX XXXX|XXXX XXXX XXX
        // Full bytes:                   ^^^^ ^^^^ ^^^^ ^^^^
        $elementCount = $this->elementCount;
        $byteCount = $elementCount + 7 >> 3;
        $firstFullByteIndex = ($firstIndex >> 3) + (0 === ($firstIndex & 7) ? 0 : 1);
        $howManyFullBytes = \min($byteCount - 1, $firstIndex + $howMany >> 3) - $firstFullByteIndex;
        if ($howManyFullBytes > 0) {
            $this->deleteBytes($primarySource, $firstFullByteIndex, $howManyFullBytes, $byteCount);
            $byteCount -= $howManyFullBytes;
            $deletedBitCount = $howManyFullBytes << 3;
            $elementCount -= $deletedBitCount;
            $howMany -= $deletedBitCount;
            if (0 === $howMany) {
                if ($primarySource) {
                    $this->elementCount = $elementCount;
                }

                return;
            }
        }
        if (0 === ($firstIndex & 7) && $firstIndex + $howMany >= $elementCount) {
            // If the first index conceptually begins a byte and everything to its right is to be deleted,
            // no bit-shifting is necessary.
            if ($primarySource) {
                $this->primarySource = \substr($this->primarySource, 0, $firstIndex >> 3);
                $this->elementCount = $firstIndex;
            } else {
                $this->nullabilitySource = \substr($this->nullabilitySource, 0, $firstIndex >> 3);
            }

            return;
        }
        // Keep rewriting the target with assembled bytes.
        // During the first iteration, the assembled byte will include some target bits.
        // After the first iteration, all the assembled bytes will consist of source bits only.
        // Conceptually:
        // Indices:            0123|4567 89ab|cdef 0123|4567 89ab|cdef
        // To be deleted:             XX XXXX XXX
        // Source bits:                          ^ ^^^^ ^^^^ ^^^^ ^^^^
        // Target bits:               ^^ ^^^^ ^^^^ ^^^^ ^^^
        // 1st assembled byte: ^^^^ ^^           ^ ^                   (includes six target bits)
        // 2nd assembled byte: 1111|1111            ^^^ ^^^^ ^         (consists entirely of source bits)
        // 3rd assembled byte: 1111|1111 2222|2222            ^^^ ^^^^ (consists of source bits and zeros)
        // The above is a simplified view. In reality, the bits are reversed in each byte:
        // Indices:            7654|3210 fedc|ba98 7654|3210 fedc|ba98
        // To be deleted:      XX         XXX XXXX
        // Source bits:                  ^         ^^^^ ^^^^ ^^^^ ^^^^
        // Target bits:        ^^        ^^^^ ^^^^  ^^^ ^^^^
        // 1st assembled byte:   ^^^^ ^^ ^                 ^           (includes six target bits)
        // 2nd assembled byte: 1111|1111           ^^^^ ^^^          ^ (consists entirely of source bits)
        // 3rd assembled byte: 1111|1111 2222|2222           ^^^^ ^^^  (consists of source bits and zeros)
        $lastByteIndex = $byteCount - 1;
        $source = $primarySource ? $this->primarySource : $this->nullabilitySource;
        $targetHeadBitAbsoluteIndex = $firstIndex;
        $sourceHeadBitAbsoluteIndex = $firstIndex + $howMany;
        while ($sourceHeadBitAbsoluteIndex < $elementCount) {
            // Find out how many target bits are needed to assemble a byte.
            $targetHeadBitRelativeBitIndex = $targetHeadBitAbsoluteIndex & 7;
            $targetByteMissingBitCount = 8 - $targetHeadBitRelativeBitIndex;
            // Get the current source byte as an integer (bit-shifting operators do not work for strings).
            $sourceHeadByteIndex = $sourceHeadBitAbsoluteIndex >> 3;
            $assembledByte = \ord($source[$sourceHeadByteIndex]);
            $sourceHeadShift = $sourceHeadBitAbsoluteIndex & 7;
            if ($sourceHeadShift > 0) {
                // Shift the source bits to be copied to the end of the assembled byte.
                $assembledByte >>= $sourceHeadShift;
                $sourceAssembledBitCount = 8 - $sourceHeadShift;
                if ($sourceAssembledBitCount < $targetByteMissingBitCount && $sourceHeadByteIndex < $lastByteIndex) {
                    // There are not enough bits in the assembled byte, so augment it with the next source byte.
                    $assembledByte |= (\ord($source[$sourceHeadByteIndex + 1]) & 0xff >> 8 - $targetByteMissingBitCount + $sourceAssembledBitCount) << $sourceAssembledBitCount;
                }
            }
            $targetHeadByteIndex = $targetHeadBitAbsoluteIndex >> 3;
            if ($targetHeadBitRelativeBitIndex > 0) {
                // Some of the bits of the target byte need to be preserved, so augment the assembled byte.
                $assembledByte = \ord($source[$targetHeadByteIndex]) & 0xff >> $targetByteMissingBitCount | $assembledByte << $targetHeadBitRelativeBitIndex;
            }
            // Overwrite the target byte with the assembled byte.
            $source[$targetHeadByteIndex] = \chr($assembledByte);
            // Advance by the number of bits rewritten.
            $targetHeadBitAbsoluteIndex += $targetByteMissingBitCount;
            $sourceHeadBitAbsoluteIndex += $targetByteMissingBitCount;
        }
        $elementCount -= \min($howMany, $elementCount - $firstIndex);
        // Remove all the bytes after the last rewritten byte.
        $source = \substr_replace($source, '', ($elementCount >> 3) + 1, \PHP_INT_MAX);
        if ($primarySource) {
            $this->primarySource = $source;
            $this->elementCount = $elementCount;
        } else {
            $this->nullabilitySource = $source;
        }
    }

    private function deleteBytes(bool $primarySource, int $firstIndex, int $howMany, int $elementCount): void
    {
        $start = $firstIndex * 3;
        if ($howMany >= $elementCount - $firstIndex) {
            if ($primarySource) {
                $this->primarySource = \substr($this->primarySource, 0, $start);
                $this->elementCount = $firstIndex;
            } else {
                $this->nullabilitySource = \substr($this->nullabilitySource, 0, $start);
            }
        } else {
            $length = $howMany * 3;
            if ($primarySource) {
                $this->primarySource = \substr_replace($this->primarySource, '', $start, $length);
                $this->elementCount -= $howMany;
            } else {
                $this->nullabilitySource = \substr_replace($this->nullabilitySource, '', $start, $length);
            }
        }
    }
}
