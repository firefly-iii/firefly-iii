<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

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
        // add each account individually:
        $destinations = TransactionJournal::destinationTransactionList($entry);

        foreach ($destinations as $transaction) {
            $amount  = strval($transaction->amount);
            $account = $transaction->account;
            if (bccomp('0', $amount) === -1) {
                $amount = bcmul($amount, '-1');
            }

            $object         = new stdClass;
            $object->amount = $amount;
            $object->name   = $account->name;
            $object->count  = 1;
            $object->id     = $account->id;

            // overrule some properties:
            if ($this->expenses->has($account->id)) {
                $object         = $this->expenses->get($account->id);
                $object->amount = bcadd($object->amount, $amount);
                $object->count++;
            }
            $this->expenses->put($account->id, $object);
        }


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
