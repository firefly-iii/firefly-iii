<?php

/**
 * CurrencyRepository.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Repositories\Currency;

use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class CurrencyRepository.
 */
class CurrencyRepository implements CurrencyRepositoryInterface
{
    private User $user;

    /**
     * Find by currency code, return NULL if unfound.
     */
    public function findByCode(string $currencyCode): ?TransactionCurrency
    {
        return TransactionCurrency::where('code', $currencyCode)->first();
    }

    /**
     * Returns the complete set of transactions but needs
     * no user object.
     */
    public function getCompleteSet(): Collection
    {
        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * Get currency exchange rate.
     */
    public function getExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): ?CurrencyExchangeRate
    {
        if ($fromCurrency->id === $toCurrency->id) {
            $rate       = new CurrencyExchangeRate();
            $rate->rate = '1';
            $rate->id   = 0;

            return $rate;
        }

        /** @var null|CurrencyExchangeRate $rate */
        $rate = $this->user->currencyExchangeRates()
            ->where('from_currency_id', $fromCurrency->id)
            ->where('to_currency_id', $toCurrency->id)
            ->where('date', $date->format('Y-m-d'))->first()
        ;
        if (null !== $rate) {
            app('log')->debug(sprintf('Found cached exchange rate in database for %s to %s on %s', $fromCurrency->code, $toCurrency->code, $date->format('Y-m-d')));

            return $rate;
        }

        return null;
    }

    /**
     * TODO must be a factory
     */
    public function setExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date, float $rate): CurrencyExchangeRate
    {
        return CurrencyExchangeRate::create(
            [
                'user_id'             => $this->user->id,
                'from_currency_id'    => $fromCurrency->id,
                'to_currency_id'      => $toCurrency->id,
                'date'                => $date,
                'date_tz'             => $date->format('e'),
                'rate'                => $rate,
            ]
        );
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    #[\Override] public function find(int $currencyId): ?TransactionCurrency
    {
        return TransactionCurrency::find($currencyId);
    }
}
