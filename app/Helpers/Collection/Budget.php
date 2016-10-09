<?php
/**
 * Budget.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;

/**
 *
 * Class Budget
 *
 * @package FireflyIII\Helpers\Collection
 */
class Budget
{
    /** @var  Collection */
    protected $budgetLines;
    /** @var string */
    protected $budgeted = '0';
    /** @var string */
    protected $left = '0';
    /** @var string */
    protected $overspent = '0';
    /** @var string */
    protected $spent = '0';

    /**
     *
     */
    public function __construct()
    {
        $this->budgetLines = new Collection;
    }

    /**
     * @param BudgetLine $budgetLine
     *
     * @return Budget
     */
    public function addBudgetLine(BudgetLine $budgetLine): Budget
    {
        $this->budgetLines->push($budgetLine);

        return $this;
    }

    /**
     * @param string $add
     *
     * @return Budget
     */
    public function addBudgeted(string $add): Budget
    {
        $add            = strval(round($add, 2));
        $this->budgeted = bcadd($this->budgeted, $add);

        return $this;
    }

    /**
     * @param string $add
     *
     * @return Budget
     */
    public function addLeft(string $add): Budget
    {
        $add        = strval(round($add, 2));
        $this->left = bcadd($this->left, $add);

        return $this;
    }

    /**
     * @param string $add
     *
     * @return Budget
     */
    public function addOverspent(string $add): Budget
    {
        $add             = strval(round($add, 2));
        $this->overspent = bcadd($this->overspent, $add);

        return $this;
    }

    /**
     * @param string $add
     *
     * @return Budget
     */
    public function addSpent(string $add): Budget
    {
        $add         = strval(round($add, 2));
        $this->spent = bcadd($this->spent, $add);

        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getBudgetLines(): Collection
    {
        return $this->budgetLines;
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
     * @return Budget
     */
    public function setBudgeted(string $budgeted): Budget
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
     * @return Budget
     */
    public function setLeft(string $left): Budget
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
     * @return Budget
     */
    public function setOverspent(string $overspent): Budget
    {
        $this->overspent = strval(round($overspent, 2));

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
     * @return Budget
     */
    public function setSpent(string $spent): Budget
    {
        $this->spent = strval(round($spent, 2));

        return $this;
    }


}
