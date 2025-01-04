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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class BillTransformer
 */
class BillTransformer extends AbstractTransformer
{
    private ExchangeRateConverter $converter;
    private array                 $currencies;
    private TransactionCurrency   $default;
    private array                 $groups;
    private array                 $notes;
    private array                 $paidDates;

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function collectMetaData(Collection $objects): Collection
    {
        $currencies       = [];
        $bills            = [];
        $this->notes      = [];
        $this->groups     = [];
        $this->paidDates  = [];

        /** @var Bill $object */
        foreach ($objects as $object) {
            $id      = $object->transaction_currency_id;
            $bills[] = $object->id;
            $currencies[$id] ??= TransactionCurrency::find($id);
        }
        $this->currencies = $currencies;
        $notes            = Note::whereNoteableType(Bill::class)->whereIn('noteable_id', array_keys($bills))->get();

        /** @var Note $note */
        foreach ($notes as $note) {
            $id               = $note->noteable_id;
            $this->notes[$id] = $note;
        }
        // grab object groups:
        $set              = DB::table('object_groupables')
            ->leftJoin('object_groups', 'object_groups.id', '=', 'object_groupables.object_group_id')
            ->where('object_groupables.object_groupable_type', Bill::class)
            ->get(['object_groupables.*', 'object_groups.title', 'object_groups.order'])
        ;

        /** @var ObjectGroup $entry */
        foreach ($set as $entry) {
            $billId                = (int) $entry->object_groupable_id;
            $id                    = (int) $entry->object_group_id;
            $order                 = $entry->order;
            $this->groups[$billId] = [
                'object_group_id'    => $id,
                'object_group_title' => $entry->title,
                'object_group_order' => $order,
            ];
        }
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $this->default    = app('amount')->getDefaultCurrency();
        $this->converter  = new ExchangeRateConverter();

        // grab all paid dates:
        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $journals     = TransactionJournal::whereIn('bill_id', $bills)
                ->where('date', '>=', $this->parameters->get('start'))
                ->where('date', '<=', $this->parameters->get('end'))
                ->get(['transaction_journals.id', 'transaction_journals.transaction_group_id', 'transaction_journals.date', 'transaction_journals.bill_id'])
            ;
            $journalIds   = $journals->pluck('id')->toArray();

            // grab transactions for amount:
            $set          = Transaction::whereIn('transaction_journal_id', $journalIds)
                ->where('transactions.amount', '<', 0)
                ->get(['transactions.id', 'transactions.transaction_journal_id', 'transactions.amount', 'transactions.foreign_amount', 'transactions.transaction_currency_id', 'transactions.foreign_currency_id'])
            ;
            $transactions = [];

            /** @var Transaction $transaction */
            foreach ($set as $transaction) {
                $journalId                = $transaction->transaction_journal_id;
                $transactions[$journalId] = $transaction->toArray();
            }

            /** @var TransactionJournal $journal */
            foreach ($journals as $journal) {
                app('log')->debug(sprintf('Processing journal #%d', $journal->id));
                $transaction                = $transactions[$journal->id] ?? [];
                $billId                     = (int) $journal->bill_id;
                $currencyId                 = (int) ($transaction['transaction_currency_id'] ?? 0);
                $currencies[$currencyId] ??= TransactionCurrency::find($currencyId);

                // foreign currency
                $foreignCurrencyId          = null;
                $foreignCurrencyCode        = null;
                $foreignCurrencyName        = null;
                $foreignCurrencySymbol      = null;
                $foreignCurrencyDp          = null;
                app('log')->debug('Foreign currency is NULL');
                if (null !== $transaction['foreign_currency_id']) {
                    app('log')->debug(sprintf('Foreign currency is #%d', $transaction['foreign_currency_id']));
                    $foreignCurrencyId     = (int) $transaction['foreign_currency_id'];
                    $currencies[$foreignCurrencyId] ??= TransactionCurrency::find($foreignCurrencyId);
                    $foreignCurrencyCode   = $currencies[$foreignCurrencyId]->code;
                    $foreignCurrencyName   = $currencies[$foreignCurrencyId]->name;
                    $foreignCurrencySymbol = $currencies[$foreignCurrencyId]->symbol;
                    $foreignCurrencyDp     = $currencies[$foreignCurrencyId]->decimal_places;
                }

                $this->paidDates[$billId][] = [
                    'transaction_group_id'            => (string) $journal->id,
                    'transaction_journal_id'          => (string) $journal->transaction_group_id,
                    'date'                            => $journal->date->toAtomString(),
                    'currency_id'                     => $currencies[$currencyId]->id,
                    'currency_code'                   => $currencies[$currencyId]->code,
                    'currency_name'                   => $currencies[$currencyId]->name,
                    'currency_symbol'                 => $currencies[$currencyId]->symbol,
                    'currency_decimal_places'         => $currencies[$currencyId]->decimal_places,
                    'native_currency_id'              => $currencies[$currencyId]->id,
                    'native_currency_code'            => $currencies[$currencyId]->code,
                    'native_currency_symbol'          => $currencies[$currencyId]->symbol,
                    'native_currency_decimal_places'  => $currencies[$currencyId]->decimal_places,
                    'foreign_currency_id'             => $foreignCurrencyId,
                    'foreign_currency_code'           => $foreignCurrencyCode,
                    'foreign_currency_name'           => $foreignCurrencyName,
                    'foreign_currency_symbol'         => $foreignCurrencySymbol,
                    'foreign_currency_decimal_places' => $foreignCurrencyDp,
                    'amount'                          => $transaction['amount'],
                    'foreign_amount'                  => $transaction['foreign_amount'],
                    'native_amount'                   => $this->converter->convert($currencies[$currencyId], $this->default, $journal->date, $transaction['amount']),
                    'foreign_native_amount'           => '' === (string) $transaction['foreign_amount'] ? null : $this->converter->convert(
                        $currencies[$foreignCurrencyId],
                        $this->default,
                        $journal->date,
                        $transaction['foreign_amount']
                    ),
                ];
            }
        }

        return $objects;
    }

    /**
     * Transform the bill.
     */
    public function transform(Bill $bill): array
    {
        $paidData              = $this->paidDates[$bill->id] ?? [];
        $nextExpectedMatch     = $this->nextExpectedMatch($bill, $this->paidDates[$bill->id] ?? []);
        $payDates              = $this->payDates($bill);
        $currency              = $this->currencies[$bill->transaction_currency_id];
        $group                 = $this->groups[$bill->id] ?? null;

        // date for currency conversion
        /** @var null|Carbon $startParam */
        $startParam            = $this->parameters->get('start');

        /** @var null|Carbon $date */
        $date                  = null === $startParam ? today() : clone $startParam;

        $nextExpectedMatchDiff = $this->getNextExpectedMatchDiff($nextExpectedMatch, $payDates);
        $this->converter->summarize();

        return [
            'id'                             => $bill->id,
            'created_at'                     => $bill->created_at->toAtomString(),
            'updated_at'                     => $bill->updated_at->toAtomString(),
            'name'                           => $bill->name,
            'amount_min'                     => app('steam')->bcround($bill->amount_min, $currency->decimal_places),
            'amount_max'                     => app('steam')->bcround($bill->amount_max, $currency->decimal_places),
            'native_amount_min'              => $this->converter->convert($currency, $this->default, $date, $bill->amount_min),
            'native_amount_max'              => $this->converter->convert($currency, $this->default, $date, $bill->amount_max),
            'currency_id'                    => (string) $bill->transaction_currency_id,
            'currency_code'                  => $currency->code,
            'currency_name'                  => $currency->name,
            'currency_symbol'                => $currency->symbol,
            'currency_decimal_places'        => $currency->decimal_places,
            'native_currency_id'             => $this->default->id,
            'native_currency_code'           => $this->default->code,
            'native_currency_name'           => $this->default->name,
            'native_currency_symbol'         => $this->default->symbol,
            'native_currency_decimal_places' => $this->default->decimal_places,
            'date'                           => $bill->date->toAtomString(),
            'end_date'                       => $bill->end_date?->toAtomString(),
            'extension_date'                 => $bill->extension_date?->toAtomString(),
            'repeat_freq'                    => $bill->repeat_freq,
            'skip'                           => $bill->skip,
            'active'                         => $bill->active,
            'order'                          => $bill->order,
            'notes'                          => $this->notes[$bill->id] ?? null,
            'object_group_id'                => $group ? $group['object_group_id'] : null,
            'object_group_order'             => $group ? $group['object_group_order'] : null,
            'object_group_title'             => $group ? $group['object_group_title'] : null,
            'next_expected_match'            => $nextExpectedMatch->toAtomString(),
            'next_expected_match_diff'       => $nextExpectedMatchDiff,
            'pay_dates'                      => $payDates,
            'paid_dates'                     => $paidData,
            'links'                          => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/bills/%d', $bill->id),
                ],
            ],
        ];
    }

    /**
     * Get the data the bill was paid and predict the next expected match.
     */
    protected function nextExpectedMatch(Bill $bill, array $dates): Carbon
    {
        // 2023-07-1 sub one day from the start date to fix a possible bug (see #7704)
        // 2023-07-18 this particular date is used to search for the last paid date.
        // 2023-07-18 the cloned $searchDate is used to grab the correct transactions.

        /** @var null|Carbon $startParam */
        $startParam   = $this->parameters->get('start');

        /** @var null|Carbon $start */
        $start        = null === $startParam ? today() : clone $startParam;
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
            // Add another period because it's the same day as the last paid date.
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }

        return $nextMatch;
    }

    /**
     * Returns the latest date in the set, or start when set is empty.
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

    protected function payDates(Bill $bill): array
    {
        // app('log')->debug(sprintf('Now in payDates() for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            // app('log')->debug('No start or end date, give empty array.');

            return [];
        }
        $set          = new Collection();
        $currentStart = clone $this->parameters->get('start');
        // 2023-06-23 subDay to fix 7655
        $currentStart->subDay();
        $loop         = 0;
        while ($currentStart <= $this->parameters->get('end')) {
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $this->parameters->get('end')) {
                break;
            }
            // add to set
            $set->push(clone $nextExpectedMatch);
            $nextExpectedMatch->addDay();
            $currentStart      = clone $nextExpectedMatch;
            ++$loop;
            if ($loop > 4) {
                break;
            }
        }
        $simple       = $set->map( // @phpstan-ignore-line
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
     * TODO this method is bad compared to the v1 one.
     */
    protected function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        // app('log')->debug(sprintf('Now in nextDateMatch(%d, %s)', $bill->id, $date->format('Y-m-d')));
        $start = clone $bill->date;
        // app('log')->debug(sprintf('Bill start date is %s', $start->format('Y-m-d')));
        while ($start < $date) {
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }

        // app('log')->debug(sprintf('End of loop, bill start date is now %s', $start->format('Y-m-d')));

        return $start;
    }

    private function getNextExpectedMatchDiff(Carbon $next, array $dates): string
    {
        if ($next->isToday()) {
            return trans('firefly.today');
        }
        $current = $dates[0] ?? null;
        if (null === $current) {
            return trans('firefly.not_expected_period');
        }
        $carbon  = new Carbon($current);

        return $carbon->diffForHumans(today(config('app.timezone')), CarbonInterface::DIFF_RELATIVE_TO_NOW);
    }
}
