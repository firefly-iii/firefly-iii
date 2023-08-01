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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\CacheProperties;

/**
 * Trait ConvertsExchangeRates
 */
trait ConvertsExchangeRates
{
    private ?bool $enabled = null;

    /**
     * @param array $set
     *
     * @return array
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
            app('log')->debug(sprintf('bcmul("%s", "%s")', (string)$entry, $rate));
            $set['entries'][$date] = (float)bcmul((string)$entry, $rate);
        }
        return $set;
    }

    /**
     * @return void
     */
    private function getPreference(): void
    {
        $this->enabled = config('cer.currency_conversion');
    }

    /**
     * @param int $currencyId
     *
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
     *
     * @return string
     * @throws FireflyException
     */
    private function getRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        // first attempt:
        $rate = $this->getFromDB((int)$from->id, (int)$to->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            return $rate;
        }
        // no result. perhaps the other way around?
        $rate = $this->getFromDB((int)$to->id, (int)$from->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            return bcdiv('1', $rate);
        }

        // if nothing in place, fall back on the rate for $from to EUR
        $first  = $this->getEuroRate($from, $date);
        $second = $this->getEuroRate($to, $date);

        // combined (if present), they can be used to calculate the necessary conversion rate.
        if ('0' === $first || '0' === $second) {
            return '0';
        }

        $second = bcdiv('1', $second);
        return bcmul($first, $second);
    }

    /**
     * @param int    $from
     * @param int    $to
     * @param string $date
     *
     * @return string|null
     */
    private function getFromDB(int $from, int $to, string $date): ?string
    {
        $key = sprintf('cer-%d-%d-%s', $from, $to, $date);

        $cache = new CacheProperties();
        $cache->addProperty($key);
        if ($cache->has()) {
            return $cache->get();
        }

        /** @var CurrencyExchangeRate $result */
        $result = auth()->user()
                        ->currencyExchangeRates()
                        ->where('from_currency_id', $from)
                        ->where('to_currency_id', $to)
                        ->where('date', '<=', $date)
                        ->orderBy('date', 'DESC')
                        ->first();
        if (null !== $result) {
            $rate = (string)$result->rate;
            $cache->store($rate);
            return $rate;
        }
        return null;
    }

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $date
     *
     * @return string
     * @throws FireflyException
     */
    private function getEuroRate(TransactionCurrency $currency, Carbon $date): string
    {
        $euroId = $this->getEuroId();
        if ($euroId === (int)$currency->id) {
            return '1';
        }
        $rate = $this->getFromDB((int)$currency->id, $euroId, $date->format('Y-m-d'));

        if (null !== $rate) {
            //            app('log')->debug(sprintf('Rate for %s to EUR is %s.', $currency->code, $rate));
            return $rate;
        }
        $rate = $this->getFromDB($euroId, (int)$currency->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            $rate = bcdiv('1', $rate);
            //            app('log')->debug(sprintf('Inverted rate for %s to EUR is %s.', $currency->code, $rate));
            return $rate;
        }
        // grab backup values from config file:
        $backup = config(sprintf('cer.rates.%s', $currency->code));
        if (null !== $backup) {
            $backup = bcdiv('1', (string)$backup);
            // app('log')->debug(sprintf('Backup rate for %s to EUR is %s.', $currency->code, $backup));
            return $backup;
        }

        //        app('log')->debug(sprintf('No rate for %s to EUR.', $currency->code));
        return '0';
    }

    /**
     * @return int
     * @throws FireflyException
     */
    private function getEuroId(): int
    {
        $cache = new CacheProperties();
        $cache->addProperty('cer-euro-id');
        if ($cache->has()) {
            return $cache->get();
        }
        $euro = TransactionCurrency::whereCode('EUR')->first();
        if (null === $euro) {
            throw new FireflyException('Cannot find EUR in system, cannot do currency conversion.');
        }
        $cache->store((int)$euro->id);
        return (int)$euro->id;
    }

    /**
     * For a sum of entries, get the exchange rate to the native currency of
     * the user.
     *
     * @param array $entries
     *
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

    /**
     * @param string              $amount
     * @param TransactionCurrency $from
     * @param TransactionCurrency $to
     * @param Carbon|null         $date
     *
     * @return string
     */
    private function convertAmount(string $amount, TransactionCurrency $from, TransactionCurrency $to, ?Carbon $date = null): string
    {
        app('log')->debug(sprintf('Converting %s from %s to %s', $amount, $from->code, $to->code));
        $date = $date ?? today(config('app.timezone'));
        $rate = $this->getRate($from, $to, $date);

        return bcmul($amount, $rate);
    }
}
