<?php

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

declare(strict_types=1);

namespace FireflyIII\Support\Http\Api;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Facades\Log;

/**
 * Class ExchangeRateConverter
 */
class ExchangeRateConverter
{
    // use ConvertsExchangeRates;
    private int   $queryCount = 0;
    private array $prepared   = [];

    /**
     * @throws FireflyException
     */
    public function convert(TransactionCurrency $from, TransactionCurrency $to, Carbon $date, string $amount): string
    {
        $rate = $this->getCurrencyRate($from, $to, $date);

        return bcmul($amount, $rate);
    }

    public function prepare(TransactionCurrency $from, TransactionCurrency $to, Carbon $start, Carbon $end): void
    {
        $start->startOfDay();
        $end->endOfDay();
        Log::debug(sprintf('Preparing for %s to %s between %s and %s', $from->code, $to->code, $start->format('Y-m-d'), $end->format('Y-m-d')));
        $set      = auth()->user()
            ->currencyExchangeRates()
            ->where('from_currency_id', $from->id)
            ->where('to_currency_id', $to->id)
            ->where('date', '<=', $end->format('Y-m-d'))
            ->where('date', '>=', $start->format('Y-m-d'))
            ->orderBy('date', 'DESC')->get()
        ;
        $fallback = $this->getRate($from, $to, $start);
        ++$this->queryCount;
        if (0 === $set->count()) {
            Log::debug('No rates found in this period.');

            return;
        }
        /*
         * Je moet een fallback rate hebben van voor de periode zodat je die altijd kan gebruiken.
         * Dus de laatste met de meest recente datum voor je periode.
         * Dan moet je gaan loopen per dag, en als er iets in $temp zit toevallig gebruik je die.
         */
        $temp  = [];
        $count = 0;
        foreach ($set as $rate) {
            $date        = $rate->date->format('Y-m-d');
            $temp[$date] ??= [
                $from->id => [
                    $to->id => $rate->rate,
                ],
            ];
            ++$count;
        }
        Log::debug(sprintf('Found %d rates in this period.', $count));
        $currentStart = clone $start;
        while ($currentStart->lte($end)) {
            $currentDate                  = $currentStart->format('Y-m-d');
            $this->prepared[$currentDate] ??= [];
            $fallback                     = $temp[$currentDate][$from->id][$to->id] ?? $fallback;
            if (0 === count($this->prepared[$currentDate]) && 0 !== bccomp('0', $fallback)) {
                // fill from temp or fallback or from temp (see before)
                $this->prepared[$currentDate][$from->id][$to->id] = $fallback;
            }
            $currentStart->addDay();
        }
    }

    /**
     * @throws FireflyException
     */
    public function getCurrencyRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        $rate = $this->getRate($from, $to, $date);

        return '0' === $rate ? '1' : $rate;
    }

    public function summarize(): void
    {
        Log::info(sprintf('ExchangeRateConverter ran %d queries.', $this->queryCount));
    }

    /**
     * @throws FireflyException
     */
    private function getRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        // first attempt:
        $rate = $this->getFromDB($from->id, $to->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            return $rate;
        }
        // no result. perhaps the other way around?
        $rate = $this->getFromDB($to->id, $from->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            return bcdiv('1', $rate);
        }

        // if nothing in place, fall back on the rate for $from to EUR
        $first  = $this->getEuroRate($from, $date);
        $second = $this->getEuroRate($to, $date);

        // combined (if present), they can be used to calculate the necessary conversion rate.
        if (0 === bccomp('0', $first) || 0 === bccomp('0', $second)) {
            Log::warning(sprintf('$first is "%s" and $second is "%s"', $first, $second));

            return '0';
        }

        $second = bcdiv('1', $second);

        return bcmul($first, $second);
    }

    private function getFromDB(int $from, int $to, string $date): ?string
    {
        if ($from === $to) {
            return '1';
        }
        $key = sprintf('cer-%d-%d-%s', $from, $to, $date);

        // perhaps the rate has been cached during this particular run
        $preparedRate = $this->prepared[$date][$from][$to] ?? null;
        if (null !== $preparedRate && '0' !== $preparedRate) {
            return $preparedRate;
        }

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

        /** @var null|CurrencyExchangeRate $result */
        $result = auth()->user()
            ->currencyExchangeRates()
            ->where('from_currency_id', $from)
            ->where('to_currency_id', $to)
            ->where('date', '<=', $date)
            ->orderBy('date', 'DESC')
            ->first()
        ;
        ++$this->queryCount;
        $rate = (string) $result?->rate;

        if ('' === $rate) {
            return null;
        }
        if('0' === $rate) {
            return null;
        }

        $cache->store($rate);

        // if the rate has not been cached during this particular run, save it
        $this->prepared[$date] ??= [
            $from => [
                $to => $rate,
            ],
        ];
        // also save the exchange rate the other way around:
        $this->prepared[$date] ??= [
            $to => [
                $from => bcdiv('1', $rate),
            ],
        ];

        return $rate;
    }

    /**
     * @throws FireflyException
     */
    private function getEuroRate(TransactionCurrency $currency, Carbon $date): string
    {
        $euroId = $this->getEuroId();
        if ($euroId === $currency->id) {
            return '1';
        }
        $rate = $this->getFromDB($currency->id, $euroId, $date->format('Y-m-d'));

        if (null !== $rate) {
            //            app('log')->debug(sprintf('Rate for %s to EUR is %s.', $currency->code, $rate));
            return $rate;
        }
        $rate = $this->getFromDB($euroId, $currency->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            return bcdiv('1', $rate);
            //            app('log')->debug(sprintf('Inverted rate for %s to EUR is %s.', $currency->code, $rate));
            // return $rate;
        }
        // grab backup values from config file:
        $backup = config(sprintf('cer.rates.%s', $currency->code));
        if (null !== $backup) {
            return bcdiv('1', (string) $backup);
            // app('log')->debug(sprintf('Backup rate for %s to EUR is %s.', $currency->code, $backup));
            // return $backup;
        }

        //        app('log')->debug(sprintf('No rate for %s to EUR.', $currency->code));
        return '0';
    }

    /**
     * @throws FireflyException
     */
    private function getEuroId(): int
    {
        $cache = new CacheProperties();
        $cache->addProperty('cer-euro-id');
        if ($cache->has()) {
            return (int) $cache->get();
        }
        $euro = TransactionCurrency::whereCode('EUR')->first();
        ++$this->queryCount;
        if (null === $euro) {
            throw new FireflyException('Cannot find EUR in system, cannot do currency conversion.');
        }
        $cache->store($euro->id);

        return $euro->id;
    }
}
