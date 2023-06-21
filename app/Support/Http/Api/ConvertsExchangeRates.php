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
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Facades\Log;

/**
 * Trait ConvertsExchangeRates
 */
trait ConvertsExchangeRates
{
    private ?bool $enabled = null;

    /**
     * @param array $set
     * @return array
     */
    public function cerChartSet(array $set): array
    {
        if (null === $this->enabled) {
            $this->getPreference();
        }

        // if not enabled, return the same array but without conversion:
        if (false === $this->enabled) {
            $set['converted'] = false;
            return $set;
        }

        $set['converted'] = true;
        /** @var TransactionCurrency $native */
        $native   = app('amount')->getDefaultCurrency();
        $currency = $this->getCurrency((int)$set['currency_id']);
        if ($native->id === $currency->id) {
            $set['native_id']             = (string)$currency->id;
            $set['native_code']           = $currency->code;
            $set['native_symbol']         = $currency->symbol;
            $set['native_decimal_places'] = $currency->decimal_places;
            return $set;
        }
        foreach ($set['entries'] as $date => $entry) {
            $carbon = Carbon::createFromFormat(DateTimeInterface::ATOM, $date);
            $rate   = $this->getRate($currency, $native, $carbon);
            $rate   = '0' === $rate ? '1' : $rate;
            Log::debug(sprintf('bcmul("%s", "%s")', (string)$entry, $rate));
            $set['entries'][$date] = (float)bcmul((string)$entry, $rate);
        }
        return $set;
    }

    /**
     * @return void
     */
    private function getPreference(): void
    {
        $this->enabled = true;
    }

    /**
     * @param int $currencyId
     * @return TransactionCurrency
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
     * @param TransactionCurrency $from
     * @param TransactionCurrency $to
     * @param Carbon              $date
     * @return string
     */
    private function getRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        Log::debug(sprintf('getRate(%s, %s, "%s")', $from->code, $to->code, $date->format('Y-m-d')));
        /** @var CurrencyExchangeRate $result */
        $result = auth()->user()
                        ->currencyExchangeRates()
                        ->where('from_currency_id', $from->id)
                        ->where('to_currency_id', $to->id)
                        ->where('date', '<=', $date->format('Y-m-d'))
                        ->orderBy('date', 'DESC')
                        ->first();
        if (null !== $result) {
            $rate = (string)$result->rate;
            Log::debug(sprintf('Rate is %s', $rate));
            return $rate;
        }
        // no result. perhaps the other way around?
        /** @var CurrencyExchangeRate $result */
        $result = auth()->user()
                        ->currencyExchangeRates()
                        ->where('from_currency_id', $to->id)
                        ->where('to_currency_id', $from->id)
                        ->where('date', '<=', $date->format('Y-m-d'))
                        ->orderBy('date', 'DESC')
                        ->first();
        if (null !== $result) {
            $rate = bcdiv('1', (string)$result->rate);
            Log::debug(sprintf('Reversed rate is %s', $rate));
            return $rate;
        }
        // try euro rates
        $result1 = $this->getEuroRate($from, $date);
        if ('0' === $result1) {
            Log::debug(sprintf('No exchange rate between EUR and %s', $from->code));
            return '0';
        }
        $result2 = $this->getEuroRate($to, $date);
        if ('0' === $result2) {
            Log::debug(sprintf('No exchange rate between EUR and %s', $to->code));
            return '0';
        }
        // still need to inverse rate 2:
        $result2 = bcdiv('1', $result2);
        $rate    = bcmul($result1, $result2);
        Log::debug(sprintf('Rate %s to EUR is %s', $from->code, $result1));
        Log::debug(sprintf('Rate EUR to %s is %s', $to->code, $result2));
        Log::debug(sprintf('Rate for %s to %s is %s', $from->code, $to->code, $rate));
        return $rate;
    }

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $date
     * @return string
     */
    private function getEuroRate(TransactionCurrency $currency, Carbon $date): string
    {
        Log::debug(sprintf('Find rate for %s to Euro', $currency->code));
        $euro = TransactionCurrency::whereCode('EUR')->first();
        if (null === $euro) {
            app('log')->warning('Cannot do indirect conversion without EUR.');
            return '0';
        }

        // try one way:
        /** @var CurrencyExchangeRate $result */
        $result = auth()->user()
                        ->currencyExchangeRates()
                        ->where('from_currency_id', $currency->id)
                        ->where('to_currency_id', $euro->id)
                        ->where('date', '<=', $date->format('Y-m-d'))
                        ->orderBy('date', 'DESC')
                        ->first();
        if (null !== $result) {
            $rate = (string)$result->rate;
            Log::debug(sprintf('Rate for %s to EUR is %s.', $currency->code, $rate));
            return $rate;
        }
        // try the other way around and inverse it.
        /** @var CurrencyExchangeRate $result */
        $result = auth()->user()
                        ->currencyExchangeRates()
                        ->where('from_currency_id', $euro->id)
                        ->where('to_currency_id', $currency->id)
                        ->where('date', '<=', $date->format('Y-m-d'))
                        ->orderBy('date', 'DESC')
                        ->first();
        if (null !== $result) {
            $rate = bcdiv('1', (string)$result->rate);
            Log::debug(sprintf('Inverted rate for %s to EUR is %s.', $currency->code, $rate));
            return $rate;
        }

        Log::debug(sprintf('No rate for %s to EUR.', $currency->code));
        return '0';
    }

    /**
     * For a sum of entries, get the exchange rate to the native currency of
     * the user.
     *
     * @param array $entries
     * @return array
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
            $currency = $this->getCurrency((int)$entry['id']);
            if ($currency->id !== $native->id) {
                $amount                         = $this->convertAmount($entry['sum'], $currency, $native);
                $entry['converted']             = true;
                $entry['native_sum']            = $amount;
                $entry['native_id']             = (string)$native->id;
                $entry['native_name']           = $native->name;
                $entry['native_symbol']         = $native->symbol;
                $entry['native_code']           = $native->code;
                $entry['native_decimal_places'] = $native->decimal_places;
            }
            if ($currency->id === $native->id) {
                $entry['converted']             = false;
                $entry['native_sum']            = $entry['sum'];
                $entry['native_id']             = (string)$native->id;
                $entry['native_name']           = $native->name;
                $entry['native_symbol']         = $native->symbol;
                $entry['native_code']           = $native->code;
                $entry['native_decimal_places'] = $native->decimal_places;
            }
            $return[] = $entry;
        }
        return $return;
    }

    private function convertAmount(string $amount, TransactionCurrency $from, TransactionCurrency $to, ?Carbon $date = null): string
    {
        Log::debug(sprintf('Converting %s from %s to %s', $amount, $from->code, $to->code));
        $date = $date ?? today(config('app.timezone'));
        $rate = $this->getRate($from, $to, $date);

        return bcmul($amount, $rate);
    }
}
