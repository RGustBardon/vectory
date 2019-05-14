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

namespace Vectory\Services;

use Vectory\Factories\VectorDefinitionFactory;
use Vectory\ValueObjects\VectorDefinitionInterface;

/* final */ class VectorDefinitionGenerator implements VectorDefinitionGeneratorInterface
{
    /**
     * @var VectorDefinitionFactory
     */
    private $vectorDefinitionFactory;

    public function __construct(
        VectorDefinitionFactory $vectorDefinitionFactory
    ) {
        $this->vectorDefinitionFactory = $vectorDefinitionFactory;
    }

    public function generate(): \Generator
    {
        foreach ([false, true] as $nullable) {
            yield $this->vectorDefinitionFactory->create(
                VectorDefinitionInterface::IMPLEMENTATION_STRING,
                null,
                $nullable,
                null,
                VectorDefinitionInterface::TYPE_BOOLEAN
            );

            foreach ([false, true] as $signed) {
                for ($bytesPerElement = 1; $bytesPerElement <= 7; ++$bytesPerElement) {
                    yield $this->vectorDefinitionFactory->create(
                        VectorDefinitionInterface::IMPLEMENTATION_STRING,
                        $bytesPerElement,
                        $nullable,
                        $signed,
                        VectorDefinitionInterface::TYPE_INTEGER
                    );
                }
            }

            yield $this->vectorDefinitionFactory->create(
                VectorDefinitionInterface::IMPLEMENTATION_STRING,
                8,
                $nullable,
                true,
                VectorDefinitionInterface::TYPE_INTEGER
            );

            foreach ([1, null] as $bytesPerElement) {
                yield $this->vectorDefinitionFactory->create(
                    VectorDefinitionInterface::IMPLEMENTATION_STRING,
                    $bytesPerElement,
                    $nullable,
                    null,
                    VectorDefinitionInterface::TYPE_STRING
                );
            }

            foreach ([
                VectorDefinitionInterface::IMPLEMENTATION_ARRAY,
                VectorDefinitionInterface::IMPLEMENTATION_DS_DEQUE,
                VectorDefinitionInterface::IMPLEMENTATION_DS_VECTOR,
                VectorDefinitionInterface::IMPLEMENTATION_SPL_FIXED_ARRAY,
            ] as $implementationId) {
                yield $this->vectorDefinitionFactory->create(
                    $implementationId,
                    null,
                    true,
                    true,
                    null
                );
            }
        }
    }
}
