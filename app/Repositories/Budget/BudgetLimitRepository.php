<?php

/**
 * BudgetLimitRepository.php
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Events\Model\BudgetLimit\CreatedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\DestroyedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\UpdatedBudgetLimit;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use FireflyIII\Support\Singleton\PreferencesSingleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Override;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

/**
 * Class BudgetLimitRepository
 */
class BudgetLimitRepository implements BudgetLimitRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    /**
     * Tells you which amount has been budgeted (for the given budgets)
     * in the selected query. Returns a positive amount as a string.
     */
    public function budgeted(Carbon $start, Carbon $end, TransactionCurrency $currency, ?Collection $budgets = null): string
    {
        $query = BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
            // same complex where query as below.
                            ->where(static function (Builder $q5) use ($start, $end): void {
                $q5->where(static function (Builder $q1) use ($start, $end): void {
                    $q1->where(static function (Builder $q2) use ($start, $end): void {
                        $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d'));
                        $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d'));
                    })->orWhere(static function (Builder $q3) use ($start, $end): void {
                        $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d'));
                        $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d'));
                    });
                })->orWhere(static function (Builder $q4) use ($start, $end): void {
                    // or start is before start AND end is after end.
                    $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d'));
                    $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d'));
                });
            })
                            ->where('budget_limits.transaction_currency_id', $currency->id)
                            ->whereNull('budgets.deleted_at')
                            ->where('budgets.active', true)
                            ->where('budgets.user_id', $this->user->id);
        if ($budgets instanceof Collection && $budgets->count() > 0) {
            $query->whereIn('budget_limits.budget_id', $budgets->pluck('id')->toArray());
        }

        $set    = $query->get(['budget_limits.*']);
        $result = '0';

        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            $result = bcadd((string)$budgetLimit->amount, $result);
        }

        return $result;
    }

    /**
     * Destroy all budget limits.
     */
    public function destroyAll(): void
    {
        $budgets = $this->user->budgets()->get();

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            Log::channel('audit')->info(sprintf('Delete all budget limits of budget #%d ("%s") through destroyAll', $budget->id, $budget->name));
            $budget->budgetlimits()->delete();
        }
    }

    /**
     * Destroy a budget limit.
     */
    public function destroyBudgetLimit(BudgetLimit $budgetLimit): void
    {
        $user = $budgetLimit->budget->user;
        $start = $budgetLimit->start_date->clone();
        $end = $budgetLimit->end_date->clone();
        $budgetLimit->delete();
        event(new DestroyedBudgetLimit($user, $start, $end));
    }

    public function getDailyAmount(BudgetLimit $budgetLimit): string
    {
        $limitPeriod = Period::make($budgetLimit->start_date, $budgetLimit->end_date, precision: Precision::DAY(), boundaries: Boundaries::EXCLUDE_NONE());
        $days        = $limitPeriod->length();
        $amount      = bcdiv($budgetLimit->amount, (string) $days, 12);
        Log::debug(sprintf(
                       'Total amount for budget limit #%d is %s. Nr. of days is %d. Amount per day is %s',
                       $budgetLimit->id,
                       $budgetLimit->amount,
                       $days,
                       $amount
                   ));

        return $amount;
    }

    public function getAllBudgetLimitsByCurrency(TransactionCurrency $currency, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        return $this->getAllBudgetLimits($start, $end)->filter(
            static fn(BudgetLimit $budgetLimit): bool => $budgetLimit->transaction_currency_id === $currency->id
        );
    }

    public function getAllBudgetLimits(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        // both are NULL:
        if (!$start instanceof Carbon && !$end instanceof Carbon) {
            return BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                              ->with(['budget'])
                              ->where('budgets.user_id', $this->user->id)
                              ->whereNull('budgets.deleted_at')
                              ->get(['budget_limits.*']);
        }
        // one of the two is NULL.
        if (!$start instanceof Carbon xor !$end instanceof Carbon) {
            $query = BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                ->with(['budget'])
                                ->whereNull('budgets.deleted_at')
                                ->where('budgets.user_id', $this->user->id);
            if ($end instanceof Carbon) {
                // end date must be before $end.
                $query->where('end_date', '<=', $end->format('Y-m-d 00:00:00'));
            }
            if ($start instanceof Carbon) {
                // start date must be after $start.
                $query->where('start_date', '>=', $start->format('Y-m-d 00:00:00'));
            }

            return $query->get(['budget_limits.*']);
        }

        // neither are NULL:
        return BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                          ->with(['budget'])
                          ->where('budgets.user_id', $this->user->id)
                          ->whereNull('budgets.deleted_at')
                          ->where(static function (Builder $q5) use ($start, $end): void {
                              $q5->where(static function (Builder $q1) use ($start, $end): void {
                                  $q1->where(static function (Builder $q2) use ($start, $end): void {
                                      $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d'));
                                      $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d'));
                                  })->orWhere(static function (Builder $q3) use ($start, $end): void {
                                      $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d'));
                                      $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d'));
                                  });
                              })->orWhere(static function (Builder $q4) use ($start, $end): void {
                                  // or start is before start AND end is after end.
                                  $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d'));
                                  $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d'));
                              });
                          })
                          ->get(['budget_limits.*']);
    }

    public function getBudgetLimits(Budget $budget, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        if (!$end instanceof Carbon && !$start instanceof Carbon) {
            return $budget->budgetlimits()->with(['transactionCurrency'])->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);
        }
        if (!$end instanceof Carbon xor !$start instanceof Carbon) {
            $query = $budget->budgetlimits()->with(['transactionCurrency'])->orderBy('budget_limits.start_date', 'DESC');
            // one of the two is null
            if ($end instanceof Carbon) {
                // end date must be before $end.
                $query->where('end_date', '<=', $end->format('Y-m-d 00:00:00'));
            }
            if ($start instanceof Carbon) {
                // start date must be after $start.
                $query->where('start_date', '>=', $start->format('Y-m-d 00:00:00'));
            }

            return $query->get(['budget_limits.*']);
        }

        // when both dates are set:
        return $budget
            ->budgetlimits()
            ->where(static function (Builder $q5) use ($start, $end): void {
                $q5->where(static function (Builder $q1) use ($start, $end): void {
                    // budget limit ends within period
                    $q1
                        ->where(static function (Builder $q2) use ($start, $end): void {
                            $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                            $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 23:59:59'));
                        })
                        // budget limit start within period
                        ->orWhere(static function (Builder $q3) use ($start, $end): void {
                            $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                            $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 23:59:59'));
                        });
                })->orWhere(static function (Builder $q4) use ($start, $end): void {
                    // or start is before start AND end is after end.
                    $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d 23:59:59'));
                    $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d 00:00:00'));
                });
            })
            ->orderBy('budget_limits.start_date', 'DESC')
            ->get(['budget_limits.*']);
    }

    #[Override]
    public function getNoteText(BudgetLimit $budgetLimit): string
    {
        return (string)$budgetLimit->notes()->first()?->text;
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): BudgetLimit
    {
        // if no currency has been provided, use the user's default currency:
        /** @var TransactionCurrencyFactory $factory */
        $factory  = app(TransactionCurrencyFactory::class);
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
        if (null === $currency) {
            $currency = Amount::getPrimaryCurrencyByUserGroup($this->user->userGroup);
        }
        $currency->enabled = true;
        $currency->save();

        // find the budget:
        /** @var null|Budget $budget */
        $budget = $this->user->budgets()->find((int)$data['budget_id']);
        if (null === $budget) {
            throw new FireflyException('200004: Budget does not exist.');
        }

        // find limit with same date range and currency.
        $limit = $budget
            ->budgetlimits()
            ->where('budget_limits.start_date', $data['start_date']->format('Y-m-d'))
            ->where('budget_limits.end_date', $data['end_date']->format('Y-m-d'))
            ->where('budget_limits.transaction_currency_id', $currency->id)
            ->first(['budget_limits.*']);
        if (null !== $limit) {
            throw new FireflyException('200027: Budget limit already exists.');
        }
        Log::debug('No existing budget limit, create a new one');

        // this is a lame trick to communicate with the observer.
        $singleton = PreferencesSingleton::getInstance();
        $singleton->setPreference('fire_webhooks_bl_store', $data['fire_webhooks'] ?? true);

        // or create one and return it.
        $limit = new BudgetLimit();
        $limit->budget()->associate($budget);
        $limit->start_date              = $data['start_date']->format('Y-m-d');
        $limit->end_date                = $data['end_date']->format('Y-m-d');
        $limit->amount                  = $data['amount'];
        $limit->generated = $data['generated'] ?? false;
        $limit->period = $data['period'] ?? '';
        $limit->transaction_currency_id = $currency->id;
        $limit->save();

        $noteText = (string)($data['notes'] ?? '');
        if ('' !== $noteText) {
            $this->setNoteText($limit, $noteText);
        }

        Log::debug(sprintf('Created new budget limit with ID #%d and amount %s', $limit->id, $data['amount']));
        event(new CreatedBudgetLimit($limit));
        return $limit;
    }

    public function find(Budget $budget, TransactionCurrency $currency, Carbon $start, Carbon $end): ?BudgetLimit
    {
        /** @var null|BudgetLimit */
        return $budget
            ->budgetlimits()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))
            ->first();
    }

    #[Override]
    public function setNoteText(BudgetLimit $budgetLimit, string $text): void
    {
        $dbNote = $budgetLimit->notes()->first();
        if ('' !== $text) {
            if (null === $dbNote) {
                $dbNote = new Note();
                $dbNote->noteable()->associate($budgetLimit);
            }
            $dbNote->text = trim($text);
            $dbNote->save();

            return;
        }
        $dbNote?->delete();
    }

    /**
     * @throws FireflyException
     */
    public function update(BudgetLimit $budgetLimit, array $data): BudgetLimit
    {
        $budgetLimit->amount    = array_key_exists('amount', $data) ? $data['amount'] : $budgetLimit->amount;
        $budgetLimit->budget_id = array_key_exists('budget_id', $data) ? $data['budget_id'] : $budgetLimit->budget_id;

        if (array_key_exists('start', $data)) {
            $budgetLimit->start_date    = $data['start']->startOfDay();
            $budgetLimit->start_date_tz = $data['start']->format('e');
        }
        if (array_key_exists('end', $data)) {
            $budgetLimit->end_date    = $data['end']->endOfDay();
            $budgetLimit->end_date_tz = $data['end']->format('e');
        }

        // if no currency has been provided, use the user's default currency:
        $currency = null;

        // update if relevant:
        if (array_key_exists('currency_id', $data) || array_key_exists('currency_code', $data)) {
            /** @var TransactionCurrencyFactory $factory */
            $factory  = app(TransactionCurrencyFactory::class);
            $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
        }
        // catch unexpected null:
        if (null === $currency) {
            $currency = $budgetLimit->transactionCurrency ?? Amount::getPrimaryCurrencyByUserGroup($this->user->userGroup);
        }
        $currency->enabled = true;
        $currency->save();

        // this is a lame trick to communicate with the observer.
        // FIXME so don't do that lol.
        $singleton = PreferencesSingleton::getInstance();
        $singleton->setPreference('fire_webhooks_bl_update', $data['fire_webhooks'] ?? true);

        $budgetLimit->transaction_currency_id = $currency->id;
        $budgetLimit->save();

        // update notes if they exist.
        if (array_key_exists('notes', $data)) {
            $this->setNoteText($budgetLimit, (string)$data['notes']);
        }
        event(new UpdatedBudgetLimit($budgetLimit));
        return $budgetLimit;
    }

}
