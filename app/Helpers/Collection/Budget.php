<?php

namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;

/**
 * @codeCoverageIgnore
 *
 * Class Budget
 *
 * @package FireflyIII\Helpers\Collection
 */
class Budget
{
    /** @var  Collection */
    protected $budgetLines;
    /** @var float */
    protected $budgeted = 0;
    /** @var float */
    protected $left = 0;
    /** @var float */
    protected $overspent = 0;
    /** @var float */
    protected $spent = 0;

    /**
     *
     */
    public function __construct()
    {
        $this->budgetLines = new Collection;
    }

    /**
     * @param BudgetLine $budgetLine
     */
    public function addBudgetLine(BudgetLine $budgetLine)
    {
        $this->budgetLines->push($budgetLine);
    }

    /**
     * @param float $add
     */
    public function addBudgeted($add)
    {
        $this->budgeted += floatval($add);
    }

    /**
     * @param float $add
     */
    public function addLeft($add)
    {
        $this->left += floatval($add);
    }

    /**
     * @param float $add
     */
    public function addOverspent($add)
    {
        $this->overspent += floatval($add);
    }

    /**
     * @param float $add
     */
    public function addSpent($add)
    {
        $this->spent += floatval($add);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getBudgetLines()
    {
        return $this->budgetLines;
    }

    /**
     * @return float
     */
    public function getBudgeted()
    {
        return $this->budgeted;
    }

    /**
     * @param float $budgeted
     */
    public function setBudgeted($budgeted)
    {
        $this->budgeted = $budgeted;
    }

    /**
     * @return float
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param float $left
     */
    public function setLeft($left)
    {
        $this->left = $left;
    }

    /**
     * @return float
     */
    public function getOverspent()
    {
        return $this->overspent;
    }

    /**
     * @param float $overspent
     */
    public function setOverspent($overspent)
    {
        $this->overspent = $overspent;
    }

    /**
     * @return float
     */
    public function getSpent()
    {
        return $this->spent;
    }

    /**
     * @param float $spent
     */
    public function setSpent($spent)
    {
        $this->spent = $spent;
    }


}
