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

/*
 * A vector of elements which belong to the same well-defined data domain.
 *
 * In addition to the exceptions thrown by the documented methods,
 * the following exceptions may be thrown:
 * - `\TypeError` (when attempting to use a value other than an integer as an index)
 *   thrown by `offsetGet`, `offsetSet`;
 * - `\OutOfRangeException` (when attempting to use a negative integer as an index)
 *   thrown by `offsetGet`, `offsetSet`;
 * - `\TypeError` (when the type of a value is not the one of the data domain)
 *   thrown by `offsetSet`, `unserialize`;
 * - `\DomainException` (when a value lies outside of the data domain despite its type)
 *   thrown by `offsetSet`, `unserialize`;
 * - `\UnexpectedValueException` (when it is not possible to unserialize a value)
 *   thrown by `unserialize`;
 * - `\UnexpectedValueException` (when an unserialized value has an unexpected structure)
 *   thrown by `unserialize`.
 */
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

    /**
     * Inserts elements at a certain index.
     *
     * Subsequent elements are shifted right by the number of elements inserted.
     *
     * @param iterable $elements   The elements to be inserted. Keys are ignored.
     * @param int      $firstIndex the index that the first newly inserted element is going to have.
     *                             If negative, `-1` represents the last element, `-2` the element preceding it, etc.
     *                             If the index is out of bounds, the vector is padded with the default element to
     *                             include the index
     *
     * @throws \TypeError       if any element that is to be inserted is not of the expected type
     * @throws \DomainException if any element that is to be inserted is of the expected type,
     *                          but does not belong to the data domain of the vector
     */
    public function insert(iterable $elements, int $firstIndex = -1): void;
}
