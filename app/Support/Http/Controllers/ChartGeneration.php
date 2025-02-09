<?php

/**
 * ChartGeneration.php
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

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Trait ChartGeneration
 */
trait ChartGeneration
{
    /**
     * Shows an overview of the account balances for a set of accounts.
     *
     * @throws FireflyException
     */
    protected function accountBalanceChart(Collection $accounts, Carbon $start, Carbon $end): array // chart helper method.
    {
        // chart properties for cache:
        $convertToNative = Amount::convertToNative();
        $cache           = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.account-balance-chart');
        $cache->addProperty($accounts);
        $cache->addProperty($convertToNative);
        if ($cache->has()) {
            return $cache->get();
        }
        Log::debug('Regenerate chart.account.account-balance-chart from scratch.');
        $locale          = app('steam')->getLocale();

        /** @var GeneratorInterface $generator */
        $generator       = app(GeneratorInterface::class);

        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos    = app(AccountRepositoryInterface::class);

        $default         = app('amount')->getNativeCurrency();
        $chartData       = [];

        Log::debug(sprintf('Start of accountBalanceChart(list, %s, %s)', $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));

        /** @var Account $account */
        foreach ($accounts as $account) {
            Log::debug(sprintf('Now at account #%d ("%s)', $account->id, $account->name));
            $currency     = $accountRepos->getAccountCurrency($account) ?? $default;
            $useNative    = $convertToNative && $default->id !== $currency->id;
            $field        = $convertToNative ? 'native_balance' : 'balance';
            $currency     = $useNative ? $default : $currency;
            Log::debug(sprintf('Will use field %s', $field));
            $currentSet   = [
                'label'           => $account->name,
                'currency_symbol' => $currency->symbol,
                'entries'         => [],
            ];

            $currentStart = clone $start;
            $range        = Steam::finalAccountBalanceInRange($account, clone $start, clone $end, $this->convertToNative);
            $previous     = array_values($range)[0];
            Log::debug(sprintf('Start balance for account #%d ("%s) is', $account->id, $account->name), $previous);
            while ($currentStart <= $end) {
                $format                        = $currentStart->format('Y-m-d');
                $label                         = trim($currentStart->isoFormat((string) trans('config.month_and_day_js', [], $locale)));
                $balance                       = $range[$format] ?? $previous;
                $previous                      = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance[$field] ?? '0';
            }
            $chartData[]  = $currentSet;
        }
        $data            = $generator->multiSet($chartData);
        $cache->store($data);

        return $data;
    }
}
