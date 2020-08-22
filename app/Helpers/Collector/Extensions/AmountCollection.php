<?php

/**
 * AmountCollection.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Collector\Extensions;

use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Trait AmountCollection
 */
trait AmountCollection
{

    /**
     * Get transactions with a specific amount.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountIs(string $amount): GroupCollectorInterface
    {
        $this->query->where(
            static function (EloquentBuilder $q) use ($amount) {
                $q->where('source.amount', app('steam')->negative($amount));
            }
        );

        return $this;
    }

    /**
     * Get transactions where the amount is less than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountLess(string $amount): GroupCollectorInterface
    {
        $this->query->where(
            function (EloquentBuilder $q) use ($amount) {
                $q->where('destination.amount', '<=', app('steam')->positive($amount));
            }
        );

        return $this;
    }

    /**
     * Get transactions where the amount is more than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountMore(string $amount): GroupCollectorInterface
    {
        $this->query->where(
            function (EloquentBuilder $q) use ($amount) {
                $q->where('destination.amount', '>=', app('steam')->positive($amount));
            }
        );

        return $this;
    }
}
