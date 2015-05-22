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
    /** @var float */
    protected $total;

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
            $newObject->amount = floatval($entry->amount);
            $newObject->name   = $entry->name;
            $newObject->count  = 1;
            $newObject->id     = $accountId;
            $this->expenses->put($accountId, $newObject);
        } else {
            $existing = $this->expenses->get($accountId);
            $existing->amount += floatval($entry->amount);
            $existing->count++;
            $this->expenses->put($accountId, $existing);
        }
    }

    /**
     * @param $add
     */
    public function addToTotal($add)
    {
        $this->total += floatval($add);
    }

    /**
     * @return Collection
     */
    public function getExpenses()
    {
        $this->expenses->sortByDesc(
            function (stdClass $object) {
                return $object->amount;
            }
        );

        return $this->expenses;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }
}
