<?php
/**
 * BudgetRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Log;
use Navigation;

/**
 * Class BudgetRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class BudgetRepository implements BudgetRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * A method that returns the amount of money budgeted per day for this budget,
     * on average.
     *
     * @param Budget $budget
     *
     * @return string
     */
    public function budgetedPerDay(Budget $budget): string
    {
        Log::debug(sprintf('Now with budget #%d "%s"', $budget->id, $budget->name));
        $total = '0';
        $count = 0;
        foreach ($budget->budgetlimits as $limit) {
            $diff   = $limit->start_date->diffInDays($limit->end_date);
            $diff   = 0 === $diff ? 1 : $diff;
            $amount = (string)$limit->amount;
            $perDay = bcdiv($amount, (string)$diff);
            $total  = bcadd($total, $perDay);
            $count++;
            Log::debug(sprintf('Found %d budget limits. Per day is %s, total is %s', $count, $perDay, $total));
        }
        $avg = $total;
        if ($count > 0) {
            $avg = bcdiv($total, (string)$count);
        }
        Log::debug(sprintf('%s / %d = %s = average.', $total, $count, $avg));

        return $avg;
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's 5.
     */
    public function cleanupBudgets(): bool
    {
        // delete limits with amount 0:
        try {
            BudgetLimit::where('amount', 0)->delete();
        } catch (Exception $e) {
            Log::debug(sprintf('Could not delete budget limit: %s', $e->getMessage()));
        }

        // do the clean up by hand because Sqlite can be tricky with this.
        $budgetLimits = BudgetLimit::orderBy('created_at', 'DESC')->get(['id', 'budget_id', 'start_date', 'end_date']);
        $count        = [];
        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            $key = $budgetLimit->budget_id . '-' . $budgetLimit->start_date->format('Y-m-d') . $budgetLimit->end_date->format('Y-m-d');
            if (isset($count[$key])) {
                // delete it!
                try {
                    BudgetLimit::find($budgetLimit->id)->delete();
                } catch (Exception $e) {
                    Log::debug(sprintf('Could not delete budget limit: %s', $e->getMessage()));
                }
            }
            $count[$key] = true;
        }

        return true;
    }

    /**
     * This method collects various info on budgets, used on the budget page and on the index.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collectBudgetInformation(Collection $budgets, Carbon $start, Carbon $end): array
    {
        // get account information
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $return            = [];
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budgetId          = $budget->id;
            $return[$budgetId] = [
                'spent'    => $this->spentInPeriod(new Collection([$budget]), $accounts, $start, $end),
                'budgeted' => '0',
            ];
            $budgetLimits      = $this->getBudgetLimits($budget, $start, $end);
            $otherLimits       = new Collection;

            // get all the budget limits relevant between start and end and examine them:
            /** @var BudgetLimit $limit */
            foreach ($budgetLimits as $limit) {
                if ($limit->start_date->isSameDay($start) && $limit->end_date->isSameDay($end)
                ) {
                    $return[$budgetId]['currentLimit'] = $limit;
                    $return[$budgetId]['budgeted']     = round($limit->amount, $defaultCurrency->decimal_places);
                    continue;
                }
                // otherwise it's just one of the many relevant repetitions:
                $otherLimits->push($limit);
            }
            $return[$budgetId]['otherLimits'] = $otherLimits;
        }

        return $return;
    }

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool
    {
        try {
            $budget->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete budget: %s', $e->getMessage()));
        }

        return true;
    }

    /**
     * @param AvailableBudget $availableBudget
     */
    public function destroyAvailableBudget(AvailableBudget $availableBudget): void
    {
        try {
            $availableBudget->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete available budget: %s', $e->getMessage()));
        }
    }

    /**
     * Destroy a budget limit.
     *
     * @param BudgetLimit $budgetLimit
     */
    public function destroyBudgetLimit(BudgetLimit $budgetLimit): void
    {
        try {
            $budgetLimit->delete();
        } catch (Exception $e) {
            Log::info(sprintf('Could not delete budget limit: %s', $e->getMessage()));
        }
    }

    /**
     * Find a budget.
     *
     * @param string $name
     *
     * @return Budget|null
     */
    public function findByName(string $name): ?Budget
    {
        $budgets = $this->user->budgets()->get(['budgets.*']);
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            if ($budget->name === $name) {
                return $budget;
            }
        }

        return null;
    }

    /**
     * Find a budget or return NULL
     *
     * @param int $budgetId |null
     *
     * @return Budget|null
     */
    public function findNull(int $budgetId = null): ?Budget
    {
        if (null === $budgetId) {
            return null;
        }

        return $this->user->budgets()->find($budgetId);
    }

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function firstUseDate(Budget $budget): ?Carbon
    {
        $oldest  = null;
        $journal = $budget->transactionJournals()->orderBy('date', 'ASC')->first();
        if (null !== $journal) {
            $oldest = $journal->date < $oldest ? $journal->date : $oldest;
        }

        $transaction = $budget
            ->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.id')
            ->orderBy('transaction_journals.date', 'ASC')->first(['transactions.*', 'transaction_journals.date']);
        if (null !== $transaction) {
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAllBudgetLimits(Carbon $start = null, Carbon $end = null): Collection
    {
        // both are NULL:
        if (null === $start && null === $end) {
            $set = BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                              ->with(['budget'])
                              ->where('budgets.user_id', $this->user->id)
                              ->whereNull('budgets.deleted_at')
                              ->get(['budget_limits.*']);

            return $set;
        }
        // one of the two is NULL.
        if (null === $start xor null === $end) {
            $query = BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                ->with(['budget'])
                                ->whereNull('budgets.deleted_at')
                                ->where('budgets.user_id', $this->user->id);
            if (null !== $end) {
                // end date must be before $end.
                $query->where('end_date', '<=', $end->format('Y-m-d 00:00:00'));
            }
            if (null !== $start) {
                // start date must be after $start.
                $query->where('start_date', '>=', $start->format('Y-m-d 00:00:00'));
            }
            $set = $query->get(['budget_limits.*']);

            return $set;
        }
        // neither are NULL:
        $set = BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                          ->with(['budget'])
                          ->where('budgets.user_id', $this->user->id)
                          ->whereNull('budgets.deleted_at')
                          ->where(
                              function (Builder $q5) use ($start, $end) {
                                  $q5->where(
                                      function (Builder $q1) use ($start, $end) {
                                          $q1->where(
                                              function (Builder $q2) use ($start, $end) {
                                                  $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                  $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 00:00:00'));
                                              }
                                          )
                                             ->orWhere(
                                                 function (Builder $q3) use ($start, $end) {
                                                     $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                     $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 00:00:00'));
                                                 }
                                             );
                                      }
                                  )
                                     ->orWhere(
                                         function (Builder $q4) use ($start, $end) {
                                             // or start is before start AND end is after end.
                                             $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d 00:00:00'));
                                             $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d 00:00:00'));
                                         }
                                     );
                              }
                          )->get(['budget_limits.*']);

        return $set;
    }

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     *
     * @return string
     */
    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string
    {
        $amount          = '0';
        $availableBudget = $this->user->availableBudgets()
                                      ->where('transaction_currency_id', $currency->id)
                                      ->where('start_date', $start->format('Y-m-d 00:00:00'))
                                      ->where('end_date', $end->format('Y-m-d 00:00:00'))->first();
        if (null !== $availableBudget) {
            $amount = (string)$availableBudget->amount;
        }

        return $amount;
    }

    /**
     * Returns all available budget objects.
     *
     * @return Collection
     */
    public function getAvailableBudgets(): Collection
    {
        return $this->user->availableBudgets()->get();
    }

    /**
     * Calculate the average amount in the budgets available in this period.
     * Grouped by day.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getAverageAvailable(Carbon $start, Carbon $end): string
    {
        /** @var Collection $list */
        $list = $this->user->availableBudgets()
                           ->where('start_date', '>=', $start->format('Y-m-d 00:00:00'))
                           ->where('end_date', '<=', $end->format('Y-m-d 00:00:00'))
                           ->get();
        if (0 === $list->count()) {
            return '0';
        }
        $total = '0';
        $days  = 0;
        /** @var AvailableBudget $availableBudget */
        foreach ($list as $availableBudget) {
            $total = bcadd($availableBudget->amount, $total);
            $days  += $availableBudget->start_date->diffInDays($availableBudget->end_date);
        }

        return bcdiv($total, (string)$days);
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getBudgetLimits(Budget $budget, Carbon $start = null, Carbon $end = null): Collection
    {
        if (null === $end && null === $start) {
            return $budget->budgetlimits()->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);
        }
        if (null === $end xor null === $start) {
            $query = $budget->budgetlimits()->orderBy('budget_limits.start_date', 'DESC');
            // one of the two is null
            if (null !== $end) {
                // end date must be before $end.
                $query->where('end_date', '<=', $end->format('Y-m-d 00:00:00'));
            }
            if (null !== $start) {
                // start date must be after $start.
                $query->where('start_date', '>=', $start->format('Y-m-d 00:00:00'));
            }
            $set = $query->get(['budget_limits.*']);

            return $set;
        }

        // when both dates are set:
        $set = $budget->budgetlimits()
                      ->where(
                          function (Builder $q5) use ($start, $end) {
                              $q5->where(
                                  function (Builder $q1) use ($start, $end) {
                                      // budget limit ends within period
                                      $q1->where(
                                          function (Builder $q2) use ($start, $end) {
                                              $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                                              $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 00:00:00'));
                                          }
                                      )
                                          // budget limit start within period
                                         ->orWhere(
                                              function (Builder $q3) use ($start, $end) {
                                                  $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                  $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 00:00:00'));
                                              }
                                          );
                                  }
                              )
                                 ->orWhere(
                                     function (Builder $q4) use ($start, $end) {
                                         // or start is before start AND end is after end.
                                         $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d 00:00:00'));
                                         $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d 00:00:00'));
                                     }
                                 );
                          }
                      )->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);

        return $set;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * This method is being used to generate the budget overview in the year/multi-year report. Its used
     * in both the year/multi-year budget overview AND in the accompanying chart.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $data[$budget->id] = [
                'name'    => $budget->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setBudgets($budgets);
        $transactions = $collector->getTransactions();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $budgetId                          = max((int)$transaction->transaction_journal_budget_id, (int)$transaction->transaction_budget_id);
            $date                              = $transaction->date->format($carbonFormat);
            $data[$budgetId]['entries'][$date] = bcadd($data[$budgetId]['entries'][$date] ?? '0', $transaction->transaction_amount);
        }

        return $data;
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
     * Get all budgets with these ID's.
     *
     * @param array $budgetIds
     *
     * @return Collection
     */
    public function getByIds(array $budgetIds): Collection
    {
        return $this->user->budgets()->whereIn('id', $budgetIds)->get();
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
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getNoBudgetPeriodReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $collector->withoutBudget();
        $transactions = $collector->getTransactions();
        $result       = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_budget'),
            'sum'     => '0',
        ];

        foreach ($transactions as $transaction) {
            $date = $transaction->date->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $transaction->transaction_amount);
        }

        return $result;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     * @param string              $amount
     *
     * @return AvailableBudget
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget
    {
        $availableBudget = $this->user->availableBudgets()
                                      ->where('transaction_currency_id', $currency->id)
                                      ->where('start_date', $start->format('Y-m-d 00:00:00'))
                                      ->where('end_date', $end->format('Y-m-d 00:00:00'))->first();
        if (null === $availableBudget) {
            $availableBudget = new AvailableBudget;
            $availableBudget->user()->associate($this->user);
            $availableBudget->transactionCurrency()->associate($currency);
            $availableBudget->start_date = $start->format('Y-m-d 00:00:00');
            $availableBudget->end_date   = $end->format('Y-m-d 00:00:00');
        }
        $availableBudget->amount = $amount;
        $availableBudget->save();

        return $availableBudget;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setBudgets($budgets)->withBudgetInformation();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getTransactions();

        return (string)$set->sum('transaction_amount');
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWoBudget(Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutBudget();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getTransactions();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return null;
            }
        );

        return (string)$set->sum('transaction_amount');
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
                'user_id' => $this->user->id,
                'name'    => $data['name'],
            ]
        );
        $newBudget->save();

        return $newBudget;
    }

    /**
     * @param array $data
     *
     * @throws FireflyException
     * @return BudgetLimit
     */
    public function storeBudgetLimit(array $data): BudgetLimit
    {
        $this->cleanupBudgets();
        /** @var Budget $budget */
        $budget = $data['budget'];

        // find limit with same date range.
        // if it exists, throw error.
        $limits = $budget->budgetlimits()
                         ->where('budget_limits.start_date', $data['start_date']->format('Y-m-d 00:00:00'))
                         ->where('budget_limits.end_date', $data['end_date']->format('Y-m-d 00:00:00'))
                         ->get(['budget_limits.*'])->count();
        Log::debug(sprintf('Found %d budget limits.', $limits));
        if ($limits > 0) {
            throw new FireflyException('A budget limit for this budget, and this date range already exists. You must update the existing one.');
        }

        Log::debug('No existing budget limit, create a new one');
        // or create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->start_date = $data['start_date']->format('Y-m-d 00:00:00');
        $limit->end_date   = $data['end_date']->format('Y-m-d 00:00:00');
        $limit->amount     = $data['amount'];
        $limit->save();
        Log::debug(sprintf('Created new budget limit with ID #%d and amount %s', $limit->id, $data['amount']));

        return $limit;
    }

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget
    {
        $oldName        = $budget->name;
        $budget->name   = $data['name'];
        $budget->active = $data['active'];
        $budget->save();
        $this->updateRuleTriggers($oldName, $data['name']);
        $this->updateRuleActions($oldName, $data['name']);
        app('preferences')->mark();

        return $budget;
    }

    /**
     * @param AvailableBudget $availableBudget
     * @param array           $data
     *
     * @return AvailableBudget
     * @throws FireflyException
     */
    public function updateAvailableBudget(AvailableBudget $availableBudget, array $data): AvailableBudget
    {
        $existing = $this->user->availableBudgets()
                               ->where('transaction_currency_id', $data['transaction_currency_id'])
                               ->where('start_date', $data['start_date']->format('Y-m-d 00:00:00'))
                               ->where('end_date', $data['end_date']->format('Y-m-d 00:00:00'))
                               ->where('id', '!=', $availableBudget->id)
                               ->first();

        if (null !== $existing) {
            throw new FireflyException(sprintf('An entry already exists for these parameters: available budget object with ID #%d', $existing->id));
        }
        $availableBudget->transaction_currency_id = $data['transaction_currency_id'];
        $availableBudget->start_date              = $data['start_date'];
        $availableBudget->end_date                = $data['end_date'];
        $availableBudget->amount                  = $data['amount'];
        $availableBudget->save();

        return $availableBudget;

    }

    /**
     * @param BudgetLimit $budgetLimit
     * @param array       $data
     *
     * @return BudgetLimit
     * @throws Exception
     */
    public function updateBudgetLimit(BudgetLimit $budgetLimit, array $data): BudgetLimit
    {
        $this->cleanupBudgets();
        /** @var Budget $budget */
        $budget = $data['budget'];

        $budgetLimit->budget()->associate($budget);
        $budgetLimit->start_date = $data['start_date']->format('Y-m-d 00:00:00');
        $budgetLimit->end_date   = $data['end_date']->format('Y-m-d 00:00:00');
        $budgetLimit->amount     = $data['amount'];
        $budgetLimit->save();
        Log::debug(sprintf('Updated budget limit with ID #%d and amount %s', $budgetLimit->id, $data['amount']));

        return $budgetLimit;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $amount
     *
     * @return BudgetLimit|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $amount): ?BudgetLimit
    {
        $this->cleanupBudgets();
        // count the limits:
        $limits = $budget->budgetlimits()
                         ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                         ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                         ->get(['budget_limits.*'])->count();
        Log::debug(sprintf('Found %d budget limits.', $limits));

        // there might be a budget limit for these dates:
        /** @var BudgetLimit $limit */
        $limit = $budget->budgetlimits()
                        ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                        ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                        ->first(['budget_limits.*']);

        // if more than 1 limit found, delete the others:
        if ($limits > 1 && null !== $limit) {
            Log::debug(sprintf('Found more than 1, delete all except #%d', $limit->id));
            $budget->budgetlimits()
                   ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                   ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                   ->where('budget_limits.id', '!=', $limit->id)->delete();
        }

        // delete if amount is zero.
        // Returns 0 if the two operands are equal,
        // 1 if the left_operand is larger than the right_operand, -1 otherwise.
        if (null !== $limit && bccomp($amount, '0') <= 0) {
            Log::debug(sprintf('%s is zero, delete budget limit #%d', $amount, $limit->id));
            try {
                $limit->delete();
            } catch (Exception $e) {
                Log::debug(sprintf('Could not delete limit: %s', $e->getMessage()));
            }


            return null;
        }
        // update if exists:
        if (null !== $limit) {
            Log::debug(sprintf('Existing budget limit is #%d, update this to amount %s', $limit->id, $amount));
            $limit->amount = $amount;
            $limit->save();

            return $limit;
        }
        Log::debug('No existing budget limit, create a new one');
        // or create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->start_date = $start->startOfDay();
        $limit->end_date   = $end->startOfDay();
        $limit->amount     = $amount;
        $limit->save();
        Log::debug(sprintf('Created new budget limit with ID #%d and amount %s', $limit->id, $amount));

        return $limit;
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function updateRuleActions(string $oldName, string $newName): void
    {
        $types   = ['set_budget',];
        $actions = RuleAction::leftJoin('rules', 'rules.id', '=', 'rule_actions.rule_id')
                             ->where('rules.user_id', $this->user->id)
                             ->whereIn('rule_actions.action_type', $types)
                             ->where('rule_actions.action_value', $oldName)
                             ->get(['rule_actions.*']);
        Log::debug(sprintf('Found %d actions to update.', $actions->count()));
        /** @var RuleAction $action */
        foreach ($actions as $action) {
            $action->action_value = $newName;
            $action->save();
            Log::debug(sprintf('Updated action %d: %s', $action->id, $action->action_value));
        }
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function updateRuleTriggers(string $oldName, string $newName): void
    {
        $types    = ['budget_is',];
        $triggers = RuleTrigger::leftJoin('rules', 'rules.id', '=', 'rule_triggers.rule_id')
                               ->where('rules.user_id', $this->user->id)
                               ->whereIn('rule_triggers.trigger_type', $types)
                               ->where('rule_triggers.trigger_value', $oldName)
                               ->get(['rule_triggers.*']);
        Log::debug(sprintf('Found %d triggers to update.', $triggers->count()));
        /** @var RuleTrigger $trigger */
        foreach ($triggers as $trigger) {
            $trigger->trigger_value = $newName;
            $trigger->save();
            Log::debug(sprintf('Updated trigger %d: %s', $trigger->id, $trigger->trigger_value));
        }
    }
}
