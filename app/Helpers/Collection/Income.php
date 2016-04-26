<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Crypt;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use stdClass;

/**
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
    protected $total = '0';

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

        $object         = new stdClass;
        $object->amount = strval(round($entry->journalAmount, 2));
        $object->name   = Crypt::decrypt($entry->account_name);
        $object->count  = 1;
        $object->id     = $accountId;

        // overrule some properties:
        if ($this->incomes->has($accountId)) {
            $object         = $this->incomes->get($accountId);
            $object->amount = bcadd($object->amount, $entry->journalAmount);
            $object->count++;
        }
        $this->incomes->put($accountId, $object);
    }

    /**
     * @param string $add
     */
    public function addToTotal(string $add)
    {
        $add         = strval(round($add, 2));
        $this->total = bcadd($this->total, $add);
    }

    /**
     * @return Collection
     */
    public function getIncomes(): Collection
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
    public function getTotal(): string
    {
        return strval(round($this->total, 2));
    }


}
