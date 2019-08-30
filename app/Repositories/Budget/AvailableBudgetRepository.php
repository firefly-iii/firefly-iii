<?php
/**
 * AvailableBudgetRepository.php
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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class AvailableBudgetRepository
 */
class AvailableBudgetRepository implements AvailableBudgetRepositoryInterface
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
     * @param TransactionCurrency $currency
     *
     * @return Collection
     */
    public function getAvailableBudgetsByCurrency(TransactionCurrency $currency): Collection
    {
        return $this->user->availableBudgets()->where('transaction_currency_id', $currency->id)->get();
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getAvailableBudgetWithCurrency(Carbon $start, Carbon $end): array
    {
        $return           = [];
        $availableBudgets = $this->user->availableBudgets()
                                       ->where('start_date', $start->format('Y-m-d 00:00:00'))
                                       ->where('end_date', $end->format('Y-m-d 00:00:00'))->get();
        /** @var AvailableBudget $availableBudget */
        foreach ($availableBudgets as $availableBudget) {
            $return[$availableBudget->transaction_currency_id] = $availableBudget->amount;
        }

        return $return;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }


}