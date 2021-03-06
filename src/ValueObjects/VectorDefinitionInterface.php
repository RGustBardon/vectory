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

interface VectorDefinitionInterface
{
    public const IMPLEMENTATION_ARRAY = 'array';
    public const IMPLEMENTATION_DS_VECTOR = \Ds\Vector::class;
    public const IMPLEMENTATION_DS_DEQUE = \Ds\Deque::class;
    public const IMPLEMENTATION_SPL_FIXED_ARRAY = \SplFixedArray::class;
    public const IMPLEMENTATION_STRING = 'string';

    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_INTEGER = 'int';
    public const TYPE_STRING = 'string';

    public function export(): array;

    public function getImplementationId(): string;

    public function getBytesPerElement(): ?int;

    public function hasBitArithmetic(): bool;

    public function hasStaticElementLength(): bool;

    public function isNullable(): bool;

    public function isSigned(): bool;

    public function isBoolean(): bool;

    public function isInteger(): bool;

    public function isString(): bool;

    public function getType(): ?string;

    public function getDefaultValue();

    public function getMinimumValue(): ?int;

    public function getMaximumValue(): ?int;

    public function getClassName(): string;

    public function getFullyQualifiedClassName(): ?string;
}
