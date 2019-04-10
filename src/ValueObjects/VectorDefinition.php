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

namespace Vectory\ValueObjects;

/* final */ class VectorDefinition implements VectorDefinitionInterface
{
    private const BYTES_PER_ELEMENT_MAXIMUM_SIGNED = 8;
    private const BYTES_PER_ELEMENT_MAXIMUM_UNSIGNED = 7;

    private const NAME_TOKEN_NULLABLE = 'Nullable';
    private const NAME_TOKEN_BOOLEAN = 'Bool';
    private const NAME_TOKEN_SIGNED_INTEGER = 'Int';
    private const NAME_TOKEN_UNSIGNED_INTEGER = 'Uint';
    private const NAME_TOKEN_STRING = 'Char';
    private const NAME_TOKEN_SUFFIX = 'Vector';

    private /* ?int */ $bytesPerElement;
    private /* bool */ $nullable;
    private /* ?bool */ $signed;
    private /* string */ $type;

    private $defaultValue;
    private /* bool */ $bitArithmetic;
    private /* ?int */ $minimumValue;
    private /* ?int */ $maximumValue;
    private /* string */ $className;
    private /* string */ $fullyQualifiedClassName;

    public function __construct(
        ?int $bytesPerElement,
        bool $nullable,
        ?bool $signed,
        string $type
    ) {
        $this->bytesPerElement = $bytesPerElement;
        $this->nullable = $nullable;
        $this->signed = $signed;
        $this->type = $type;

        $this->assertValidParameters();
        $this->deriveProperties();
    }

    public function export(): array
    {
        $properties = [];
        $class = new \ReflectionClass($this);
        foreach ($class->getProperties() as $property) {
            $property->setAccessible(true);
            $properties[$property->getName()] = $property->getValue($this);
            $property->setAccessible(false);
        }

        return $properties;
    }

    public function getBytesPerElement(): int
    {
        return $this->bytesPerElement;
    }
    
    public function hasBitArithmetic(): bool
    {
        return $this->bitArithmetic;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isSigned(): bool
    {
        return $this->signed;
    }

    public function isBoolean(): bool
    {
        return self::TYPE_BOOLEAN === $this->type;
    }

    public function isInteger(): bool
    {
        return self::TYPE_INTEGER === $this->type;
    }

    public function isString(): bool
    {
        return self::TYPE_STRING === $this->type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getMinimumValue(): int
    {
        return $this->minimumValue;
    }

    public function getMaximumValue(): int
    {
        return $this->maximumValue;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
    
    public function getFullyQualifiedClassName(): string
    {
        return $this->fullyQualifiedClassName;
    }

    private function assertValidParameters(): void
    {
        switch ($this->type) {
            case self::TYPE_BOOLEAN:
                \assert(null === $this->bytesPerElement);
                \assert(null === $this->signed);

                break;
            case self::TYPE_INTEGER:
                \assert($this->bytesPerElement > 0);
                \assert(\is_bool($this->signed));
                if ($this->signed) {
                    \assert($this->bytesPerElement <= self::BYTES_PER_ELEMENT_MAXIMUM_SIGNED);
                } else {
                    \assert($this->bytesPerElement <= self::BYTES_PER_ELEMENT_MAXIMUM_UNSIGNED);
                }

                break;
            case self::TYPE_STRING:
                \assert($this->bytesPerElement > 0);
                \assert(null === $this->signed);

                break;
            default:
                throw new \DomainException('Invalid element type: '.$this->type);
        }
    }

    private function deriveProperties(): void
    {
        $this->bitArithmetic = $this->nullable || $this->isBoolean();
        $this->className = '';
        if ($this->nullable) {
            $this->className .= self::NAME_TOKEN_NULLABLE;
        }

        if ($this->isBoolean()) {
            $this->defaultValue = false;
            $this->className .= self::NAME_TOKEN_BOOLEAN;
        } elseif ($this->isInteger()) {
            $this->defaultValue = 0;
            if ($this->isSigned()) {
                $this->maximumValue =
                    \hexdec('7f'.\str_repeat('ff', $this->bytesPerElement - 1));
                $this->minimumValue = -$this->maximumValue - 1;
                $this->className .= self::NAME_TOKEN_SIGNED_INTEGER;
            } else {
                $this->minimumValue = 0;
                $this->maximumValue = 256 ** $this->bytesPerElement - 1;
                $this->className .= self::NAME_TOKEN_UNSIGNED_INTEGER;
            }
            $this->className .= 8 * $this->bytesPerElement;
        } elseif ($this->isString()) {
            $this->defaultValue = \str_repeat("\x0", $this->bytesPerElement);
            $this->className .= self::NAME_TOKEN_STRING.$this->bytesPerElement;
        }

        $this->className .= self::NAME_TOKEN_SUFFIX;
        $this->fullyQualifiedClassName = '\\Vectory\\'.$this->className;
    }
}
