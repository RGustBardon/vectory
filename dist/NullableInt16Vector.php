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

class NullableInt16Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private const SERIALIZATION_FORMAT_VERSION = 1;
    private const SUPPORTED_SERIALIZATION_FORMAT_VERSIONS = [self::SERIALIZATION_FORMAT_VERSION];
    private $elementCount = 0;
    private $primarySource = '';
    private $nullabilitySource = '';
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
        static $mask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', '�'];
        $byteIndex = $index >> 3;
        $isNull = $this->nullabilitySource[$byteIndex];
        $isNull = "\0" !== ($isNull & $mask[$index & 7]);
        if ($isNull) {
            $value = null;
        } else {
            $packedInteger = \substr($this->primarySource, $index * 2, 2);
            $value = \unpack('s', $packedInteger)[1];
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
            if ($value < -32768 || $value > 32767) {
                throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$value.', expected '.-32768 .' <= x <= '. 32767);
            }
        }
        static $originalMask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', '�'];
        static $invertedMask = ['�', '�', '�', '�', '�', '�', '�', ''];
        if (null === $value) {
            $byteIndex = $index >> 3;
            $unassignedCount = $byteIndex - \strlen($this->nullabilitySource) / 1;
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
            $unassignedCount = $byteIndex - \strlen($this->nullabilitySource) / 1;
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
        }
        $unpackedInteger = $value ?? 0;
        $packedInteger = \pack('s', $unpackedInteger);
        $unassignedCount = $index - \strlen($this->primarySource) / 2;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * 2;
            do {
                $this->primarySource[$byteIndex++] = $packedInteger[$elementIndex++];
            } while ($elementIndex < 2);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $packedInteger;
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat('  ', (int) $unassignedCount).$packedInteger;
        }
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            static $mask = ['�', '�', '�', '�', '�', '�', '�', '�'];
            static $shiftOneRight = ["\0" => "\0", "\1" => "\0", "\2" => "\1", "\3" => "\1", "\4" => "\2", "\5" => "\2", "\6" => "\3", "\7" => "\3", "\10" => "\4", "\t" => "\4", "\n" => "\5", "\v" => "\5", "\f" => "\6", "\r" => "\6", "\16" => "\7", "\17" => "\7", "\20" => "\10", "\21" => "\10", "\22" => "\t", "\23" => "\t", "\24" => "\n", "\25" => "\n", "\26" => "\v", "\27" => "\v", "\30" => "\f", "\31" => "\f", "\32" => "\r", "\33" => "\r", "\34" => "\16", "\35" => "\16", "\36" => "\17", "\37" => "\17", ' ' => "\20", '!' => "\20", '"' => "\21", '#' => "\21", '$' => "\22", '%' => "\22", '&' => "\23", "'" => "\23", '(' => "\24", ')' => "\24", '*' => "\25", '+' => "\25", ',' => "\26", '-' => "\26", '.' => "\27", '/' => "\27", '0' => "\30", '1' => "\30", '2' => "\31", '3' => "\31", '4' => "\32", '5' => "\32", '6' => "\33", '7' => "\33", '8' => "\34", '9' => "\34", ':' => "\35", ';' => "\35", '<' => "\36", '=' => "\36", '>' => "\37", '?' => "\37", '@' => ' ', 'A' => ' ', 'B' => '!', 'C' => '!', 'D' => '"', 'E' => '"', 'F' => '#', 'G' => '#', 'H' => '$', 'I' => '$', 'J' => '%', 'K' => '%', 'L' => '&', 'M' => '&', 'N' => "'", 'O' => "'", 'P' => '(', 'Q' => '(', 'R' => ')', 'S' => ')', 'T' => '*', 'U' => '*', 'V' => '+', 'W' => '+', 'X' => ',', 'Y' => ',', 'Z' => '-', '[' => '-', '\\' => '.', ']' => '.', '^' => '/', '_' => '/', '`' => '0', 'a' => '0', 'b' => '1', 'c' => '1', 'd' => '2', 'e' => '2', 'f' => '3', 'g' => '3', 'h' => '4', 'i' => '4', 'j' => '5', 'k' => '5', 'l' => '6', 'm' => '6', 'n' => '7', 'o' => '7', 'p' => '8', 'q' => '8', 'r' => '9', 's' => '9', 't' => ':', 'u' => ':', 'v' => ';', 'w' => ';', 'x' => '<', 'y' => '<', 'z' => '=', '{' => '=', '|' => '>', '}' => '>', '~' => '?', '' => '?', '�' => '@', '�' => '@', '�' => 'A', '�' => 'A', '�' => 'B', '
' => 'B', '�' => 'C', '�' => 'C', '�' => 'D', '�' => 'D', '�' => 'E', '�' => 'E', '�' => 'F', '�' => 'F', '�' => 'G', '�' => 'G', '�' => 'H', '�' => 'H', '�' => 'I', '�' => 'I', '�' => 'J', '�' => 'J', '�' => 'K', '�' => 'K', '�' => 'L', '�' => 'L', '�' => 'M', '�' => 'M', '�' => 'N', '�' => 'N', '�' => 'O', '�' => 'O', '�' => 'P', '�' => 'P', '�' => 'Q', '�' => 'Q', '�' => 'R', '�' => 'R', '�' => 'S', '�' => 'S', '�' => 'T', '�' => 'T', '�' => 'U', '�' => 'U', '�' => 'V', '�' => 'V', '�' => 'W', '�' => 'W', '�' => 'X', '�' => 'X', '�' => 'Y', '�' => 'Y', '�' => 'Z', '�' => 'Z', '�' => '[', '�' => '[', '�' => '\\', '�' => '\\', '�' => ']', '�' => ']', '�' => '^', '�' => '^', '�' => '_', '�' => '_', '�' => '`', '�' => '`', '�' => 'a', '�' => 'a', '�' => 'b', '�' => 'b', '�' => 'c', '�' => 'c', '�' => 'd', '�' => 'd', '�' => 'e', '�' => 'e', '�' => 'f', '�' => 'f', '�' => 'g', '�' => 'g', '�' => 'h', '�' => 'h', '�' => 'i', '�' => 'i', '�' => 'j', '�' => 'j', '�' => 'k', '�' => 'k', '�' => 'l', '�' => 'l', '�' => 'm', '�' => 'm', '�' => 'n', '�' => 'n', '�' => 'o', '�' => 'o', '�' => 'p', '�' => 'p', '�' => 'q', '�' => 'q', '�' => 'r', '�' => 'r', '�' => 's', '�' => 's', '�' => 't', '�' => 't', '�' => 'u', '�' => 'u', '�' => 'v', '�' => 'v', '�' => 'w', '�' => 'w', '�' => 'x', '�' => 'x', '�' => 'y', '�' => 'y', '�' => 'z', '�' => 'z', '�' => '{', '�' => '{', '�' => '|', '�' => '|', '�' => '}', '�' => '}', '�' => '~', '�' => '~', '�' => '', '�' => ''];
            --$this->elementCount;
            if ($this->elementCount > $index) {
                $carry = "\0";
                $byteCount = \strlen($this->nullabilitySource) / 1;
                for ($i = $byteCount - 1, $byteIndex = $index >> 3; $i > $byteIndex; --$i) {
                    $this->nullabilitySource[$i] = $shiftOneRight[$byte = $this->nullabilitySource[$i]] | $carry;
                    $carry = "\1" === ($byte & "\1") ? '�' : "\0";
                }
                // https://graphics.stanford.edu/~seander/bithacks.html#MaskedMerge
                $byte = $this->nullabilitySource[$i];
                $this->nullabilitySource[$i] = $byte ^ ($byte ^ ($shiftOneRight[$byte] | $carry)) & $mask[$index & 7];
            }
            if (0 === ($this->elementCount & 7)) {
                $this->nullabilitySource = \substr($this->nullabilitySource, 0, -1);
            }
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
            static $mask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', '�'];
            $byteIndex = $getIteratorIndex >> 3;
            $isNull = $clone->nullabilitySource[$byteIndex];
            $isNull = "\0" !== ($isNull & $mask[$getIteratorIndex & 7]);
            if ($isNull) {
                $result = null;
            } else {
                $packedInteger = \substr($clone->primarySource, $getIteratorIndex * 2, 2);
                $result = \unpack('s', $packedInteger)[1];
            }
            (yield $getIteratorIndex => $result);
        }
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $elementCount = $this->elementCount;
        for ($getIteratorIndex = 0; $getIteratorIndex < $elementCount; ++$getIteratorIndex) {
            static $mask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', '�'];
            $byteIndex = $getIteratorIndex >> 3;
            $isNull = $this->nullabilitySource[$byteIndex];
            $isNull = "\0" !== ($isNull & $mask[$getIteratorIndex & 7]);
            if ($isNull) {
                $element = null;
            } else {
                $packedInteger = \substr($this->primarySource, $getIteratorIndex * 2, 2);
                $element = \unpack('s', $packedInteger)[1];
            }
            $result[] = $element;
        }

        return $result;
    }

    public function serialize(): string
    {
        return \serialize([self::SERIALIZATION_FORMAT_VERSION, \pack('S', 1) === \pack('v', 1), $this->elementCount, $this->primarySource, $this->nullabilitySource]);
    }

    public function unserialize($serialized)
    {
        $errorMessage = 'Details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $newValues = \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\NullableInt16Vector', '\\')]]);
        \restore_error_handler();
        if (false === $newValues) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedTypes = ['integer', 'boolean', 'integer', 'string', 'string'];
        if (!\is_array($newValues) || \array_keys($newValues) !== \array_keys($expectedTypes) || \array_map('gettype', $newValues) !== $expectedTypes) {
            $errorMessage = 'Expected an array of '.\implode(', ', $expectedTypes);

            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $previousValues = [$this->elementCount, $this->primarySource, $this->nullabilitySource];
        [$version, $littleEndian, $this->elementCount, $this->primarySource, $this->nullabilitySource] = $newValues;
        if (!\in_array($version, self::SUPPORTED_SERIALIZATION_FORMAT_VERSIONS, true)) {
            $errorMessage = 'Unsupported version: '.$version;

            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        if ((\pack('S', 1) === \pack('v', 1)) !== $littleEndian) {
            $pattern = '~(.)(.)~';
            $replacement = '${2}${1}';
            $this->primarySource = \preg_replace($pattern, $replacement, $this->primarySource);
        }

        try {
            $expectedLength = $this->elementCount * 2;
            if (\strlen($this->primarySource) !== $expectedLength) {
                $errorMessage = \sprintf('Unexpected length of the primary source: expected %d bytes, found %d instead', $expectedLength, \strlen($this->primarySource));

                throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
            }
            $expectedLength = $this->elementCount + 7 >> 3;
            if (\strlen($this->nullabilitySource) !== $expectedLength) {
                $errorMessage = \sprintf('Unexpected length of the nullability source: expected %d bytes, found %d instead', $expectedLength, \strlen($this->nullabilitySource));

                throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
            }
            if ($this->elementCount < 0) {
                $errorMessage = 'The element count must not be negative';

                throw new \DomainException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
            }
        } catch (\Throwable $e) {
            [$this->elementCount, $this->primarySource, $this->nullabilitySource] = $previousValues;

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
        $this->deleteBits(false, $firstIndex, $howMany, $elementCount);
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
        $start = $firstIndex * 2;
        if ($howMany >= $elementCount - $firstIndex) {
            if ($primarySource) {
                $this->primarySource = \substr($this->primarySource, 0, $start);
                $this->elementCount = $firstIndex;
            } else {
                $this->nullabilitySource = \substr($this->nullabilitySource, 0, $start);
            }
        } else {
            $length = $howMany * 2;
            if ($primarySource) {
                $this->primarySource = \substr_replace($this->primarySource, '', $start, $length);
                $this->elementCount -= $howMany;
            } else {
                $this->nullabilitySource = \substr_replace($this->nullabilitySource, '', $start, $length);
            }
        }
    }
}
