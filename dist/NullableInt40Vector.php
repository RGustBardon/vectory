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

class NullableInt40Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
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
            $packedInteger = \substr($this->primarySource, $index * 5, 5);
            $value = \unpack('P', $packedInteger."\0\0\0")[1];
            if ($value > 549755813887) {
                $value = 549755813887 - $value;
            }
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
            if ($value < -549755813888 || $value > 549755813887) {
                throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Value out of range: '.$value.', expected '.-549755813888 .' <= x <= '. 549755813887);
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
        $packedInteger = \pack('PXXX', $unpackedInteger >= 0 ? $unpackedInteger : -$unpackedInteger + 549755813887);
        $unassignedCount = $index - \strlen($this->primarySource) / 5;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * 5;
            do {
                $this->primarySource[$byteIndex++] = $packedInteger[$elementIndex++];
            } while ($elementIndex < 5);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $packedInteger;
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat('     ', (int) $unassignedCount).$packedInteger;
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
            $this->primarySource = \substr_replace($this->primarySource, '', $index * 5, 5);
        }
    }

    public function count(): int
    {
        return $this->elementCount;
    }

    // __iterator_aggregate_methods()
    // __json_serializable_methods()
    // __serializable_methods()
}
