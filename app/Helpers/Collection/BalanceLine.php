<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Budget as BudgetModel;
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

    /** @var  Collection */
    protected $balanceEntries;

    /** @var BudgetModel */
    protected $budget;

    /** @var float  */
    protected $budgetAmount = 0.0;

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
     * @return float
     */
    public function getBudgetAmount()
    {
        return $this->budgetAmount;
    }

    /**
     * @param float $budgetAmount
     */
    public function setBudgetAmount($budgetAmount)
    {
        $this->budgetAmount = $budgetAmount;
    }

    /**
     * @return float
     */
    public function left() {
        $start = $this->getBudgetAmount();
        /** @var BalanceEntry $balanceEntry */
        foreach($this->getBalanceEntries() as $balanceEntry) {
            $start += $balanceEntry->getSpent();
        }
        return $start;
    }



}