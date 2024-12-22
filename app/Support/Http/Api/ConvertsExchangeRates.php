<?php

/*
 * ConvertsExchangeRates.php
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

namespace FireflyIII\Support\Http\Api;

use Carbon\Carbon;
use DateTimeInterface;
use FireflyIII\Models\TransactionCurrency;

/**
 * Trait ConvertsExchangeRates
 */
trait ConvertsExchangeRates
{
    private ?bool $enabled = null;

    /**
     * @deprecated
     */
    public function cerChartSet(array $set): array
    {
        if (null === $this->enabled) {
            $this->getPreference();
        }

        // if not enabled, return the same array but without conversion:
        return $set;
        $this->enabled = false;
        if (false === $this->enabled) {
            $set['converted'] = false;

            return $set;
        }

        $set['converted'] = true;

        /** @var TransactionCurrency $native */
        $native   = app('amount')->getDefaultCurrency();
        $currency = $this->getCurrency((int) $set['currency_id']);
        if ($native->id === $currency->id) {
            $set['native_currency_id']             = (string) $currency->id;
            $set['native_currency_code']           = $currency->code;
            $set['native_currency_symbol']         = $currency->symbol;
            $set['native_currency_decimal_places'] = $currency->decimal_places;

            return $set;
        }
        foreach ($set['entries'] as $date => $entry) {
            $carbon = Carbon::createFromFormat(DateTimeInterface::ATOM, $date);
            $rate   = $this->getRate($currency, $native, $carbon);
            $rate   = '0' === $rate ? '1' : $rate;
            app('log')->debug(sprintf('bcmul("%s", "%s")', (string) $entry, $rate));
            $set['entries'][$date] = (float) bcmul((string) $entry, $rate);
        }

        return $set;
    }

    /**
     * @deprecated
     */
    private function getPreference(): void
    {
        $this->enabled = config('cer.currency_conversion');
    }

    /**
     * @deprecated
     */
    private function getCurrency(int $currencyId): TransactionCurrency
    {
        $result = TransactionCurrency::find($currencyId);
        if (null === $result) {
            return app('amount')->getDefaultCurrency();
        }

        return $result;
    }

    /**
     * For a sum of entries, get the exchange rate to the native currency of
     * the user.
     *
     * @deprecated
     */
    public function cerSum(array $entries): array
    {
        if (null === $this->enabled) {
            $this->getPreference();
        }

        // if false, return the same array without conversion info
        if (false === $this->enabled) {
            $return = [];

            /** @var array $entry */
            foreach ($entries as $entry) {
                $entry['converted'] = false;
                $return[]           = $entry;
            }

            return $return;
        }

        /** @var TransactionCurrency $native */
        $native = app('amount')->getDefaultCurrency();
        $return = [];

        /** @var array $entry */
        foreach ($entries as $entry) {
            $currency = $this->getCurrency((int) $entry['id']);
            if ($currency->id !== $native->id) {
                $amount                                  = $this->convertAmount($entry['sum'], $currency, $native);
                $entry['converted']                      = true;
                $entry['native_sum']                     = $amount;
                $entry['native_currency_id']             = (string) $native->id;
                $entry['native_currency_name']           = $native->name;
                $entry['native_currency_symbol']         = $native->symbol;
                $entry['native_currency_code']           = $native->code;
                $entry['native_currency_decimal_places'] = $native->decimal_places;
            }
            if ($currency->id === $native->id) {
                $entry['converted']                      = false;
                $entry['native_sum']                     = $entry['sum'];
                $entry['native_currency_id']             = (string) $native->id;
                $entry['native_currency_name']           = $native->name;
                $entry['native_currency_symbol']         = $native->symbol;
                $entry['native_currency_code']           = $native->code;
                $entry['native_currency_decimal_places'] = $native->decimal_places;
            }
            $return[] = $entry;
        }

        return $return;
    }

    /**
     * @deprecated
     */
    private function convertAmount(string $amount, TransactionCurrency $from, TransactionCurrency $to, ?Carbon $date = null): string
    {
        app('log')->debug(sprintf('Converting %s from %s to %s', $amount, $from->code, $to->code));
        $date ??= today(config('app.timezone'));
        $rate = $this->getRate($from, $to, $date);

        return bcmul($amount, $rate);
    }
}
