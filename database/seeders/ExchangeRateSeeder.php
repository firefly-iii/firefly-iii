<?php

/*
 * ExchangeRateSeeder.php
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

namespace Database\Seeders;

use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Database\Seeder;

/**
 * Class ExchangeRateSeeder
 */
class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $count  = User::count();
        if (0 === $count) {
            app('log')->debug('Will not seed exchange rates yet.');

            return;
        }
        $users  = User::get();
        $date   = config('cer.date');
        $rates  = config('cer.rates');
        $usable = [];
        $euro   = $this->getCurrency('EUR');
        if (null === $euro) {
            return;
        }
        foreach ($rates as $currencyCode => $rate) {
            // grab opposing currency
            $foreign = $this->getCurrency($currencyCode);
            if (null !== $foreign) {
                // save rate in array:
                $usable[] = [$foreign, $rate];
            }
        }
        unset($rates, $foreign, $rate);

        // for each user, for each rate, check and save
        /** @var User $user */
        foreach ($users as $user) {
            foreach ($usable as $rate) {
                if (!$this->hasRate($user, $euro, $rate[0], $date)) {
                    $this->addRate($user, $euro, $rate[0], $date, $rate[1]);
                }
            }
        }
    }

    private function getCurrency(string $code): ?TransactionCurrency
    {
        return TransactionCurrency::whereNull('deleted_at')->where('code', $code)->first();
    }

    private function hasRate(User $user, TransactionCurrency $from, TransactionCurrency $to, string $date): bool
    {
        return $user->currencyExchangeRates()
            ->where('from_currency_id', $from->id)
            ->where('to_currency_id', $to->id)
            ->where('date', $date)
            ->count() > 0
        ;
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    private function addRate(User $user, TransactionCurrency $from, TransactionCurrency $to, string $date, float $rate): void
    {
        CurrencyExchangeRate::create(
            [
                'user_id'          => $user->id,
                'user_group_id'    => $user->user_group_id ?? null,
                'from_currency_id' => $from->id,
                'to_currency_id'   => $to->id,
                'date'             => $date,
                'rate'             => $rate,
            ]
        );
    }
}
