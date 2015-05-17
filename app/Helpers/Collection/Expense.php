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

        $id = $entry->account_id;
        if (!$this->expenses->has($id)) {
            $newObject         = new stdClass;
            $newObject->amount = floatval($entry->queryAmount);
            $newObject->name   = $entry->name;
            $newObject->count  = 1;
            $newObject->id     = $id;
            $this->expenses->put($id, $newObject);
        } else {
            $existing = $this->expenses->get($id);
            $existing->amount += floatval($entry->queryAmount);
            $existing->count++;
            $this->expenses->put($id, $existing);
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
        $this->expenses->sortBy(
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