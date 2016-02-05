<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Crypt;
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
        bcscale(2);

        $accountId = $entry->account_id;
        $amount    = strval(round($entry->journalAmount, 2));
        if (bccomp('0', $amount) === -1) {
            $amount = bcmul($amount, '-1');
        }

        if (!$this->expenses->has($accountId)) {
            $newObject         = new stdClass;
            $newObject->amount = $amount;
            $newObject->name   = Crypt::decrypt($entry->account_name);
            $newObject->count  = 1;
            $newObject->id     = $accountId;
            $this->expenses->put($accountId, $newObject);
        } else {
            $existing         = $this->expenses->get($accountId);
            $existing->amount = bcadd($existing->amount, $amount);
            $existing->count++;
            $this->expenses->put($accountId, $existing);
        }
    }

    /**
     * @param string $add
     */
    public function addToTotal(string $add)
    {
        bcscale(2);


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
