<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 25/02/15
 * Time: 07:40
 */

namespace FireflyIII\Repositories\Bill;

use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;

/**
 * Interface BillRepositoryInterface
 *
 * @package FireflyIII\Repositories\Bill
 */
interface BillRepositoryInterface {

    /**
     * @param Bill $bill
     *
     * @return Carbon|null
     */
    public function nextExpectedMatch(Bill $bill);

    /**
     * @param array $data
     *
     * @return Bill
     */
    public function store(array $data);

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return mixed
     */
    public function update(Bill $bill, array $data);

    /**
     * @param Bill               $bill
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(Bill $bill, TransactionJournal $journal);

}