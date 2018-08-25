<?php
/**
 * BalanceLine.php
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

namespace FireflyIII\Helpers\Collection;

use Carbon\Carbon;
use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Support\Collection;

/**
 * Class BalanceLine.
 *
 * @codeCoverageIgnore
 */
class BalanceLine
{
    /**
     *
     */
    public const ROLE_DEFAULTROLE = 1;
    /**
     *
     */
    public const ROLE_TAGROLE = 2;

    /** @var Collection */
    protected $balanceEntries;

    /** @var BudgetModel */
    protected $budget;
    /** @var BudgetLimit */
    protected $budgetLimit;
    /** @var int */
    protected $role = self::ROLE_DEFAULTROLE;

    /**
     *
     */
    public function __construct()
    {
        $this->balanceEntries = new Collection;
    }

    /**
     * @param BalanceEntry $balanceEntry
     */
    public function addBalanceEntry(BalanceEntry $balanceEntry): void
    {
        $this->balanceEntries->push($balanceEntry);
    }

    /**
     * @return Collection
     */
    public function getBalanceEntries(): Collection
    {
        return $this->balanceEntries;
    }

    /**
     * @param Collection $balanceEntries
     */
    public function setBalanceEntries(Collection $balanceEntries): void
    {
        $this->balanceEntries = $balanceEntries;
    }

    /**
     * @return BudgetModel
     */
    public function getBudget(): BudgetModel
    {
        return $this->budget ?? new BudgetModel;
    }

    /**
     * @param BudgetModel $budget
     */
    public function setBudget(BudgetModel $budget): void
    {
        $this->budget = $budget;
    }

    /**
     * @return BudgetLimit
     */
    public function getBudgetLimit(): BudgetLimit
    {
        return $this->budgetLimit;
    }

    /**
     * @param BudgetLimit $budgetLimit
     */
    public function setBudgetLimit(BudgetLimit $budgetLimit): void
    {
        $this->budgetLimit = $budgetLimit;
    }

    /**
     * @return Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->budgetLimit->end_date ?? new Carbon;
    }

    /**
     * @return int
     */
    public function getRole(): int
    {
        return $this->role;
    }

    /**
     * @param int $role
     */
    public function setRole(int $role): void
    {
        $this->role = $role;
    }

    /**
     * @return Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->budgetLimit->start_date ?? new Carbon;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        $title = '';
        if ($this->getBudget() instanceof BudgetModel && null !== $this->getBudget()->id) {
            $title = $this->getBudget()->name;
        }
        if ('' === $title && self::ROLE_DEFAULTROLE === $this->getRole()) {
            $title = (string)trans('firefly.no_budget');
        }
        if ('' === $title && self::ROLE_TAGROLE === $this->getRole()) {
            $title = (string)trans('firefly.coveredWithTags');
        }

        return $title;
    }

    /**
     * If a BalanceLine has a budget/repetition, each BalanceEntry in this BalanceLine
     * should have a "spent" value, which is the amount of money that has been spent
     * on the given budget/repetition. If you subtract all those amounts from the budget/repetition's
     * total amount, this is returned:.
     *
     * @return string
     */
    public function leftOfRepetition(): string
    {
        $start = $this->budgetLimit->amount ?? '0';
        /** @var BalanceEntry $balanceEntry */
        foreach ($this->getBalanceEntries() as $balanceEntry) {
            $start = bcadd($balanceEntry->getSpent(), $start);
        }

        return $start;
    }
}
