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

class NullableUint24Vector implements VectorInterface
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
        if (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($index).' given');
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
            $packedInteger = \substr($this->primarySource, $index * 3, 3);
            $value = \unpack('V', $packedInteger."\0")[1];
        }

        return $value;
    }

    public function offsetSet($index, $value)
    {
        if (null === $index) {
            $index = $this->elementCount;
        } elseif (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($index).' given');
        } elseif ($index < 0) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Negative index: '.$index);
        }
        if (null !== $value) {
            if (!\is_int($value)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'int', ' or null', \gettype($value)));
            }
            if ($value < 0 || $value > 16777215) {
                throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$value.', expected '. 0 .' <= x <= '. 16777215);
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
        $unpackedInteger = $value ?? 0;
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
            $this->primarySource .= \str_repeat("\0\0\0", (int) $unassignedCount).$packedInteger;
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
                $packedInteger = \substr($clone->primarySource, $getIteratorIndex * 3, 3);
                $result = \unpack('V', $packedInteger."\0")[1];
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
                $packedInteger = \substr($this->primarySource, $getIteratorIndex * 3, 3);
                $element = \unpack('V', $packedInteger."\0")[1];
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
        $newValues = \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\NullableUint24Vector', '\\')]]);
        \restore_error_handler();
        if (false === $newValues) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedTypes = ['integer', 'integer', 'string', 'string'];
        if (!\is_array($newValues) || \array_keys($newValues) !== \array_keys($expectedTypes) || \array_map('gettype', $newValues) !== $expectedTypes) {
            $errorMessage = 'Expected an array of '.\implode(', ', $expectedTypes);

            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        [$version, $elementCount, $primarySource, $nullabilitySource] = $newValues;
        if (!\in_array($version, self::SUPPORTED_SERIALIZATION_FORMAT_VERSIONS, true)) {
            $errorMessage = 'Unsupported version: '.$version;

            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        if ($elementCount < 0) {
            $errorMessage = 'The element count must not be negative';

            throw new \DomainException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedLength = $elementCount * 3;
        if (\strlen($primarySource) !== $expectedLength) {
            $errorMessage = \sprintf('Unexpected length of the primary source: expected %d bytes, found %d instead', $expectedLength, \strlen($primarySource));

            throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedLength = $elementCount + 7 >> 3;
        if (\strlen($nullabilitySource) !== $expectedLength) {
            $errorMessage = \sprintf('Unexpected length of the nullability source: expected %d bytes, found %d instead', $expectedLength, \strlen($nullabilitySource));

            throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $this->elementCount = $elementCount;
        $this->primarySource = $primarySource;
        $this->nullabilitySource = $nullabilitySource;
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

    public function insert(iterable $elements, int $firstIndex = -1): void
    {
        // Prepare a substring to insert.
        $defaultValue = 0;
        $defaultValue = \pack('VX', $defaultValue);
        $substringToInsert = '';
        $nullabilitySubstring = '';
        $nullabilityByte = 0;
        $howManyBitsToInsert = 0;
        foreach ($elements as $element) {
            if (null === $element) {
                $nullabilityByte = $nullabilityByte | 1 << ($howManyBitsToInsert & 7);
                $substringToInsert .= $defaultValue;
            } else {
                if (!\is_int($element)) {
                    throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'int', ' or null', \gettype($element)));
                }
                if ($element < 0 || $element > 16777215) {
                    throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$element.', expected '. 0 .' <= x <= '. 16777215);
                }
                $packedInteger = \pack('VX', $element);
                $substringToInsert .= $packedInteger;
            }
            ++$howManyBitsToInsert;
            if (0 === ($howManyBitsToInsert & 7)) {
                $nullabilitySubstring .= \chr($nullabilityByte);
                $nullabilityByte = 0;
            }
        }
        if (($howManyBitsToInsert & 7) > 0) {
            $nullabilitySubstring .= \chr($nullabilityByte);
        }
        // Insert the elements.
        $this->insertBits(false, $nullabilitySubstring, $firstIndex, $howManyBitsToInsert);
        $this->insertBytes($substringToInsert, $firstIndex);
    }

    private function deleteBits(bool $primarySource, int $firstIndex, int $howMany, int $elementCount): int
    {
        $byteCount = $elementCount + 7 >> 3;
        // Bit-shifting a substring is expensive, so delete all the full bytes in the range, for example:
        //
        // Indices:            0123|4567 89ab|cdef 0123|4567 89ab|cdef 0123|4567 89ab|cdef
        // To be deleted:             XX XXXX|XXXX XXXX|XXXX XXXX XXX
        // Full bytes:                   ^^^^ ^^^^ ^^^^ ^^^^
        $firstFullByteIndex = ($firstIndex >> 3) + (0 === ($firstIndex & 7) ? 0 : 1);
        $howManyFullBytes = \min($byteCount - 1, $firstIndex + $howMany >> 3) - $firstFullByteIndex;
        if ($howManyFullBytes > 0) {
            $this->deleteBytes($primarySource, $firstFullByteIndex, $howManyFullBytes, $byteCount);
            $byteCount -= $howManyFullBytes;
            $deletedBitCount = $howManyFullBytes << 3;
            $elementCount -= $deletedBitCount;
            $howMany -= $deletedBitCount;
            if (0 === $howMany) {
                return $elementCount;
            }
        }
        if (0 === ($firstIndex & 7) && $firstIndex + $howMany >= $elementCount) {
            // If the first index conceptually begins a byte and everything to its right is to be deleted,
            // no bit-shifting is necessary.
            $this->nullabilitySource = \substr($this->nullabilitySource, 0, $firstIndex >> 3);

            return $firstIndex;
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
        $this->nullabilitySource = $source;

        return $elementCount;
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

    private function insertBits(bool $primarySource, string $substringToInsert, int $firstIndex, int $howManyBitsToInsert): void
    {
        // Conceptually, after the insertion, the string will consist of at most three different substrings.
        // Elements might already exist in the source. These will be denoted by E.
        // New elements might need to be inserted. These will be denoted by N.
        // There might be a gap between existing elements and new elements.
        // It will be filled with zeros and denoted by G.
        // The question mark means that the substring is optional.
        // Substrings will be concatenated quickly, and then the `delete` method will remove all the
        // superfluous bits. For instance, if the source contains 3 bits and 2 bits are to be inserted with
        // their first index being 10 (0xa), then:
        // Indices:          0123|4567 89ab|cdef 0123|4567
        // To be inserted:   NN
        // Original source:  EEE0|0000
        // `$firstIndex`:                ^
        // Concatenation:    EEE0|0000 GGGG|GGGG NN00|0000
        // Superfluous bits:    ^ ^^^^         ^   ^^ ^^^^
        // Deletion:         EEEG|GGGG GGNN|0000
        // The above is a simplified view. In reality, the bits are reversed in each byte:
        // Indices:          7654|3210 fedc|ba98 7654|3210
        // To be inserted:   NN
        // Original source:  0000|0EEE
        // Concatenation:    0000|0EEE GGGG|GGGG 0000|00NN
        // Deletion:         GGGG|GEEE 0000|NNGG
        // If `$firstIndex` is out of bounds (for instance, in case there are originally 3 bits, -4 or 3
        // would be an out-of-bound first index) and no elements are to be inserted, then the source
        // will still be mutated: it will be padded with zeros in the direction where elements would
        // have been inserted.
        $elementCount = (int) $this->elementCount;
        $byteCount = $elementCount + 7 >> 3;
        if (-1 === $firstIndex || $firstIndex > $elementCount - 1) {
            // Zero or more elements are to be inserted after the existing elements (X?G?N?).
            $originalBitCount = $elementCount;
            $tailRelativeBitIndex = $elementCount & 7;
            // Calculate if a gap should exist between the existing elements and the new ones.
            $gapInBits = \max(0, $firstIndex - $elementCount);
            $gapInBytes = ($gapInBits >> 3) + (0 === ($gapInBits & 7) ? 0 : 1);
            if ($gapInBytes > 0) {
                // Append the gap (X?GN?).
                $byteCount += $gapInBytes;
                $elementCount += $gapInBytes << 3;
                $this->nullabilitySource .= \str_repeat("\0", $gapInBytes);
                $elementCount = $this->deleteBits($primarySource, $originalBitCount + $gapInBits, \PHP_INT_MAX, $elementCount);
                $byteCount = $elementCount + 7 >> 3;
            }
            if ($howManyBitsToInsert > 0) {
                // Append new elements (X?G?N).
                $bitCountAfterFillingTheGap = $elementCount;
                $tailRelativeBitIndex = $elementCount & 7;
                $byteCount += \strlen($substringToInsert);
                $elementCount = $byteCount << 3;
                $this->nullabilitySource .= $substringToInsert;
                if ($tailRelativeBitIndex > 0) {
                    // The gap did not end at a full byte, so remove the superfluous bits.
                    $elementCount = $this->deleteBits($primarySource, $bitCountAfterFillingTheGap, 8 - $tailRelativeBitIndex, $elementCount);
                }
                // Delete all the bits after the last inserted bit.
                $this->deleteBits($primarySource, $originalBitCount + $gapInBits + $howManyBitsToInsert, \PHP_INT_MAX, $elementCount);
            }
        } else {
            // Elements are to be inserted left of the rightmost bit though not necessarily immediately before it.
            $originalFirstIndex = $firstIndex;
            // Calculate the positive index corresponding to the negative one.
            if ($firstIndex < 0) {
                $firstIndex += $elementCount;
                // Keep the indices within the bounds.
                if ($firstIndex < 0) {
                    $firstIndex = 0;
                }
            }
            $newBitCount = $elementCount + $howManyBitsToInsert;
            if (-$originalFirstIndex > $newBitCount) {
                // Resize the source if the negative first bit index is greater than the new bit count (N?GX?).
                $originalBitCount = $elementCount;
                $overflowInBits = -$originalFirstIndex - $newBitCount - ($howManyBitsToInsert > 0 ? 0 : 1);
                $padLengthInBits = $overflowInBits + $howManyBitsToInsert;
                $padLengthInBytes = $padLengthInBits + 7 >> 3;
                $substringToInsert = \str_pad($substringToInsert, $padLengthInBytes, "\0", \STR_PAD_RIGHT);
                $byteCount += \strlen($substringToInsert);
                $elementCount += $padLengthInBytes << 3;
                $this->nullabilitySource = $substringToInsert.$this->nullabilitySource;
                if (($padLengthInBits & 7) > 0) {
                    // The gap did not end at a full byte, so remove the superfluous bits.
                    $this->deleteBits($primarySource, $padLengthInBits, 8 - ($padLengthInBits & 7), $elementCount);
                }
            } elseif ($howManyBitsToInsert > 0) {
                // There will be no gap left or right of the original source (X?NX).
                if (0 === ($firstIndex & 7)) {
                    // The bits are to be inserted at a full byte.
                    if ($firstIndex > 0) {
                        // The bits are not to be inserted at the beginning, so splice (XNX).
                        $this->nullabilitySource = \substr($this->nullabilitySource, 0, $firstIndex >> 3).$substringToInsert.\substr($this->nullabilitySource, $firstIndex >> 3);
                    } else {
                        // The bits are to be inserted at the beginning, so prepend (NX).
                        $this->nullabilitySource = $substringToInsert.$this->nullabilitySource;
                    }
                    $byteCount = \strlen($this->nullabilitySource);
                    $elementCount += \strlen($substringToInsert) << 3;
                    if (($howManyBitsToInsert & 7) > 0) {
                        // The inserted bits did not end at a full byte, so remove the superfluous bits.
                        $this->deleteBits($primarySource, $firstIndex + $howManyBitsToInsert, 8 - ($howManyBitsToInsert & 7), $elementCount);
                    }
                } else {
                    // Splice inside a byte (XNX).
                    // The part of the original bytemap to the left of what is being inserted will be
                    // referred to as 'head,' the part to the right will be referred to as 'tail.'
                    // Since splicing does not start at a full byte, both the head and the tail will
                    // originally have one byte in common. The overlapping bits (rightmost in the head
                    // and leftmost in the tail) will then by removed by calling the `delete` method.
                    // Head bits will be denoted as H, tail bits will be denoted as T.
                    // For instance, if the source contains 20 bits and 5 bits are to be inserted with
                    // their first index being 10 (0xa), then:
                    // Indices:         0123|4567 89ab|cdef 0123|4567 89ab|cdef 0123|4567
                    // To be inserted:  NNNNN
                    // Original source: EEEE|EEEE EEEE|EEEE EEEE|0000
                    //                            ---------
                    //                                |     same byte
                    //                                |------------------.
                    //                                |                   \
                    //                            ---------           ---------
                    // Concatenation:   HHHH|HHHH HHHH|HHHH NNNN|N000 TTTT|TTTT TTTT|0000
                    // `$firstIndex`:               ^
                    // Overlapping bits:            ^^ ^^^^           ^^
                    // 1st deletion:                                                 ^^^^
                    // 2nd deletion:                              ^^^ ^^                  ('middle gap')
                    // 3rd deletion:                ^^ ^^^^
                    // Result:          HHHH|HHHH HHNN|NNNT TTTT|TTTT T000|0000
                    // The above is a simplified view. In reality, the bits are reversed in each byte:
                    // Indices:         7654|3210 fedc|ba98 7654|3210 fedc|ba98 7654|3210
                    // To be inserted:  NNNNN
                    // Original source: EEEE|EEEE EEEE|EEEE 0000|EEEE
                    //                            ---------
                    //                                |     same byte
                    //                                |------------------.
                    //                                |                   \
                    //                            ---------           ---------
                    // Concatenation:   HHHH|HHHH HHHH|HHHH 000N|NNNN TTTT|TTTT 0000|TTTT
                    // Result:          HHHH|HHHH TNNN|NNHH TTTT|TTTT 0000|000T
                    $originalBitCount = $elementCount;
                    $head = '';
                    $head = \substr($this->nullabilitySource, 0, ($firstIndex >> 3) + 1);
                    $tail = \substr($this->nullabilitySource, $firstIndex >> 3);
                    $this->nullabilitySource = $head.$substringToInsert.$tail;
                    $elementCount = \strlen($this->nullabilitySource) << 3;
                    if (($originalBitCount & 7) > 0) {
                        // The tail did not end at a full byte, so remove the superfluous bits.
                        $elementCount = $this->deleteBits($primarySource, $elementCount + ($originalBitCount & 7) - 8, \PHP_INT_MAX, $elementCount);
                    }
                    // Remove the middle gap.
                    $middleGapLengthInBits = $firstIndex & 7;
                    if (($howManyBitsToInsert & 7) > 0) {
                        $middleGapLengthInBits += 8 - ($howManyBitsToInsert & 7);
                    }
                    $elementCount = $this->deleteBits($primarySource, (\strlen($head) << 3) + $howManyBitsToInsert, $middleGapLengthInBits, $elementCount);
                    // The head did not end at a full byte, so remove the superfluous bits.
                    $this->deleteBits($primarySource, $firstIndex, 8 - ($firstIndex & 7), $elementCount);
                }
            }
        }
    }

    private function insertBytes(string $substringToInsert, int $firstIndex): void
    {
        $defaultValue = 0;
        $defaultValue = \pack('VX', $defaultValue);
        if (-1 === $firstIndex || $firstIndex > $this->elementCount - 1) {
            // Insert the elements.
            $padLength = \strlen($substringToInsert) + \max(0, $firstIndex - $this->elementCount) * 3;
            $this->primarySource .= \str_pad($substringToInsert, (int) $padLength, $defaultValue, \STR_PAD_LEFT);
            $this->elementCount += $padLength / 3;
        } else {
            $originalFirstIndex = $firstIndex;
            // Calculate the positive index corresponding to the negative one.
            if ($firstIndex < 0) {
                $firstIndex += $this->elementCount;
                // Keep the indices within the bounds.
                if ($firstIndex < 0) {
                    $firstIndex = 0;
                }
            }
            // Resize the bytemap if the negative first element index is greater than the new element count.
            $insertedElementCount = (int) (\strlen($substringToInsert) / 3);
            $newElementCount = $this->elementCount + $insertedElementCount;
            if (-$originalFirstIndex > $newElementCount) {
                $overflow = -$originalFirstIndex - $newElementCount - ($insertedElementCount > 0 ? 0 : 1);
                $padLength = ($overflow + $insertedElementCount) * 3;
                $substringToInsert = \str_pad($substringToInsert, (int) $padLength, $defaultValue, \STR_PAD_RIGHT);
            }
            // Insert the elements.
            $this->primarySource = \substr_replace($this->primarySource, $substringToInsert, $firstIndex * 3, 0);
            $this->elementCount += (int) (\strlen($substringToInsert) / 3);
        }
    }
}
