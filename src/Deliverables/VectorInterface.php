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

interface VectorInterface extends
    \ArrayAccess,
    \Countable,
    \IteratorAggregate,
    \JsonSerializable,
    \Serializable
{
}
