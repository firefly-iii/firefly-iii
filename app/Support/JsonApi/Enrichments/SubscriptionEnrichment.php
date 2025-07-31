<?php

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Models\BillDateCalculator;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionEnrichment implements EnrichmentInterface
{
    private User                $user;
    private UserGroup           $userGroup;
    private Collection          $collection;
    private bool                $convertToNative = false;
    private ?Carbon             $start           = null;
    private ?Carbon             $end             = null;
    private array               $subscriptionIds = [];
    private array               $objectGroups    = [];
    private array               $mappedObjects   = [];
    private array               $paidDates       = [];
    private array               $notes           = [];
    private array               $payDates        = [];
    private TransactionCurrency $nativeCurrency;
    private BillDateCalculator  $calculator;

    public function enrich(Collection $collection): Collection
    {
        $this->calculator = app(BillDateCalculator::class);
        $this->collection = $collection;
        $this->collectSubscriptionIds();
        $this->collectNotes();
        $this->collectObjectGroups();
        $this->collectPaidDates();
        $this->collectPayDates();

        $notes            = $this->notes;
        $objectGroups     = $this->objectGroups;
        $paidDates        = $this->paidDates;
        $payDates         = $this->payDates;
        $this->collection = $this->collection->map(function (Bill $item) use ($notes, $objectGroups, $paidDates, $payDates) {
            $id       = (int)$item->id;
            $currency = $item->transactionCurrency;
            $nem      = $this->getNextExpectedMatch($payDates[$id] ?? []);

            $meta    = [
                'notes'              => null,
                'object_group_id'    => null,
                'object_group_title' => null,
                'object_group_order' => null,
                'last_paid_date'     => $this->getLastPaidDate($paidDates[$id] ?? []),
                'paid_dates'         => $this->filterPaidDates($paidDates[$id] ?? []),
                'pay_dates'          => $payDates[$id] ?? [],
                'nem'                => $nem,
                'nem_diff'           => $this->getNextExpectedMatchDiff($nem, $payDates[$id] ?? [])
            ];
            $amounts = [
                'amount_min' => Steam::bcround($item->amount_min, $currency->decimal_places),
                'amount_max' => Steam::bcround($item->amount_max, $currency->decimal_places),
                'average'    => Steam::bcround(bcdiv(bcadd($item->amount_min, $item->amount_max), '2'), $currency->decimal_places),
            ];

            // add object group if available
            if (array_key_exists($id, $this->mappedObjects)) {
                $key                        = $this->mappedObjects[$id];
                $meta['object_group_id']    = $objectGroups[$key]['id'];
                $meta['object_group_title'] = $objectGroups[$key]['title'];
                $meta['object_group_order'] = $objectGroups[$key]['order'];
            }

            // Add notes if available.
            if (array_key_exists($item->id, $notes)) {
                $meta['notes'] = $notes[$item->id];
            }

            // Convert amounts to native currency if needed
            if ($this->convertToNative && $item->currency_id !== $this->nativeCurrency->id) {
                $converter          = new ExchangeRateConverter();
                $amounts            = [
                    'amount_min' => Steam::bcround($converter->convert($item->transactionCurrency, $this->nativeCurrency, today(), $item->amount_min), $this->nativeCurrency->decimal_places),
                    'amount_max' => Steam::bcround($converter->convert($item->transactionCurrency, $this->nativeCurrency, today(), $item->amount_max), $this->nativeCurrency->decimal_places),
                ];
                $amounts['average'] = Steam::bcround(bcdiv(bcadd($amounts['amount_min'], $amounts['amount_max']), '2'), $this->nativeCurrency->decimal_places);
            }
            $item->amounts = $amounts;
            $item->meta    = $meta;

            return $item;
        });

        return $collection;
    }

    public function enrichSingle(array|Model $model): array|Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection([$model]);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->subscriptionIds)
                     ->whereNotNull('notes.text')
                     ->where('notes.text', '!=', '')
                     ->where('noteable_type', Bill::class)->get(['notes.noteable_id', 'notes.text'])->toArray();
        foreach ($notes as $note) {
            $this->notes[(int)$note['noteable_id']] = (string)$note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    public function setUser(User $user): void
    {
        $this->user      = $user;
        $this->userGroup = $user->userGroup;
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    public function setConvertToNative(bool $convertToNative): void
    {
        $this->convertToNative = $convertToNative;
    }

    public function setNative(TransactionCurrency $nativeCurrency): void
    {
        $this->nativeCurrency = $nativeCurrency;
    }

    private function collectSubscriptionIds(): void
    {
        /** @var Bill $bill */
        foreach ($this->collection as $bill) {
            $this->subscriptionIds[] = (int)$bill->id;
        }
        $this->subscriptionIds = array_unique($this->subscriptionIds);
    }

    private function collectObjectGroups(): void
    {
        $set = DB::table('object_groupables')
                 ->whereIn('object_groupable_id', $this->subscriptionIds)
                 ->where('object_groupable_type', Bill::class)
                 ->get(['object_groupable_id', 'object_group_id']);

        $ids    = array_unique($set->pluck('object_group_id')->toArray());

        foreach ($set as $entry) {
            $this->mappedObjects[(int)$entry->object_groupable_id] = (int)$entry->object_group_id;
        }

        $groups = ObjectGroup::whereIn('id', $ids)->get(['id', 'title', 'order'])->toArray();
        foreach ($groups as $group) {
            $group['id']                           = (int)$group['id'];
            $group['order']                        = (int)$group['order'];
            $this->objectGroups[(int)$group['id']] = $group;
        }
    }

    private function collectPaidDates(): void
    {
        Log::debug('Now in collectPaidDates for bills');
        if (null === $this->start || null === $this->end) {
            Log::debug('Parameters are NULL, return empty array');
            $this->paidDates = [];
            return;
        }

        // 2023-07-1 sub one day from the start date to fix a possible bug (see #7704)
        // 2023-07-18 this particular date is used to search for the last paid date.
        // 2023-07-18 the cloned $searchDate is used to grab the correct transactions.
        /** @var Carbon $start */
        $start       = clone $this->start;
        $searchStart = clone $start;
        $start->subDay();

        /** @var Carbon $end */
        $end       = clone $this->end;
        $searchEnd = clone $end;

        // move the search dates to the start of the day.
        $searchStart->startOfDay();
        $searchEnd->endOfDay();

        Log::debug(sprintf('Parameters are start: %s, end: %s', $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));
        Log::debug(sprintf('Search parameters are: start: %s, end: %s', $searchStart->format('Y-m-d H:i:s'), $searchEnd->format('Y-m-d H:i:s')));

        // Get from database when bills were paid.
        $set = $this->user->transactionJournals()
                          ->whereIn('bill_id', $this->subscriptionIds)
                          ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                          ->leftJoin('transaction_currencies AS currency', 'currency.id', '=', 'transactions.transaction_currency_id')
                          ->leftJoin('transaction_currencies AS foreign_currency', 'foreign_currency.id', '=', 'transactions.foreign_currency_id')
                          ->where('transactions.amount', '>', 0)
                          ->before($searchEnd)->after($searchStart)->get(
                [
                    'transaction_journals.id',
                    'transaction_journals.date',
                    'transaction_journals.transaction_group_id',
                    'transactions.transaction_currency_id',
                    'currency.code AS transaction_currency_code',
                    'currency.decimal_places AS transaction_currency_decimal_places',
                    'transactions.foreign_currency_id',
                    'foreign_currency.code AS foreign_currency_code',
                    'foreign_currency.decimal_places AS foreign_currency_decimal_places',
                    'transactions.amount',
                    'transactions.foreign_amount',
                ]
            );
        Log::debug(sprintf('Count %d entries in set', $set->count()));

        // for each bill, do a loop.
        /** @var Bill $subscription */
        foreach ($this->collection as $subscription) {
            // Grab from array the most recent payment. If none exist, fall back to the start date and pretend *that* was the last paid date.
            Log::debug(sprintf('Grab last paid date from function, return %s if it comes up with nothing.', $start->format('Y-m-d')));
            $lastPaidDate = $this->lastPaidDate($subscription, $set, $start);
            Log::debug(sprintf('Result of lastPaidDate is %s', $lastPaidDate->format('Y-m-d')));

            // At this point the "next match" is exactly after the last time the bill was paid.
            $result = [];
            foreach ($set as $entry) {
                $array = [
                    'transaction_group_id'    => (string)$entry->transaction_group_id,
                    'transaction_journal_id'  => (string)$entry->id,
                    'date'                    => $entry->date->toAtomString(),
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
            $this->paidDates[$subscription->id] = $result;
        }

    }

    public function setStart(?Carbon $start): void
    {
        $this->start = $start;
    }

    public function setEnd(?Carbon $end): void
    {
        $this->end = $end;
    }

    /**
     * Returns the latest date in the set, or start when set is empty.
     */
    protected function lastPaidDate(Bill $subscription, Collection $dates, Carbon $default): Carbon
    {
        $filtered = $dates->filter(function (TransactionJournal $journal) use ($subscription) {
            return $journal->bill_id === $subscription->id;
        });
        if (0 === $filtered->count()) {
            return $default;
        }

        $latest = $filtered->first()->date;

        /** @var TransactionJournal $journal */
        foreach ($filtered as $journal) {
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

    private function collectPayDates(): void
    {
        /** @var Bill $subscription */
        foreach ($this->collection as $subscription) {
            $id                = (int)$subscription->id;
            $lastPaidDate      = $this->getLastPaidDate($paidDates[$id] ?? []);
            $payDates          = $this->calculator->getPayDates($this->start, $this->end, $subscription->date, $subscription->repeat_freq, $subscription->skip, $lastPaidDate);
            $payDatesFormatted = [];
            foreach ($payDates as $string) {
                $date = Carbon::createFromFormat('!Y-m-d', $string, config('app.timezone'));
                if (!$date instanceof Carbon) {
                    $date = today(config('app.timezone'));
                }
                $payDatesFormatted[] = $date->toAtomString();
            }
            $this->payDates[$id] = $payDatesFormatted;
        }
    }

    private function filterPaidDates(array $entries): array
    {
        return array_map(function (array $entry) {
            unset($entry['date_object']);
            return $entry;
        }, $entries);
    }

    private function getNextExpectedMatch(array $payDates): ?Carbon
    {
        // next expected match
        $nem          = null;
        $firstPayDate = $payDates[0] ?? null;

        if (null !== $firstPayDate) {
            $nemDate = Carbon::parse($firstPayDate, config('app.timezone'));
            if (!$nemDate instanceof Carbon) {
                $nemDate = today(config('app.timezone'));
            }
            $nem = $nemDate;

            // nullify again when it's outside the current view range.
            if (
                (null !== $this->start && $nemDate->lt($this->start))
                || (null !== $this->end && $nemDate->gt($this->end))
            ) {
                $nem          = null;
                $nemDate      = null;
                $firstPayDate = null;
            }
        }
        return $nem;
    }

    private function getNextExpectedMatchDiff(?Carbon $nem, array $payDates): string
    {
        if (null === $nem) {
            return trans('firefly.not_expected_period');
        }
        $nemDiff = trans('firefly.not_expected_period');;
        // converting back and forth is bad code but OK.
        if ($nem->isToday()) {
            $nemDiff = trans('firefly.today');
        }

        $current = $payDates[0] ?? null;
        if (null !== $current && !$nem->isToday()) {
            $temp2 = Carbon::parse($current, config('app.timezone'));
            if (!$temp2 instanceof Carbon) {
                $temp2 = today(config('app.timezone'));
            }
            $nemDiff = trans('firefly.bill_expected_date', ['date' => $temp2->diffForHumans(today(config('app.timezone')), CarbonInterface::DIFF_RELATIVE_TO_NOW)]);
        }
        unset($temp2);

        return $nemDiff;
    }
}

