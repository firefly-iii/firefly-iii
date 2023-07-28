<?php

/*
 * DownloadExchangeRates.php
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

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class DownloadExchangeRates
 */
class DownloadExchangeRates implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private array                       $active;
    private Carbon                      $date;
    private CurrencyRepositoryInterface $repository;
    private Collection                  $users;

    /**
     * Create a new job instance.
     *
     *
     * @param Carbon|null $date
     */
    public function __construct(?Carbon $date)
    {
        $this->active     = [];
        $this->repository = app(CurrencyRepositoryInterface::class);

        // get all users:
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);
        $this->users    = $userRepository->all();

        if (null !== $date) {
            $newDate = clone $date;
            $newDate->startOfDay();
            $this->date = $newDate;
            Log::debug(sprintf('Created new DownloadExchangeRates("%s")', $this->date->format('Y-m-d')));
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug('Now in handle()');
        $currencies = $this->repository->get();

        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            $this->downloadRates($currency);
        }
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return void
     * @throws GuzzleException
     */
    private function downloadRates(TransactionCurrency $currency): void
    {
        Log::debug(sprintf('Now downloading new exchange rates for currency %s.', $currency->code));
        $base   = sprintf('%s/%s/%s', (string)config('cer.url'), $this->date->year, $this->date->isoWeek);
        $client = new Client();
        $url    = sprintf('%s/%s.json', $base, $currency->code);
        try {
            $res = $client->get($url);
        } catch (RequestException $e) {
            app('log')->warning(sprintf('Trying to grab "%s" resulted in error "%d".', $url, $e->getMessage()));
            return;
        }
        $statusCode = $res->getStatusCode();
        if (200 !== $statusCode) {
            app('log')->warning(sprintf('Trying to grab "%s" resulted in status code %d.', $url, $statusCode));
            return;
        }
        $body = (string)$res->getBody();
        $json = json_decode($body, true);
        if (false === $json || null === $json) {
            app('log')->warning(sprintf('Trying to grab "%s" resulted in bad JSON.', $url));
            return;
        }
        $date = Carbon::createFromFormat('Y-m-d', $json['date'], config('app.timezone'));
        $this->saveRates($currency, $date, $json['rates']);
    }

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $date
     * @param array               $rates
     *
     * @return void
     */
    private function saveRates(TransactionCurrency $currency, Carbon $date, array $rates): void
    {
        foreach ($rates as $code => $rate) {
            $to = $this->getCurrency($code);
            if (null === $to) {
                Log::debug(sprintf('Currency %s is not in use, do not save rate.', $code));
                continue;
            }
            Log::debug(sprintf('Currency %s is in use.', $code));
            $this->saveRate($currency, $to, $date, $rate);
        }
    }

    /**
     * @param string $code
     *
     * @return TransactionCurrency|null
     */
    private function getCurrency(string $code): ?TransactionCurrency
    {
        // if we have it already, don't bother searching for it again.
        if (array_key_exists($code, $this->active)) {
            Log::debug(sprintf('Already know what the result is of searching for %s', $code));
            return $this->active[$code];
        }
        // find it in the database.
        $currency = $this->repository->findByCode($code);
        if (null === $currency) {
            Log::debug(sprintf('Did not find currency %s.', $code));
            $this->active[$code] = null;
            return null;
        }
        if (false === $currency->enabled) {
            Log::debug(sprintf('Currency %s is not enabled.', $code));
            $this->active[$code] = null;
            return null;
        }
        Log::debug(sprintf('Currency %s is enabled.', $code));
        $this->active[$code] = $currency;

        return $currency;
    }

    /**
     * @param TransactionCurrency $from
     * @param TransactionCurrency $to
     * @param Carbon              $date
     * @param float               $rate
     *
     * @return void
     */
    private function saveRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date, float $rate): void
    {
        foreach ($this->users as $user) {
            $this->repository->setUser($user);
            $existing = $this->repository->getExchangeRate($from, $to, $date);
            if (null === $existing) {
                Log::debug(sprintf('Saved rate from %s to %s for user #%d.', $from->code, $to->code, $user->id));
                $this->repository->setExchangeRate($from, $to, $date, $rate);
            }
        }
    }

    /**
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $newDate = clone $date;
        $newDate->startOfDay();
        $this->date = $newDate;
    }
}
