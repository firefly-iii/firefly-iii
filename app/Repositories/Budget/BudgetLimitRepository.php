<?php
/**
 * BudgetLimitRepository.php
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

namespace FireflyIII\Repositories\Budget;


use Carbon\Carbon;
use Exception;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class BudgetLimitRepository
 */
class BudgetLimitRepository implements BudgetLimitRepositoryInterface
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
            die(get_class($this));
        }
    }

    /**
     * Tells you which amount has been budgeted (for the given budgets)
     * in the selected query. Returns a positive amount as a string.
     *
     * @param Carbon              $start
     * @param Carbon              $end
     * @param TransactionCurrency $currency
     * @param Collection|null     $budgets
     *
     * @return string
     */
    public function budgeted(Carbon $start, Carbon $end, TransactionCurrency $currency, ?Collection $budgets = null): string
    {
        $query = BudgetLimit
            ::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
            ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
            ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
            ->where('budget_limits.transaction_currency_id', $currency->id)
            ->whereNull('budgets.deleted_at')
            ->where('budgets.user_id', $this->user->id);
        if (null !== $budgets && $budgets->count() > 0) {
            $query->whereIn('budget_limits.budget_id', $budgets->pluck('id')->toArray());
        }

        $set    = $query->get(['budget_limits.*']);
        $result = '0';
        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            $result = bcadd($budgetLimit->amount, $result);
        }

        return $result;
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
     * @param Budget              $budget
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     *
     * @return BudgetLimit|null
     */
    public function find(Budget $budget, TransactionCurrency $currency, Carbon $start, Carbon $end): ?BudgetLimit
    {
        return $budget->budgetlimits()
                      ->where('transaction_currency_id', $currency->id)
                      ->where('start_date', $start->format('Y-m-d'))
                      ->where('end_date', $end->format('Y-m-d'))->first();
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     *
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
                              static function (Builder $q5) use ($start, $end) {
                                  $q5->where(
                                      static function (Builder $q1) use ($start, $end) {
                                          $q1->where(
                                              static function (Builder $q2) use ($start, $end) {
                                                  $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                  $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 00:00:00'));
                                              }
                                          )
                                             ->orWhere(
                                                 static function (Builder $q3) use ($start, $end) {
                                                     $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                     $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 00:00:00'));
                                                 }
                                             );
                                      }
                                  )
                                     ->orWhere(
                                         static function (Builder $q4) use ($start, $end) {
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
     * @return Collection
     */
    public function getAllBudgetLimitsByCurrency(TransactionCurrency $currency, Carbon $start = null, Carbon $end = null): Collection
    {
        return $this->getAllBudgetLimits($start, $end)->filter(
            static function (BudgetLimit $budgetLimit) use ($currency) {
                return $budgetLimit->transaction_currency_id === $currency->id;
            }
        );
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     *
     */
    public function getBudgetLimits(Budget $budget, Carbon $start = null, Carbon $end = null): Collection
    {
        if (null === $end && null === $start) {
            return $budget->budgetlimits()->with(['transactionCurrency'])->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);
        }
        if (null === $end xor null === $start) {
            $query = $budget->budgetlimits()->with(['transactionCurrency'])->orderBy('budget_limits.start_date', 'DESC');
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
                          static function (Builder $q5) use ($start, $end) {
                              $q5->where(
                                  static function (Builder $q1) use ($start, $end) {
                                      // budget limit ends within period
                                      $q1->where(
                                          static function (Builder $q2) use ($start, $end) {
                                              $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                                              $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 23:59:59'));
                                          }
                                      )
                                          // budget limit start within period
                                         ->orWhere(
                                              static function (Builder $q3) use ($start, $end) {
                                                  $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                  $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 23:59:59'));
                                              }
                                          );
                                  }
                              )
                                 ->orWhere(
                                     static function (Builder $q4) use ($start, $end) {
                                         // or start is before start AND end is after end.
                                         $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d 23:59:59'));
                                         $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d 00:00:00'));
                                     }
                                 );
                          }
                      )->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);

        return $set;
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
     * @return BudgetLimit
     */
    public function store(array $data): BudgetLimit
    {
        return BudgetLimit::create($data);
    }

    /**
     * @param array $data
     *
     * @return BudgetLimit
     * @deprecated
     */
    public function storeBudgetLimit(array $data): BudgetLimit
    {
        /** @var Budget $budget */
        $budget = $data['budget'];

        // if no currency has been provided, use the user's default currency:
        /** @var TransactionCurrencyFactory $factory */
        $factory  = app(TransactionCurrencyFactory::class);
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrencyByUser($this->user);
        }

        // find limit with same date range.
        // if it exists, return that one.
        $limit = $budget->budgetlimits()
                        ->where('budget_limits.start_date', $data['start']->format('Y-m-d 00:00:00'))
                        ->where('budget_limits.end_date', $data['end']->format('Y-m-d 00:00:00'))
                        ->where('budget_limits.transaction_currency_id', $currency->id)
                        ->get(['budget_limits.*'])->first();
        if (null !== $limit) {
            return $limit;
        }
        Log::debug('No existing budget limit, create a new one');

        // or create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->start_date              = $data['start']->format('Y-m-d 00:00:00');
        $limit->end_date                = $data['end']->format('Y-m-d 00:00:00');
        $limit->amount                  = $data['amount'];
        $limit->transaction_currency_id = $currency->id;
        $limit->save();
        Log::debug(sprintf('Created new budget limit with ID #%d and amount %s', $limit->id, $data['amount']));

        return $limit;
    }

    /**
     * @param BudgetLimit $budgetLimit
     * @param array       $data
     *
     * @return BudgetLimit
     */
    public function update(BudgetLimit $budgetLimit, array $data): BudgetLimit
    {
        $budgetLimit->amount = $data['amount'] ?? $budgetLimit->amount;
        $budgetLimit->save();

        return $budgetLimit;
    }

    /**
     * @param BudgetLimit $budgetLimit
     * @param array       $data
     *
     * @return BudgetLimit
     * @throws Exception
     * @deprecated
     */
    public function updateBudgetLimit(BudgetLimit $budgetLimit, array $data): BudgetLimit
    {
        /** @var Budget $budget */
        $budget = $data['budget'];

        $budgetLimit->budget()->associate($budget);
        $budgetLimit->start_date = $data['start']->format('Y-m-d 00:00:00');
        $budgetLimit->end_date   = $data['end']->format('Y-m-d 00:00:00');
        $budgetLimit->amount     = $data['amount'];

        // if no currency has been provided, use the user's default currency:
        /** @var TransactionCurrencyFactory $factory */
        $factory  = app(TransactionCurrencyFactory::class);
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrencyByUser($this->user);
        }
        $currency->enabled = true;
        $currency->save();
        $budgetLimit->transaction_currency_id = $currency->id;

        $budgetLimit->save();
        Log::debug(sprintf('Updated budget limit with ID #%d and amount %s', $budgetLimit->id, $data['amount']));

        return $budgetLimit;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $amount
     *
     * @return BudgetLimit|null
     *
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $amount): ?BudgetLimit
    {
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
     * Destroy all budget limits.
     */
    public function destroyAll(): void
    {
        $budgets = $this->user->budgets()->get();
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budget->budgetlimits()->delete();
        }
    }
}