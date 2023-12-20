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
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;

class SummaryBalanceGrouped
{
    private TransactionCurrency $default;
    private array               $amounts = [];
    private array               $keys    = [];
    private const string SUM = 'sum';
    private CurrencyRepositoryInterface $currencyRepository;

    public function __construct()
    {
        $this->keys               = [self::SUM];
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
    }

    /**
     * TODO remember to do -1 for deposits.
     *
     * @param array $journals
     *
     * @return void
     */
    public function groupTransactions(string $key, array $journals): void
    {
        $converter    = new ExchangeRateConverter();
        $this->keys[] = $key;
        /** @var array $journal */
        foreach ($journals as $journal) {
            // transaction info:
            $currencyId              = (int)$journal['currency_id'];
            $amount                  = $journal['amount'];
            $currency                = $currencies[$currencyId] ?? TransactionCurrency::find($currencyId);
            $currencies[$currencyId] = $currency;
            $nativeAmount            = $converter->convert($currency, $this->default, $journal['date'], $amount);
            if ((int)$journal['foreign_currency_id'] === $this->default->id) {
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
            $this->amounts[$key][$currencyId]      = bcadd($this->amounts[$key][$currencyId], $amount);
            $this->amounts[self::SUM][$currencyId] = bcadd($this->amounts[self::SUM][$currencyId], $amount);
            $this->amounts[$key]['native']         = bcadd($this->amounts[$key]['native'], $nativeAmount);
            $this->amounts[self::SUM]['native']    = bcadd($this->amounts[self::SUM]['native'], $nativeAmount);
        }
    }

    /**
     * @return array
     */
    public function groupData(): array
    {
        $return = [];
        foreach ($this->keys as $key) {
            $title    = match ($key) {
                'sum'     => 'balance',
                'expense' => 'spent',
                'income'  => 'earned',
                default   => 'something'
            };
            $amount   = 'income' === $key ? bcsub($this->amounts[$key]['native'], '-1') : $this->amounts[$key]['native'];
            $return[] = [
                'key'                     => sprintf('%s-in-native', $title),
                'value'                   => $amount,
                'currency_id'             => (string)$this->default->id,
                'currency_code'           => $this->default->code,
                'currency_symbol'         => $this->default->symbol,
                'currency_decimal_places' => $this->default->decimal_places,
            ];
        }
        // loop 3: format amounts:
        $currencyIds = array_keys($this->amounts[self::SUM]);
        foreach ($currencyIds as $currencyId) {
            if ('native' === $currencyId) {
                // skip native entries.
                continue;
            }
            $currency                = $currencies[$currencyId] ?? $this->currencyRepository->find($currencyId);
            $currencies[$currencyId] = $currency;
            // create objects for big array.
            foreach ($this->keys as $key) {
                $title    = match ($key) {
                    'sum'     => 'balance',
                    'expense' => 'spent',
                    'income'  => 'earned',
                    default   => 'something'
                };
                $amount   = $this->amounts[$key][$currencyId] ?? '0';
                $amount   = 'income' === $key ? bcsub($amount, '-1') : $amount;
                $return[] = [
                    'key'                     => sprintf('%s-in-%s', $title, $currency->code),
                    'value'                   => $amount,
                    'currency_id'             => (string)$currency->id,
                    'currency_code'           => $currency->code,
                    'currency_symbol'         => $currency->symbol,
                    'currency_decimal_places' => $currency->decimal_places,
                ];
            }
        }
        return $return;
    }

    public function setDefault(TransactionCurrency $default): void
    {
        $this->default = $default;
    }


}
