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
use FireflyIII\Support\Facades\Amount;
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
    private bool                $convertToPrimary;
    private ?Carbon             $start           = null;
    private ?Carbon             $end             = null;
    private array               $subscriptionIds = [];
    private array               $objectGroups    = [];
    private array               $mappedObjects   = [];
    private array               $paidDates       = [];
    private array               $notes           = [];
    private array               $payDates        = [];
    private TransactionCurrency $primaryCurrency;
    private BillDateCalculator  $calculator;

    public function __construct()
    {
        $this->convertToPrimary = Amount::convertToPrimary();
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
    }

    public function enrich(Collection $collection): Collection
    {
        Log::debug(sprintf('%s(%s item(s))', __METHOD__, $collection->count()));
        $this->calculator = app(BillDateCalculator::class);
        $this->collection = $collection;
        $this->collectSubscriptionIds();
        $this->collectNotes();
        $this->collectObjectGroups();
        $this->collectPaidDates();
        $this->collectPayDates();


        // TODO clean me up.

        $notes            = $this->notes;
        $objectGroups     = $this->objectGroups;
        $paidDates        = $this->paidDates;
        $payDates         = $this->payDates;
        $this->collection = $this->collection->map(function (Bill $item) use ($notes, $objectGroups, $paidDates, $payDates) {
            $id            = (int) $item->id;
            $currency      = $item->transactionCurrency;
            $nem           = $this->getNextExpectedMatch($payDates[$id] ?? []);

            $meta          = [
                'notes'              => null,
                'object_group_id'    => null,
                'object_group_title' => null,
                'object_group_order' => null,
                'last_paid_date'     => $this->getLastPaidDate($paidDates[$id] ?? []),
                'paid_dates'         => $this->filterPaidDates($paidDates[$id] ?? []),
                'pay_dates'          => $payDates[$id] ?? [],
                'nem'                => $nem,
                'nem_diff'           => $this->getNextExpectedMatchDiff($nem, $payDates[$id] ?? []),
            ];
            $amounts       = [
                'amount_min'    => Steam::bcround($item->amount_min, $currency->decimal_places),
                'amount_max'    => Steam::bcround($item->amount_max, $currency->decimal_places),
                'average'       => Steam::bcround(bcdiv(bcadd($item->amount_min, $item->amount_max), '2'), $currency->decimal_places),
                'pc_amount_min' => null,
                'pc_amount_max' => null,
                'pc_average'    => null,
            ];
            if ($this->convertToPrimary && $currency->id === $this->primaryCurrency->id) {
                $amounts['pc_amount_min'] = $amounts['amount_min'];
                $amounts['pc_amount_max'] = $amounts['amount_max'];
                $amounts['pc_average']    = $amounts['average'];
            }
            if ($this->convertToPrimary && $currency->id !== $this->primaryCurrency->id) {
                $amounts['pc_amount_min'] = Steam::bcround($item->native_amount_min, $this->primaryCurrency->decimal_places);
                $amounts['pc_amount_max'] = Steam::bcround($item->native_amount_max, $this->primaryCurrency->decimal_places);
                $amounts['pc_average']    = Steam::bcround(bcdiv(bcadd($item->native_amount_min, $item->native_amount_max), '2'), $this->primaryCurrency->decimal_places);
            }

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
            ->where('noteable_type', Bill::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int) $note['noteable_id']] = (string) $note['text'];
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

    private function collectSubscriptionIds(): void
    {
        /** @var Bill $bill */
        foreach ($this->collection as $bill) {
            $this->subscriptionIds[] = (int) $bill->id;
        }
        $this->subscriptionIds = array_unique($this->subscriptionIds);
    }

    private function collectObjectGroups(): void
    {
        $set    = DB::table('object_groupables')
            ->whereIn('object_groupable_id', $this->subscriptionIds)
            ->where('object_groupable_type', Bill::class)
            ->get(['object_groupable_id', 'object_group_id'])
        ;

        $ids    = array_unique($set->pluck('object_group_id')->toArray());

        foreach ($set as $entry) {
            $this->mappedObjects[(int) $entry->object_groupable_id] = (int) $entry->object_group_id;
        }

        $groups = ObjectGroup::whereIn('id', $ids)->get(['id', 'title', 'order'])->toArray();
        foreach ($groups as $group) {
            $group['id']                            = (int) $group['id'];
            $group['order']                         = (int) $group['order'];
            $this->objectGroups[(int) $group['id']] = $group;
        }
    }

    private function collectPaidDates(): void
    {
        $this->paidDates = [];
        Log::debug('Now in collectPaidDates for bills');
        if (null === $this->start || null === $this->end) {
            Log::debug('Parameters are NULL, set empty array');

            return;
        }

        // 2023-07-1 sub one day from the start date to fix a possible bug (see #7704)
        // 2023-07-18 this particular date is used to search for the last paid date.
        // 2023-07-18 the cloned $searchDate is used to grab the correct transactions.
        /** @var Carbon $start */
        $start           = clone $this->start;
        $searchStart     = clone $start;
        $start->subDay();

        /** @var Carbon $end */
        $end             = clone $this->end;
        $searchEnd       = clone $end;

        // move the search dates to the start of the day.
        $searchStart->startOfDay();
        $searchEnd->endOfDay();

        Log::debug(sprintf('Search parameters are: start: %s, end: %s', $searchStart->format('Y-m-d H:i:s'), $searchEnd->format('Y-m-d H:i:s')));

        // Get from database when bills were paid.
        $set             = $this->user->transactionJournals()
            ->whereIn('bill_id', $this->subscriptionIds)
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->leftJoin('transaction_currencies AS currency', 'currency.id', '=', 'transactions.transaction_currency_id')
            ->leftJoin('transaction_currencies AS foreign_currency', 'foreign_currency.id', '=', 'transactions.foreign_currency_id')
            ->where('transactions.amount', '>', 0)
            ->before($searchEnd)->after($searchStart)->get(
                [
                    'transaction_journals.id',
                    'transaction_journals.date',
                    'transaction_journals.bill_id',
                    'transaction_journals.transaction_group_id',
                    'transactions.transaction_currency_id',
                    'currency.code AS transaction_currency_code',
                    'currency.symbol AS transaction_currency_symbol',
                    'currency.decimal_places AS transaction_currency_decimal_places',
                    'transactions.foreign_currency_id',
                    'foreign_currency.code AS foreign_currency_code',
                    'foreign_currency.symbol AS foreign_currency_symbol',
                    'foreign_currency.decimal_places AS foreign_currency_decimal_places',
                    'transactions.amount',
                    'transactions.foreign_amount',
                ]
            )
        ;
        Log::debug(sprintf('Count %d entries in set', $set->count()));

        // for each bill, do a loop.
        $converter       = new ExchangeRateConverter();

        /** @var Bill $subscription */
        foreach ($this->collection as $subscription) {
            // Grab from array the most recent payment. If none exist, fall back to the start date and pretend *that* was the last paid date.
            Log::debug(sprintf('Grab last paid date from function, return %s if it comes up with nothing.', $start->format('Y-m-d')));
            $lastPaidDate                             = $this->lastPaidDate($subscription, $set, $start);
            Log::debug(sprintf('Result of lastPaidDate is %s', $lastPaidDate->format('Y-m-d')));

            // At this point the "next match" is exactly after the last time the bill was paid.
            $result                                   = [];
            $filtered                                 = $set->filter(function (TransactionJournal $journal) use ($subscription) {
                return (int) $journal->bill_id === (int) $subscription->id;
            });
            foreach ($filtered as $entry) {
                $array    = [
                    'transaction_group_id'                    => (string) $entry->transaction_group_id,
                    'transaction_journal_id'                  => (string) $entry->id,
                    'date'                                    => $entry->date->toAtomString(),
                    'date_object'                             => $entry->date,
                    'subscription_id'                         => (string) $entry->bill_id,
                    'currency_id'                             => (string) $entry->transaction_currency_id,
                    'currency_code'                           => $entry->transaction_currency_code,
                    'currency_symbol'                         => $entry->transaction_currency_symbol,
                    'currency_decimal_places'                 => $entry->transaction_currency_decimal_places,
                    'primary_currency_id'                     => (string) $this->primaryCurrency->id,
                    'primary_currency_code'                   => $this->primaryCurrency->code,
                    'primary_currency_symbol'                 => $this->primaryCurrency->symbol,
                    'primary_currency_decimal_places'         => $this->primaryCurrency->decimal_places,
                    'amount'                                  => Steam::bcround($entry->amount, $entry->transaction_currency_decimal_places),
                    'pc_amount'                               => null,
                    'foreign_amount'                          => null,
                    'pc_foreign_amount'                       => null,

                ];
                if (null !== $entry->foreign_amount && null !== $entry->foreign_currency_code) {
                    $array['foreign_currency_id']             = (string) $entry->foreign_currency_id;
                    $array['foreign_currency_code']           = $entry->foreign_currency_code;
                    $array['foreign_currency_symbol']         = $entry->foreign_currency_symbol;
                    $array['foreign_currency_decimal_places'] = $entry->foreign_currency_decimal_places;
                    $array['foreign_amount']                  = Steam::bcround($entry->foreign_amount, $entry->foreign_currency_decimal_places);
                }
                // convert to primary, but is already primary.
                if ($this->convertToPrimary && (int) $entry->transaction_currency_id === $this->primaryCurrency->id) {
                    $array['pc_amount'] = $array['amount'];
                }
                // convert to primary, but is NOT already primary.
                if ($this->convertToPrimary && (int) $entry->transaction_currency_id !== $this->primaryCurrency->id) {
                    $array['pc_amount'] = $converter->convert($entry->transactionCurrency, $this->primaryCurrency, $entry->date, $entry->amount);
                }
                // convert to primary, but foreign is already primary.
                if ($this->convertToPrimary && (int) $entry->foreign_currency_id === $this->primaryCurrency->id) {
                    $array['pc_foreign_amount'] = $array['foreign_amount'];
                }
                // convert to primary, but foreign is NOT already primary.
                if ($this->convertToPrimary && null !== $entry->foreign_currency_id && (int) $entry->foreign_currency_id !== $this->primaryCurrency->id) {
                    // TODO this is very database intensive.
                    $foreignCurrency            = TransactionCurrency::find($entry->foreign_currency_id);
                    $array['pc_foreign_amount'] = $converter->convert($foreignCurrency, $this->primaryCurrency, $entry->date, $entry->amount);
                }
                $result[] = $array;
            }
            $this->paidDates[(int) $subscription->id] = $result;
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
            return (int) $journal->bill_id === (int) $subscription->id;
        });
        Log::debug(sprintf('Filtered down from %d to %d entries for bill #%d.', $dates->count(), $filtered->count(), $subscription->id));
        if (0 === $filtered->count()) {
            return $default;
        }

        $latest   = $filtered->first()->date;

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
        // Log::debug('getLastPaidDate()');
        $return = null;
        foreach ($paidData as $entry) {
            if (null !== $return) {
                /** @var Carbon $current */
                $current = $entry['date_object'];
                if ($current->gt($return)) {
                    $return = clone $current;
                }
                Log::debug(sprintf('[a] Last paid date is: %s', $return->format('Y-m-d')));
            }
            if (null === $return) {
                /** @var Carbon $return */
                $return = $entry['date_object'];
                Log::debug(sprintf('[b] Last paid date is: %s', $return->format('Y-m-d')));
            }
        }
        Log::debug(sprintf('[c] Last paid date is: "%s"', $return?->format('Y-m-d')));

        return $return;
    }

    private function collectPayDates(): void
    {
        if (null === $this->start || null === $this->end) {
            Log::debug('Parameters are NULL, set empty array');

            return;
        }

        /** @var Bill $subscription */
        foreach ($this->collection as $subscription) {
            $id                  = (int) $subscription->id;
            $lastPaidDate        = $this->getLastPaidDate($paidDates[$id] ?? []);
            $payDates            = $this->calculator->getPayDates($this->start, $this->end, $subscription->date, $subscription->repeat_freq, $subscription->skip, $lastPaidDate);
            $payDatesFormatted   = [];
            foreach ($payDates as $string) {
                $date                = Carbon::createFromFormat('!Y-m-d', $string, config('app.timezone'));
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
            $nem     = $nemDate;

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
        $nemDiff = trans('firefly.not_expected_period');
        // converting back and forth is bad code but OK.
        if ($nem->isToday()) {
            $nemDiff = trans('firefly.today');
        }

        $current = $payDates[0] ?? null;
        if (null !== $current && !$nem->isToday()) {
            $temp2   = Carbon::parse($current, config('app.timezone'));
            if (!$temp2 instanceof Carbon) {
                $temp2 = today(config('app.timezone'));
            }
            $nemDiff = trans('firefly.bill_expected_date', ['date' => $temp2->diffForHumans(today(config('app.timezone')), CarbonInterface::DIFF_RELATIVE_TO_NOW)]);
        }
        unset($temp2);

        return $nemDiff;
    }
}
