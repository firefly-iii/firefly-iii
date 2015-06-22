<?php

namespace FireflyIII\Helpers\Collection;


use Illuminate\Support\Collection;

/**
 * @codeCoverageIgnore
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
    public function getBills()
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
