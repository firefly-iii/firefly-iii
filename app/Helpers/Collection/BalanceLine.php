<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\LimitRepetition;
use Illuminate\Support\Collection;

/**
 * @codeCoverageIgnore
 *
 * Class BalanceLine
 *
 * @package FireflyIII\Helpers\Collection
 */
class BalanceLine
{

    const ROLE_DEFAULTROLE = 1;
    const ROLE_TAGROLE     = 2;
    const ROLE_DIFFROLE    = 3;

    /** @var  Collection */
    protected $balanceEntries;

    /** @var BudgetModel */
    protected $budget;

    /** @var  LimitRepetition */
    protected $repetition;

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
    public function addBalanceEntry(BalanceEntry $balanceEntry)
    {
        $this->balanceEntries->push($balanceEntry);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if ($this->getBudget() instanceof BudgetModel) {
            return $this->getBudget()->name;
        }
        if ($this->getRole() == self::ROLE_DEFAULTROLE) {
            return trans('firefly.noBudget');
        }
        if ($this->getRole() == self::ROLE_TAGROLE) {
            return trans('firefly.coveredWithTags');
        }
        if ($this->getRole() == self::ROLE_DIFFROLE) {
            return trans('firefly.leftUnbalanced');
        }

        return '';
    }

    /**
     * @return BudgetModel
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @param BudgetModel $budget
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;
    }

    /**
     * @return int
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param int $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * If a BalanceLine has a budget/repetition, each BalanceEntry in this BalanceLine
     * should have a "spent" value, which is the amount of money that has been spent
     * on the given budget/repetition. If you subtract all those amounts from the budget/repetition's
     * total amount, this is returned:
     *
     * @return float
     */
    public function leftOfRepetition()
    {
        $start = $this->getRepetition() ? $this->getRepetition()->amount : 0;
        /** @var BalanceEntry $balanceEntry */
        foreach ($this->getBalanceEntries() as $balanceEntry) {
            $start -= $balanceEntry->getSpent();
        }

        return $start;
    }

    /**
     * @return LimitRepetition
     */
    public function getRepetition()
    {
        return $this->repetition;
    }

    /**
     * @param LimitRepetition $repetition
     */
    public function setRepetition($repetition)
    {
        $this->repetition = $repetition;
    }

    /**
     * @return Collection
     */
    public function getBalanceEntries()
    {
        return $this->balanceEntries;
    }

    /**
     * @param Collection $balanceEntries
     */
    public function setBalanceEntries($balanceEntries)
    {
        $this->balanceEntries = $balanceEntries;
    }

    /**
     * If the BalanceEntries for a BalanceLine have a "left" value, the amount
     * of money left in the entire BalanceLine is returned here:
     *
     * @return float
     */
    public function sumOfLeft()
    {
        $sum = '0';
        bcscale(2);
        /** @var BalanceEntry $balanceEntry */
        foreach ($this->getBalanceEntries() as $balanceEntry) {
            $sum = bcadd($sum, $balanceEntry->getSpent());
        }

        return $sum;
    }


}
