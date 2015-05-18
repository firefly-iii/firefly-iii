<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\LimitRepetition;

/**
 * @codeCoverageIgnore
 *
 * Class BudgetLine
 *
 * @package FireflyIII\Helpers\Collection
 */
class BudgetLine
{

    /** @var  BudgetModel */
    protected $budget;

    /** @var  LimitRepetition */
    protected $repetition;

    /** @var float */
    protected $budgeted  = 0;
    /** @var float */
    protected $left      = 0;
    /** @var float */
    protected $overspent = 0;
    /** @var float */
    protected $spent     = 0;

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






}