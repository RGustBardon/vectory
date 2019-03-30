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

namespace Vectory\Factories;

use Vectory\ValueObjects\VectorDefinition;
use Vectory\ValueObjects\VectorDefinitionInterface;

/* final */ class VectorDefinitionFactory
{
    public function create(
        ?int $bytesPerElement,
        bool $nullable,
        ?bool $signed,
        string $type
    ): VectorDefinitionInterface {
        return new VectorDefinition($bytesPerElement, $nullable, $signed, $type);
    }
}
