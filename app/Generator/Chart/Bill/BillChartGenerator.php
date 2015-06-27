<?php
namespace FireflyIII\Generator\Chart\Bill;


use FireflyIII\Models\Bill;
use Illuminate\Support\Collection;

/**
 * Interface BillChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Bill
 */
interface BillChartGenerator
{

    /**
     * @param Bill       $bill
     * @param Collection $entries
     *
     * @return array
     */
    public function single(Bill $bill, Collection $entries);

    /**
     * @param Collection $paid
     * @param Collection $unpaid
     *
     * @return array
     */
    public function frontpage(Collection $paid, Collection $unpaid);

}