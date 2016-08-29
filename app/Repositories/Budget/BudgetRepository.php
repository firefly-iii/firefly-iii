<?php
/**
 * BudgetRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Events\BudgetLimitStored;
use FireflyIII\Events\BudgetLimitUpdated;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class BudgetRepository
 *
 * @package FireflyIII\Repositories\Budget
 */
class BudgetRepository implements BudgetRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * BudgetRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return bool
     */
    public function cleanupBudgets(): bool
    {
        // delete limits with amount 0:
        BudgetLimit::where('amount', 0)->delete();

        return true;

    }

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool
    {
        $budget->delete();

        return true;
    }

    /**
     * Find a budget.
     *
     * @param int $budgetId
     *
     * @return Budget
     */
    public function find(int $budgetId): Budget
    {
        $budget = $this->user->budgets()->find($budgetId);
        if (is_null($budget)) {
            $budget = new Budget;
        }

        return $budget;
    }

    /**
     * Find a budget.
     *
     * @param string $name
     *
     * @return Budget
     */
    public function findByName(string $name): Budget
    {
        $budgets = $this->user->budgets()->get(['budgets.*']);
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            if ($budget->name === $name) {
                return $budget;
            }
        }

        return new Budget;
    }

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function firstUseDate(Budget $budget): Carbon
    {
        $oldest  = Carbon::create()->startOfYear();
        $journal = $budget->transactionJournals()->orderBy('date', 'ASC')->first();
        if (!is_null($journal)) {
            $oldest = $journal->date < $oldest ? $journal->date : $oldest;
        }

        $transaction = $budget
            ->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.id')
            ->orderBy('transaction_journals.date', 'ASC')->first(['transactions.*', 'transaction_journals.date']);
        if (!is_null($transaction)) {
            $carbon = new Carbon($transaction->date);
            $oldest = $carbon < $oldest ? $carbon : $oldest;
        }

        return $oldest;

    }

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 1)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getAllBudgetLimitRepetitions(Carbon $start, Carbon $end): Collection
    {
        $query = LimitRepetition::
        leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                                ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                ->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d 00:00:00'))
                                ->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d 00:00:00'))
                                ->where('budgets.user_id', $this->user->id);

        $set = $query->get(['limit_repetitions.*', 'budget_limits.budget_id']);

        return $set;
    }

    /**
     * @return Collection
     */
    public function getBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 0)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $return     = new Collection;
        $accountIds = [];
        // expand the number of grabbed fields:
        $fields   = TransactionJournal::queryFields();
        $fields[] = 'source.account_id';
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
        }

        // first get all journals for all budget(s):
        $journalQuery = $this->user->transactionJournals()
                                   ->expanded()
                                   ->sortCorrectly()
                                   ->before($end)
                                   ->after($start)
                                   ->leftJoin(
                                       'transactions as source',
                                       function (JoinClause $join) {
                                           $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', '0');
                                       }
                                   )
                                   ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                                   ->whereIn('budget_transaction_journal.budget_id', $budgets->pluck('id')->toArray());
        // add account id's, if relevant:
        if (count($accountIds) > 0) {
            $journalQuery->whereIn('source.account_id', $accountIds);
        }
        // get them:
        $journals = $journalQuery->get(TransactionJournal::queryFields());

        // then get transactions themselves.
        $transactionQuery = $this->user->transactionJournals()
                                       ->expanded()
                                       ->before($end)
                                       ->sortCorrectly()
                                       ->after($start)
                                       ->leftJoin('transactions as related', 'related.transaction_journal_id', '=', 'transaction_journals.id')
                                       ->leftJoin('budget_transaction', 'budget_transaction.transaction_id', '=', 'related.id')
                                       ->leftJoin(
                                           'transactions as source',
                                           function (JoinClause $join) {
                                               $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', '0');
                                           }
                                       )
                                       ->whereIn('budget_transaction.budget_id', $budgets->pluck('id')->toArray());

        if (count($accountIds) > 0) {
            $transactionQuery->whereIn('source.account_id', $accountIds);
        }

        $transactions = $transactionQuery->get($fields);

        // return complete set:
        $return = $return->merge($transactions);
        $return = $return->merge($journals);

        return $return;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriodWithoutBudget(Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $accountIds = [];
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
        }

        /** @var Collection $set */
        $query = $this->user
            ->transactionJournals()
            ->expanded()
            ->sortCorrectly()
            ->transactionTypes([TransactionType::WITHDRAWAL])
            ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
            ->whereNull('budget_transaction_journal.id')
            ->leftJoin(
                'transactions as source',
                function (JoinClause $join) {
                    $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', '0');
                }
            )
            ->before($end)
            ->after($start)->with(
                [
                    'transactions' => function (HasMany $query) {
                        $query->where('transactions.amount', '<', 0);
                    },
                    'transactions.budgets',
                ]
            );

        // add account id's, if relevant:
        if (count($accountIds) > 0) {
            $query->whereIn('source.account_id', $accountIds);
        }

        $set = $query->get(TransactionJournal::queryFields());
        $set = $set->filter(
            function (TransactionJournal $journal) {
                foreach ($journal->transactions as $t) {
                    if ($t->budgets->count() === 0) {
                        return true;
                    }
                }
                return false;
            }
        );

        return $set;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end) : string
    {
        // first collect actual transaction journals (fairly easy)
        $query = $this->user
            ->transactionJournals()
            ->leftJoin(
                'transactions as source', function (JoinClause $join) {
                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
            }
            )
            ->leftJoin(
                'transactions as destination', function (JoinClause $join) {
                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
            }
            );
        $query->whereNull('source.deleted_at');
        $query->whereNull('destination.deleted_at');
        $query->where('transaction_journals.completed', 1);

        if ($end >= $start) {
            $query->before($end)->after($start);
        }
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $set        = join(', ', $accountIds);
            $query->whereRaw('(source.account_id in (' . $set . ') XOR destination.account_id in (' . $set . '))');

        }
        if ($budgets->count() > 0) {
            $budgetIds = $budgets->pluck('id')->toArray();
            $query->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('budget_transaction_journal.budget_id', $budgetIds);

        }

        // that should do it:
        $ids   = $query->distinct()->get(['transaction_journals.id'])->pluck('id')->toArray();
        $first = '0';
        if (count($ids) > 0) {
            $first = strval(
                $this->user->transactions()
                           ->whereIn('transaction_journal_id', $ids)
                           ->where('amount', '<', '0')
                           ->whereNull('transactions.deleted_at')
                           ->sum('amount')
            );
        }
        // then collection transactions (harder)
        $query = $this->user->transactions()
                            ->where('transactions.amount', '<', 0)
                            ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
                            ->where('transaction_journals.date', '<=', $end->format('Y-m-d 23:59:59'));
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->whereIn('transactions.account_id', $accountIds);
        }
        if ($budgets->count() > 0) {
            $budgetIds = $budgets->pluck('id')->toArray();
            $query->leftJoin('budget_transaction', 'budget_transaction.transaction_id', '=', 'transactions.id');
            $query->whereIn('budget_transaction.budget_id', $budgetIds);
        }
        $second = strval($query->sum('transactions.amount'));

        return bcadd($first, $second);
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutBudget(Collection $accounts, Carbon $start, Carbon $end): string
    {
        $types = [TransactionType::WITHDRAWAL];
        $query = $this->user->transactionJournals()
                            ->distinct()
                            ->transactionTypes($types)
                            ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                            ->leftJoin(
                                'transactions as source', function (JoinClause $join) {
                                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
                            }
                            )
                            ->leftJoin(
                                'transactions as destination', function (JoinClause $join) {
                                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
                            }
                            )
                            ->leftJoin('budget_transaction', 'source.id', '=', 'budget_transaction.transaction_id')
                            ->whereNull('budget_transaction_journal.id')
                            ->whereNull('budget_transaction.id')
                            ->before($end)
                            ->after($start)
                            ->whereNull('source.deleted_at')
                            ->whereNull('destination.deleted_at')
                            ->where('transaction_journals.completed', 1);

        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();

            $set = join(', ', $accountIds);
            $query->whereRaw('(source.account_id in (' . $set . ') XOR destination.account_id in (' . $set . '))');
        }
        $ids = $query->get(['transaction_journals.id'])->pluck('id')->toArray();
        $sum = '0';
        if (count($ids) > 0) {
            $sum = strval(
                $this->user->transactions()
                           ->whereIn('transaction_journal_id', $ids)
                           ->where('amount', '<', '0')
                           ->whereNull('transactions.deleted_at')
                           ->sum('amount')
            );
        }

        return $sum;
    }

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget
    {
        $newBudget = new Budget(
            [
                'user_id' => $data['user'],
                'name'    => $data['name'],
            ]
        );
        $newBudget->save();

        return $newBudget;
    }

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget
    {
        // update the account:
        $budget->name   = $data['name'];
        $budget->active = $data['active'];
        $budget->save();

        return $budget;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $range
     * @param int    $amount
     *
     * @return BudgetLimit
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $range, int $amount) : BudgetLimit
    {
        // there might be a budget limit for this startdate:
        $repeatFreq = config('firefly.range_to_repeat_freq.' . $range);
        /** @var BudgetLimit $limit */
        $limit = $budget->budgetlimits()
                        ->where('budget_limits.startdate', $start)
                        ->where('budget_limits.repeat_freq', $repeatFreq)->first(['budget_limits.*']);

        // delete if amount is zero.
        if (!is_null($limit) && $amount <= 0.0) {
            $limit->delete();

            return new BudgetLimit;
        }
        // update if exists:
        if (!is_null($limit)) {
            $limit->amount = $amount;
            $limit->save();

            // fire event to create or update LimitRepetition.
            event(new BudgetLimitUpdated($limit, $end));

            return $limit;
        }

        // create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->startdate   = $start;
        $limit->amount      = $amount;
        $limit->repeat_freq = $repeatFreq;
        $limit->repeats     = 0;
        $limit->save();
        event(new BudgetLimitStored($limit, $end));


        // likewise, there should be a limit repetition to match the end date
        // (which is always the end of the month) but that is caught by an event.
        // so handled automatically.

        return $limit;
    }
}
