<?php

/**
 * BillRepository.php
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

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\BillFactory;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\Rule;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use FireflyIII\Services\Internal\Destroy\BillDestroyService;
use FireflyIII\Services\Internal\Update\BillUpdateService;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class BillRepository.
 */
class BillRepository implements BillRepositoryInterface
{
    use CreatesObjectGroups;

    private User $user;

    public function billEndsWith(string $query, int $limit): Collection
    {
        $search = $this->user->bills();
        if ('' !== $query) {
            $search->whereLike('name', sprintf('%%%s', $query));
        }
        $search->orderBy('name', 'ASC')
            ->where('active', true)
        ;

        return $search->take($limit)->get();
    }

    public function billStartsWith(string $query, int $limit): Collection
    {
        $search = $this->user->bills();
        if ('' !== $query) {
            $search->whereLike('name', sprintf('%s%%', $query));
        }
        $search->orderBy('name', 'ASC')
            ->where('active', true)
        ;

        return $search->take($limit)->get();
    }

    /**
     * Correct order of piggies in case of issues.
     */
    public function correctOrder(): void
    {
        $set     = $this->user->bills()->orderBy('order', 'ASC')->get();
        $current = 1;
        foreach ($set as $bill) {
            if ($bill->order !== $current) {
                $bill->order = $current;
                $bill->save();
            }
            ++$current;
        }
    }

    public function destroy(Bill $bill): bool
    {
        /** @var BillDestroyService $service */
        $service = app(BillDestroyService::class);
        $service->destroy($bill);

        return true;
    }

    public function destroyAll(): void
    {
        Log::channel('audit')->info('Delete all bills through destroyAll');
        $this->user->bills()->delete();
    }

    /**
     * Find bill by parameters.
     */
    public function findBill(?int $billId, ?string $billName): ?Bill
    {
        if (null !== $billId) {
            $searchResult = $this->find($billId);
            if (null !== $searchResult) {
                app('log')->debug(sprintf('Found bill based on #%d, will return it.', $billId));

                return $searchResult;
            }
        }
        if (null !== $billName) {
            $searchResult = $this->findByName($billName);
            if (null !== $searchResult) {
                app('log')->debug(sprintf('Found bill based on "%s", will return it.', $billName));

                return $searchResult;
            }
        }
        app('log')->debug('Found nothing');

        return null;
    }

    /**
     * Find a bill by ID.
     */
    public function find(int $billId): ?Bill
    {
        return $this->user->bills()->find($billId);
    }

    /**
     * Find a bill by name.
     */
    public function findByName(string $name): ?Bill
    {
        return $this->user->bills()->where('name', $name)->first(['bills.*']);
    }

    /**
     * Get all attachments.
     */
    public function getAttachments(Bill $bill): Collection
    {
        $set  = $bill->attachments()->get();

        /** @var \Storage $disk */
        $disk = \Storage::disk('upload');

        return $set->each(
            static function (Attachment $attachment) use ($disk) {
                $notes                   = $attachment->notes()->first();
                $attachment->file_exists = $disk->exists($attachment->fileName());
                $attachment->notes_text  = null !== $notes ? $notes->text : '';

                return $attachment;
            }
        );
    }

    public function getBills(): Collection
    {
        return $this->user->bills()
            ->orderBy('order', 'ASC')
            ->orderBy('active', 'DESC')
            ->orderBy('name', 'ASC')->get()
        ;
    }

    public function getBillsForAccounts(Collection $accounts): Collection
    {
        $fields = [
            'bills.id',
            'bills.created_at',
            'bills.updated_at',
            'bills.deleted_at',
            'bills.user_id',
            'bills.name',
            'bills.amount_min',
            'bills.amount_max',
            'bills.date',
            'bills.transaction_currency_id',
            'bills.repeat_freq',
            'bills.skip',
            'bills.automatch',
            'bills.active',
        ];
        $ids    = $accounts->pluck('id')->toArray();

        return $this->user->bills()
            ->leftJoin(
                'transaction_journals',
                static function (JoinClause $join): void {
                    $join->on('transaction_journals.bill_id', '=', 'bills.id')->whereNull('transaction_journals.deleted_at');
                }
            )
            ->leftJoin(
                'transactions',
                static function (JoinClause $join): void {
                    $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                }
            )
            ->whereIn('transactions.account_id', $ids)
            ->whereNull('transaction_journals.deleted_at')
            ->orderBy('bills.active', 'DESC')
            ->orderBy('bills.name', 'ASC')
            ->groupBy($fields)
            ->get($fields)
        ;
    }

    /**
     * Get all bills with these ID's.
     */
    public function getByIds(array $billIds): Collection
    {
        return $this->user->bills()->whereIn('id', $billIds)->get();
    }

    /**
     * Get text or return empty string.
     */
    public function getNoteText(Bill $bill): string
    {
        /** @var null|Note $note */
        $note = $bill->notes()->first();

        return (string) $note?->text;
    }

    public function getOverallAverage(Bill $bill): array
    {
        /** @var JournalRepositoryInterface $repos */
        $repos    = app(JournalRepositoryInterface::class);
        $repos->setUser($this->user);

        // get and sort on currency
        $result   = [];
        $journals = $bill->transactionJournals()->get();

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            /** @var Transaction $transaction */
            $transaction                       = $journal->transactions()->where('amount', '<', 0)->first();
            $currencyId                        = (int) $journal->transaction_currency_id;
            $currency                          = $journal->transactionCurrency;
            $result[$currencyId] ??= [
                'sum'                     => '0',
                'native_sum'              => '0',
                'count'                   => 0,
                'avg'                     => '0',
                'native_avg'              => '0',
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];
            $result[$currencyId]['sum']        = bcadd($result[$currencyId]['sum'], $transaction->amount);
            $result[$currencyId]['native_sum'] = bcadd($result[$currencyId]['native_sum'], $transaction->native_amount ?? '0');
            if ($journal->foreign_currency_id === Amount::getDefaultCurrency()->id) {
                $result[$currencyId]['native_sum'] = bcadd($result[$currencyId]['native_sum'], $transaction->amount);
            }
            ++$result[$currencyId]['count'];
        }

        // after loop, re-loop for avg.
        /**
         * @var int   $currencyId
         * @var array $arr
         */
        foreach ($result as $currencyId => $arr) {
            $result[$currencyId]['avg']        = bcdiv($arr['sum'], (string) $arr['count']);
            $result[$currencyId]['native_avg'] = bcdiv($arr['native_sum'], (string) $arr['count']);
        }

        return $result;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function getPaginator(int $size): LengthAwarePaginator
    {
        return $this->user->bills()
            ->orderBy('active', 'DESC')
            ->orderBy('name', 'ASC')->paginate($size)
        ;
    }

    /**
     * The "paid dates" list is a list of dates of transaction journals that are linked to this bill.
     */
    public function getPaidDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection
    {
        // app('log')->debug('Now in getPaidDatesInRange()');

        Log::debug(sprintf('Search for linked journals between %s and %s', $start->toW3cString(), $end->toW3cString()));

        return $bill->transactionJournals()
            ->before($end)->after($start)->get(
                [
                    'transaction_journals.id',
                    'transaction_journals.date',
                    'transaction_journals.transaction_group_id',
                ]
            )
        ;
    }

    /**
     * Return all rules for one bill
     */
    public function getRulesForBill(Bill $bill): Collection
    {
        return $this->user->rules()
            ->leftJoin('rule_actions', 'rule_actions.rule_id', '=', 'rules.id')
            ->where('rule_actions.action_type', 'link_to_bill')
            ->where('rule_actions.action_value', $bill->name)
            ->get(['rules.*'])
        ;
    }

    /**
     * Return all rules related to the bills in the collection, in an associative array:
     * 5= billid
     *
     * 5 => [['id' => 1, 'title' => 'Some rule'],['id' => 2, 'title' => 'Some other rule']]
     */
    public function getRulesForBills(Collection $collection): array
    {
        $rules  = $this->user->rules()
            ->leftJoin('rule_actions', 'rule_actions.rule_id', '=', 'rules.id')
            ->where('rule_actions.action_type', 'link_to_bill')
            ->get(['rules.id', 'rules.title', 'rule_actions.action_value', 'rules.active'])
        ;
        $array  = [];

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $array[$rule->action_value] ??= [];
            $array[$rule->action_value][] = ['id' => $rule->id, 'title' => $rule->title, 'active' => $rule->active];
        }
        $return = [];
        foreach ($collection as $bill) {
            $return[$bill->id] = $array[$bill->name] ?? [];
        }

        return $return;
    }

    public function getYearAverage(Bill $bill, Carbon $date): array
    {
        /** @var JournalRepositoryInterface $repos */
        $repos    = app(JournalRepositoryInterface::class);
        $repos->setUser($this->user);

        // get and sort on currency
        $result   = [];

        $journals = $bill->transactionJournals()
            ->where('date', '>=', $date->year.'-01-01 00:00:00')
            ->where('date', '<=', $date->year.'-12-31 23:59:59')
            ->get()
        ;

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            /** @var null|Transaction $transaction */
            $transaction                       = $journal->transactions()->where('amount', '<', 0)->first();
            if (null === $transaction) {
                continue;
            }
            $currencyId                        = (int) $journal->transaction_currency_id;
            $currency                          = $journal->transactionCurrency;
            $result[$currencyId] ??= [
                'sum'                     => '0',
                'native_sum'              => '0',
                'count'                   => 0,
                'avg'                     => '0',
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];
            $result[$currencyId]['sum']        = bcadd($result[$currencyId]['sum'], $transaction->amount);
            $result[$currencyId]['native_sum'] = bcadd($result[$currencyId]['native_sum'], $transaction->native_amount ?? '0');
            if ($journal->foreign_currency_id === Amount::getDefaultCurrency()->id) {
                $result[$currencyId]['native_sum'] = bcadd($result[$currencyId]['native_sum'], $transaction->amount);
            }
            ++$result[$currencyId]['count'];
        }

        // after loop, re-loop for avg.
        /**
         * @var int   $currencyId
         * @var array $arr
         */
        foreach ($result as $currencyId => $arr) {
            $result[$currencyId]['avg']        = bcdiv($arr['sum'], (string) $arr['count']);
            $result[$currencyId]['native_avg'] = bcdiv($arr['native_sum'], (string) $arr['count']);
        }

        return $result;
    }

    /**
     * Link a set of journals to a bill.
     */
    public function linkCollectionToBill(Bill $bill, array $transactions): void
    {
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $journal          = $bill->user->transactionJournals()->find((int) $transaction['transaction_journal_id']);
            $journal->bill_id = $bill->id;
            $journal->save();
            app('log')->debug(sprintf('Linked journal #%d to bill #%d', $journal->id, $bill->id));
        }
    }

    /**
     * Given the date in $date, this method will return a moment in the future where the bill is expected to be paid.
     */
    public function nextExpectedMatch(Bill $bill, Carbon $date): Carbon
    {
        $cache        = new CacheProperties();
        $cache->addProperty($bill->id);
        $cache->addProperty('nextExpectedMatch');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get();
        }
        // find the most recent date for this bill NOT in the future. Cache this date:
        $start        = clone $bill->date;
        $start->startOfDay();
        app('log')->debug('nextExpectedMatch: Start is '.$start->format('Y-m-d'));

        while ($start < $date) {
            app('log')->debug(sprintf('$start (%s) < $date (%s)', $start->format('Y-m-d H:i:s'), $date->format('Y-m-d H:i:s')));
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
            app('log')->debug('Start is now '.$start->format('Y-m-d H:i:s'));
        }

        $end          = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        $end->endOfDay();

        // see if the bill was paid in this period.
        $journalCount = $bill->transactionJournals()->before($end)->after($start)->count();

        if ($journalCount > 0) {
            // this period had in fact a bill. The new start is the current end, and we create a new end.
            app('log')->debug(sprintf('Journal count is %d, so start becomes %s', $journalCount, $end->format('Y-m-d')));
            $start = clone $end;
            $end   = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }
        app('log')->debug('nextExpectedMatch: Final start is '.$start->format('Y-m-d'));
        app('log')->debug('nextExpectedMatch: Matching end is '.$end->format('Y-m-d'));

        $cache->store($start);

        return $start;
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): Bill
    {
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    public function removeObjectGroup(Bill $bill): Bill
    {
        $bill->objectGroups()->sync([]);

        return $bill;
    }

    public function searchBill(string $query, int $limit): Collection
    {
        $query = sprintf('%%%s%%', $query);

        return $this->user->bills()->whereLike('name', $query)->take($limit)->get();
    }

    public function setObjectGroup(Bill $bill, string $objectGroupTitle): Bill
    {
        $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
        if (null !== $objectGroup) {
            $bill->objectGroups()->sync([$objectGroup->id]);
        }

        return $bill;
    }

    public function setOrder(Bill $bill, int $order): void
    {
        $bill->order = $order;
        $bill->save();
    }

    public function sumPaidInRange(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('sumPaidInRange from %s to %s', $start->toW3cString(), $end->toW3cString()));
        $bills           = $this->getActiveBills();
        $return          = [];
        $convertToNative = app('preferences')->getForUser($this->user, 'convert_to_native', false)->data;
        $default         = app('amount')->getDefaultCurrency();

        /** @var Bill $bill */
        foreach ($bills as $bill) {

            /** @var Collection $set */
            $set                          = $bill->transactionJournals()->after($start)->before($end)->get(['transaction_journals.*']);
            $currency                     = $convertToNative && $bill->transactionCurrency->id !== $default->id ? $default : $bill->transactionCurrency;
            $return[(int) $currency->id] ??= [
                'id'             => (string) $currency->id,
                'name'           => $currency->name,
                'symbol'         => $currency->symbol,
                'code'           => $currency->code,
                'decimal_places' => $currency->decimal_places,
                'sum'            => '0',
            ];
            $setAmount                    = '0';

            /** @var TransactionJournal $transactionJournal */
            foreach ($set as $transactionJournal) {
                $setAmount = bcadd($setAmount, Amount::getAmountFromJournalObject($transactionJournal));
            }
            Log::debug(sprintf('Bill #%d ("%s") with %d transaction(s) and sum %s %s', $bill->id, $bill->name, $set->count(), $currency->code, $setAmount));
            $return[$currency->id]['sum'] = bcadd($return[$currency->id]['sum'], $setAmount);
            Log::debug(sprintf('Total sum is now %s', $return[$currency->id]['sum']));
        }

        return $return;
    }

    public function getActiveBills(): Collection
    {
        return $this->user->bills()
            ->where('active', true)
            ->orderBy('bills.name', 'ASC')
            ->get(['bills.*', \DB::raw('((bills.amount_min + bills.amount_max) / 2) AS expectedAmount')]) // @phpstan-ignore-line
        ;
    }

    public function sumUnpaidInRange(Carbon $start, Carbon $end): array
    {
        app('log')->debug(sprintf('Now in sumUnpaidInRange("%s", "%s")', $start->format('Y-m-d'), $end->format('Y-m-d')));
        $bills           = $this->getActiveBills();
        $return          = [];
        $convertToNative = app('preferences')->getForUser($this->user, 'convert_to_native', false)->data;
        $default         = app('amount')->getDefaultCurrency();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            //            app('log')->debug(sprintf('Processing bill #%d ("%s")', $bill->id, $bill->name));
            $dates    = $this->getPayDatesInRange($bill, $start, $end);
            $count    = $bill->transactionJournals()->after($start)->before($end)->count();
            $total    = $dates->count() - $count;
            // app('log')->debug(sprintf('Pay dates: %d, count: %d, left: %d', $dates->count(), $count, $total));
            // app('log')->debug('dates', $dates->toArray());

            $minField = $convertToNative && $bill->transactionCurrency->id !== $default->id ? 'native_amount_min' : 'amount_min';
            $maxField = $convertToNative && $bill->transactionCurrency->id !== $default->id ? 'native_amount_max' : 'amount_max';
            // Log::debug(sprintf('min field is %s, max field is %s', $minField, $maxField));

            if ($total > 0) {
                $currency                     = $convertToNative && $bill->transactionCurrency->id !== $default->id ? $default : $bill->transactionCurrency;
                $average                      = bcdiv(bcadd($bill->{$maxField}, $bill->{$minField}), '2');
                Log::debug(sprintf('Amount to pay is %s %s (%d times)', $currency->code, $average, $total));
                $return[$currency->id] ??= [
                    'id'             => (string) $currency->id,
                    'name'           => $currency->name,
                    'symbol'         => $currency->symbol,
                    'code'           => $currency->code,
                    'decimal_places' => $currency->decimal_places,
                    'sum'            => '0',
                ];
                $return[$currency->id]['sum'] = bcadd($return[$currency->id]['sum'], bcmul($average, (string) $total));
            }
        }

        return $return;
    }

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
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
     * transaction. Whether or not it is there already, is not relevant.
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

    public function unlinkAll(Bill $bill): void
    {
        $this->user->transactionJournals()->where('bill_id', $bill->id)->update(['bill_id' => null]);
    }

    /**
     * @throws FireflyException
     */
    public function update(Bill $bill, array $data): Bill
    {
        /** @var BillUpdateService $service */
        $service = app(BillUpdateService::class);

        return $service->update($bill, $data);
    }
}
