<?php

/**
 * AvailableBudgetRepository.php
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
use Deprecated;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

/**
 * Class AvailableBudgetRepository
 */
class AvailableBudgetRepository implements AvailableBudgetRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    public function cleanup(): void
    {
        $exists           = [];
        $availableBudgets = $this->user->availableBudgets()->get();

        /** @var AvailableBudget $availableBudget */
        foreach ($availableBudgets as $availableBudget) {
            $start = $availableBudget->start_date->format('Y-m-d');
            $end   = $availableBudget->end_date->format('Y-m-d');
            $key   = sprintf('%s-%s-%s', $availableBudget->transaction_currency_id, $start, $end);
            if (array_key_exists($key, $exists)) {
                Log::debug(sprintf(
                               'Found duplicate AB: %s %s, %s-%s. Has been deleted',
                               $availableBudget->transaction_currency_id,
                               $availableBudget->amount,
                               $start,
                               $end
                           ));
                $availableBudget->delete();
            }
            $exists[$key] = true;
        }
    }

    /**
     * Return a list of all available budgets (in all currencies) (for the selected period).
     */
    public function get(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $query = $this->user->availableBudgets()->with(['transactionCurrency']);
        if ($start instanceof Carbon && $end instanceof Carbon) {
            $query->where(static function (Builder $q1) use ($start, $end): void {
                $q1->where('start_date', '=', $start->format('Y-m-d'));
                $q1->where('end_date', '=', $end->format('Y-m-d'));
            });
        }
        $result = $query->get(['available_budgets.*']);
        Log::debug(sprintf('Found %d available budgets between %s and %s', $result->count(), $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));

        return $result;
    }

    /**
     * Delete all available budgets.
     */
    public function destroyAll(): void
    {
        Log::channel('audit')->info('Delete all available budgets through destroyAll');
        $this->user->availableBudgets()->delete();
    }

    public function destroyAvailableBudget(AvailableBudget $availableBudget): void
    {
        $availableBudget->delete();
    }

    public function findById(int $id): ?AvailableBudget
    {
        return $this->user->availableBudgets->find($id);
    }

    /**
     * Find existing AB.
     */
    public function find(TransactionCurrency $currency, Carbon $start, Carbon $end): ?AvailableBudget
    {
        /** @var null|AvailableBudget */
        return $this->user
            ->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))
            ->first();
    }

    #[Deprecated]
    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string
    {
        $amount = '0';

        /** @var null|AvailableBudget $availableBudget */
        $availableBudget = $this->user
            ->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))
            ->first();
        if (null !== $availableBudget) {
            return $availableBudget->amount;
        }

        return $amount;
    }

    public function getAvailableBudgetWithCurrency(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Now in %s(%s, %s)', __METHOD__, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));
        $return           = [];
        $availableBudgets = $this->user
            ->availableBudgets()
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))
            ->get();

        Log::debug(sprintf('Found %d available budgets (already converted)', $availableBudgets->count()));

        // use primary amount if necessary?
        $convertToPrimary = Amount::convertToPrimary($this->user);
        $primary          = Amount::getPrimaryCurrency();

        /** @var AvailableBudget $availableBudget */
        foreach ($availableBudgets as $availableBudget) {
            $currencyId          = $convertToPrimary && $availableBudget->transaction_currency_id !== $primary->id
                ? $primary->id
                : $availableBudget->transaction_currency_id;
            $field               = $convertToPrimary && $availableBudget->transaction_currency_id !== $primary->id ? 'native_amount' : 'amount';
            $return[$currencyId] ??= '0';
            $amount              = '' === (string)$availableBudget->{$field} ? '0' : (string)$availableBudget->{$field};
            $return[$currencyId] = bcadd($return[$currencyId], $amount);
            Log::debug(sprintf('Add #%d %s (%s) for a total of %s', $currencyId, $amount, $field, $return[$currencyId]));
        }

        return $return;
    }

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByCurrency(TransactionCurrency $currency): Collection
    {
        return $this->user
            ->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->get();
    }

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByDate(?Carbon $start, ?Carbon $end): Collection
    {
        $query = $this->user->availableBudgets();

        if ($start instanceof Carbon) {
            $query->where('start_date', '>=', $start->format('Y-m-d'));
        }
        if ($end instanceof Carbon) {
            $query->where('end_date', '<=', $end->format('Y-m-d'));
        }

        return $query->get();
    }

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByExactDate(Carbon $start, Carbon $end): Collection
    {
        return $this->user
            ->availableBudgets()
            ->where('start_date', '=', $start->format('Y-m-d'))
            ->where('end_date', '=', $end->format('Y-m-d'))
            ->get();
    }

    public function getByCurrencyDate(Carbon $start, Carbon $end, TransactionCurrency $currency): ?AvailableBudget
    {
        /** @var null|AvailableBudget */
        return $this->user
            ->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))
            ->first();
    }

    #[Deprecated]
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget
    {
        /** @var null|AvailableBudget $availableBudget */
        $availableBudget = $this->user
            ->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))
            ->first();
        if (null === $availableBudget) {
            $availableBudget = new AvailableBudget();
            $availableBudget->user()->associate($this->user);
            $availableBudget->transactionCurrency()->associate($currency);
            $availableBudget->start_date    = $start->startOfDay();
            $availableBudget->start_date_tz = $start->format('e');
            $availableBudget->end_date      = $end->endOfDay();
            $availableBudget->end_date_tz   = $end->format('e');
        }
        $availableBudget->amount = $amount;
        $availableBudget->save();

        return $availableBudget;
    }

    public function store(array $data): ?AvailableBudget
    {
        $start = $data['start'];
        if ($start instanceof Carbon) {
            $start = $data['start']->startOfDay();
        }
        $end = $data['end'];
        if ($end instanceof Carbon) {
            $end = $data['end']->endOfDay();
        }

        return AvailableBudget::create([
                                           'user_id'                 => $this->user->id,
                                           'user_group_id'           => $this->user->user_group_id,
                                           'transaction_currency_id' => $data['currency_id'],
                                           'amount'                  => $data['amount'],
                                           'start_date'              => $start,
                                           'start_date_tz'           => $start->format('e'),
                                           'end_date'                => $end,
                                           'end_date_tz'             => $end->format('e'),
                                       ]);
    }

    public function update(AvailableBudget $availableBudget, array $data): AvailableBudget
    {
        if (array_key_exists('amount', $data)) {
            $availableBudget->amount = $data['amount'];
        }
        $availableBudget->save();

        return $availableBudget;
    }

    public function updateAvailableBudget(AvailableBudget $availableBudget, array $data): AvailableBudget
    {
        if (array_key_exists('start', $data)) {
            $start = $data['start'];
            if ($start instanceof Carbon) {
                $start                          = $data['start']->startOfDay();
                $availableBudget->start_date    = $start;
                $availableBudget->start_date_tz = $start->format('e');
                $availableBudget->save();
            }
        }

        if (array_key_exists('end', $data)) {
            $end = $data['end'];
            if ($end instanceof Carbon) {
                $end                          = $data['end']->endOfDay();
                $availableBudget->end_date    = $end;
                $availableBudget->end_date_tz = $end->format('e');
                $availableBudget->save();
            }
        }
        if (array_key_exists('currency_id', $data)) {
            $availableBudget->transaction_currency_id = $data['currency_id'];
            $availableBudget->save();
        }
        if (array_key_exists('amount', $data)) {
            $availableBudget->amount = $data['amount'];
            $availableBudget->save();
        }

        return $availableBudget;
    }

    #[\Override]
    public function recalculateAmount(AvailableBudget $availableBudget): void
    {
        Log::debug(sprintf('Now in recalculateAmount(#%d)', $availableBudget->id));
        $newAmount = '0';
        $period    = Period::make($availableBudget->start_date, $availableBudget->end_date, Precision::DAY());
        Log::debug(sprintf('Now recalculating available budget #%d, (%s to %s)', $availableBudget->id, $availableBudget->start_date->format('Y-m-d'), $availableBudget->end_date->format('Y-m-d')));
        // have to recalculate everything just in case.
        $blRepository = app(BudgetLimitRepositoryInterface::class);
        $blRepository->setUser($this->user);
        $set = $blRepository->getAllBudgetLimitsByCurrency($availableBudget->transactionCurrency, $availableBudget->start_date, $availableBudget->end_date);
        Log::debug(sprintf('Found %d interesting budget limit(s).', $set->count()));

        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            $newAmount = bcadd($newAmount, $this->getAmountFromBudgetLimit($budgetLimit, $period));
        }
        if (0 === bccomp('0', $newAmount)) {
            Log::debug('New amount is zero, deleting AB.');
            $availableBudget->delete();

            return;
        }
        Log::debug(sprintf('Concluded new amount for this AB must be %s', $newAmount));
        $this->update($availableBudget, ['amount' => $newAmount]);
    }


    private function getAmountFromBudgetLimit(BudgetLimit $budgetLimit, Period $availableBudgetPeriod): string
    {
        $blRepository = app(BudgetLimitRepositoryInterface::class);
        $blRepository->setUser($this->user);

        Log::debug(sprintf('Found interesting budget limit #%d (%s to %s)', $budgetLimit->id, $budgetLimit->start_date->format('Y-m-d'), $budgetLimit->end_date->format('Y-m-d')));
        // overlap in days:
        $limitPeriod = Period::make($budgetLimit->start_date, $budgetLimit->end_date, precision: Precision::DAY(), boundaries: Boundaries::EXCLUDE_NONE());
        // if both equal each other, amount from this BL must be added to the AB
        if ($limitPeriod->equals($availableBudgetPeriod)) {
            Log::debug('This budget limit is equal to the available budget period.');
            return (string)$budgetLimit->amount;
        }
        // if budget limit period is inside AB period, it can be added in full.
        if (!$limitPeriod->equals($availableBudgetPeriod) && $availableBudgetPeriod->contains($limitPeriod)) {
            Log::debug('This budget limit is smaller than the available budget period.');
            return (string)$budgetLimit->amount;
        }

        if (!$limitPeriod->equals($availableBudgetPeriod) && !$availableBudgetPeriod->contains($limitPeriod) && $availableBudgetPeriod->overlapsWith($limitPeriod)) {
            Log::debug('This budget limit is something else entirely!');
            $overlap = $availableBudgetPeriod->overlap($limitPeriod);
            if ($overlap instanceof Period) {
                $length = $overlap->length();
                return bcmul($blRepository->getDailyAmount($budgetLimit), (string)$length);
            }
        }
        return '0';
    }
}
