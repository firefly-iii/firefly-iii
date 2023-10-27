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
use Illuminate\Support\Collection;

/**
 * Class BillTransformer
 */
class BillTransformer extends AbstractTransformer
{
    private BillRepositoryInterface $repository;

    /**
     * BillTransformer constructor.
     *

     */
    public function __construct()
    {
        $this->repository = app(BillRepositoryInterface::class);
    }

    /**
     * Transform the bill.
     *
     * @param Bill $bill
     *
     * @return array
     */
    public function transform(Bill $bill): array
    {
        $paidData = $this->paidData($bill);
        $payDates = $this->payDates($bill);
        $currency = $bill->transactionCurrency;
        $notes = $this->repository->getNoteText($bill);
        $notes = '' === $notes ? null : $notes;
        $this->repository->setUser($bill->user);

        $objectGroupId = null;
        $objectGroupOrder = null;
        $objectGroupTitle = null;
        /** @var ObjectGroup $objectGroup */
        $objectGroup = $bill->objectGroups->first();
        if (null !== $objectGroup) {
            $objectGroupId = (int)$objectGroup->id;
            $objectGroupOrder = (int)$objectGroup->order;
            $objectGroupTitle = $objectGroup->title;
        }

        $paidDataFormatted = [];
        $payDatesFormatted = [];
        foreach ($paidData['paid_dates'] as $object) {
            $object['date'] = Carbon::createFromFormat('!Y-m-d', $object['date'], config('app.timezone'))->toAtomString();
            $paidDataFormatted[] = $object;
        }

        foreach ($payDates as $string) {
            $payDatesFormatted[] = Carbon::createFromFormat('!Y-m-d', $string, config('app.timezone'))->toAtomString();
        }
        $nextExpectedMatch = null;
        if (null !== $paidData['next_expected_match']) {
            $nextExpectedMatch = Carbon::createFromFormat('!Y-m-d', $paidData['next_expected_match'], config('app.timezone'))->toAtomString();
        }
        $nextExpectedMatchDiff = trans('firefly.not_expected_period');
        // converting back and forth is bad code but OK.
        $temp = new Carbon($nextExpectedMatch);
        if ($temp->isToday()) {
            $nextExpectedMatchDiff = trans('firefly.today');
        }

        $current = $payDatesFormatted[0] ?? null;
        if (null !== $current && !$temp->isToday()) {
            $temp2 = Carbon::createFromFormat('Y-m-d\TH:i:sP', $current);
            $nextExpectedMatchDiff = $temp2->diffForHumans(today(config('app.timezone')), CarbonInterface::DIFF_RELATIVE_TO_NOW);
        }
        unset($temp, $temp2);

        return [
            'id' => (int)$bill->id,
            'created_at' => $bill->created_at->toAtomString(),
            'updated_at' => $bill->updated_at->toAtomString(),
            'currency_id' => (string)$bill->transaction_currency_id,
            'currency_code' => $currency->code,
            'currency_symbol' => $currency->symbol,
            'currency_decimal_places' => (int)$currency->decimal_places,
            'name' => $bill->name,
            'amount_min' => app('steam')->bcround($bill->amount_min, $currency->decimal_places),
            'amount_max' => app('steam')->bcround($bill->amount_max, $currency->decimal_places),
            'date' => $bill->date->toAtomString(),
            'end_date' => $bill->end_date?->toAtomString(),
            'extension_date' => $bill->extension_date?->toAtomString(),
            'repeat_freq' => $bill->repeat_freq,
            'skip' => (int)$bill->skip,
            'active' => $bill->active,
            'order' => (int)$bill->order,
            'notes' => $notes,
            'object_group_id' => $objectGroupId ? (string)$objectGroupId : null,
            'object_group_order' => $objectGroupOrder,
            'object_group_title' => $objectGroupTitle,

            // these fields need work:
            'next_expected_match' => $nextExpectedMatch,
            'next_expected_match_diff' => $nextExpectedMatchDiff,
            'pay_dates' => $payDatesFormatted,
            'paid_dates' => $paidDataFormatted,
            'links' => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/' . $bill->id,
                ],
            ],
        ];
    }

    /**
     * Get the data the bill was paid and predict the next expected match.
     *
     * @param Bill $bill
     *
     * @return array
     */
    protected function paidData(Bill $bill): array
    {
        app('log')->debug(sprintf('Now in paidData for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            app('log')->debug('parameters are NULL, return empty array');

            return [
                'paid_dates' => [],
                'next_expected_match' => null,
            ];
        }
        // 2023-07-1 sub one day from the start date to fix a possible bug (see #7704)
        // 2023-07-18 this particular date is used to search for the last paid date.
        // 2023-07-18 the cloned $searchDate is used to grab the correct transactions.
        /** @var Carbon $start */
        $start = clone $this->parameters->get('start');
        $searchStart = clone $start;
        $start->subDay();

        app('log')->debug(sprintf('Parameters are start: %s end: %s', $start->format('Y-m-d'), $this->parameters->get('end')->format('Y-m-d')));
        app('log')->debug(sprintf('Search parameters are: start: %s', $searchStart->format('Y-m-d')));

        /*
         *  Get from database when bill was paid.
         */
        $set = $this->repository->getPaidDatesInRange($bill, $searchStart, $this->parameters->get('end'));
        app('log')->debug(sprintf('Count %d entries in getPaidDatesInRange()', $set->count()));

        /*
         * Grab from array the most recent payment. If none exist, fall back to the start date and pretend *that* was the last paid date.
         */
        app('log')->debug(sprintf('Grab last paid date from function, return %s if it comes up with nothing.', $start->format('Y-m-d')));
        $lastPaidDate = $this->lastPaidDate($set, $start);
        app('log')->debug(sprintf('Result of lastPaidDate is %s', $lastPaidDate->format('Y-m-d')));

        /*
         * The next expected match (nextMatch) is, initially, the bill's date.
         */
        $nextMatch = clone $bill->date;
        /*
         * Diff in months (or other period) between bill start and last paid date or $start.
         */
        $steps = app('navigation')->diffInPeriods($bill->repeat_freq, $bill->skip, $start, $nextMatch);
        $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $steps);

        if ($nextMatch->lt($lastPaidDate)) {
            /*
             * Add another period because it's before the last paid date
             */
            app('log')->debug('Because the last paid date was before our next expected match, add another period.');
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }

        if ($nextMatch->isSameDay($lastPaidDate)) {
            /*
             * Add another period because it's the same day as the last paid date.
             */
            app('log')->debug('Because the last paid date was on the same day as our next expected match, add another day.');
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }
        /*
         * At this point the "next match" is exactly after the last time the bill was paid.
         */
        $result = [];
        foreach ($set as $entry) {
            $result[] = [
                'transaction_group_id' => (int)$entry->transaction_group_id,
                'transaction_journal_id' => (int)$entry->id,
                'date' => $entry->date->format('Y-m-d'),
            ];
        }

        app('log')->debug(sprintf('Next match: %s', $nextMatch->toIso8601String()));

        return [
            'paid_dates' => $result,
            'next_expected_match' => $nextMatch->format('Y-m-d'),
        ];
    }

    /**
     * Returns the latest date in the set, or start when set is empty.
     *
     * @param Collection $dates
     * @param Carbon $default
     *
     * @return Carbon
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

    /**
     * @param Bill $bill
     *
     * @return array
     */
    protected function payDates(Bill $bill): array
    {
        app('log')->debug(sprintf('Now in payDates() for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            app('log')->debug('No start or end date, give empty array.');

            return [];
        }
        app('log')->debug(sprintf('Start: %s, end: %s', $this->parameters->get('start')->format('Y-m-d'), $this->parameters->get('end')->format('Y-m-d')));
        $set = new Collection();
        $currentStart = clone $this->parameters->get('start');
        // 2023-06-23 subDay to fix 7655
        $currentStart->subDay();
        $loop = 0;
        app('log')->debug('start of loop');
        /*
         * De eerste dag van de bill telt sowieso. Vanaf daarna gaan we door tellen.
         * Weekly die start op 01-10
         * 01-10: dit is hem dus.
         * alle
         */


        /*
         * In de eerste week blijft aantal steps hangen op 0.
         * Dus dan krijg je:
         * 1 okt: 0
         * 2 okt: 0
         * 3 okt 0
         * en daarna pas begint-ie te lopen.
         * maar je moet sowieso een periode verder kijken.
         *
         * dus stel je begint op 1 oktober monthly.
         * dan is de eerste hit (want subday) vanaf 30 sept gerekend.
         */
        while ($currentStart <= $this->parameters->get('end')) {
            app('log')->debug(sprintf('Current start is %s', $currentStart->format('Y-m-d')));
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);


            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $this->parameters->get('end')) {
                app('log')->debug('Next expected match is after END, so stop looking');
                break;
            }
            app('log')->debug(sprintf('Next expected match is %s', $nextExpectedMatch->format('Y-m-d')));
            // add to set
            $set->push(clone $nextExpectedMatch);

            // 2023-10
            // for the next loop, go to end of period, THEN add day.
            //$nextExpectedMatch = app('navigation')->endOfPeriod($nextExpectedMatch, $bill->repeat_freq);
            $nextExpectedMatch->addDay();
            $currentStart = clone $nextExpectedMatch;


            $loop++;
            if ($loop > 4) {
                break;
            }
        }
        app('log')->debug('end of loop');
        $simple = $set->map(
            static function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );
        app('log')->debug(sprintf('Found %d pay dates', $set->count()), $simple->toArray());

        return $simple->toArray();
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. That date must be AFTER $date as a sanity check.
     *
     * @param Bill $bill
     * @param Carbon $date
     *
     * @return Carbon
     */
    protected function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        app('log')->debug(sprintf('Now in nextDateMatch(#%d, %s)', $bill->id, $date->format('Y-m-d')));
        $start = clone $bill->date;
        app('log')->debug(sprintf('Bill start date is %s', $start->format('Y-m-d')));
        if ($start->gt($date)) {
            app('log')->debug('Start is after bill start, just return bill start date.');
            return clone $start;
        }

        $steps = app('navigation')->diffInPeriods($bill->repeat_freq, $bill->skip, $start, $date);
        $result = clone $start;
        if ($steps > 0) {
            $steps = $steps - 1;
            app('log')->debug(sprintf('Steps is %d, because addPeriod already adds 1.', $steps));
            $result = app('navigation')->addPeriod($start, $bill->repeat_freq, $steps);
        }
        app('log')->debug(sprintf('Number of steps is %d, result is %s', $steps, $result->format('Y-m-d')));
        return $result;
    }
}
