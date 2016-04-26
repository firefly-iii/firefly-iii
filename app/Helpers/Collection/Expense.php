<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Crypt;
use FireflyIII\Models\TransactionJournal;
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
     * @param TransactionJournal $entry
     */
    public function addOrCreateExpense(TransactionJournal $entry)
    {
        $accountId = $entry->account_id;
        $amount    = strval(round($entry->journalAmount, 2));
        if (bccomp('0', $amount) === -1) {
            $amount = bcmul($amount, '-1');
        }

        $object         = new stdClass;
        $object->amount = $amount;
        $object->name   = Crypt::decrypt($entry->account_name);
        $object->count  = 1;
        $object->id     = $accountId;

        // overrule some properties:
        if ($this->expenses->has($accountId)) {
            $object         = $this->expenses->get($accountId);
            $object->amount = bcadd($object->amount, $amount);
            $object->count++;
        }
        $this->expenses->put($accountId, $object);
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
