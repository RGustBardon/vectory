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

class Int64Vector implements VectorInterface
{
    private const EXCEPTION_PREFIX = 'Vectory: ';
    private $elementCount = 0;
    private $primarySource = [];

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

        return $this->primarySource[$index] ?? 0;
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
        $this->primarySource[$index] = $value;
        if ($this->elementCount < $index + 1) {
            $this->elementCount = $index + 1;
        }
    }

    public function offsetUnset($index)
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            if ($this->elementCount === $index) {
                --$this->elementCount;
                unset($this->primarySource[$index]);
            } else {
                if (\count($this->primarySource) !== $this->elementCount) {
                    $this->primarySource += \array_fill(0, $this->elementCount, 0);
                }
                \ksort($this->primarySource, \SORT_NUMERIC);
                \array_splice($this->primarySource, $index, 1);
                $this->primarySource = \array_diff($this->primarySource, [0]);
                if (!isset($this->primarySource[$this->elementCount - 2])) {
                    $this->primarySource[$this->elementCount - 2] = 0;
                }
                --$this->elementCount;
            }
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
        for ($index = 0; $index < $elementCount; ++$index) {
            (yield $index => $primarySource[$index] ?? 0);
        }
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $elementCount = $this->elementCount;
        $primarySource = $this->primarySource;
        for ($index = 0; $index < $elementCount; ++$index) {
            $result[] = $primarySource[$index] ?? 0;
        }

        return $result;
    }

    public function serialize(): string
    {
        return \serialize([$this->elementCount, $this->primarySource]);
    }

    public function unserialize(string $serialized): void
    {
        $errorMessage = 'Details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $result = \unserialize($serialized, ['allowed_classes' => [\ltrim('\\Vectory\\Int64Vector', '\\')]]);
        \restore_error_handler();
        if (false === $result) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        if (!\is_array($result) || [0, 1] !== \array_keys($result) || ['int', 'array'] !== \array_map('gettype', $result)) {
            $errorMessage = 'Expected an array of int, array';

            throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        [$elementCount, $primarySource] = $result;
        if ($elementCount < 0) {
            $errorMessage = 'The element count must not be negative';

            throw new \DomainException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        if (\count($primarySource) > $elementCount) {
            $errorMessage = 'Too many elements in the primary source';

            throw new \OverflowException(self::EXCEPTION_PREFIX.\sprintf('Failed to unserialize (%s)', $errorMessage));
        }
        $this->elementCount = $elementCount;

        try {
            foreach ($primarySource as $index => $element) {
                if (!\is_int($index)) {
                    throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($index).' given');
                }
                if (0 === $this->elementCount) {
                    throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'The container is empty, so index '.$index.' does not exist');
                }
                if ($index < 0 || $index >= $this->elementCount) {
                    throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Index out of range: '.$index.', expected 0 <= x <= '.($this->elementCount - 1));
                }
                if (!\is_int($element)) {
                    throw new \TypeError(self::EXCEPTION_PREFIX.\sprintf('Value must be of type %s%s, %s given', 'int', '', \gettype($element)));
                }
            }
        } catch (\Throwable $e) {
            $this->elementCount = null;

            throw $e;
        }
        [, $this->primarySource] = $result;
    }
}
