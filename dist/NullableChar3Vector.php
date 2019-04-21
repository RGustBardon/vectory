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

class NullableChar3Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private const SERIALIZATION_FORMAT_VERSION = 1;
    private const SUPPORTED_SERIALIZATION_FORMAT_VERSIONS = [self::SERIALIZATION_FORMAT_VERSION];
    private $elementCount = 0;
    private $primarySource = '';
    private $nullabilitySource = '';

    public function __debugInfo(): array
    {
        $info = ['elementCount' => $this->elementCount, 'elements' => \iterator_to_array($this->getIterator()), 'primarySource' => '', 'nullabilitySource' => ''];
        $sources = ['primary'];
        $sources[] = 'nullability';
        if ($this->elementCount > 0) {
            foreach ($sources as $sourcePrefix) {
                $info[$sourcePrefix.'Source'] = \PHP_EOL;
                $property = new \ReflectionProperty($this, $sourcePrefix.'Source');
                $property->setAccessible(true);
                $source = $property->getValue($this);
                $bytesPerElement = 3 ?? 1;
                $elements = \str_split(\bin2hex($source), $bytesPerElement * 2);
                \assert(\is_iterable($elements));
                foreach ($elements as $index => $element) {
                    $line = '';
                    $line .= \substr(\strtoupper($sourcePrefix), 0, 1);
                    $line .= \sprintf('% '.\strlen((string) (\strlen($source) / $bytesPerElement)).'d: ', $index);
                    foreach (\str_split($element, 2) as $value) {
                        $decimal = (int) \hexdec($value);
                        $binary = \strrev(\str_pad(\decbin($decimal), 8, '0', \STR_PAD_LEFT));
                        $line .= \sprintf('% 3s (0x% 2s) %04s %04s | ', $decimal, $value, \substr($binary, 0, 4), \substr($binary, 4));
                    }
                    $info[$sourcePrefix.'Source'] .= \substr($line, 0, -3).\PHP_EOL;
                }
                $info[$sourcePrefix.'Source'] = \rtrim($info[$sourcePrefix.'Source'], \PHP_EOL);
            }
        }

        return $info;
    }

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
        static $mask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', "\200"];
        $byteIndex = $index >> 3;
        $isNull = $this->nullabilitySource[$byteIndex];
        $isNull = "\0" !== ($isNull & $mask[$index & 7]);
        if ($isNull) {
            $value = null;
        } else {
            $value = \substr($this->primarySource, $index * 3, 3);
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
            if (3 !== \strlen($value)) {
                throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Value must be exactly %d bytes, %d given', 3, \strlen($value)));
            }
        }
        static $originalMask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', "\200"];
        static $invertedMask = ["\376", "\375", "\373", "\367", "\357", "\337", "\277", "\177"];
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
                $this->nullabilitySource .= \str_repeat("\0", (int) $unassignedCount).(($this->nullabilitySource[$byteIndex] ?? "\0") | $originalMask[$index & 7]);
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
                $this->nullabilitySource .= \str_repeat("\0", (int) $unassignedCount).(($this->nullabilitySource[$byteIndex] ?? "\0") & $invertedMask[$index & 7]);
            }
        }
        $unassignedCount = $index - \strlen($this->primarySource) / 3;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * 3;
            do {
                $this->primarySource[$byteIndex++] = ($value ?? "\0\0\0")[$elementIndex++];
            } while ($elementIndex < 3);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $value ?? "\0\0\0";
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat("\0\0\0", (int) $unassignedCount).($value ?? "\0\0\0");
        }
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            static $mask = ["\377", "\376", "\374", "\370", "\360", "\340", "\300", "\200"];
            static $shiftOneRight = ["\0" => "\0", "\1" => "\0", "\2" => "\1", "\3" => "\1", "\4" => "\2", "\5" => "\2", "\6" => "\3", "\7" => "\3", "\10" => "\4", "\t" => "\4", "\n" => "\5", "\v" => "\5", "\f" => "\6", "\r" => "\6", "\16" => "\7", "\17" => "\7", "\20" => "\10", "\21" => "\10", "\22" => "\t", "\23" => "\t", "\24" => "\n", "\25" => "\n", "\26" => "\v", "\27" => "\v", "\30" => "\f", "\31" => "\f", "\32" => "\r", "\33" => "\r", "\34" => "\16", "\35" => "\16", "\36" => "\17", "\37" => "\17", ' ' => "\20", '!' => "\20", '"' => "\21", '#' => "\21", '$' => "\22", '%' => "\22", '&' => "\23", "'" => "\23", '(' => "\24", ')' => "\24", '*' => "\25", '+' => "\25", ',' => "\26", '-' => "\26", '.' => "\27", '/' => "\27", '0' => "\30", '1' => "\30", '2' => "\31", '3' => "\31", '4' => "\32", '5' => "\32", '6' => "\33", '7' => "\33", '8' => "\34", '9' => "\34", ':' => "\35", ';' => "\35", '<' => "\36", '=' => "\36", '>' => "\37", '?' => "\37", '@' => ' ', 'A' => ' ', 'B' => '!', 'C' => '!', 'D' => '"', 'E' => '"', 'F' => '#', 'G' => '#', 'H' => '$', 'I' => '$', 'J' => '%', 'K' => '%', 'L' => '&', 'M' => '&', 'N' => "'", 'O' => "'", 'P' => '(', 'Q' => '(', 'R' => ')', 'S' => ')', 'T' => '*', 'U' => '*', 'V' => '+', 'W' => '+', 'X' => ',', 'Y' => ',', 'Z' => '-', '[' => '-', '\\' => '.', ']' => '.', '^' => '/', '_' => '/', '`' => '0', 'a' => '0', 'b' => '1', 'c' => '1', 'd' => '2', 'e' => '2', 'f' => '3', 'g' => '3', 'h' => '4', 'i' => '4', 'j' => '5', 'k' => '5', 'l' => '6', 'm' => '6', 'n' => '7', 'o' => '7', 'p' => '8', 'q' => '8', 'r' => '9', 's' => '9', 't' => ':', 'u' => ':', 'v' => ';', 'w' => ';', 'x' => '<', 'y' => '<', 'z' => '=', '{' => '=', '|' => '>', '}' => '>', '~' => '?', "\177" => '?', "\200" => '@', "\201" => '@', "\202" => 'A', "\203" => 'A', "\204" => 'B', "\205" => 'B', "\206" => 'C', "\207" => 'C', "\210" => 'D', "\211" => 'D', "\212" => 'E', "\213" => 'E', "\214" => 'F', "\215" => 'F', "\216" => 'G', "\217" => 'G', "\220" => 'H', "\221" => 'H', "\222" => 'I', "\223" => 'I', "\224" => 'J', "\225" => 'J', "\226" => 'K', "\227" => 'K', "\230" => 'L', "\231" => 'L', "\232" => 'M', "\233" => 'M', "\234" => 'N', "\235" => 'N', "\236" => 'O', "\237" => 'O', "\240" => 'P', "\241" => 'P', "\242" => 'Q', "\243" => 'Q', "\244" => 'R', "\245" => 'R', "\246" => 'S', "\247" => 'S', "\250" => 'T', "\251" => 'T', "\252" => 'U', "\253" => 'U', "\254" => 'V', "\255" => 'V', "\256" => 'W', "\257" => 'W', "\260" => 'X', "\261" => 'X', "\262" => 'Y', "\263" => 'Y', "\264" => 'Z', "\265" => 'Z', "\266" => '[', "\267" => '[', "\270" => '\\', "\271" => '\\', "\272" => ']', "\273" => ']', "\274" => '^', "\275" => '^', "\276" => '_', "\277" => '_', "\300" => '`', "\301" => '`', "\302" => 'a', "\303" => 'a', "\304" => 'b', "\305" => 'b', "\306" => 'c', "\307" => 'c', "\310" => 'd', "\311" => 'd', "\312" => 'e', "\313" => 'e', "\314" => 'f', "\315" => 'f', "\316" => 'g', "\317" => 'g', "\320" => 'h', "\321" => 'h', "\322" => 'i', "\323" => 'i', "\324" => 'j', "\325" => 'j', "\326" => 'k', "\327" => 'k', "\330" => 'l', "\331" => 'l', "\332" => 'm', "\333" => 'm', "\334" => 'n', "\335" => 'n', "\336" => 'o', "\337" => 'o', "\340" => 'p', "\341" => 'p', "\342" => 'q', "\343" => 'q', "\344" => 'r', "\345" => 'r', "\346" => 's', "\347" => 's', "\350" => 't', "\351" => 't', "\352" => 'u', "\353" => 'u', "\354" => 'v', "\355" => 'v', "\356" => 'w', "\357" => 'w', "\360" => 'x', "\361" => 'x', "\362" => 'y', "\363" => 'y', "\364" => 'z', "\365" => 'z', "\366" => '{', "\367" => '{', "\370" => '|', "\371" => '|', "\372" => '}', "\373" => '}', "\374" => '~', "\375" => '~', "\376" => "\177", "\377" => "\177"];
            --$this->elementCount;
            if ($this->elementCount > $index) {
                $carry = "\0";
                $byteCount = \strlen($this->nullabilitySource) / 1;
                for ($i = $byteCount - 1, $byteIndex = $index >> 3; $i > $byteIndex; --$i) {
                    $this->nullabilitySource[$i] = $shiftOneRight[$byte = $this->nullabilitySource[$i]] | $carry;
                    $carry = "\1" === ($byte & "\1") ? "\200" : "\0";
                }
                // https://graphics.stanford.edu/~seander/bithacks.html#MaskedMerge
                $byte = $this->nullabilitySource[$i];
                $this->nullabilitySource[$i] = $byte ^ ($byte ^ ($shiftOneRight[$byte] | $carry)) & $mask[$index & 7];
            }
            if (0 === ($this->elementCount & 7)) {
                $this->nullabilitySource = \substr($this->nullabilitySource, 0, -1);
            }
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
            static $mask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', "\200"];
            $byteIndex = $getIteratorIndex >> 3;
            $isNull = $clone->nullabilitySource[$byteIndex];
            $isNull = "\0" !== ($isNull & $mask[$getIteratorIndex & 7]);
            if ($isNull) {
                $result = null;
            } else {
                $result = \substr($clone->primarySource, $getIteratorIndex * 3, 3);
            }
            (yield $getIteratorIndex => $result);
        }
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $elementCount = $this->elementCount;
        for ($getIteratorIndex = 0; $getIteratorIndex < $elementCount; ++$getIteratorIndex) {
            static $mask = ["\1", "\2", "\4", "\10", "\20", ' ', '@', "\200"];
            $byteIndex = $getIteratorIndex >> 3;
            $isNull = $this->nullabilitySource[$byteIndex];
            $isNull = "\0" !== ($isNull & $mask[$getIteratorIndex & 7]);
            if ($isNull) {
                $element = null;
            } else {
                $element = \substr($this->primarySource, $getIteratorIndex * 3, 3);
            }
            $result[] = $element;
        }

        return $result;
    }

    public function serialize(): string
    {
        return \serialize([self::SERIALIZATION_FORMAT_VERSION, $this->elementCount, $this->primarySource, $this->nullabilitySource]);
    }

    public function unserialize($serialized)
    {
        $errorMessage = 'Details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $newValues = \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\NullableChar3Vector', '\\')]]);
        \restore_error_handler();
        if (false === $newValues) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedTypes = ['integer', 'integer', 'string', 'string'];
        if (!\is_array($newValues) || \array_keys($newValues) !== \array_keys($expectedTypes) || \array_map('gettype', $newValues) !== $expectedTypes) {
            $errorMessage = 'Expected an array of '.\implode(', ', $expectedTypes);

            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $previousValues = [$this->elementCount, $this->primarySource, $this->nullabilitySource];
        [$version, $this->elementCount, $this->primarySource, $this->nullabilitySource] = $newValues;
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
        $elementCount = (int) $this->elementCount;
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
        if ($howMany >= $elementCount - $firstIndex) {
            if ($primarySource) {
                $this->primarySource = \substr($this->primarySource, 0, $firstIndex * 3);
                $this->elementCount = $firstIndex;
            } else {
                $this->nullabilitySource = \substr($this->nullabilitySource, 0, $firstIndex);
            }
        } else {
            if ($primarySource) {
                $this->primarySource = \substr_replace($this->primarySource, '', $firstIndex * 3, $howMany * 3);
                $this->elementCount -= $howMany;
            } else {
                $this->nullabilitySource = \substr_replace($this->nullabilitySource, '', $firstIndex, $howMany);
            }
        }
    }
}
