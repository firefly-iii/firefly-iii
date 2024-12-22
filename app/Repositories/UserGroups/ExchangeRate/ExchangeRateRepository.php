<?php
/*
 * ExchangeRateRepository.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\UserGroups\ExchangeRate;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    use UserGroupTrait;


    #[\Override] public function getRates(TransactionCurrency $from, TransactionCurrency $to): Collection
    {
        return
            $this->userGroup->currencyExchangeRates()
                            ->where(function (Builder $q) use ($from, $to) {
                                $q->where('from_currency_id', $from->id)
                                  ->orWhere('to_currency_id', $to->id);
                            })
                            ->orWhere(function (Builder $q) use ($from, $to) {
                                $q->where('from_currency_id', $to->id)
                                  ->orWhere('to_currency_id', $from->id);
                            })
                            ->orderBy('date', 'DESC')->get();

    }
}
