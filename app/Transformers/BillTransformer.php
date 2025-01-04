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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\Models\BillDateCalculator;
use Illuminate\Support\Collection;

/**
 * Class BillTransformer
 */
class BillTransformer extends AbstractTransformer
{
    private BillDateCalculator      $calculator;
    private BillRepositoryInterface $repository;

    /**
     * BillTransformer constructor.
     */
    public function __construct()
    {
        $this->repository = app(BillRepositoryInterface::class);
        $this->calculator = app(BillDateCalculator::class);
    }

    /**
     * Transform the bill.
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function transform(Bill $bill): array
    {
        $defaultCurrency   = $this->parameters->get('defaultCurrency') ?? app('amount')->getDefaultCurrency();

        $paidData          = $this->paidData($bill);
        $lastPaidDate      = $this->getLastPaidDate($paidData);
        $start             = $this->parameters->get('start') ?? today()->subYears(10);
        $end               = $this->parameters->get('end') ?? today()->addYears(10);
        $payDates          = $this->calculator->getPayDates($start, $end, $bill->date, $bill->repeat_freq, $bill->skip, $lastPaidDate);
        $currency          = $bill->transactionCurrency;
        $notes             = $this->repository->getNoteText($bill);
        $notes             = '' === $notes ? null : $notes;
        $objectGroupId     = null;
        $objectGroupOrder  = null;
        $objectGroupTitle  = null;
        $this->repository->setUser($bill->user);

        /** @var null|ObjectGroup $objectGroup */
        $objectGroup       = $bill->objectGroups->first();
        if (null !== $objectGroup) {
            $objectGroupId    = $objectGroup->id;
            $objectGroupOrder = $objectGroup->order;
            $objectGroupTitle = $objectGroup->title;
        }

        $paidDataFormatted = [];
        $payDatesFormatted = [];
        foreach ($paidData as $object) {
            $date                = Carbon::createFromFormat('!Y-m-d', $object['date'], config('app.timezone'));
            if (null === $date) {
                $date = today(config('app.timezone'));
            }
            $object['date']      = $date->toAtomString();
            $paidDataFormatted[] = $object;
        }

        foreach ($payDates as $string) {
            $date                = Carbon::createFromFormat('!Y-m-d', $string, config('app.timezone'));
            if (null === $date) {
                $date = today(config('app.timezone'));
            }
            $payDatesFormatted[] = $date->toAtomString();
        }
        // next expected match
        $nem               = null;
        $nemDate           = null;
        $nemDiff           = trans('firefly.not_expected_period');
        $firstPayDate      = $payDates[0] ?? null;

        if (null !== $firstPayDate) {
            $nemDate = Carbon::createFromFormat('!Y-m-d', $firstPayDate, config('app.timezone'));
            if (null === $nemDate) {
                $nemDate = today(config('app.timezone'));
            }
            $nem     = $nemDate->toAtomString();

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
                $temp2   = Carbon::createFromFormat('Y-m-d\TH:i:sP', $current);
                if (null === $temp2) {
                    $temp2 = today(config('app.timezone'));
                }
                $nemDiff = trans('firefly.bill_expected_date', ['date' => $temp2->diffForHumans(today(config('app.timezone')), CarbonInterface::DIFF_RELATIVE_TO_NOW)]);
            }
            unset($temp2);
        }

        return [
            'id'                       => $bill->id,
            'created_at'               => $bill->created_at->toAtomString(),
            'updated_at'               => $bill->updated_at->toAtomString(),
            'currency_id'              => (string) $bill->transaction_currency_id,
            'currency_code'            => $currency->code,
            'currency_symbol'          => $currency->symbol,
            'currency_decimal_places'  => $currency->decimal_places,
            'name'                     => $bill->name,
            'amount_min'               => app('steam')->bcround($bill->amount_min, $currency->decimal_places),
            'amount_max'               => app('steam')->bcround($bill->amount_max, $currency->decimal_places),
            'native_amount_min'        => app('steam')->bcround($bill->native_amount_min, $defaultCurrency->decimal_places),
            'native_amount_max'        => app('steam')->bcround($bill->native_amount_max, $defaultCurrency->decimal_places),
            'date'                     => $bill->date->toAtomString(),
            'end_date'                 => $bill->end_date?->toAtomString(),
            'extension_date'           => $bill->extension_date?->toAtomString(),
            'repeat_freq'              => $bill->repeat_freq,
            'skip'                     => $bill->skip,
            'active'                   => $bill->active,
            'order'                    => $bill->order,
            'notes'                    => $notes,
            'object_group_id'          => null !== $objectGroupId ? (string) $objectGroupId : null,
            'object_group_order'       => $objectGroupOrder,
            'object_group_title'       => $objectGroupTitle,

            // these fields need work:
            'next_expected_match'      => $nem,
            'next_expected_match_diff' => $nemDiff,
            'pay_dates'                => $payDatesFormatted,
            'paid_dates'               => $paidDataFormatted,
            'links'                    => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/'.$bill->id,
                ],
            ],
        ];
    }

    /**
     * Get the data the bill was paid and predict the next expected match.
     */
    protected function paidData(Bill $bill): array
    {
        app('log')->debug(sprintf('Now in paidData for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            app('log')->debug('parameters are NULL, return empty array');

            return [];
        }

        // 2023-07-1 sub one day from the start date to fix a possible bug (see #7704)
        // 2023-07-18 this particular date is used to search for the last paid date.
        // 2023-07-18 the cloned $searchDate is used to grab the correct transactions.
        /** @var Carbon $start */
        $start        = clone $this->parameters->get('start');
        $searchStart  = clone $start;
        $start->subDay();

        /** @var Carbon $end */
        $end          = clone $this->parameters->get('end');
        $searchEnd    = clone $end;

        // move the search dates to the start of the day.
        $searchStart->startOfDay();
        $searchEnd->endOfDay();

        app('log')->debug(sprintf('Parameters are start: %s end: %s', $start->format('Y-m-d'), $end->format('Y-m-d')));
        app('log')->debug(sprintf('Search parameters are: start: %s', $searchStart->format('Y-m-d')));

        // Get from database when bill was paid.
        $set          = $this->repository->getPaidDatesInRange($bill, $searchStart, $searchEnd);
        app('log')->debug(sprintf('Count %d entries in getPaidDatesInRange()', $set->count()));

        // Grab from array the most recent payment. If none exist, fall back to the start date and pretend *that* was the last paid date.
        app('log')->debug(sprintf('Grab last paid date from function, return %s if it comes up with nothing.', $start->format('Y-m-d')));
        $lastPaidDate = $this->lastPaidDate($set, $start);
        app('log')->debug(sprintf('Result of lastPaidDate is %s', $lastPaidDate->format('Y-m-d')));

        // At this point the "next match" is exactly after the last time the bill was paid.
        $result       = [];
        foreach ($set as $entry) {
            $result[] = [
                'transaction_group_id'   => (string) $entry->transaction_group_id,
                'transaction_journal_id' => (string) $entry->id,
                'date'                   => $entry->date->format('Y-m-d'),
                'date_object'            => $entry->date,
            ];
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
        app('log')->debug('getLastPaidDate()');
        $return = null;
        foreach ($paidData as $entry) {
            if (null !== $return) {
                /** @var Carbon $current */
                $current = $entry['date_object'];
                if ($current->gt($return)) {
                    $return = clone $current;
                }
                app('log')->debug(sprintf('Last paid date is: %s', $return->format('Y-m-d')));
            }
            if (null === $return) {
                /** @var Carbon $return */
                $return = $entry['date_object'];
                app('log')->debug(sprintf('Last paid date is: %s', $return->format('Y-m-d')));
            }
        }
        app('log')->debug(sprintf('Last paid date is: "%s"', $return?->format('Y-m-d')));

        return $return;
    }
}
