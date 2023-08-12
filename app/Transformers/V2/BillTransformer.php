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

namespace FireflyIII\Transformers\V2;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Log;

/**
 * Class BillTransformer
 */
class BillTransformer extends AbstractTransformer
{
    private array                   $currencies;
    private TransactionCurrency     $default;
    private array                   $groups;
    private array                   $notes;
    private array                   $paidDates;
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
     * @inheritDoc
     */
    public function collectMetaData(Collection $objects): void
    {
        $currencies      = [];
        $bills           = [];
        $this->notes     = [];
        $this->groups    = [];
        $this->paidDates = [];


        // start with currencies:
        /** @var Bill $object */
        foreach ($objects as $object) {
            $id              = (int)$object->transaction_currency_id;
            $bills[]         = (int)$object->id;
            $currencies[$id] = $currencies[$id] ?? TransactionCurrency::find($id);
        }
        $this->currencies = $currencies;

        // continue with notes
        $notes = Note::whereNoteableType(Bill::class)->whereIn('noteable_id', array_keys($bills))->get();
        /** @var Note $note */
        foreach ($notes as $note) {
            $id               = (int)$note->noteable_id;
            $this->notes[$id] = $note;
        }
        // grab object groups:
        $set = DB::table('object_groupables')
                 ->leftJoin('object_groups', 'object_groups.id', '=', 'object_groupables.object_group_id')
                 ->where('object_groupables.object_groupable_type', Bill::class)
                 ->get(['object_groupables.*', 'object_groups.title', 'object_groups.order']);
        /** @var ObjectGroup $entry */
        foreach ($set as $entry) {
            $billId                = (int)$entry->object_groupable_id;
            $id                    = (int)$entry->object_group_id;
            $order                 = (int)$entry->order;
            $this->groups[$billId] = [
                'object_group_id'    => $id,
                'object_group_title' => $entry->title,
                'object_group_order' => $order,
            ];

        }
        $this->default = app('amount')->getDefaultCurrency();

        // grab all paid dates:
        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $journals = TransactionJournal::whereIn('bill_id', $bills)
                                          ->where('date', '>=', $this->parameters->get('start'))
                                          ->where('date', '<=', $this->parameters->get('end'))
                                          ->get(['transaction_journals.id', 'transaction_journals.transaction_group_id', 'transaction_journals.date', 'transaction_journals.bill_id']);
            /** @var TransactionJournal $journal */
            foreach ($journals as $journal) {
                $billId                     = (int)$journal->bill_id;
                $this->paidDates[$billId][] = [
                    'transaction_group_id'   => (string)$journal->id,
                    'transaction_journal_id' => (string)$journal->transaction_group_id,
                    'date'                   => $journal->date->toAtomString(),
                ];
            }
        }
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
        $paidData              = $this->paidDates[(int)$bill->id] ?? [];
        $nextExpectedMatch     = $this->nextExpectedMatch($bill, $this->paidDates[(int)$bill->id] ?? []);
        $payDates              = $this->payDates($bill);
        $currency              = $this->currencies[(int)$bill->transaction_currency_id];
        $group                 = $this->groups[(int)$bill->id] ?? null;
        $nextExpectedMatchDiff = $this->getNextExpectedMatchDiff($nextExpectedMatch, $payDates);
        return [
            'id'                       => (int)$bill->id,
            'created_at'               => $bill->created_at->toAtomString(),
            'updated_at'               => $bill->updated_at->toAtomString(),
            'name'                     => $bill->name,
            'amount_min'               => app('steam')->bcround($bill->amount_min, $currency->decimal_places),
            'amount_max'               => app('steam')->bcround($bill->amount_max, $currency->decimal_places),
            'currency_id'              => (string)$bill->transaction_currency_id,
            'currency_code'            => $currency->code,
            'currency_symbol'          => $currency->symbol,
            'currency_decimal_places'  => (int)$currency->decimal_places,
            'date'                     => $bill->date->toAtomString(),
            'end_date'                 => $bill->end_date?->toAtomString(),
            'extension_date'           => $bill->extension_date?->toAtomString(),
            'repeat_freq'              => $bill->repeat_freq,
            'skip'                     => (int)$bill->skip,
            'active'                   => $bill->active,
            'order'                    => (int)$bill->order,
            'notes'                    => $this->notes[(int)$bill->id] ?? null,
            'object_group_id'          => $group ? $group['object_group_id'] : null,
            'object_group_order'       => $group ? $group['object_group_order'] : null,
            'object_group_title'       => $group ? $group['object_group_title'] : null,
            'next_expected_match'      => $nextExpectedMatch->toAtomString(),
            'next_expected_match_diff' => $nextExpectedMatchDiff,
            'pay_dates'                => $payDates,
            'paid_dates'               => $paidData,
            'links'                    => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/bills/%d', $bill->id),
                ],
            ],
        ];
    }

    /**
     * Get the data the bill was paid and predict the next expected match.
     *
     * @param Bill  $bill
     * @param array $dates
     *
     * @return Carbon
     */
    protected function nextExpectedMatch(Bill $bill, array $dates): Carbon
    {
        // 2023-07-1 sub one day from the start date to fix a possible bug (see #7704)
        // 2023-07-18 this particular date is used to search for the last paid date.
        // 2023-07-18 the cloned $searchDate is used to grab the correct transactions.
        /** @var Carbon $start */
        $start = clone $this->parameters->get('start');
        $start->subDay();

        $lastPaidDate = $this->lastPaidDate($dates, $start);
        $nextMatch    = clone $bill->date;
        while ($nextMatch < $lastPaidDate) {
            /*
             * As long as this date is smaller than the last time the bill was paid, keep jumping ahead.
             * For example: 1 jan, 1 feb, etc.
             */
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }
        if ($nextMatch->isSameDay($lastPaidDate)) {
            /*
             * Add another period because it's the same day as the last paid date.
             */
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }
        return $nextMatch;
    }

    /**
     * Returns the latest date in the set, or start when set is empty.
     *
     * @param Collection $dates
     * @param Carbon     $default
     *
     * @return Carbon
     */
    protected function lastPaidDate(array $dates, Carbon $default): Carbon
    {
        if (0 === count($dates)) {
            return $default;
        }
        $latest = $dates[0]['date'];
        /** @var array $row */
        foreach ($dates as $row) {
            $carbon = new Carbon($row['date']);
            if ($carbon->gte($latest)) {
                $latest = $row['date'];
            }
        }

        return new Carbon($latest);
    }

    /**
     * @param Bill $bill
     *
     * @return array
     */
    protected function payDates(Bill $bill): array
    {
        //Log::debug(sprintf('Now in payDates() for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            //Log::debug('No start or end date, give empty array.');

            return [];
        }
        $set          = new Collection();
        $currentStart = clone $this->parameters->get('start');
        // 2023-06-23 subDay to fix 7655
        $currentStart->subDay();
        $loop = 0;
        while ($currentStart <= $this->parameters->get('end')) {
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $this->parameters->get('end')) {
                break;
            }
            // add to set
            $set->push(clone $nextExpectedMatch);
            $nextExpectedMatch->addDay();
            $currentStart = clone $nextExpectedMatch;
            $loop++;
            if ($loop > 4) {
                break;
            }
        }
        $simple = $set->map(
            static function (Carbon $date) {
                return $date->toAtomString();
            }
        );

        return $simple->toArray();
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return Carbon
     */
    protected function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        //Log::debug(sprintf('Now in nextDateMatch(%d, %s)', $bill->id, $date->format('Y-m-d')));
        $start = clone $bill->date;
        //Log::debug(sprintf('Bill start date is %s', $start->format('Y-m-d')));
        while ($start < $date) {
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }

        //Log::debug(sprintf('End of loop, bill start date is now %s', $start->format('Y-m-d')));

        return $start;
    }

    /**
     * @param Carbon $next
     * @param array  $dates
     *
     * @return string
     */
    private function getNextExpectedMatchDiff(Carbon $next, array $dates): string
    {
        if ($next->isToday()) {
            return trans('firefly.today');
        }
        $current = $dates[0] ?? null;
        if (null === $current) {
            return trans('firefly.not_expected_period');
        }
        $carbon = new Carbon($current);
        return $carbon->diffForHumans(today(config('app.timezone')), CarbonInterface::DIFF_RELATIVE_TO_NOW);
    }


}
