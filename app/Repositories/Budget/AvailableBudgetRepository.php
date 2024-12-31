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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AvailableBudgetRepository
 */
class AvailableBudgetRepository implements AvailableBudgetRepositoryInterface
{
    private User $user;

    public function cleanup(): void
    {
        $exists           = [];
        $availableBudgets = $this->user->availableBudgets()->get();

        /** @var AvailableBudget $availableBudget */
        foreach ($availableBudgets as $availableBudget) {
            $start        = $availableBudget->start_date->format('Y-m-d');
            $end          = $availableBudget->end_date->format('Y-m-d');
            $key          = sprintf('%s-%s-%s', $availableBudget->transaction_currency_id, $start, $end);
            if (array_key_exists($key, $exists)) {
                app('log')->debug(sprintf('Found duplicate AB: %s %s, %s-%s. Has been deleted', $availableBudget->transaction_currency_id, $availableBudget->amount, $start, $end));
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
        $query  = $this->user->availableBudgets()->with(['transactionCurrency']);
        if (null !== $start && null !== $end) {
            $query->where(
                static function (Builder $q1) use ($start, $end): void { // @phpstan-ignore-line
                    $q1->where('start_date', '=', $start->format('Y-m-d'));
                    $q1->where('end_date', '=', $end->format('Y-m-d'));
                }
            );
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
        return $this->user->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))
            ->first()
        ;
    }

    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string
    {
        $amount          = '0';

        /** @var null|AvailableBudget $availableBudget */
        $availableBudget = $this->user->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))->first()
        ;
        if (null !== $availableBudget) {
            $amount = $availableBudget->amount;
        }

        return $amount;
    }

    public function getAvailableBudgetWithCurrency(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Now in %s(%s, %s)', __METHOD__, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));
        $return           = [];
        $availableBudgets = $this->user->availableBudgets()
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))->get()
        ;

        Log::debug(sprintf('Found %d available budgets', $availableBudgets->count()));

        // use native amount if necessary?
        $convertToNative  = Amount::convertToNative($this->user);
        $default          = Amount::getDefaultCurrency();

        /** @var AvailableBudget $availableBudget */
        foreach ($availableBudgets as $availableBudget) {
            $currencyId          = $convertToNative && $availableBudget->transaction_currency_id !== $default->id ? $default->id : $availableBudget->transaction_currency_id;
            $field               = $convertToNative && $availableBudget->transaction_currency_id !== $default->id ? 'native_amount' : 'amount';
            $return[$currencyId] ??= '0';
            $return[$currencyId] = bcadd($return[$currencyId], $availableBudget->{$field});
            Log::debug(sprintf('Add #%d %s (%s) for a total of %s', $currencyId, $availableBudget->{$field}, $field, $return[$currencyId]));
        }

        return $return;
    }

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByCurrency(TransactionCurrency $currency): Collection
    {
        return $this->user->availableBudgets()->where('transaction_currency_id', $currency->id)->get();
    }

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByDate(?Carbon $start, ?Carbon $end): Collection
    {
        $query = $this->user->availableBudgets();

        if (null !== $start) {
            $query->where('start_date', '>=', $start->format('Y-m-d'));
        }
        if (null !== $end) {
            $query->where('end_date', '<=', $end->format('Y-m-d'));
        }

        return $query->get();
    }

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByExactDate(Carbon $start, Carbon $end): Collection
    {
        return $this->user->availableBudgets()
            ->where('start_date', '=', $start->format('Y-m-d'))
            ->where('end_date', '=', $end->format('Y-m-d'))
            ->get()
        ;
    }

    public function getByCurrencyDate(Carbon $start, Carbon $end, TransactionCurrency $currency): ?AvailableBudget
    {
        return $this->user
            ->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))->first()
        ;
    }

    /**
     * @deprecated
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget
    {
        $availableBudget         = $this->user->availableBudgets()
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))->first()
        ;
        if (null === $availableBudget) {
            $availableBudget                = new AvailableBudget();
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

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function store(array $data): ?AvailableBudget
    {
        $start = $data['start'];
        if ($start instanceof Carbon) {
            $start = $data['start']->startOfDay();
        }
        $end   = $data['end'];
        if ($end instanceof Carbon) {
            $end = $data['end']->endOfDay();
        }

        return AvailableBudget::create(
            [
                'user_id'                 => $this->user->id,
                'user_group_id'           => $this->user->user_group_id,
                'transaction_currency_id' => $data['currency_id'],
                'amount'                  => $data['amount'],
                'start_date'              => $start,
                'start_date_tz'           => $start->format('e'),
                'end_date'                => $end,
                'end_date_tz'             => $end->format('e'),
            ]
        );
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
}
