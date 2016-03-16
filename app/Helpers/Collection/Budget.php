<?php
declare(strict_types = 1);
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
     */
    public function addBudgetLine(BudgetLine $budgetLine)
    {
        $this->budgetLines->push($budgetLine);
    }

    /**
     * @param string $add
     */
    public function addBudgeted(string $add)
    {
        $add = strval(round($add, 2));
        $this->budgeted = bcadd($this->budgeted, $add);
    }

    /**
     * @param string $add
     */
    public function addLeft(string $add)
    {
        $add = strval(round($add, 2));
        $this->left = bcadd($this->left, $add);
    }

    /**
     * @param string $add
     */
    public function addOverspent(string $add)
    {
        $add = strval(round($add, 2));
        $this->overspent = bcadd($this->overspent, $add);
    }

    /**
     * @param string $add
     */
    public function addSpent(string $add)
    {
        $add = strval(round($add, 2));
        $this->spent = bcadd($this->spent, $add);
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
     */
    public function setBudgeted(string $budgeted)
    {
        $this->budgeted = $budgeted;
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
     */
    public function setLeft(string $left)
    {
        $this->left = $left;
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
     */
    public function setOverspent(string $overspent)
    {
        $this->overspent = strval(round($overspent, 2));
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
     */
    public function setSpent(string $spent)
    {
        $this->spent = strval(round($spent, 2));
    }


}
