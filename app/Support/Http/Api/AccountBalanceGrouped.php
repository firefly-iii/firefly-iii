<?php
/*
 * AccountBalanceGrouped.php
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
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountBalanceGrouped
 */
class AccountBalanceGrouped
{
    private array                 $accountIds;
    private string                $carbonFormat;
    private array                 $currencies = [];
    private array                 $data       = [];
    private TransactionCurrency   $default;
    private Carbon                $end;
    private array                 $journals   = [];
    private string                $preferredRange;
    private Carbon                $start;
    private ExchangeRateConverter $converter;

    public function __construct()
    {
        $this->accountIds = [];
        $this->converter  = app(ExchangeRateConverter::class);
    }

    /**
     * Convert the given input to a chart compatible array.
     */
    public function convertToChartData(): array
    {
        $chartData = [];

        // loop2: loop this data, make chart bars for each currency:
        /** @var array $currency */
        foreach ($this->data as $currency) {
            // income and expense array prepped:
            $income       = [
                'label'                          => 'earned',
                'currency_id'                    => (string) $currency['currency_id'],
                'currency_symbol'                => $currency['currency_symbol'],
                'currency_code'                  => $currency['currency_code'],
                'currency_decimal_places'        => $currency['currency_decimal_places'],
                'native_currency_id'             => (string) $currency['native_currency_id'],
                'native_currency_symbol'         => $currency['native_currency_symbol'],
                'native_currency_code'           => $currency['native_currency_code'],
                'native_currency_decimal_places' => $currency['native_currency_decimal_places'],
                'date'                           => $this->start->toAtomString(),
                'start'                          => $this->start->toAtomString(),
                'end'                            => $this->end->toAtomString(),
                'period'                         => $this->preferredRange,
                'entries'                        => [],
                'native_entries'                 => [],
            ];
            $expense      = [
                'label'                          => 'spent',
                'currency_id'                    => (string) $currency['currency_id'],
                'currency_symbol'                => $currency['currency_symbol'],
                'currency_code'                  => $currency['currency_code'],
                'currency_decimal_places'        => $currency['currency_decimal_places'],
                'native_currency_id'             => (string) $currency['native_currency_id'],
                'native_currency_symbol'         => $currency['native_currency_symbol'],
                'native_currency_code'           => $currency['native_currency_code'],
                'native_currency_decimal_places' => $currency['native_currency_decimal_places'],
                'date'                           => $this->start->toAtomString(),
                'start'                          => $this->start->toAtomString(),
                'end'                            => $this->end->toAtomString(),
                'period'                         => $this->preferredRange,
                'entries'                        => [],
                'native_entries'                 => [],
            ];
            // loop all possible periods between $start and $end, and add them to the correct dataset.
            $currentStart = clone $this->start;
            while ($currentStart <= $this->end) {
                $key                               = $currentStart->format($this->carbonFormat);
                $label                             = $currentStart->toAtomString();
                // normal entries
                $income['entries'][$label]         = app('steam')->bcround($currency[$key]['earned'] ?? '0', $currency['currency_decimal_places']);
                $expense['entries'][$label]        = app('steam')->bcround($currency[$key]['spent'] ?? '0', $currency['currency_decimal_places']);

                // converted entries
                $income['native_entries'][$label]  = app('steam')->bcround($currency[$key]['native_earned'] ?? '0', $currency['native_currency_decimal_places']);
                $expense['native_entries'][$label] = app('steam')->bcround($currency[$key]['native_spent'] ?? '0', $currency['native_currency_decimal_places']);

                // next loop
                $currentStart                      = app('navigation')->addPeriod($currentStart, $this->preferredRange, 0);
            }

            $chartData[]  = $income;
            $chartData[]  = $expense;
        }

        return $chartData;
    }

    /**
     * Group the given journals by currency and then by period.
     * If they are part of a set of accounts this basically means it's balance chart.
     */
    public function groupByCurrencyAndPeriod(): void
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $converter = new ExchangeRateConverter();

        // loop. group by currency and by period.
        /** @var array $journal */
        foreach ($this->journals as $journal) {
            $this->processJournal($journal);
        }
        $converter->summarize();
    }

    public function setAccounts(Collection $accounts): void
    {
        $this->accountIds = $accounts->pluck('id')->toArray();
    }

    public function setDefault(TransactionCurrency $default): void
    {
        $this->default                  = $default;
        $defaultCurrencyId              = $default->id;
        $this->currencies               = [$default->id => $default]; // currency cache
        $this->data[$defaultCurrencyId] = [
            'currency_id'                    => (string) $defaultCurrencyId,
            'currency_symbol'                => $default->symbol,
            'currency_code'                  => $default->code,
            'currency_name'                  => $default->name,
            'currency_decimal_places'        => $default->decimal_places,
            'native_currency_id'             => (string) $defaultCurrencyId,
            'native_currency_symbol'         => $default->symbol,
            'native_currency_code'           => $default->code,
            'native_currency_name'           => $default->name,
            'native_currency_decimal_places' => $default->decimal_places,
        ];
    }

    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    public function setJournals(array $journals): void
    {
        $this->journals = $journals;
    }

    public function setPreferredRange(string $preferredRange): void
    {
        $this->preferredRange = $preferredRange;
        $this->carbonFormat   = app('navigation')->preferredCarbonFormatByPeriod($preferredRange);
    }

    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }

    private function processJournal(array $journal): void
    {
        // format the date according to the period
        $period                                          = $journal['date']->format($this->carbonFormat);
        $currencyId                                      = (int) $journal['currency_id'];
        $currency                                        = $this->findCurrency($currencyId);

        // set the array with monetary info, if it does not exist.
        $this->createDefaultDataEntry($journal);
        // set the array (in monetary info) with spent/earned in this $period, if it does not exist.
        $this->createDefaultPeriodEntry($journal);

        // is this journal's amount in- our outgoing?
        $key                                             = $this->getDataKey($journal);
        $amount                                          = 'spent' === $key ? app('steam')->negative($journal['amount']) : app('steam')->positive($journal['amount']);

        // get conversion rate
        $rate                                            = $this->getRate($currency, $journal['date']);
        $amountConverted                                 = bcmul($amount, $rate);

        // perhaps transaction already has the foreign amount in the native currency.
        if ((int) $journal['foreign_currency_id'] === $this->default->id) {
            $amountConverted = $journal['foreign_amount'] ?? '0';
            $amountConverted = 'earned' === $key ? app('steam')->positive($amountConverted) : app('steam')->negative($amountConverted);
        }

        // add normal entry
        $this->data[$currencyId][$period][$key]          = bcadd($this->data[$currencyId][$period][$key], $amount);

        // add converted entry
        $convertedKey                                    = sprintf('native_%s', $key);
        $this->data[$currencyId][$period][$convertedKey] = bcadd($this->data[$currencyId][$period][$convertedKey], $amountConverted);
    }

    private function findCurrency(int $currencyId): TransactionCurrency
    {
        if (array_key_exists($currencyId, $this->currencies)) {
            return $this->currencies[$currencyId];
        }
        $this->currencies[$currencyId] = TransactionCurrency::find($currencyId);

        return $this->currencies[$currencyId];
    }

    private function createDefaultDataEntry(array $journal): void
    {
        $currencyId = (int) $journal['currency_id'];
        $this->data[$currencyId] ??= [
            'currency_id'                    => (string) $currencyId,
            'currency_symbol'                => $journal['currency_symbol'],
            'currency_code'                  => $journal['currency_code'],
            'currency_name'                  => $journal['currency_name'],
            'currency_decimal_places'        => $journal['currency_decimal_places'],
            // native currency info (could be the same)
            'native_currency_id'             => (string) $this->default->id,
            'native_currency_code'           => $this->default->code,
            'native_currency_symbol'         => $this->default->symbol,
            'native_currency_decimal_places' => $this->default->decimal_places,
        ];
    }

    private function createDefaultPeriodEntry(array $journal): void
    {
        $currencyId = (int) $journal['currency_id'];
        $period     = $journal['date']->format($this->carbonFormat);
        $this->data[$currencyId][$period] ??= [
            'period'        => $period,
            'spent'         => '0',
            'earned'        => '0',
            'native_spent'  => '0',
            'native_earned' => '0',
        ];
    }

    private function getDataKey(array $journal): string
    {
        $key = 'spent';
        // deposit = incoming
        // transfer or reconcile or opening balance, and these accounts are the destination.
        if (
            TransactionType::DEPOSIT === $journal['transaction_type_type']

            || (
                (
                    TransactionType::TRANSFER === $journal['transaction_type_type']
                    || TransactionType::RECONCILIATION === $journal['transaction_type_type']
                    || TransactionType::OPENING_BALANCE === $journal['transaction_type_type']
                )
                && in_array($journal['destination_account_id'], $this->accountIds, true)
            )
        ) {
            $key = 'earned';
        }

        return $key;
    }

    private function getRate(TransactionCurrency $currency, Carbon $date): string
    {
        try {
            $rate = $this->converter->getCurrencyRate($currency, $this->default, $date);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            $rate = '1';
        }

        return $rate;
    }
}
