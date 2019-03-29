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
    
    private /* ?int */ $bytesPerElement;
    private /* bool */ $nullable;
    private /* ?bool */ $signed;
    private /* string */ $type;
    
    private $defaultValue;
    private /* ?int */ $minimumValue;
    private /* ?int */ $maximumValue;

    public function __construct(
        ?int $bytesPerElement,
        bool $nullable,
        ?bool $signed,
        string $type
    )
    {
        $this->bytesPerElement = $bytesPerElement;
        $this->nullable = $nullable;
        $this->signed = $signed;
        $this->type = $type;
        
        $this->assertValidParameters();
        $this->deriveProperties();
    }
    
    public function getBytesPerElement(): int
    {
        return $this->bytesPerElement;
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
                    \assert($this->bytesPerElement < self::BYTES_PER_ELEMENT_MAXIMUM_SIGNED);
                } else {
                    \assert($this->bytesPerElement < self::BYTES_PER_ELEMENT_MAXIMUM_UNSIGNED);
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
        if ($this->isBoolean()) {
            $this->defaultValue = false;
        } elseif ($this->isInteger()) {
            $this->defaultValue = 0;
            if ($this->isSigned()) {
                $this->maximumValue =
                    \hexdec('7f'.\str_repeat('ff', $this->bytesPerElement - 1));
                $this->minimumValue = -$this->maximumValue - 1;
            } else {
                $this->minimumValue = 0;
                $this->maximumValue = 256 ** $this->bytesPerElement - 1;
                
            }
        } elseif ($this->isString()) {
            $this->defaultValue = \str_repeat("\x0", $this->bytesPerElement);
        }
    }
}