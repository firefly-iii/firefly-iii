<?php
/**
 * Expense.php
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
use stdClass;

/**
 *
 * Class Expense
 *
 * @package FireflyIII\Helpers\Collection
 */
class Expense
{
    /** @var Collection */
    protected $expenses;
    /** @var string */
    protected $total = '0';

    /**
     *
     */
    public function __construct()
    {
        $this->expenses = new Collection;
    }

    /**
     * @param stdClass $entry
     */
    public function addOrCreateExpense(stdClass $entry)
    {
        $this->expenses->put($entry->id, $entry);
    }

    /**
     * @param string $add
     */
    public function addToTotal(string $add)
    {
        $add = strval(round($add, 2));
        if (bccomp('0', $add) === -1) {
            $add = bcmul($add, '-1');
        }

        // if amount is positive, the original transaction
        // was a transfer. But since this is an expense report,
        // that amount must be negative.

        $this->total = bcadd($this->total, $add);
    }

    /**
     * @return Collection
     */
    public function getExpenses(): Collection
    {
        $set = $this->expenses->sortBy(
            function (stdClass $object) {
                return $object->amount;
            }
        );

        return $set;
    }

    /**
     * @return string
     */
    public function getTotal(): string
    {
        return strval(round($this->total, 2));
    }
}
