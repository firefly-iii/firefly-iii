<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use stdClass;

/**
 * @codeCoverageIgnore
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
     * @param TransactionJournal $entry
     */
    public function addOrCreateExpense(TransactionJournal $entry)
    {
        $accountId = $entry->account_id;
        if (!$this->expenses->has($accountId)) {
            $newObject         = new stdClass;
            $newObject->amount = strval(round($entry->amount, 2));
            $newObject->name   = $entry->name;
            $newObject->count  = 1;
            $newObject->id     = $accountId;
            $this->expenses->put($accountId, $newObject);
        } else {
            bcscale(2);
            $existing         = $this->expenses->get($accountId);
            $existing->amount = bcadd($existing->amount, $entry->amount);
            $existing->count++;
            $this->expenses->put($accountId, $existing);
        }
    }

    /**
     * @param $add
     */
    public function addToTotal($add)
    {
        bcscale(2);

        
        $add = strval(round($add, 2));
        if (bccomp('0', $add) === 1) {
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
    public function getExpenses()
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
    public function getTotal()
    {
        return strval(round($this->total, 2));
    }
}
