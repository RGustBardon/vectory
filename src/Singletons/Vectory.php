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

use Vectory\ValueObjects\VectorDefinitionInterface;

/* final */ class Vectory
{
    private static $vectorDefinition;

    public static function __callStatic($name, $arguments)
    {
        return self::$vectorDefinition->{$name}(...$arguments);
    }

    public static function setDefinition(VectorDefinitionInterface $vectorDefinition)
    {
        self::$vectorDefinition = $vectorDefinition;
    }
}
