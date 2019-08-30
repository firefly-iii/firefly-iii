<?php
/**
 * BudgetLimitRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}