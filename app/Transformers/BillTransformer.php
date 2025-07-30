<?php

/**
 * BillTransformer.php
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

namespace FireflyIII\Transformers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Models\BillDateCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class BillTransformer
 */
class BillTransformer extends AbstractTransformer
{
    private readonly BillDateCalculator      $calculator;
    private readonly bool                    $convertToNative;
    private readonly TransactionCurrency     $native;
    private readonly BillRepositoryInterface $repository;

    /**
     * BillTransformer constructor.
     */
    public function __construct()
    {
        $this->repository      = app(BillRepositoryInterface::class);
        $this->calculator      = app(BillDateCalculator::class);
        $this->native          = Amount::getNativeCurrency();
        $this->convertToNative = Amount::convertToNative();
    }

    /**
     * Transform the bill.
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function transform(Bill $bill): array
    {
        $paidData         = $this->paidData($bill);
        $lastPaidDate     = $this->getLastPaidDate($paidData);
        $start            = $this->parameters->get('start') ?? today()->subYears(10);
        $end              = $this->parameters->get('end') ?? today()->addYears(10);
        $payDates         = $this->calculator->getPayDates($start, $end, $bill->date, $bill->repeat_freq, $bill->skip, $lastPaidDate);
        $currency         = $bill->transactionCurrency;
        $this->repository->setUser($bill->user);


        $paidDataFormatted = [];
        $payDatesFormatted = [];
        foreach ($paidData as $object) {
            $date = Carbon::createFromFormat('!Y-m-d', $object['date'], config('app.timezone'));
            if (!$date instanceof Carbon) {
                $date = today(config('app.timezone'));
            }
            $object['date']      = $date->toAtomString();
            $paidDataFormatted[] = $object;
        }

        foreach ($payDates as $string) {
            $date = Carbon::createFromFormat('!Y-m-d', $string, config('app.timezone'));
            if (!$date instanceof Carbon) {
                $date = today(config('app.timezone'));
            }
            $payDatesFormatted[] = $date->toAtomString();
        }
        // next expected match
        $nem          = null;
        $nemDate      = null;
        $nemDiff      = trans('firefly.not_expected_period');
        $firstPayDate = $payDates[0] ?? null;

        if (null !== $firstPayDate) {
            $nemDate = Carbon::createFromFormat('!Y-m-d', $firstPayDate, config('app.timezone'));
            if (!$nemDate instanceof Carbon) {
                $nemDate = today(config('app.timezone'));
            }
            $nem = $nemDate->toAtomString();

            // nullify again when it's outside the current view range.
            if (
                (null !== $this->parameters->get('start') && $nemDate->lt($this->parameters->get('start')))
                || (null !== $this->parameters->get('end') && $nemDate->gt($this->parameters->get('end')))
            ) {
                $nem          = null;
                $nemDate      = null;
                $firstPayDate = null;
            }
        }

        // converting back and forth is bad code but OK.
        if (null !== $nemDate) {
            if ($nemDate->isToday()) {
                $nemDiff = trans('firefly.today');
            }

            $current = $payDatesFormatted[0] ?? null;
            if (null !== $current && !$nemDate->isToday()) {
                $temp2 = Carbon::createFromFormat('Y-m-d\TH:i:sP', $current);
                if (!$temp2 instanceof Carbon) {
                    $temp2 = today(config('app.timezone'));
                }
                $nemDiff = trans('firefly.bill_expected_date', ['date' => $temp2->diffForHumans(today(config('app.timezone')), CarbonInterface::DIFF_RELATIVE_TO_NOW)]);
            }
            unset($temp2);
        }

        return [
            'id'                      => $bill->id,
            'created_at'              => $bill->created_at->toAtomString(),
            'updated_at'              => $bill->updated_at->toAtomString(),
            'currency_id'             => (string)$bill->transaction_currency_id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,

            'native_currency_id'             => (string)$this->native->id,
            'native_currency_code'           => $this->native->code,
            'native_currency_symbol'         => $this->native->symbol,
            'native_currency_decimal_places' => $this->native->decimal_places,

            'name'           => $bill->name,
            'amount_min'     => $bill->amounts['amount_min'],
            'amount_max'     => $bill->amounts['amount_max'],
            'amount_avg'     => $bill->amounts['average'],
            'date'           => $bill->date->toAtomString(),
            'end_date'       => $bill->end_date?->toAtomString(),
            'extension_date' => $bill->extension_date?->toAtomString(),
            'repeat_freq'    => $bill->repeat_freq,
            'skip'           => $bill->skip,
            'active'         => $bill->active,
            'order'          => $bill->order,
            'notes'          => $bill->meta['notes'],
            'object_group_id' => $bill->meta['object_group_id'],
            'object_group_order' => $bill->meta['object_group_order'],
            'object_group_title' => $bill->meta['object_group_title'],

            // these fields need work:
            //            'next_expected_match'            => $nem,
            //            'next_expected_match_diff'       => $nemDiff,
            //            'pay_dates'                      => $payDatesFormatted,
            //            'paid_dates'                     => $paidDataFormatted,
            'links'          => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/' . $bill->id,
                ],
            ],
        ];
    }

    /**
     * Get the data the bill was paid and predict the next expected match.
     */
    protected function paidData(Bill $bill): array
    {
        Log::debug(sprintf('Now in paidData for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            Log::debug('parameters are NULL, return empty array');

            return [];
        }

        // 2023-07-1 sub one day from the start date to fix a possible bug (see #7704)
        // 2023-07-18 this particular date is used to search for the last paid date.
        // 2023-07-18 the cloned $searchDate is used to grab the correct transactions.
        /** @var Carbon $start */
        $start       = clone $this->parameters->get('start');
        $searchStart = clone $start;
        $start->subDay();

        /** @var Carbon $end */
        $end       = clone $this->parameters->get('end');
        $searchEnd = clone $end;

        // move the search dates to the start of the day.
        $searchStart->startOfDay();
        $searchEnd->endOfDay();

        Log::debug(sprintf('Parameters are start: %s end: %s', $start->format('Y-m-d'), $end->format('Y-m-d')));
        Log::debug(sprintf('Search parameters are: start: %s', $searchStart->format('Y-m-d')));

        // Get from database when bill was paid.
        $set = $this->repository->getPaidDatesInRange($bill, $searchStart, $searchEnd);
        Log::debug(sprintf('Count %d entries in getPaidDatesInRange()', $set->count()));

        // Grab from array the most recent payment. If none exist, fall back to the start date and pretend *that* was the last paid date.
        Log::debug(sprintf('Grab last paid date from function, return %s if it comes up with nothing.', $start->format('Y-m-d')));
        $lastPaidDate = $this->lastPaidDate($set, $start);
        Log::debug(sprintf('Result of lastPaidDate is %s', $lastPaidDate->format('Y-m-d')));

        // At this point the "next match" is exactly after the last time the bill was paid.
        $result = [];
        foreach ($set as $entry) {
            $array = [
                'transaction_group_id'    => (string)$entry->transaction_group_id,
                'transaction_journal_id'  => (string)$entry->id,
                'date'                    => $entry->date->format('Y-m-d'),
                'date_object'             => $entry->date,
                'currency_id'             => $entry->transaction_currency_id,
                'currency_code'           => $entry->transaction_currency_code,
                'currency_decimal_places' => $entry->transaction_currency_decimal_places,
                'amount'                  => Steam::bcround($entry->amount, $entry->transaction_currency_decimal_places),
            ];
            if (null !== $entry->foreign_amount && null !== $entry->foreign_currency_code) {
                $array['foreign_currency_id']             = $entry->foreign_currency_id;
                $array['foreign_currency_code']           = $entry->foreign_currency_code;
                $array['foreign_currency_decimal_places'] = $entry->foreign_currency_decimal_places;
                $array['foreign_amount']                  = Steam::bcround($entry->foreign_amount, $entry->foreign_currency_decimal_places);
            }

            $result[] = $array;

        }

        return $result;
    }

    /**
     * Returns the latest date in the set, or start when set is empty.
     */
    protected function lastPaidDate(Collection $dates, Carbon $default): Carbon
    {
        if (0 === $dates->count()) {
            return $default;
        }
        $latest = $dates->first()->date;

        /** @var TransactionJournal $journal */
        foreach ($dates as $journal) {
            if ($journal->date->gte($latest)) {
                $latest = $journal->date;
            }
        }

        return $latest;
    }

    private function getLastPaidDate(array $paidData): ?Carbon
    {
        Log::debug('getLastPaidDate()');
        $return = null;
        foreach ($paidData as $entry) {
            if (null !== $return) {
                /** @var Carbon $current */
                $current = $entry['date_object'];
                if ($current->gt($return)) {
                    $return = clone $current;
                }
                Log::debug(sprintf('Last paid date is: %s', $return->format('Y-m-d')));
            }
            if (null === $return) {
                /** @var Carbon $return */
                $return = $entry['date_object'];
                Log::debug(sprintf('Last paid date is: %s', $return->format('Y-m-d')));
            }
        }
        Log::debug(sprintf('Last paid date is: "%s"', $return?->format('Y-m-d')));

        return $return;
    }
}
