<?php

/*
 * AutoSum.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Response\Sum;

use Closure;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class AutoSum
 *
 * @deprecated
 */
class AutoSum
{
    /**
     * @throws FireflyException
     */
    public function autoSum(Collection $objects, Closure $getCurrency, Closure $getSum): array
    {
        $return = [];

        /** @var Model $object */
        foreach ($objects as $object) {
            /** @var TransactionCurrency $currency */
            $currency                     = $getCurrency($object);

            /** @var string $amount */
            $amount                       = $getSum($object);

            $return[$currency->id] ??= [
                'id'             => (string) $currency->id,
                'name'           => $currency->name,
                'symbol'         => $currency->symbol,
                'code'           => $currency->code,
                'decimal_places' => $currency->decimal_places,
                'sum'            => '0',
            ];

            $return[$currency->id]['sum'] = bcadd($return[$currency->id]['sum'], $amount);
        }

        var_dump(array_values($return));

        throw new FireflyException('Not implemented');
    }
}
