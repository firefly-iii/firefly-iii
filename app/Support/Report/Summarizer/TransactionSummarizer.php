<?php

/*
 * TransactionSummarizer.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Report\Summarizer;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

class TransactionSummarizer
{
    private bool                $convertToNative = false;
    private TransactionCurrency $default;
    private User                $user;

    public function __construct(?User $user = null)
    {
        if ($user instanceof User) {
            $this->setUser($user);
        }
    }

    public function setUser(User $user): void
    {
        $this->user            = $user;
        $this->default         = Amount::getNativeCurrencyByUserGroup($user->userGroup);
        $this->convertToNative = Amount::convertToNative($user);
    }

    public function groupByCurrencyId(array $journals, string $method = 'negative', bool $includeForeign = true): array
    {
        Log::debug(sprintf('Now in groupByCurrencyId([%d journals], "%s")', count($journals), $method));
        $array = [];
        foreach ($journals as $journal) {
            $field                        = 'amount';

            // grab default currency information.
            $currencyId                   = (int) $journal['currency_id'];
            $currencyName                 = $journal['currency_name'];
            $currencySymbol               = $journal['currency_symbol'];
            $currencyCode                 = $journal['currency_code'];
            $currencyDecimalPlaces        = $journal['currency_decimal_places'];

            // prepare foreign currency info:
            $foreignCurrencyId            = 0;
            $foreignCurrencyName          = null;
            $foreignCurrencySymbol        = null;
            $foreignCurrencyCode          = null;
            $foreignCurrencyDecimalPlaces = null;

            if ($this->convertToNative) {
                Log::debug('convertToNative is true.');
                // if convert to native, use the native amount yes or no?
                $useNative  = $this->default->id !== (int) $journal['currency_id'];
                $useForeign = $this->default->id === (int) $journal['foreign_currency_id'];
                if ($useNative) {
                    Log::debug(sprintf('Journal #%d switches to native amount (original is %s)', $journal['transaction_journal_id'], $journal['currency_code']));
                    $field                 = 'native_amount';
                    $currencyId            = $this->default->id;
                    $currencyName          = $this->default->name;
                    $currencySymbol        = $this->default->symbol;
                    $currencyCode          = $this->default->code;
                    $currencyDecimalPlaces = $this->default->decimal_places;
                }
                if ($useForeign) {
                    Log::debug(sprintf('Journal #%d switches to foreign amount (foreign is %s)', $journal['transaction_journal_id'], $journal['foreign_currency_code']));
                    $field                 = 'foreign_amount';
                    $currencyId            = (int) $journal['foreign_currency_id'];
                    $currencyName          = $journal['foreign_currency_name'];
                    $currencySymbol        = $journal['foreign_currency_symbol'];
                    $currencyCode          = $journal['foreign_currency_code'];
                    $currencyDecimalPlaces = $journal['foreign_currency_decimal_places'];
                }
            }
            if (!$this->convertToNative) {
                Log::debug('convertToNative is false.');
                // use foreign amount?
                $foreignCurrencyId = (int) $journal['foreign_currency_id'];
                if (0 !== $foreignCurrencyId) {
                    Log::debug(sprintf('Journal #%d also includes foreign amount (foreign is "%s")', $journal['transaction_journal_id'], $journal['foreign_currency_code']));
                    $foreignCurrencyName          = $journal['foreign_currency_name'];
                    $foreignCurrencySymbol        = $journal['foreign_currency_symbol'];
                    $foreignCurrencyCode          = $journal['foreign_currency_code'];
                    $foreignCurrencyDecimalPlaces = $journal['foreign_currency_decimal_places'];
                }
            }

            // first process normal amount
            $amount                       = (string) ($journal[$field] ?? '0');
            $array[$currencyId] ??= [
                'sum'                     => '0',
                'currency_id'             => $currencyId,
                'currency_name'           => $currencyName,
                'currency_symbol'         => $currencySymbol,
                'currency_code'           => $currencyCode,
                'currency_decimal_places' => $currencyDecimalPlaces,
            ];

            if ('positive' === $method) {
                $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], (string) app('steam')->positive($amount));
            }
            if ('negative' === $method) {
                $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], (string) app('steam')->negative($amount));
            }

            // then process foreign amount, if it exists.
            if (0 !== $foreignCurrencyId && true === $includeForeign) {
                $amount = (string) ($journal['foreign_amount'] ?? '0');
                $array[$foreignCurrencyId] ??= [
                    'sum'                     => '0',
                    'currency_id'             => $foreignCurrencyId,
                    'currency_name'           => $foreignCurrencyName,
                    'currency_symbol'         => $foreignCurrencySymbol,
                    'currency_code'           => $foreignCurrencyCode,
                    'currency_decimal_places' => $foreignCurrencyDecimalPlaces,
                ];

                if ('positive' === $method) {
                    $array[$foreignCurrencyId]['sum'] = bcadd($array[$foreignCurrencyId]['sum'], (string) app('steam')->positive($amount));
                }
                if ('negative' === $method) {
                    $array[$foreignCurrencyId]['sum'] = bcadd($array[$foreignCurrencyId]['sum'], (string) app('steam')->negative($amount));
                }
            }

            // $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->{$method}($amount));
            // Log::debug(sprintf('Journal #%d adds amount %s %s', $journal['transaction_journal_id'], $currencyCode, $amount));
        }
        Log::debug('End of sumExpenses.', $array);

        return $array;
    }

    public function groupByDirection(array $journals, string $method, string $direction): array
    {

        $array           = [];
        $idKey           = sprintf('%s_account_id', $direction);
        $nameKey         = sprintf('%s_account_name', $direction);
        $convertToNative = Amount::convertToNative($this->user);
        $default         = Amount::getNativeCurrencyByUserGroup($this->user->userGroup);


        Log::debug(sprintf('groupByDirection(array, %s, %s).', $direction, $method));
        foreach ($journals as $journal) {
            // currency
            $currencyId            = $journal['currency_id'];
            $currencyName          = $journal['currency_name'];
            $currencySymbol        = $journal['currency_symbol'];
            $currencyCode          = $journal['currency_code'];
            $currencyDecimalPlaces = $journal['currency_decimal_places'];
            $field                 = $convertToNative && $currencyId !== $default->id ? 'native_amount' : 'amount';

            // perhaps use default currency instead?
            if ($convertToNative && $journal['currency_id'] !== $default->id) {
                $currencyId            = $default->id;
                $currencyName          = $default->name;
                $currencySymbol        = $default->symbol;
                $currencyCode          = $default->code;
                $currencyDecimalPlaces = $default->decimal_places;
            }
            // use foreign amount when the foreign currency IS the default currency.
            if ($convertToNative && $journal['currency_id'] !== $default->id && $default->id === $journal['foreign_currency_id']) {
                $field = 'foreign_amount';
            }
            $key                   = sprintf('%s-%s', $journal[$idKey], $currencyId);
            // sum it all up or create a new array.
            $array[$key] ??= [
                'id'                      => $journal[$idKey],
                'name'                    => $journal[$nameKey],
                'sum'                     => '0',
                'currency_id'             => $currencyId,
                'currency_name'           => $currencyName,
                'currency_symbol'         => $currencySymbol,
                'currency_code'           => $currencyCode,
                'currency_decimal_places' => $currencyDecimalPlaces,
            ];

            // add the data from the $field to the array.
            $array[$key]['sum']    = bcadd($array[$key]['sum'], (string) app('steam')->{$method}((string) ($journal[$field] ?? '0'))); // @phpstan-ignore-line
            Log::debug(sprintf('Field for transaction #%d is "%s" (%s). Sum: %s', $journal['transaction_group_id'], $currencyCode, $field, $array[$key]['sum']));

            // also do foreign amount, but only when convertToNative is false (otherwise we have it already)
            // or when convertToNative is true and the foreign currency is ALSO not the default currency.
            if ((!$convertToNative || $journal['foreign_currency_id'] !== $default->id) && 0 !== (int) $journal['foreign_currency_id']) {
                Log::debug(sprintf('Use foreign amount from transaction #%d: %s %s. Sum: %s', $journal['transaction_group_id'], $currencyCode, $journal['foreign_amount'], $array[$key]['sum']));
                $key                = sprintf('%s-%s', $journal[$idKey], $journal['foreign_currency_id']);
                $array[$key] ??= [
                    'id'                      => $journal[$idKey],
                    'name'                    => $journal[$nameKey],
                    'sum'                     => '0',
                    'currency_id'             => $journal['foreign_currency_id'],
                    'currency_name'           => $journal['foreign_currency_name'],
                    'currency_symbol'         => $journal['foreign_currency_symbol'],
                    'currency_code'           => $journal['foreign_currency_code'],
                    'currency_decimal_places' => $journal['foreign_currency_decimal_places'],
                ];
                $array[$key]['sum'] = bcadd($array[$key]['sum'], (string) app('steam')->{$method}((string) $journal['foreign_amount'])); // @phpstan-ignore-line
            }
        }

        return $array;
    }

    public function setConvertToNative(bool $convertToNative): void
    {
        Log::debug(sprintf('Overrule convertToNative to become %s', var_export($convertToNative, true)));
        $this->convertToNative = $convertToNative;
    }
}
