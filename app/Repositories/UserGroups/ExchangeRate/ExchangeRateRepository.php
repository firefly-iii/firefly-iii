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

use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    use UserGroupTrait;

    #[\Override]
    public function getRates(TransactionCurrency $from, TransactionCurrency $to): Collection
    {
        // orderBy('date', 'DESC')->toRawSql();
        return
            $this->userGroup->currencyExchangeRates()
                ->where(function (Builder $q1) use ($from, $to): void {
                    $q1->where(function (Builder $q) use ($from, $to): void {
                        $q->where('from_currency_id', $from->id)
                            ->where('to_currency_id', $to->id)
                        ;
                    })->orWhere(function (Builder $q) use ($from, $to): void {
                        $q->where('from_currency_id', $to->id)
                            ->where('to_currency_id', $from->id)
                        ;
                    });
                })
                ->orderBy('date', 'DESC')
                ->get(['currency_exchange_rates.*'])
        ;

    }

    #[\Override]
    public function getSpecificRateOnDate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): ?CurrencyExchangeRate
    {
        return
            $this->userGroup->currencyExchangeRates()
                ->where('from_currency_id', $from->id)
                ->where('to_currency_id', $to->id)
                ->where('date', $date->format('Y-m-d'))
                ->first()
        ;
    }

    #[\Override]
    public function deleteRate(CurrencyExchangeRate $rate): void
    {
        $this->userGroup->currencyExchangeRates()->where('id', $rate->id)->delete();
    }

    #[\Override]
    public function updateExchangeRate(CurrencyExchangeRate $object, string $rate, ?Carbon $date = null): CurrencyExchangeRate
    {
        $object->rate = $rate;
        if (null !== $date) {
            $object->date = $date;
        }
        $object->save();

        return $object;
    }

    #[\Override]
    public function storeExchangeRate(TransactionCurrency $from, TransactionCurrency $to, string $rate, Carbon $date): CurrencyExchangeRate
    {
        $object                   = new CurrencyExchangeRate();
        $object->user_id          = auth()->user()->id;
        $object->user_group_id    = $this->userGroup->id;
        $object->from_currency_id = $from->id;
        $object->to_currency_id   = $to->id;
        $object->rate             = $rate;
        $object->date             = $date;
        $object->date_tz          = $date->format('e');
        $object->save();

        return $object;
    }

    /**
     * @return Collection
     */
    #[\Override] public function getAll(): Collection
    {
        return $this->userGroup->currencyExchangeRates()->get();
    }
}
