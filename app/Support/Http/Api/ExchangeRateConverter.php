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
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class ExchangeRateConverter
 */
class ExchangeRateConverter
{
    // use ConvertsExchangeRates;
    private bool  $ignoreSettings = false;
    private array $prepared       = [];
    private int   $queryCount     = 0;

    private UserGroup $userGroup;

    public function __construct()
    {
        if (auth()->check()) {
            $this->userGroup = auth()->user()->userGroup;
        }
    }

    /**
     * @throws FireflyException
     */
    public function convert(TransactionCurrency $from, TransactionCurrency $to, Carbon $date, string $amount): string
    {
        if (false === $this->enabled()) {
            Log::debug('ExchangeRateConverter: disabled, return amount as is.');

            return $amount;
        }
        $rate = $this->getCurrencyRate($from, $to, $date);

        return Steam::bcround(bcmul($amount, $rate), $to->decimal_places);
    }

    public function enabled(): bool
    {
        return false !== config('cer.enabled') || true === $this->ignoreSettings;
    }

    /**
     * @throws FireflyException
     */
    public function getCurrencyRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        if (false === $this->enabled()) {
            // Log::debug('ExchangeRateConverter: disabled, return "1".');

            return '1';
        }
        if ($from->id === $to->id) {
//            Log::debug('ExchangeRateConverter: From and to are the same, return "1".');

            return '1';
        }
        $rate = $this->getRate($from, $to, $date);

        return '0' === $rate ? '1' : $rate;
    }

    /**
     * @throws FireflyException
     */
    private function getRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        $key    = $this->getCacheKey($from, $to, $date);
        $res    = Cache::get($key, null);

        // find in cache
        if (null !== $res) {
            Log::debug(sprintf('ExchangeRateConverter: Return cached rate from %s to %s on %s.', $from->code, $to->code, $date->format('Y-m-d')));

            return $res;
        }

        // find in database
        $rate   = $this->getFromDB($from->id, $to->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            Cache::forever($key, $rate);
            Log::debug(sprintf('ExchangeRateConverter: Return DB rate from %s to %s on %s.', $from->code, $to->code, $date->format('Y-m-d')));

            return $rate;
        }

        // find reverse in database
        $rate   = $this->getFromDB($to->id, $from->id, $date->format('Y-m-d'));
        if (null !== $rate) {
            $rate = bcdiv('1', $rate);
            Cache::forever($key, $rate);
            Log::debug(sprintf('ExchangeRateConverter: Return inverse DB rate from %s to %s on %s.', $from->code, $to->code, $date->format('Y-m-d')));

            return $rate;
        }

        // fallback scenario.
        $first  = $this->getEuroRate($from, $date);
        $second = $this->getEuroRate($to, $date);

        // combined (if present), they can be used to calculate the necessary conversion rate.
        if (0 === bccomp('0', $first) || 0 === bccomp('0', $second)) {
            Log::warning(sprintf('There is not enough information to convert %s to %s on date %s', $from->code, $to->code, $date->format('Y-m-d')));

            return '1';
        }

        $second = bcdiv('1', $second);
        $rate   = bcmul($first, $second);
        Log::debug(sprintf('ExchangeRateConverter: Return DB rate from %s to %s on %s.', $from->code, $to->code, $date->format('Y-m-d')));
        Cache::forever($key, $rate);

        return $rate;
    }

    private function getCacheKey(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        return sprintf('cer-%d-%d-%s', $from->id, $to->id, $date->format('Y-m-d'));
    }

    private function getFromDB(int $from, int $to, string $date): ?string
    {
        if ($from === $to) {
            Log::debug('ExchangeRateConverter: From and to are the same, return "1".');

            return '1';
        }
        $key          = sprintf('cer-%d-%d-%s', $from, $to, $date);

        // perhaps the rate has been cached during this particular run
        $preparedRate = $this->prepared[$date][$from][$to] ?? null;
        if (null !== $preparedRate && 0 !== bccomp('0', $preparedRate)) {
            Log::debug(sprintf('ExchangeRateConverter: Found prepared rate from #%d to #%d on %s.', $from, $to, $date));

            return $preparedRate;
        }

        $cache        = new CacheProperties();
        $cache->addProperty($key);
        if ($cache->has()) {
            $rate = $cache->get();
            if ('' === $rate) {
                return null;
            }
            Log::debug(sprintf('ExchangeRateConverter: Found cached rate from #%d to #%d on %s.', $from, $to, $date));

            return $rate;
        }

        /** @var null|CurrencyExchangeRate $result */
        $result       = $this->userGroup->currencyExchangeRates()
            ->where('from_currency_id', $from)
            ->where('to_currency_id', $to)
            ->where('date', '<=', $date)
            ->orderBy('date', 'DESC')
            ->first()
        ;
        ++$this->queryCount;
        $rate         = (string) $result?->rate;

        if ('' === $rate) {
            app('log')->debug(sprintf('ExchangeRateConverter: Found no rate for #%d->#%d (%s) in the DB.', $from, $to, $date));

            return null;
        }
        if (0 === bccomp('0', $rate)) {
            app('log')->debug(sprintf('ExchangeRateConverter: Found rate for #%d->#%d (%s) in the DB, but it\'s zero.', $from, $to, $date));

            return null;
        }
        app('log')->debug(sprintf('ExchangeRateConverter: Found rate for #%d->#%d (%s) in the DB: %s.', $from, $to, $date, $rate));
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
        $rate   = $this->getFromDB($currency->id, $euroId, $date->format('Y-m-d'));

        if (null !== $rate) {
            //            app('log')->debug(sprintf('Rate for %s to EUR is %s.', $currency->code, $rate));
            return $rate;
        }
        $rate   = $this->getFromDB($euroId, $currency->id, $date->format('Y-m-d'));
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
        Log::debug('getEuroId()');
        $cache = new CacheProperties();
        $cache->addProperty('cer-euro-id');
        if ($cache->has()) {
            return (int) $cache->get();
        }
        $euro  = TransactionCurrency::whereCode('EUR')->first();
        ++$this->queryCount;
        if (null === $euro) {
            throw new FireflyException('Cannot find EUR in system, cannot do currency conversion.');
        }
        $cache->store($euro->id);

        return $euro->id;
    }

    public function setIgnoreSettings(bool $ignoreSettings): void
    {
        $this->ignoreSettings = $ignoreSettings;
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    public function summarize(): void
    {
        if (false === $this->enabled()) {
            return;
        }
        Log::debug(sprintf('ExchangeRateConverter ran %d queries.', $this->queryCount));
    }
}
