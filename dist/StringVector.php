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

class StringVector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private const SERIALIZATION_FORMAT_VERSION = 1;
    private const SUPPORTED_SERIALIZATION_FORMAT_VERSIONS = [self::SERIALIZATION_FORMAT_VERSION];
    private $elementCount = 0;
    private $primarySource = '';
    private $elementLength;
    private $defaultValue;

    public function __construct(int $elementLength)
    {
        if ($elementLength <= 1) {
            throw new \DomainException(self::EXCEPTION_PREFIX.'Element length must be at least 2');
        }
        $this->defaultValue = \str_repeat("\0", $elementLength);
        $this->elementLength = $elementLength;
    }

    public function __debugInfo(): array
    {
        $info = ['elementCount' => $this->elementCount, 'elements' => \iterator_to_array($this->getIterator()), 'primarySource' => ''];
        $sources = ['primary'];
        if ($this->elementCount > 0) {
            foreach ($sources as $sourcePrefix) {
                $info[$sourcePrefix.'Source'] = \PHP_EOL;
                $property = new \ReflectionProperty($this, $sourcePrefix.'Source');
                $property->setAccessible(true);
                $source = $property->getValue($this);
                $bytesPerElement = $this->elementLength ?? 1;
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

        return \substr($this->primarySource, $index * $this->elementLength, $this->elementLength);
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
        if (!\is_string($value)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'string', '', \gettype($value)));
        }
        if (\strlen($value) !== $this->elementLength) {
            throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Value must be exactly %d bytes, %d given', $this->elementLength, \strlen($value)));
        }
        $unassignedCount = $index - \strlen($this->primarySource) / $this->elementLength;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $elementIndex = 0;
            $byteIndex = $index * $this->elementLength;
            do {
                $this->primarySource[$byteIndex++] = $value[$elementIndex++];
            } while ($elementIndex < $this->elementLength);
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an element right after the last one.
            $this->primarySource .= $value;
        } else {
            // Case 3. Append to a gap after the last element. Fill the gap with default values.
            $this->primarySource .= \str_repeat($this->defaultValue, (int) $unassignedCount).$value;
        }
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            --$this->elementCount;
            $this->primarySource = \substr_replace($this->primarySource, '', $index * $this->elementLength, $this->elementLength);
        }
    }

    public function count(): int
    {
        return $this->elementCount;
    }

    public function getIterator(): \Traversable
    {
        $elementCount = $this->elementCount;
        $primarySource = $this->primarySource;
        $batchSize = 256 * $this->elementLength;
        for ($index = 0; $index < $elementCount; $index += 256) {
            yield from \array_combine(\range($index, \min($elementCount, $index + 256) - 1), (array) \str_split(\substr($primarySource, $index * $this->elementLength, $batchSize), $this->elementLength));
        }
    }

    public function jsonSerialize(): array
    {
        if (0 === $this->elementCount) {
            return [];
        }
        $jsonData = [];
        $elementCount = $this->elementCount;
        $primarySource = $this->primarySource;
        $batchSize = 256 * $this->elementLength;
        for ($index = 0; $index < $elementCount; $index += 256) {
            \array_push($jsonData, ...(array) \str_split(\substr($primarySource, $index * $this->elementLength, $batchSize), $this->elementLength));
        }

        return $jsonData;
    }

    public function serialize(): string
    {
        return \serialize([self::SERIALIZATION_FORMAT_VERSION, $this->elementCount, $this->elementLength, $this->primarySource]);
    }

    public function unserialize($serialized)
    {
        $errorMessage = 'Details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $newValues = \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\StringVector', '\\')]]);
        \restore_error_handler();
        if (false === $newValues) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedTypes = ['integer', 'integer', 'integer', 'string'];
        if (!\is_array($newValues) || \array_keys($newValues) !== \array_keys($expectedTypes) || \array_map('gettype', $newValues) !== $expectedTypes) {
            $errorMessage = 'Expected an array of '.\implode(', ', $expectedTypes);

            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        [$version, $elementCount, $elementLength, $primarySource] = $newValues;
        if (!\in_array($version, self::SUPPORTED_SERIALIZATION_FORMAT_VERSIONS, true)) {
            $errorMessage = 'Unsupported version: '.$version;

            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        if ($elementCount < 0) {
            $errorMessage = 'The element count must not be negative';

            throw new \DomainException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $expectedLength = $elementCount * $elementLength;
        if (\strlen($primarySource) !== $expectedLength) {
            $errorMessage = \sprintf('Unexpected length of the primary source: expected %d bytes, found %d instead', $expectedLength, \strlen($primarySource));

            throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $this->elementCount = $elementCount;
        $this->elementLength = $elementLength;
        $this->defaultValue = \str_repeat("\0", $elementLength);
        $this->primarySource = $primarySource;
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
        $this->deleteBytes(true, $firstIndex, $howMany, $elementCount);
    }

    public function insert(iterable $elements, int $firstIndex = -1): void
    {
        // Prepare a substring to insert.
        $defaultValue = $this->defaultValue;
        $substringToInsert = '';
        foreach ($elements as $element) {
            if (!\is_string($element)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'string', '', \gettype($element)));
            }
            if (\strlen($element) !== $this->elementLength) {
                throw new \LengthException(self::EXCEPTION_PREFIX.\sprintf('Value must be exactly %d bytes, %d given', $this->elementLength, \strlen($element)));
            }
            $substringToInsert .= $element;
        }
        // Insert the elements.
        $this->insertBytes($substringToInsert, $firstIndex);
    }

    private function deleteBytes(bool $primarySource, int $firstIndex, int $howMany, int $elementCount): void
    {
        if ($howMany >= $elementCount - $firstIndex) {
            if ($primarySource) {
                $this->primarySource = \substr($this->primarySource, 0, $firstIndex * $this->elementLength);
                $this->elementCount = $firstIndex;
            }
        } else {
            if ($primarySource) {
                $this->primarySource = \substr_replace($this->primarySource, '', $firstIndex * $this->elementLength, $howMany * $this->elementLength);
                $this->elementCount -= $howMany;
            }
        }
    }

    private function insertBytes(string $substringToInsert, int $firstIndex): void
    {
        $defaultValue = $this->defaultValue;
        if (-1 === $firstIndex || $firstIndex > $this->elementCount - 1) {
            // Insert the elements.
            $padLength = \strlen($substringToInsert) + \max(0, $firstIndex - $this->elementCount) * $this->elementLength;
            $this->primarySource .= \str_pad($substringToInsert, (int) $padLength, $defaultValue, \STR_PAD_LEFT);
            $this->elementCount += $padLength / $this->elementLength;
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
            $insertedElementCount = (int) (\strlen($substringToInsert) / $this->elementLength);
            $newElementCount = $this->elementCount + $insertedElementCount;
            if (-$originalFirstIndex > $newElementCount) {
                $overflow = -$originalFirstIndex - $newElementCount - ($insertedElementCount > 0 ? 0 : 1);
                $padLength = ($overflow + $insertedElementCount) * $this->elementLength;
                $substringToInsert = \str_pad($substringToInsert, (int) $padLength, $defaultValue, \STR_PAD_RIGHT);
            }
            // Insert the elements.
            $this->primarySource = \substr_replace($this->primarySource, $substringToInsert, $firstIndex * $this->elementLength, 0);
            $this->elementCount += (int) (\strlen($substringToInsert) / $this->elementLength);
        }
    }
}