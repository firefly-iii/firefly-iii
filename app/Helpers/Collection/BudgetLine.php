<?php
/**
 * BudgetLine.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Budget as BudgetModel;
use FireflyIII\Models\LimitRepetition;

/**
 *
 * Class BudgetLine
 *
 * @package FireflyIII\Helpers\Collection
 */
class BudgetLine
{

    /** @var  BudgetModel */
    protected $budget;
    /** @var string */
    protected $budgeted = '0';
    /** @var string */
    protected $left = '0';
    /** @var string */
    protected $overspent = '0';
    /** @var  LimitRepetition */
    protected $repetition;
    /** @var string */
    protected $spent = '0';

    /**
     * @return BudgetModel
     */
    public function getBudget(): BudgetModel
    {
        return $this->budget ?? new BudgetModel;
    }

    /**
     * @param BudgetModel $budget
     *
     * @return BudgetLine
     */
    public function setBudget(BudgetModel $budget): BudgetLine
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * @return string
     */
    public function getBudgeted(): string
    {
        return $this->budgeted;
    }

    /**
     * @param string $budgeted
     *
     * @return BudgetLine
     */
    public function setBudgeted(string $budgeted): BudgetLine
    {
        $this->budgeted = $budgeted;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeft(): string
    {
        return $this->left;
    }

    /**
     * @param string $left
     *
     * @return BudgetLine
     */
    public function setLeft(string $left): BudgetLine
    {
        $this->left = $left;

        return $this;
    }

    /**
     * @return string
     */
    public function getOverspent(): string
    {
        return $this->overspent;
    }

    /**
     * @param string $overspent
     *
     * @return BudgetLine
     */
    public function setOverspent(string $overspent): BudgetLine
    {
        $this->overspent = $overspent;

        return $this;
    }

    /**
     * @return LimitRepetition
     */
    public function getRepetition(): LimitRepetition
    {
        return $this->repetition ?? new LimitRepetition;
    }

    /**
     * @param LimitRepetition $repetition
     *
     * @return BudgetLine
     */
    public function setRepetition(LimitRepetition $repetition): BudgetLine
    {
        $this->repetition = $repetition;

        return $this;
    }

    /**
     * @return string
     */
    public function getSpent(): string
    {
        return $this->spent;
    }

    /**
     * @param string $spent
     *
     * @return BudgetLine
     */
    public function setSpent(string $spent): BudgetLine
    {
        $this->spent = $spent;

        return $this;
    }


}
