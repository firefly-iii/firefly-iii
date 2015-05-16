<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use stdClass;

/**
 * @codeCoverageIgnore
 * 
 * Class Income
 *
 * @package FireflyIII\Helpers\Collection
 */
class Income
{

    /** @var Collection */
    protected $incomes;
    /** @var float */
    protected $total;

    /**
     *
     */
    public function __construct()
    {
        $this->incomes = new Collection;
    }

    /**
     * @param TransactionJournal $entry
     */
    public function addOrCreateIncome(TransactionJournal $entry)
    {

        $id = $entry->account_id;
        if (!$this->incomes->has($id)) {
            $newObject         = new stdClass;
            $newObject->amount = floatval($entry->queryAmount);
            $newObject->name   = $entry->name;
            $newObject->count  = 1;
            $newObject->id     = $id;
            $this->incomes->put($id, $newObject);
        } else {
            $existing = $this->incomes->get($id);
            $existing->amount += floatval($entry->queryAmount);
            $existing->count++;
            $this->incomes->put($id, $existing);
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
    public function getIncomes()
    {
        $this->incomes->sortByDesc(
            function (stdClass $object) {
                return $object->amount;
            }
        );

        return $this->incomes;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }


}