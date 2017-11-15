<?php
/**
 * TransactionLoader.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Twig\Loader;

use FireflyIII\Support\Twig\Extension\Transaction;
use Twig_RuntimeLoaderInterface;

/**
 * Class TransactionLoader
 *
 * @package FireflyIII\Support\Twig\Extension
 */
class TransactionLoader implements Twig_RuntimeLoaderInterface
{

    /**
     * Creates the runtime implementation of a Twig element (filter/function/test).
     *
     * @param string $class A runtime class
     *
     * @return object|null The runtime instance or null if the loader does not know how to create the runtime for this class
     */
    public function load($class)
    {
        // implement the logic to create an instance of $class
        // and inject its dependencies
        // most of the time, it means using your dependency injection container

        if (Transaction::class === $class) {
            return app(Transaction::class);
        }

        return null;
    }
}
