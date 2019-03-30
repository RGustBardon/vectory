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

use Vectory\ValueObjects\VectorDefinitionInterface;

interface VectorDefinitionGeneratorInterface
{
    /**
     * @return \Generator|VectorDefinitionInterface[] a generator of the
     *                                                definitions of all the vectors that are to be built
     */
    public function generate(): \Generator;
}
