<?php
/**
 * BillRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use DB;
use FireflyIII\Factory\BillFactory;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\BillDestroyService;
use FireflyIII\Services\Internal\Update\BillUpdateService;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BillRepository.
 *
 */
class BillRepository implements BillRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param Bill $bill
     *
     * @return bool
     *

     */
    public function destroy(Bill $bill): bool
    {
        /** @var BillDestroyService $service */
        $service = app(BillDestroyService::class);
        $service->destroy($bill);

        return true;
    }

    /**
     * Find a bill by ID.
     *
     * @param int $billId
     *
     * @return Bill
     */
    public function find(int $billId): ?Bill
    {
        return $this->user->bills()->find($billId);
    }

    /**
     * Find bill by parameters.
     *
     * @param int|null    $billId
     * @param string|null $billName
     *
     * @return Bill|null
     */
    public function findBill(?int $billId, ?string $billName): ?Bill
    {
        if (null !== $billId) {
            $searchResult = $this->find((int)$billId);
            if (null !== $searchResult) {
                Log::debug(sprintf('Found bill based on #%d, will return it.', $billId));

                return $searchResult;
            }
        }
        if (null !== $billName) {
            $searchResult = $this->findByName((string)$billName);
            if (null !== $searchResult) {
                Log::debug(sprintf('Found bill based on "%s", will return it.', $billName));

                return $searchResult;
            }
        }
        Log::debug('Found nothing');

        return null;
    }

    /**
     * Find a bill by name.
     *
     * @param string $name
     *
     * @return Bill
     */
    public function findByName(string $name): ?Bill
    {
        $bills = $this->user->bills()->get(['bills.*']);

        // TODO no longer need to loop like this

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            if ($bill->name === $name) {
                return $bill;
            }
        }

        return null;
    }

    /**
     * @return Collection
     */
    public function getActiveBills(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->bills()
                          ->where('active', 1)
                          ->orderBy('bills.name', 'ASC')
                          ->get(['bills.*', DB::raw('((bills.amount_min + bills.amount_max) / 2) AS expectedAmount'),]);

        return $set;
    }

    /**
     * Get all attachments.
     *
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getAttachments(Bill $bill): Collection
    {
        return $bill->attachments()->get();
    }

    /**
     * @return Collection
     */
    public function getBills(): Collection
    {
        /** @var Collection $set */
        return $this->user->bills()->orderBy('active', 'DESC')->orderBy('name', 'ASC')->get();
    }

    /**
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBillsForAccounts(Collection $accounts): Collection
    {
        $fields = ['bills.id', 'bills.created_at', 'bills.updated_at', 'bills.deleted_at', 'bills.user_id', 'bills.name', 'bills.amount_min',
                   'bills.amount_max', 'bills.date', 'bills.transaction_currency_id', 'bills.repeat_freq', 'bills.skip', 'bills.automatch', 'bills.active',];
        $ids    = $accounts->pluck('id')->toArray();
        $set    = $this->user->bills()
                             ->leftJoin(
                                 'transaction_journals',
                                 static function (JoinClause $join) {
                                     $join->on('transaction_journals.bill_id', '=', 'bills.id')->whereNull('transaction_journals.deleted_at');
                                 }
                             )
                             ->leftJoin(
                                 'transactions',
                                 static function (JoinClause $join) {
                                     $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                                 }
                             )
                             ->whereIn('transactions.account_id', $ids)
                             ->whereNull('transaction_journals.deleted_at')
                             ->orderBy('bills.active', 'DESC')
                             ->orderBy('bills.name', 'ASC')
                             ->groupBy($fields)
                             ->get($fields);
        return $set;
    }

    /**
     * Get the total amount of money paid for the users active bills in the date range given.
     * This amount will be negative (they're expenses). This method is equal to
     * getBillsUnpaidInRange. So the debug comments are gone.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsPaidInRange(Carbon $start, Carbon $end): string
    {
        $bills = $this->getActiveBills();
        $sum   = '0';
        /** @var Bill $bill */
        foreach ($bills as $bill) {
            /** @var Collection $set */
            $set = $bill->transactionJournals()->after($start)->before($end)->get(['transaction_journals.*']);
            if ($set->count() > 0) {
                $journalIds = $set->pluck('id')->toArray();
                $amount     = (string)Transaction::whereIn('transaction_journal_id', $journalIds)->where('amount', '<', 0)->sum('amount');
                $sum        = bcadd($sum, $amount);
                Log::debug(sprintf('Total > 0, so add to sum %f, which becomes %f', $amount, $sum));
            }
        }

        return $sum;
    }

    /**
     * Get the total amount of money paid for the users active bills in the date range given,
     * grouped per currency.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getBillsPaidInRangePerCurrency(Carbon $start, Carbon $end): array
    {
        $bills  = $this->getActiveBills();
        $return = [];
        /** @var Bill $bill */
        foreach ($bills as $bill) {
            /** @var Collection $set */
            $set        = $bill->transactionJournals()->after($start)->before($end)->get(['transaction_journals.*']);
            $currencyId = (int)$bill->transaction_currency_id;
            if ($set->count() > 0) {
                $journalIds          = $set->pluck('id')->toArray();
                $amount              = (string)Transaction::whereIn('transaction_journal_id', $journalIds)->where('amount', '<', 0)->sum('amount');
                $return[$currencyId] = $return[$currencyId] ?? '0';
                $return[$currencyId] = bcadd($amount, $return[$currencyId]);
                Log::debug(sprintf('Total > 0, so add to sum %f, which becomes %f (currency %d)', $amount, $return[$currencyId], $currencyId));
            }
        }

        return $return;
    }

    /**
     * Get the total amount of money due for the users active bills in the date range given. This amount will be positive.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsUnpaidInRange(Carbon $start, Carbon $end): string
    {
        $bills = $this->getActiveBills();
        $sum   = '0';
        /** @var Bill $bill */
        foreach ($bills as $bill) {
            Log::debug(sprintf('Now at bill #%d (%s)', $bill->id, $bill->name));
            $dates = $this->getPayDatesInRange($bill, $start, $end);
            $count = $bill->transactionJournals()->after($start)->before($end)->count();
            $total = $dates->count() - $count;

            Log::debug(sprintf('Dates = %d, journalCount = %d, total = %d', $dates->count(), $count, $total));

            if ($total > 0) {
                $average = bcdiv(bcadd($bill->amount_max, $bill->amount_min), '2');
                $multi   = bcmul($average, (string)$total);
                $sum     = bcadd($sum, $multi);
                Log::debug(sprintf('Total > 0, so add to sum %f, which becomes %f', $multi, $sum));
            }
        }

        return $sum;
    }

    /**
     * Get the total amount of money due for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getBillsUnpaidInRangePerCurrency(Carbon $start, Carbon $end): array
    {
        $bills  = $this->getActiveBills();
        $return = [];
        /** @var Bill $bill */
        foreach ($bills as $bill) {
            Log::debug(sprintf('Now at bill #%d (%s)', $bill->id, $bill->name));
            $dates      = $this->getPayDatesInRange($bill, $start, $end);
            $count      = $bill->transactionJournals()->after($start)->before($end)->count();
            $total      = $dates->count() - $count;
            $currencyId = (int)$bill->transaction_currency_id;

            Log::debug(sprintf('Dates = %d, journalCount = %d, total = %d', $dates->count(), $count, $total));

            if ($total > 0) {
                $average             = bcdiv(bcadd($bill->amount_max, $bill->amount_min), '2');
                $multi               = bcmul($average, (string)$total);
                $return[$currencyId] = $return[$currencyId] ?? '0';
                $return[$currencyId] = bcadd($return[$currencyId], $multi);
                Log::debug(sprintf('Total > 0, so add to sum %f, which becomes %f (for currency %d)', $multi, $return[$currencyId], $currencyId));
            }
        }

        return $return;
    }

    /**
     * Get all bills with these ID's.
     *
     * @param array $billIds
     *
     * @return Collection
     */
    public function getByIds(array $billIds): Collection
    {
        return $this->user->bills()->whereIn('id', $billIds)->get();
    }

    /**
     * Get text or return empty string.
     *
     * @param Bill $bill
     *
     * @return string
     */
    public function getNoteText(Bill $bill): string
    {
        /** @var Note $note */
        $note = $bill->notes()->first();
        if (null !== $note) {
            return (string)$note->text;
        }

        return '';
    }

    /**
     * @param Bill $bill
     *
     * @return string
     */
    public function getOverallAverage(Bill $bill): string
    {
        /** @var JournalRepositoryInterface $repos */
        $repos = app(JournalRepositoryInterface::class);
        $repos->setUser($this->user);
        $journals = $bill->transactionJournals()->get();
        $sum      = '0';
        $count    = (string)$journals->count();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $sum = bcadd($sum, $repos->getJournalTotal($journal));
        }
        $avg = '0';
        if ($journals->count() > 0) {
            $avg = bcdiv($sum, $count);
        }

        return $avg;
    }

    /**
     * @param int $size
     *
     * @return LengthAwarePaginator
     */
    public function getPaginator(int $size): LengthAwarePaginator
    {
        return $this->user->bills()
                          ->orderBy('active', 'DESC')
                          ->orderBy('name', 'ASC')->paginate($size);
    }

    /**
     * The "paid dates" list is a list of dates of transaction journals that are linked to this bill.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPaidDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection
    {
        return $bill->transactionJournals()
                    ->before($end)->after($start)->get(
                [
                    'transaction_journals.id', 'transaction_journals.date',
                    'transaction_journals.transaction_group_id',
                ]
            );
    }

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection
    {
        $set          = new Collection;
        $currentStart = clone $start;
        Log::debug(sprintf('Now at bill "%s" (%s)', $bill->name, $bill->repeat_freq));
        Log::debug(sprintf('First currentstart is %s', $currentStart->format('Y-m-d')));

        while ($currentStart <= $end) {
            Log::debug(sprintf('Currentstart is now %s.', $currentStart->format('Y-m-d')));
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            Log::debug(sprintf('Next Date match after %s is %s', $currentStart->format('Y-m-d'), $nextExpectedMatch->format('Y-m-d')));
            if ($nextExpectedMatch > $end) {// If nextExpectedMatch is after end, we continue
                Log::debug(
                    sprintf('nextExpectedMatch %s is after %s, so we skip this bill now.', $nextExpectedMatch->format('Y-m-d'), $end->format('Y-m-d'))
                );
                break;
            }
            $set->push(clone $nextExpectedMatch);
            Log::debug(sprintf('Now %d dates in set.', $set->count()));
            $nextExpectedMatch->addDay();

            Log::debug(sprintf('Currentstart (%s) has become %s.', $currentStart->format('Y-m-d'), $nextExpectedMatch->format('Y-m-d')));

            $currentStart = clone $nextExpectedMatch;
        }
        $simple = $set->each(
            static function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );
        Log::debug(sprintf('Found dates between %s and %s:', $start->format('Y-m-d'), $end->format('Y-m-d')), $simple->toArray());

        return $set;
    }

    /**
     * Return all rules for one bill
     *
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getRulesForBill(Bill $bill): Collection
    {
        return $this->user->rules()
                          ->leftJoin('rule_actions', 'rule_actions.rule_id', '=', 'rules.id')
                          ->where('rule_actions.action_type', 'link_to_bill')
                          ->where('rule_actions.action_value', $bill->name)
                          ->get(['rules.*']);
    }

    /**
     * Return all rules related to the bills in the collection, in an associative array:
     * 5= billid
     *
     * 5 => [['id' => 1, 'title' => 'Some rule'],['id' => 2, 'title' => 'Some other rule']]
     *
     * @param Collection $collection
     *
     * @return array
     */
    public function getRulesForBills(Collection $collection): array
    {
        $rules = $this->user->rules()
                            ->leftJoin('rule_actions', 'rule_actions.rule_id', '=', 'rules.id')
                            ->where('rule_actions.action_type', 'link_to_bill')
                            ->get(['rules.id', 'rules.title', 'rule_actions.action_value', 'rules.active']);
        $array = [];
        foreach ($rules as $rule) {
            $array[$rule->action_value]   = $array[$rule->action_value] ?? [];
            $array[$rule->action_value][] = ['id' => $rule->id, 'title' => $rule->title, 'active' => $rule->active];
        }
        $return = [];
        foreach ($collection as $bill) {
            $return[$bill->id] = $array[$bill->name] ?? [];
        }

        return $return;
    }

    /**
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return string
     */
    public function getYearAverage(Bill $bill, Carbon $date): string
    {
        /** @var JournalRepositoryInterface $repos */
        $repos = app(JournalRepositoryInterface::class);
        $repos->setUser($this->user);

        $journals = $bill->transactionJournals()
                         ->where('date', '>=', $date->year . '-01-01 00:00:00')
                         ->where('date', '<=', $date->year . '-12-31 23:59:59')
                         ->get();
        $sum      = '0';
        $count    = (string)$journals->count();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $sum = bcadd($sum, $repos->getJournalTotal($journal));
        }
        $avg = '0';
        if ($journals->count() > 0) {
            $avg = bcdiv($sum, $count);
        }

        return $avg;
    }

    /**
     * Link a set of journals to a bill.
     *
     * @param Bill  $bill
     * @param array $transactions
     */
    public function linkCollectionToBill(Bill $bill, array $transactions): void
    {
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $journal          = $bill->user->transactionJournals()->find((int)$transaction['transaction_journal_id']);
            $journal->bill_id = $bill->id;
            $journal->save();
            Log::debug(sprintf('Linked journal #%d to bill #%d', $journal->id, $bill->id));
        }
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        $cache = new CacheProperties;
        $cache->addProperty($bill->id);
        $cache->addProperty('nextDateMatch');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        // find the most recent date for this bill NOT in the future. Cache this date:
        $start = clone $bill->date;
        //Log::debug('nextDateMatch: Start is ' . $start->format('Y-m-d'));

        while ($start < $date) {
            //Log::debug(sprintf('$start (%s) < $date (%s)', $start->format('Y-m-d'), $date->format('Y-m-d')));
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
            //Log::debug('Start is now ' . $start->format('Y-m-d'));
        }

        $end = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);

        Log::debug('nextDateMatch: Final start is ' . $start->format('Y-m-d'));
        Log::debug('nextDateMatch: Matching end is ' . $end->format('Y-m-d'));

        $cache->store($start);

        return $start;
    }

    /**
     * Given the date in $date, this method will return a moment in the future where the bill is expected to be paid.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return Carbon
     */
    public function nextExpectedMatch(Bill $bill, Carbon $date): Carbon
    {
        $cache = new CacheProperties;
        $cache->addProperty($bill->id);
        $cache->addProperty('nextExpectedMatch');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        // find the most recent date for this bill NOT in the future. Cache this date:
        $start = clone $bill->date;
        Log::debug('nextExpectedMatch: Start is ' . $start->format('Y-m-d'));

        while ($start < $date) {
            Log::debug(sprintf('$start (%s) < $date (%s)', $start->format('Y-m-d'), $date->format('Y-m-d')));
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
            Log::debug('Start is now ' . $start->format('Y-m-d'));
        }

        $end = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);

        // see if the bill was paid in this period.
        $journalCount = $bill->transactionJournals()->before($end)->after($start)->count();

        if ($journalCount > 0) {
            // this period had in fact a bill. The new start is the current end, and we create a new end.
            Log::debug(sprintf('Journal count is %d, so start becomes %s', $journalCount, $end->format('Y-m-d')));
            $start = clone $end;
            $end   = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }
        Log::debug('nextExpectedMatch: Final start is ' . $start->format('Y-m-d'));
        Log::debug('nextExpectedMatch: Matching end is ' . $end->format('Y-m-d'));

        $cache->store($start);

        return $start;
    }

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchBill(string $query): Collection
    {
        $query = sprintf('%%%s%%', $query);

        return $this->user->bills()->where('name', 'LIKE', $query)->get();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Bill|null
     */
    public function store(array $data): ?Bill
    {
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill
    {
        /** @var BillUpdateService $service */
        $service = app(BillUpdateService::class);

        return $service->update($bill, $data);
    }

    /**
     * @param Bill $bill
     */
    public function unlinkAll(Bill $bill): void
    {
        $this->user->transactionJournals()->where('bill_id', $bill->id)->update(['bill_id' => null]);
    }
}
