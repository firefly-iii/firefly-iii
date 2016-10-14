<?php
/**
 * Bill.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;


use Illuminate\Support\Collection;

/**
 * Class Bill
 *
 * @package FireflyIII\Helpers\Collection
 */
class Bill
{

    /**
     * @var Collection
     */
    protected $bills;

    /**
     *
     */
    public function __construct()
    {
        $this->bills = new Collection;
    }

    /**
     * @param BillLine $bill
     */
    public function addBill(BillLine $bill)
    {
        $this->bills->push($bill);
    }

    /**
     * @return Collection
     */
    public function getBills(): Collection
    {
        $set = $this->bills->sortBy(
            function (BillLine $bill) {
                $active = intval($bill->getBill()->active) == 0 ? 1 : 0;
                $name   = $bill->getBill()->name;

                return $active . $name;
            }
        );


        return $set;
    }

}
