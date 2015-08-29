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
    /** @var string */
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

        $accountId = $entry->account_id;
        if (!$this->incomes->has($accountId)) {
            $newObject         = new stdClass;
            $newObject->amount = strval(round($entry->actual_amount, 2));
            $newObject->name   = $entry->name;
            $newObject->count  = 1;
            $newObject->id     = $accountId;
            $this->incomes->put($accountId, $newObject);
        } else {
            bcscale(2);
            $existing         = $this->incomes->get($accountId);
            $existing->amount = bcadd($existing->amount, $entry->actual_amount);
            $existing->count++;
            $this->incomes->put($accountId, $existing);
        }
    }

    /**
     * @param $add
     */
    public function addToTotal($add)
    {
        $add = strval(round($add, 2));
        bcscale(2);
        $this->total = bcadd($this->total, $add);
    }

    /**
     * @return Collection
     */
    public function getIncomes()
    {
        $set = $this->incomes->sortByDesc(
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
