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

\error_reporting(\E_ALL);

\ini_set('precision', '14');
\ini_set('serialize_precision', '14');

require_once __DIR__.'/../vendor/autoload.php';

(static function (int $seed): void {
    echo 'RNG seed is ', $seed, \PHP_EOL;
    \mt_srand($seed);
})(\random_int(\PHP_INT_MIN, \PHP_INT_MAX));
