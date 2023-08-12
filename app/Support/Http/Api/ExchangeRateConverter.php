<?php

declare(strict_types=1);
/*
 * ExchangeRateConverter.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Support\Http\Api;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\CacheProperties;

/**
 * Class ExchangeRateConverter
 */
class ExchangeRateConverter
{
    //use ConvertsExchangeRates;

    /**
     * @param TransactionCurrency $from
     * @param TransactionCurrency $to
     * @param Carbon              $date
     * @param string              $amount
     *
     * @return string
     * @throws FireflyException
     */
    public function convert(TransactionCurrency $from, TransactionCurrency $to, Carbon $date, string $amount): string
    {
        $rate = $this->getCurrencyRate($from, $to, $date);
        return bcmul($amount, $rate);
    }

    /**
     * @param TransactionCurrency $from
     * @param TransactionCurrency $to
     * @param Carbon              $date
     *
     * @return string
     * @throws FireflyException
     */
    public function getCurrencyRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        $rate = $this->getRate($from, $to, $date);
        return '0' === $rate ? '1' : $rate;
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
            $rate = $cache->get();
            if ('' === $rate) {
                return null;
            }
            return $rate;
        }
        app('log')->debug(sprintf('Going to get rate #%d->#%d (%s) from DB.', $from, $to, $date));

        /** @var CurrencyExchangeRate $result */
        $result = auth()->user()
                        ->currencyExchangeRates()
                        ->where('from_currency_id', $from)
                        ->where('to_currency_id', $to)
                        ->where('date', '<=', $date)
                        ->orderBy('date', 'DESC')
                        ->first();
        $rate   = (string)$result?->rate;
        $cache->store($rate);
        if ('' === $rate) {
            return null;
        }
        return $rate;
    }


    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $date
     *
     * @return string
     * @throws FireflyException
     *
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
            return bcdiv('1', $rate);
            //            app('log')->debug(sprintf('Inverted rate for %s to EUR is %s.', $currency->code, $rate));
            //return $rate;
        }
        // grab backup values from config file:
        $backup = config(sprintf('cer.rates.%s', $currency->code));
        if (null !== $backup) {
            return bcdiv('1', (string)$backup);
            // app('log')->debug(sprintf('Backup rate for %s to EUR is %s.', $currency->code, $backup));
            //return $backup;
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
            return (int)$cache->get();
        }
        $euro = TransactionCurrency::whereCode('EUR')->first();
        if (null === $euro) {
            throw new FireflyException('Cannot find EUR in system, cannot do currency conversion.');
        }
        $cache->store((int)$euro->id);
        return (int)$euro->id;
    }
}
