<?php
/**
 * Income.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

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
        // add each account individually:
        $sources = TransactionJournal::sourceTransactionList($entry);

        foreach ($sources as $transaction) {
            $amount  = strval($transaction->amount);
            $account = $transaction->account;
            $amount  = bcmul($amount, '-1');

            $object         = new stdClass;
            $object->amount = $amount;
            $object->name   = $account->name;
            $object->count  = 1;
            $object->id     = $account->id;

            // overrule some properties:
            if ($this->incomes->has($account->id)) {
                $object         = $this->incomes->get($account->id);
                $object->amount = bcadd($object->amount, $amount);
                $object->count++;
            }
            $this->incomes->put($account->id, $object);
        }

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
