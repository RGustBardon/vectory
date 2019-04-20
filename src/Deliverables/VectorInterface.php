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

interface VectorInterface extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Serializable
{
    /**
     * Deletes a certain number of elements.
     *
     * Subsequent elements are shifted left by the number of elements deleted.
     *
     * @param int $firstIndex the index of the first element that is to be deleted.
     *                        If negative, `-1` represents the last element, `-2` the element preceding it, etc.
     *                        If the index is negative and its absolute value is greater than the current number
     *                        of elements, the index of the first element will be `0`, and `$howMany` will be
     *                        decreased by their difference
     * @param int $howMany    the number of elements to be deleted
     */
    public function delete(int $firstIndex = -1, int $howMany = \PHP_INT_MAX): void;
}
