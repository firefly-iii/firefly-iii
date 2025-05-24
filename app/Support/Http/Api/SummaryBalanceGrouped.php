<?php

/*
 * SummaryBalanceGrouped.php
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

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Facades\Log;

class SummaryBalanceGrouped
{
    private const string SUM                              = 'sum';
    private array                                $amounts = [];
    private array                                $currencies;
    private readonly CurrencyRepositoryInterface $currencyRepository;
    private TransactionCurrency                  $default;
    private array                                $keys;

    public function __construct()
    {
        $this->keys               = [self::SUM];
        $this->currencies         = [];
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
    }

    public function groupData(): array
    {
        Log::debug('Now going to group data.');
        $return      = [];
        foreach ($this->keys as $key) {
            $title    = match ($key) {
                'sum'     => 'balance',
                'expense' => 'spent',
                'income'  => 'earned',
                default   => 'something'
            };

            $return[] = [
                'key'                     => sprintf('%s-in-native', $title),
                'value'                   => $this->amounts[$key]['native'] ?? '0',
                'currency_id'             => (string) $this->default->id,
                'currency_code'           => $this->default->code,
                'currency_symbol'         => $this->default->symbol,
                'currency_decimal_places' => $this->default->decimal_places,
            ];
        }
        // loop 3: format amounts:
        $currencyIds = array_keys($this->amounts[self::SUM] ?? []);
        foreach ($currencyIds as $currencyId) {
            if ('native' === $currencyId) {
                // skip native entries.
                continue;
            }
            $currencyId                    = (int) $currencyId;
            $currency                      = $this->currencies[$currencyId] ?? $this->currencyRepository->find($currencyId);
            $this->currencies[$currencyId] = $currency;
            // create objects for big array.
            foreach ($this->keys as $key) {
                $title    = match ($key) {
                    'sum'     => 'balance',
                    'expense' => 'spent',
                    'income'  => 'earned',
                    default   => 'something'
                };
                $return[] = [
                    'key'                     => sprintf('%s-in-%s', $title, $currency->code),
                    'value'                   => $this->amounts[$key][$currencyId] ?? '0',
                    'currency_id'             => (string) $currency->id,
                    'currency_code'           => $currency->code,
                    'currency_symbol'         => $currency->symbol,
                    'currency_decimal_places' => $currency->decimal_places,
                ];
            }
        }

        return $return;
    }

    public function groupTransactions(string $key, array $journals): void
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        Log::debug(sprintf('Now in groupTransactions with key "%s" and %d journal(s)', $key, count($journals)));
        $converter    = new ExchangeRateConverter();
        $this->keys[] = $key;
        $multiplier   = 'income' === $key ? '-1' : '1';

        /** @var array $journal */
        foreach ($journals as $journal) {
            // transaction info:
            $currencyId                            = (int) $journal['currency_id'];
            $amount                                = bcmul((string) $journal['amount'], $multiplier);
            $currency                              = $this->currencies[$currencyId] ?? TransactionCurrency::find($currencyId);
            $this->currencies[$currencyId]         = $currency;
            $nativeAmount                          = $converter->convert($currency, $this->default, $journal['date'], $amount);
            if ((int) $journal['foreign_currency_id'] === $this->default->id) {
                // use foreign amount instead
                $nativeAmount = $journal['foreign_amount'];
            }
            // prep the arrays
            $this->amounts[$key]                   ??= [];
            $this->amounts[$key][$currencyId]      ??= '0';
            $this->amounts[$key]['native']         ??= '0';
            $this->amounts[self::SUM][$currencyId] ??= '0';
            $this->amounts[self::SUM]['native']    ??= '0';

            // add values:
            $this->amounts[$key][$currencyId]      = bcadd((string) $this->amounts[$key][$currencyId], $amount);
            $this->amounts[self::SUM][$currencyId] = bcadd((string) $this->amounts[self::SUM][$currencyId], $amount);
            $this->amounts[$key]['native']         = bcadd((string) $this->amounts[$key]['native'], (string) $nativeAmount);
            $this->amounts[self::SUM]['native']    = bcadd((string) $this->amounts[self::SUM]['native'], (string) $nativeAmount);
        }
        $converter->summarize();
    }

    public function setDefault(TransactionCurrency $default): void
    {
        $this->default = $default;
    }
}
