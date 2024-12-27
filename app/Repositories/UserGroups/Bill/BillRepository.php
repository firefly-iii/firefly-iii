<?php

/*
 * BillRepository.php
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

namespace FireflyIII\Repositories\UserGroups\Bill;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class BillRepository
 */
class BillRepository implements BillRepositoryInterface
{
    use UserGroupTrait;

    /**
     * Correct order of piggies in case of issues.
     */
    public function correctOrder(): void
    {
        $set     = $this->userGroup->bills()->orderBy('order', 'ASC')->get();
        $current = 1;
        foreach ($set as $bill) {
            if ($bill->order !== $current) {
                $bill->order = $current;
                $bill->save();
            }
            ++$current;
        }
    }

    public function getBills(): Collection
    {
        return $this->userGroup->bills()
            ->orderBy('bills.name', 'ASC')
            ->get(['bills.*'])
        ;
    }

    public function sumPaidInRange(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $bills     = $this->getActiveBills();
        $default   = app('amount')->getDefaultCurrency();
        $return    = [];
        $converter = new ExchangeRateConverter();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            /** @var Collection $set */
            $set        = $bill->transactionJournals()->after($start)->before($end)->get(['transaction_journals.*']);
            $currency   = $bill->transactionCurrency;
            $currencyId = $bill->transaction_currency_id;

            $return[$currencyId] ??= [
                'currency_id'                    => (string) $currency->id,
                'currency_name'                  => $currency->name,
                'currency_symbol'                => $currency->symbol,
                'currency_code'                  => $currency->code,
                'currency_decimal_places'        => $currency->decimal_places,
                'native_currency_id'             => (string) $default->id,
                'native_currency_name'           => $default->name,
                'native_currency_symbol'         => $default->symbol,
                'native_currency_code'           => $default->code,
                'native_currency_decimal_places' => $default->decimal_places,
                'sum'                            => '0',
                'native_sum'                     => '0',
            ];

            /** @var TransactionJournal $transactionJournal */
            foreach ($set as $transactionJournal) {
                /** @var null|Transaction $sourceTransaction */
                $sourceTransaction = $transactionJournal->transactions()->where('amount', '<', 0)->first();
                if (null !== $sourceTransaction) {
                    $amount                            = $sourceTransaction->amount;
                    if ((int) $sourceTransaction->foreign_currency_id === $currency->id) {
                        // use foreign amount instead!
                        $amount = (string) $sourceTransaction->foreign_amount;
                    }
                    // convert to native currency
                    $nativeAmount                      = $amount;
                    if ($currencyId !== $default->id) {
                        // get rate and convert.
                        $nativeAmount = $converter->convert($currency, $default, $transactionJournal->date, $amount);
                    }
                    if ((int) $sourceTransaction->foreign_currency_id === $default->id) {
                        // ignore conversion, use foreign amount
                        $nativeAmount = (string) $sourceTransaction->foreign_amount;
                    }
                    $return[$currencyId]['sum']        = bcadd($return[$currencyId]['sum'], $amount);
                    $return[$currencyId]['native_sum'] = bcadd($return[$currencyId]['native_sum'], $nativeAmount);
                }
            }
        }
        $converter->summarize();

        return $return;
    }

    public function getActiveBills(): Collection
    {
        return $this->userGroup->bills()
            ->where('active', true)
            ->orderBy('bills.name', 'ASC')
            ->get(['bills.*'])
        ;
    }

    public function sumUnpaidInRange(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $bills     = $this->getActiveBills();
        $return    = [];
        $default   = app('amount')->getDefaultCurrency();
        $converter = new ExchangeRateConverter();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $dates = $this->getPayDatesInRange($bill, $start, $end);
            $count = $bill->transactionJournals()->after($start)->before($end)->count();
            $total = $dates->count() - $count;

            if ($total > 0) {
                $currency                          = $bill->transactionCurrency;
                $currencyId                        = $bill->transaction_currency_id;
                $average                           = bcdiv(bcadd($bill->amount_max, $bill->amount_min), '2');
                $nativeAverage                     = $converter->convert($currency, $default, $start, $average);
                $return[$currencyId] ??= [
                    'currency_id'                    => (string) $currency->id,
                    'currency_name'                  => $currency->name,
                    'currency_symbol'                => $currency->symbol,
                    'currency_code'                  => $currency->code,
                    'currency_decimal_places'        => $currency->decimal_places,
                    'native_currency_id'             => (string) $default->id,
                    'native_currency_name'           => $default->name,
                    'native_currency_symbol'         => $default->symbol,
                    'native_currency_code'           => $default->code,
                    'native_currency_decimal_places' => $default->decimal_places,
                    'sum'                            => '0',
                    'native_sum'                     => '0',
                ];
                $return[$currencyId]['sum']        = bcadd($return[$currencyId]['sum'], bcmul($average, (string) $total));
                $return[$currencyId]['native_sum'] = bcadd($return[$currencyId]['native_sum'], bcmul($nativeAverage, (string) $total));
            }
        }
        $converter->summarize();

        return $return;
    }

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     * TODO duplicate of function in other billrepositoryinterface
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection
    {
        $set          = new Collection();
        $currentStart = clone $start;
        // app('log')->debug(sprintf('Now at bill "%s" (%s)', $bill->name, $bill->repeat_freq));
        // app('log')->debug(sprintf('First currentstart is %s', $currentStart->format('Y-m-d')));

        while ($currentStart <= $end) {
            // app('log')->debug(sprintf('Currentstart is now %s.', $currentStart->format('Y-m-d')));
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            // app('log')->debug(sprintf('Next Date match after %s is %s', $currentStart->format('Y-m-d'), $nextExpectedMatch->format('Y-m-d')));
            if ($nextExpectedMatch > $end) {// If nextExpectedMatch is after end, we continue
                break;
            }
            $set->push(clone $nextExpectedMatch);
            // app('log')->debug(sprintf('Now %d dates in set.', $set->count()));
            $nextExpectedMatch->addDay();

            // app('log')->debug(sprintf('Currentstart (%s) has become %s.', $currentStart->format('Y-m-d'), $nextExpectedMatch->format('Y-m-d')));

            $currentStart      = clone $nextExpectedMatch;
        }

        return $set;
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether it is there already, is not relevant.
     *
     * TODO duplicate of other repos
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        $cache = new CacheProperties();
        $cache->addProperty($bill->id);
        $cache->addProperty('nextDateMatch');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get();
        }
        // find the most recent date for this bill NOT in the future. Cache this date:
        $start = clone $bill->date;

        while ($start < $date) {
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }
        $cache->store($start);

        return $start;
    }
}
